import json
import base64
import cv2
import datetime
import numpy as np
import mysql.connector
import os
from flask import Flask, request, jsonify
from flask_cors import CORS, cross_origin
from deepface import DeepFace
from facial_auth import register_user_with_facial_recognition
from werkzeug.security import generate_password_hash

# Create a directory to store face embeddings if it doesn't exist
os.makedirs('face_embeddings', exist_ok=True)

context = (r'C:\xampp\apache\conf\ssl.crt\server.crt', r'C:\xampp\apache\conf\ssl.key\server.key')

# Define the connection
connection = mysql.connector.connect(
    host="localhost",
    user="root",
    password="password",
    database="superpack_database",
    port = "3306"
)

# Define the cursor
cursor = connection.cursor()

app = Flask(__name__)

CORS(app, resources={r"/*": {"origins": ["https://superpack-adu.com", "http://localhost"]}})
@app.route('/Face_API/receive', methods=['POST'])
@cross_origin()  # This decorator is optional if you've set CORS globally
def receive_image():
    try:
        # Parse the incoming JSON data
        data = request.get_json()
        base64_string = data.get('image')
        
        # Check if the image data is present
        if not base64_string:
            return jsonify({"error": "No image data provided"}), 400

        # Decode the base64 string to get the image bytes
        image_data = base64.b64decode(base64_string)
        
        # Process the image data as needed
        # For demonstration, we'll just print the first 100 bytes
        print(image_data[:100])

        # Respond back to the client
        return jsonify({"message": "Image received and processed"}), 200
    
    except Exception as e:
        return jsonify({"error": str(e)}), 400



@app.route('/Face_API/register', methods=['POST'])
@cross_origin()
def register_user():
    temp_path = None  # Initialize temp_path at the start
    try:
        data = request.get_json()
        print("Received registration data:", data)
        
        # Extract fields
        image = data.get('image')
        name = data.get('name')
        role = data.get('role')
        department = data.get('department')
        username = data.get('username')
        password = data.get('password')
        
        # Validate required fields
        if not all([name, role, department]):
            missing_fields = []
            if not name: missing_fields.append('name')
            if not role: missing_fields.append('role')
            if not department: missing_fields.append('department')
            return jsonify({
                'error': f'Missing required fields: {", ".join(missing_fields)}',
                'details': {
                    'name': name,
                    'role': role,
                    'department': department
                }
            }), 400
        
        # Check if user already exists by name
        cursor.execute("SELECT * FROM register WHERE name = %s", (name,))
        if cursor.fetchone():
            return jsonify({
                'error': 'User already registered',
                'details': {'name': name}
            }), 400
        
        # Check for duplicate face registration
        if image:
            try:
                # Decode base64 image more safely
                try:
                    # Handle different base64 image formats
                    if ',' in image:
                        # Format like "data:image/jpeg;base64,/9j/4AAQ..."
                        image_data = base64.b64decode(image.split(',')[1])
                    else:
                        # Format without prefix
                        image_data = base64.b64decode(image)
                    
                    print(f"Successfully decoded base64 image, length: {len(image_data)}")
                except Exception as e:
                    print(f"Base64 decoding error: {str(e)}")
                    print(f"Image string prefix: {image[:30]}...")
                    return jsonify({
                        'error': 'Invalid base64 image format',
                        'details': {'name': name, 'error': str(e)}
                    }), 400
                
                nparr = np.frombuffer(image_data, np.uint8)
                img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
                
                if img is None:
                    return jsonify({
                        'error': 'Invalid image data',
                        'details': {'name': name}
                    }), 400
                
                # Save temporary image
                temp_path = os.path.join('face_embeddings', f'temp_{name}.jpg')
                cv2.imwrite(temp_path, img)
                
                # First try to detect if there's a face in the image
                try:
                    # Use extract_faces instead of analyze with 'detection'
                    extracted_faces = DeepFace.extract_faces(temp_path, enforce_detection=False)
                    if not extracted_faces or len(extracted_faces) == 0:
                        if os.path.exists(temp_path):
                            os.remove(temp_path)
                        return jsonify({
                            'error': 'No face detected in the image',
                            'details': {'name': name}
                        }), 400
                    print(f"Detected {len(extracted_faces)} faces in the image")
                except Exception as e:
                    print(f"Face detection error: {str(e)}")
                    if os.path.exists(temp_path):
                        os.remove(temp_path)
                    return jsonify({
                        'error': 'Face detection failed',
                        'details': {'name': name, 'error': str(e)}
                    }), 400
                
                # If face is detected, proceed with embedding generation
                try:
                    # Print file size for debugging
                    if os.path.exists(temp_path):
                        file_size = os.path.getsize(temp_path)
                        print(f"Temporary image file size: {file_size} bytes")
                    
                    # Try different models if Facenet fails
                    try:
                        new_embeddings = DeepFace.represent(temp_path, model_name="Facenet", enforce_detection=False)
                        print(f"DeepFace Facenet returned: {type(new_embeddings)}, length: {len(new_embeddings) if isinstance(new_embeddings, list) else 'not a list'}")
                    except Exception as e1:
                        print(f"Facenet model failed, trying VGG-Face: {str(e1)}")
                        try:
                            new_embeddings = DeepFace.represent(temp_path, model_name="VGG-Face", enforce_detection=False)
                            print(f"DeepFace VGG-Face returned: {type(new_embeddings)}, length: {len(new_embeddings) if isinstance(new_embeddings, list) else 'not a list'}")
                        except Exception as e2:
                            print(f"VGG-Face model also failed: {str(e2)}")
                            if os.path.exists(temp_path):
                                os.remove(temp_path)
                            return jsonify({
                                'error': 'Failed to generate face embeddings with multiple models',
                                'details': {'name': name, 'error': f"{str(e1)} and {str(e2)}"}
                            }), 400
                    
                    # Validate the embeddings structure
                    if not isinstance(new_embeddings, list) or len(new_embeddings) == 0:
                        print(f"Invalid embeddings type or empty list: {type(new_embeddings)}")
                        if os.path.exists(temp_path):
                            os.remove(temp_path)
                        return jsonify({
                            'error': 'No face embeddings generated',
                            'details': {'name': name}
                        }), 400
                    
                    # Check if the embedding has the expected structure
                    if 'embedding' not in new_embeddings[0]:
                        print(f"Expected 'embedding' key not found in: {list(new_embeddings[0].keys())}")
                        if os.path.exists(temp_path):
                            os.remove(temp_path)
                        return jsonify({
                            'error': 'Invalid embedding structure',
                            'details': {'name': name}
                        }), 400
                    
                    # Get all registered users
                    cursor.execute("SELECT name FROM register")
                    registered_users = cursor.fetchall()
                    
                    # Add additional check to avoid empty results
                    if not registered_users:
                        print("No registered users found in database")
                    else:
                        print(f"Found {len(registered_users)} registered users for comparison")
                    
                    # Compare with all existing face embeddings
                    for user in registered_users:
                        # Skip comparison with the current user
                        if user[0] == name:
                            continue
                            
                        ref_path = os.path.join('face_embeddings', f"{user[0]}.jpg")
                        if os.path.exists(ref_path):
                            try:
                                ref_file_size = os.path.getsize(ref_path)
                                print(f"Reference image for {user[0]} size: {ref_file_size} bytes")
                                
                                # Try both direct verification and embedding comparison
                                try:
                                    # First try direct verification which is more accurate
                                    verification = DeepFace.verify(
                                        img1_path=temp_path,
                                        img2_path=ref_path,
                                        model_name="VGG-Face",
                                        distance_metric="cosine",
                                        enforce_detection=False
                                    )
                                    
                                    print(f"Direct verification with {user[0]}: {'✓ Match' if verification['verified'] else '✗ No match'}, distance: {verification['distance']}")
                                    
                                    # Use stricter threshold for registration than for login (0.3 vs 0.4)
                                    if verification['verified'] or verification['distance'] < 0.3:
                                        if os.path.exists(temp_path):
                                            os.remove(temp_path)
                                        return jsonify({
                                            'error': 'Face already registered',
                                            'details': {
                                                'name': name,
                                                'existing_user': user[0],
                                                'similarity': f"{(1 - verification['distance']) * 100:.1f}%"
                                            }
                                        }), 400
                                except Exception as e1:
                                    print(f"Direct verification failed, trying embedding comparison: {str(e1)}")
                                    
                                    # Fallback to embedding comparison
                                    ref_embeddings = DeepFace.represent(ref_path, model_name="VGG-Face" if 'VGG-Face' in str(new_embeddings) else "Facenet", enforce_detection=False)
                                    
                                    if ref_embeddings and len(ref_embeddings) > 0 and 'embedding' in ref_embeddings[0]:
                                        # Compare embeddings
                                        try:
                                            distance = DeepFace.compute_distance(new_embeddings[0]['embedding'], ref_embeddings[0]['embedding'])
                                            print(f"Embedding distance from {user[0]}: {distance}")
                                            if distance < 0.3:  # Stricter threshold for registration (0.3 vs 0.4)
                                                # Clean up temporary file
                                                if os.path.exists(temp_path):
                                                    os.remove(temp_path)
                                                return jsonify({
                                                    'error': 'Face already registered',
                                                    'details': {
                                                        'name': name,
                                                        'existing_user': user[0],
                                                        'similarity': f"{(1 - distance) * 100:.1f}%"
                                                    }
                                                }), 400
                                        except Exception as e2:
                                            print(f"Error computing distance: {str(e2)}")
                                            continue
                            except Exception as e:
                                print(f"Error comparing with {user[0]}: {str(e)}")
                                continue
                    
                    # If no duplicate face found, proceed with registration
                    try:
                        # Store embedding as string (truncate if too large)
                        embedding_str = str(new_embeddings[0]['embedding'])
                        if len(embedding_str) > 5000:  # Set a reasonable limit
                            landmarks_hash = embedding_str[:5000]
                            print("Truncated embedding string (too large)")
                        else:
                            landmarks_hash = embedding_str
                    except Exception as e:
                        print(f"Error converting embedding to string: {str(e)}")
                        landmarks_hash = "error_generating_hash"
                    
                except Exception as e:
                    print(f"Embedding generation error: {str(e)}")
                    if os.path.exists(temp_path):
                        os.remove(temp_path)
                    return jsonify({
                        'error': 'Failed to generate face embeddings',
                        'details': {'name': name, 'error': str(e)}
                    }), 400
                    
            except Exception as e:
                print(f"Image processing error: {str(e)}")
                if temp_path and os.path.exists(temp_path):
                    os.remove(temp_path)
                return jsonify({
                    'error': 'Image processing failed',
                    'details': {'name': name, 'error': str(e)}
                }), 400
        else:
            landmarks_hash = None
        
        # Insert into register table
        insert_query = """
        INSERT INTO register (name, role, department, landmarks_hash)
        VALUES (%s, %s, %s, %s)
        """
        cursor.execute(insert_query, (name, role, department, landmarks_hash))
        connection.commit()
        
        # If username and password provided, create user account
        if username and password:
            # Check if username already exists
            cursor.execute("SELECT * FROM users WHERE username = %s", (username,))
            if cursor.fetchone():
                # Rollback register table insert
                cursor.execute("DELETE FROM register WHERE name = %s", (name,))
                connection.commit()
                return jsonify({
                    'error': 'Username already exists',
                    'details': {'username': username}
                }), 400
            
            # Hash password
            hashed_password = generate_password_hash(password)
            
            # Insert into users table
            insert_user_query = """
            INSERT INTO users (name, username, password, role, department)
            VALUES (%s, %s, %s, %s, %s)
            """
            cursor.execute(insert_user_query, (name, username, hashed_password, role, department))
            connection.commit()
        
        # Save face image if provided
        if image and temp_path:
            try:
                # Move temporary file to permanent location
                final_path = os.path.join('face_embeddings', f"{name}.jpg")
                if os.path.exists(temp_path):
                    os.rename(temp_path, final_path)
                print(f"✓ Face image saved for {name}")
            except Exception as e:
                print(f"Error saving face image: {str(e)}")
                if os.path.exists(temp_path):
                    os.remove(temp_path)
        
        return jsonify({
            'message': 'User registered successfully',
            'details': {
                'name': name,
                'role': role,
                'department': department
            }
        })
        
    except Exception as e:
        print(f"Registration error: {str(e)}")
        # Clean up temp file if it exists
        if temp_path and os.path.exists(temp_path):
            os.remove(temp_path)
        return jsonify({
            'error': 'Registration failed',
            'details': {'error': str(e)}
        }), 500


@app.route('/Face_API/mark-attendance', methods=['POST'])
@cross_origin()
def mark_attendance():
    temp_img_path = None
    try:
        # Parse the incoming JSON data
        data = request.get_json()
        base64_string = data.get('image')
        
        # The 'name' parameter is now optional as we'll detect the person automatically
        name = data.get('name', None)

        if not base64_string:
            return jsonify({"success": False, "message": "No image data provided"}), 400

        # Decode the base64 string to get the image bytes
        try:
            # Handle different base64 image formats
            if ',' in base64_string:
                # Format like "data:image/jpeg;base64,/9j/4AAQ..."
                image_data = base64.b64decode(base64_string.split(',')[1])
            else:
                # Format without prefix
                image_data = base64.b64decode(base64_string)
            
            print(f"Successfully decoded base64 image for attendance, length: {len(image_data)}")
        except Exception as e:
            print(f"Base64 decoding error: {str(e)}")
            return jsonify({"success": False, "message": f"Invalid image format: {str(e)}"}), 400

        # Convert bytes data to a NumPy array
        nparr = np.frombuffer(image_data, np.uint8)

        # Decode the image from the NumPy array (OpenCV expects this format)
        image = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        
        if image is None:
            return jsonify({"success": False, "message": "Invalid image data"}), 400
        
        # Create directory if it doesn't exist
        os.makedirs('face_embeddings', exist_ok=True)
        
        # Save the image temporarily
        temp_img_path = "face_embeddings/temp_login.jpg"
        cv2.imwrite(temp_img_path, image)
        
        # First verify if there's a face in the image
        try:
            # Check if we have any registered users
            query = "SELECT COUNT(*) FROM register"
            cursor.execute(query)
            user_count = cursor.fetchone()[0]
            
            if user_count == 0:
                return jsonify({"success": False, "message": "No users registered in the system. Please register first."}), 400
            
            # First extract faces to verify a face is present
            try:
                extracted_faces = DeepFace.extract_faces(temp_img_path, enforce_detection=False)
                if not extracted_faces or len(extracted_faces) == 0:
                    return jsonify({"success": False, "message": "No face detected in the image"}), 400
                print(f"Detected {len(extracted_faces)} faces in the login image")
            except Exception as e:
                print(f"Face detection error during login: {str(e)}")
                return jsonify({"success": False, "message": f"Face detection failed: {str(e)}"}), 400
            
            # Use direct image paths for verification instead of embeddings
            if name is None:
                # Fetch all registered users
                query = "SELECT name, role, department FROM register"
                cursor.execute(query)
                all_users = cursor.fetchall()
                
                if not all_users:
                    return jsonify({"success": False, "message": "No registered users found"}), 400
                
                best_match = None
                lowest_distance = float('inf')
                valid_user_count = 0
                
                # Compare with all registered users using direct image verification
                for user_data in all_users:
                    stored_name = user_data[0]
                    stored_role = user_data[1]
                    stored_department = user_data[2]
                    
                    try:
                        # Create path to the stored face image
                        stored_img_path = f"face_embeddings/{stored_name}.jpg"
                        
                        # Skip if the file doesn't exist
                        if not os.path.exists(stored_img_path):
                            print(f"Warning: Reference image for {stored_name} not found")
                            continue
                            
                        valid_user_count += 1
                            
                        # Use DeepFace's verify function with the image path directly
                        verification = DeepFace.verify(
                            img1_path=temp_img_path,
                            img2_path=stored_img_path,
                            model_name="VGG-Face",
                            distance_metric="cosine",
                            enforce_detection=False
                        )
                        
                        distance = verification["distance"]
                        print(f"Distance from {stored_name}: {distance}")
                        
                        if distance < lowest_distance:
                            lowest_distance = distance
                            best_match = (stored_name, stored_role, stored_department, distance)
                    except Exception as e:
                        print(f"Error comparing with user {stored_name}: {str(e)}")
                        continue
                
                if valid_user_count == 0:
                    return jsonify({"success": False, "message": "No valid reference images found. Please register users again."}), 400
                
                # Set recognition threshold
                threshold = 0.4  # Adjusted threshold for better recognition
                
                if best_match and best_match[3] < threshold:
                    name = best_match[0]
                    role = best_match[1]
                    department = best_match[2]
                    print(f"Identified person: {name}, distance: {best_match[3]}")
                else:
                    return jsonify({"success": False, "message": f"Face not recognized, please try again. Best match: {best_match[0] if best_match else 'None'}, distance: {best_match[3] if best_match else 'N/A'}"}), 400
            else:
                # If name is provided, verify the person directly using images
                query = "SELECT role, department FROM register WHERE name = %s"
                cursor.execute(query, (name,))
                result = cursor.fetchone()
                
                if not result:
                    return jsonify({"success": False, "message": "User not registered"}), 400
                
                role = result[0]
                department = result[1]
                
                # Check if reference image exists
                ref_img_path = f"face_embeddings/{name}.jpg"
                if not os.path.exists(ref_img_path):
                    return jsonify({"success": False, "message": f"Reference image not found for user {name}. Please register again."}), 400
                
                # Verify if the face matches using direct image comparison
                try:
                    verification = DeepFace.verify(
                        img1_path=temp_img_path,
                        img2_path=ref_img_path,
                        model_name="VGG-Face",
                        distance_metric="cosine",
                        enforce_detection=False
                    )
                    
                    if not verification["verified"]:
                        return jsonify({"success": False, "message": f"Face not recognized, try again. Distance: {verification['distance']}"}), 400
                except Exception as e:
                    return jsonify({"success": False, "message": f"Face verification error: {str(e)}"}), 400
            
            # At this point, we have a verified or identified user
            # Check if the user has already marked attendance
            query = "SELECT time_in FROM attendance WHERE name = %s"
            cursor.execute(query, (name,))
            res = cursor.fetchone()

            if res and res[0] is not None:
                response = jsonify({
                    "success": True, 
                    "message": "Attendance already marked", 
                    "name": name, 
                    "department": department, 
                    "role": role
                })
                # update attendance of user's time_out column
                query = "UPDATE attendance SET time_out = %s WHERE name = %s"
                cursor.execute(query, (datetime.datetime.now(), name))
                connection.commit()
            else:
                # Insert attendance if it's not already marked
                query = "INSERT INTO attendance (name, role, time_in) VALUES (%s, %s, %s)"
                cursor.execute(query, (name, role, datetime.datetime.now()))
                connection.commit()

                response = jsonify({
                    "success": True, 
                    "message": "Attendance marked successfully", 
                    "name": name, 
                    "department": department, 
                    "role": role
                })
                
            return response, 200
            
        except Exception as e:
            print(f"Attendance marking error: {str(e)}")
            return jsonify({"success": False, "message": f"Error marking attendance: {str(e)}"}), 400
            
        finally:
            # Clean up the temporary image
            if temp_img_path and os.path.exists(temp_img_path):
                os.remove(temp_img_path)

    except Exception as e:
        print(f"General attendance error: {str(e)}")
        # Clean up the temporary image
        if temp_img_path and os.path.exists(temp_img_path):
            os.remove(temp_img_path)
        return jsonify({"success": False, "message": f"Error: {str(e)}"}), 400



if __name__ == '__main__':
    app.run(
        debug=True, 
        host='0.0.0.0', 
        port=5000, 
        #ssl_context=context
    )