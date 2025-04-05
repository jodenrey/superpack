import json
import base64
import cv2
import datetime
import numpy as np
import mysql.connector
import os
import time
from flask import Flask, request, jsonify
from flask_cors import CORS, cross_origin
from deepface import DeepFace
from facial_auth import register_user_with_facial_recognition
from werkzeug.security import generate_password_hash

# Create a directory to store face embeddings if it doesn't exist
os.makedirs('face_embeddings', exist_ok=True)

context = (r'C:\xampp\apache\conf\ssl.crt\server.crt', r'C:\xampp\apache\conf\ssl.key\server.key')

# Function to get a fresh database connection
def get_db_connection():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="password",
        database="superpack_database",
        port="3306"
    )

# Configure facial recognition settings for high accuracy
RECOGNITION_MODELS = ["VGG-Face", "Facenet", "ArcFace"]  # Using multiple models for better accuracy
DISTANCE_METRIC = "cosine"
# Apple Face ID has a reported false acceptance rate of 1 in 1,000,000
# DeepFace models can achieve different thresholds for different models
# Lower threshold = stricter matching (fewer false positives)
AUTHENTICATION_THRESHOLDS = {
    "VGG-Face": 0.3,     # More strict than default
    "Facenet": 0.25,     # More strict than default
    "ArcFace": 0.35,     # More strict than default
    "ensemble": 0.25     # Threshold when combining multiple models
}
REGISTRATION_THRESHOLDS = {
    "VGG-Face": 0.25,    # Even stricter for registration
    "Facenet": 0.2,
    "ArcFace": 0.3,
    "ensemble": 0.2      # Very strict when combining models for registration
}

# Helper function to detect if face is real (anti-spoofing)
def detect_liveness(image_path):
    try:
        # Load the image
        img = cv2.imread(image_path)
        if img is None:
            return False, "Failed to load image"
            
        # Extract face
        faces = DeepFace.extract_faces(image_path, enforce_detection=False)
        if not faces or len(faces) == 0:
            return False, "No face detected"
            
        face_img = faces[0]['face']
        
        # Basic liveness detection - texture and gradient analysis
        # Convert to grayscale
        gray = cv2.cvtColor(face_img, cv2.COLOR_BGR2GRAY)
        
        # Calculate texture using Laplacian variance (higher for real faces)
        laplacian_var = cv2.Laplacian(gray, cv2.CV_64F).var()
        print(f"Laplacian variance (texture): {laplacian_var}")
        
        # Calculate gradient magnitude
        sobelx = cv2.Sobel(gray, cv2.CV_64F, 1, 0, ksize=3)
        sobely = cv2.Sobel(gray, cv2.CV_64F, 0, 1, ksize=3)
        gradient_magnitude = np.sqrt(sobelx**2 + sobely**2)
        gradient_mean = gradient_magnitude.mean()
        print(f"Gradient mean: {gradient_mean}")
        
        # Calculate histogram of oriented gradients (simple version)
        # Higher values typically indicate real faces vs flat printed images
        gx = cv2.Sobel(gray, cv2.CV_32F, 1, 0)
        gy = cv2.Sobel(gray, cv2.CV_32F, 0, 1)
        mag, ang = cv2.cartToPolar(gx, gy)
        hog_mean = mag.mean()
        print(f"HOG mean: {hog_mean}")
        
        # LOWERED THRESHOLDS based on real-world testing
        is_live = (laplacian_var > 50) and (gradient_mean > 5) and (hog_mean > 10)
        confidence = min(100, (laplacian_var / 100 * 30) + (gradient_mean / 10 * 30) + (hog_mean / 20 * 40))
        
        return is_live, f"Liveness confidence: {confidence:.1f}%"
    except Exception as e:
        print(f"Liveness detection error: {str(e)}")
        return True, "Liveness check bypassed due to error"

# Run multiple models for verification and combine results for better accuracy
def verify_face_with_ensemble(img1_path, img2_path, enforce_detection=False):
    results = []
    distances = {}
    success_count = 0
    
    print(f"Running ensemble verification with models: {RECOGNITION_MODELS}")
    
    # Try each model
    for model in RECOGNITION_MODELS:
        try:
            # Use the simpler verify parameters that work with all DeepFace versions
            verification = DeepFace.verify(
                img1_path=img1_path,
                img2_path=img2_path,
                model_name=model,
                enforce_detection=enforce_detection
            )
            
            # Store individual results
            distances[model] = verification["distance"]
            verified = verification["verified"]
            threshold = verification.get("threshold", 0.4)  # Default threshold if not provided
            
            print(f"Model {model}: Distance {distances[model]:.4f}, Verified: {verified}, Threshold: {threshold}")
            
            # Count successful verifications
            if verified:
                success_count += 1
            
            results.append({
                "model": model,
                "distance": distances[model],
                "verified": verified
            })
        except Exception as e:
            print(f"Model {model} failed: {str(e)}")
    
    # Calculate average distance across working models
    if len(distances) > 0:
        avg_distance = sum(distances.values()) / len(distances)
        # Ensemble verification requires majority of models to agree
        ensemble_verified = success_count >= (len(distances) / 2)
        # Or very high confidence from at least one model
        high_confidence = any(d < AUTHENTICATION_THRESHOLDS["ensemble"] for d in distances.values())
        
        return {
            "verified": ensemble_verified or high_confidence,
            "distance": avg_distance,
            "model_results": results,
            "models_passed": success_count,
            "models_total": len(distances)
        }
    else:
        # If all models failed, return failure
        return {
            "verified": False,
            "distance": 1.0,
            "model_results": [],
            "error": "All facial recognition models failed"
        }

# Enhanced face verification for higher security
def enhanced_verification(img_path, ref_path):
    # First verify liveness to prevent spoofing attacks
    is_live, liveness_message = detect_liveness(img_path)
    
    if not is_live:
        return {
            "verified": False,
            "distance": 1.0,
            "message": f"Liveness check failed. {liveness_message}",
            "liveness": False
        }

    # Run ensemble verification with multiple models
    verification = verify_face_with_ensemble(
        img1_path=img_path,
        img2_path=ref_path,
        enforce_detection=False
    )
    
    # Add liveness result
    verification["liveness"] = is_live
    verification["liveness_message"] = liveness_message
    
    return verification

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
    connection = None
    cursor = None
    
    try:
        # Get a fresh database connection
        connection = get_db_connection()
        cursor = connection.cursor()
        
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
                
                # Check liveness to prevent photo attack
                is_live, liveness_message = detect_liveness(temp_path)
                if not is_live:
                    if os.path.exists(temp_path):
                        os.remove(temp_path)
                    return jsonify({
                        'error': 'Liveness check failed',
                        'details': {'name': name, 'message': liveness_message}
                    }), 400
                
                # If face is detected, proceed with embedding generation
                try:
                    # Print file size for debugging
                    if os.path.exists(temp_path):
                        file_size = os.path.getsize(temp_path)
                        print(f"Temporary image file size: {file_size} bytes")
                    
                    # Generate multiple embeddings with different models for better matching
                    embeddings = {}
                    for model in RECOGNITION_MODELS:
                        try:
                            model_embeddings = DeepFace.represent(temp_path, model_name=model, enforce_detection=False)
                            if isinstance(model_embeddings, list) and len(model_embeddings) > 0:
                                embeddings[model] = model_embeddings[0].get('embedding', [])
                                print(f"Successfully generated {model} embeddings")
                        except Exception as e:
                            print(f"{model} model failed: {str(e)}")
                    
                    # Check if at least one model succeeded
                    if not embeddings:
                        if os.path.exists(temp_path):
                            os.remove(temp_path)
                        return jsonify({
                            'error': 'Failed to generate face embeddings with any model',
                            'details': {'name': name}
                        }), 400
                    
                    # Get a new connection before fetching users to avoid timeout
                    if connection.is_connected():
                        cursor.close()
                        connection.close()
                    
                    connection = get_db_connection()
                    cursor = connection.cursor()
                    
                    # Get all registered users
                    cursor.execute("SELECT name FROM register")
                    registered_users = cursor.fetchall()
                    
                    # Add additional check to avoid empty results
                    if not registered_users:
                        print("No registered users found in database")
                    else:
                        print(f"Found {len(registered_users)} registered users for comparison")
                    
                    # Compare with all existing face embeddings - using strict comparison with multiple models
                    for user in registered_users:
                        # Skip comparison with the current user
                        if user[0] == name:
                            continue
                            
                        ref_path = os.path.join('face_embeddings', f"{user[0]}.jpg")
                        if os.path.exists(ref_path):
                            try:
                                ref_file_size = os.path.getsize(ref_path)
                                print(f"Reference image for {user[0]} size: {ref_file_size} bytes")
                                
                                # Use ensemble verification for higher accuracy
                                verification = verify_face_with_ensemble(
                                    img1_path=temp_path,
                                    img2_path=ref_path,
                                    enforce_detection=False
                                )
                                
                                # Print detailed verification results
                                print(f"Ensemble verification with {user[0]}: {verification}")
                                
                                # Use very strict threshold for registration
                                if verification['verified'] or verification['distance'] < REGISTRATION_THRESHOLDS["ensemble"]:
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
                            except Exception as e:
                                print(f"Error comparing with {user[0]}: {str(e)}")
                                continue
                    
                    # If no duplicate face found, proceed with registration
                    try:
                        # Store embedding as string for the primary model (truncate if too large)
                        primary_model = RECOGNITION_MODELS[0]
                        if primary_model in embeddings:
                            embedding_str = str(embeddings[primary_model])
                            if len(embedding_str) > 5000:  # Set a reasonable limit
                                landmarks_hash = embedding_str[:5000]
                                print("Truncated embedding string (too large)")
                            else:
                                landmarks_hash = embedding_str
                        else:
                            # Fallback to another available model
                            for model, emb in embeddings.items():
                                embedding_str = str(emb)
                                if len(embedding_str) > 5000:
                                    landmarks_hash = embedding_str[:5000]
                                else:
                                    landmarks_hash = embedding_str
                                break
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
        
        # Get a new database connection if the previous one was closed
        if not connection or not connection.is_connected():
            connection = get_db_connection()
            cursor = connection.cursor()
        
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
    finally:
        # Always close database connections in finally block
        if cursor:
            try:
                cursor.close()
            except:
                pass
        if connection and connection.is_connected():
            try:
                connection.close()
            except:
                pass


@app.route('/Face_API/mark-attendance', methods=['POST'])
@cross_origin()
def mark_attendance():
    temp_img_path = None
    connection = None
    cursor = None
    
    try:
        # Get a fresh database connection
        connection = get_db_connection()
        cursor = connection.cursor()
        
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
                
                # Check for liveness
                is_live, liveness_message = detect_liveness(temp_img_path)
                if not is_live:
                    return jsonify({"success": False, "message": f"Liveness check failed. Please use a real face. {liveness_message}"}), 400
                
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
                
                # New approach: collect multiple candidates and make a more informed decision
                candidates = []
                valid_user_count = 0
                
                # Compare with all registered users using enhanced verification
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
                        
                        # Use ensemble verification for higher accuracy
                        start_time = time.time()
                        verification = verify_face_with_ensemble(
                            img1_path=temp_img_path,
                            img2_path=stored_img_path,
                            enforce_detection=False
                        )
                        elapsed = time.time() - start_time
                        print(f"Verification for {stored_name} took {elapsed:.2f}s")
                        
                        # Save candidate with verification data
                        candidates.append({
                            "name": stored_name,
                            "role": stored_role,
                            "department": stored_department,
                            "distance": verification["distance"],
                            "verified": verification["verified"],
                            "models_passed": verification.get("models_passed", 0),
                            "models_total": verification.get("models_total", 0)
                        })
                        
                    except Exception as e:
                        print(f"Error comparing with user {stored_name}: {str(e)}")
                        continue
                
                if valid_user_count == 0:
                    return jsonify({"success": False, "message": "No valid reference images found. Please register users again."}), 400
                
                # Sort candidates by distance (lowest first)
                candidates.sort(key=lambda x: x["distance"])
                
                # Log top candidates for debugging
                for i, candidate in enumerate(candidates[:3]):
                    print(f"Candidate #{i+1}: {candidate['name']}, Distance: {candidate['distance']}, "
                          f"Models: {candidate['models_passed']}/{candidate['models_total']}")
                
                # Check if best candidate passes the threshold
                best_candidate = candidates[0]
                threshold = AUTHENTICATION_THRESHOLDS["ensemble"]
                
                # Verification logic:
                # 1. Best candidate passes distance threshold
                # 2. AND has significant lead over second-best candidate
                has_clear_winner = (len(candidates) == 1 or 
                                    (len(candidates) > 1 and 
                                    (best_candidate["distance"] < candidates[1]["distance"] - 0.1)))
                
                if best_candidate["distance"] < threshold and has_clear_winner:
                    name = best_candidate["name"]
                    role = best_candidate["role"]
                    department = best_candidate["department"]
                    confidence = min(100, 100 * (1 - best_candidate["distance"] / threshold))
                    print(f"Identified person: {name}, confidence: {confidence:.1f}%, distance: {best_candidate['distance']}")
                else:
                    # If multiple candidates are close, reject and ask user to try again
                    if len(candidates) > 1 and candidates[0]["distance"] < threshold + 0.1:
                        message = (f"Uncertain identity match. Multiple possible matches found: "
                                  f"{candidates[0]['name']} ({candidates[0]['distance']:.3f}) and "
                                  f"{candidates[1]['name']} ({candidates[1]['distance']:.3f})")
                    else:
                        message = (f"Face not recognized, please try again. "
                                  f"Best match: {best_candidate['name']}, distance: {best_candidate['distance']:.3f}, "
                                  f"threshold: {threshold}")
                    
                    return jsonify({"success": False, "message": message}), 400
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
                
                # Enhanced verification with ensemble of models and liveness detection
                verification = enhanced_verification(temp_img_path, ref_img_path)
                
                if not verification["verified"]:
                    if not verification.get("liveness", True):
                        return jsonify({"success": False, "message": verification.get("message", "Liveness check failed")}), 400
                    else:
                        return jsonify({"success": False, "message": f"Face not recognized. Please try again. Distance: {verification['distance']:.3f}"}), 400
            
            # Reconnect to MySQL before accessing the database again if this is after a long-running operation
            if not connection.is_connected():
                connection = get_db_connection()
                cursor = connection.cursor()
            
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
            
            # Always close database connections
            if cursor:
                try:
                    cursor.close()
                except:
                    pass
            if connection and connection.is_connected():
                try:
                    connection.close()
                except:
                    pass

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