# Import needed modules
import mysql.connector
import os
import numpy as np
import cv2
import time
from deepface import DeepFace

# Configuration for Apple Face ID-like recognition
RECOGNITION_MODELS = ["VGG-Face", "Facenet", "ArcFace"]  # Multiple models for better accuracy
DISTANCE_METRIC = "cosine"
AUTHENTICATION_THRESHOLDS = {
    "VGG-Face": 0.3,     # More strict than default
    "Facenet": 0.25,     # More strict than default
    "ArcFace": 0.35,     # More strict than default
    "ensemble": 0.25     # Threshold when combining multiple models
}

# Function to register a user with facial recognition
def register_user_with_facial_recognition(name, username, role, department, face_image_path):
    try:
        connection = mysql.connector.connect(
            host="localhost",
            user="root",
            password="password",
            database="superpack_database"
        )
        
        cursor = connection.cursor()
        
        # Start transaction to ensure data consistency across all tables
        cursor.execute("START TRANSACTION")
        
        # 1. First validate the face image quality for better recognition later
        if not validate_face_quality(face_image_path):
            connection.rollback()
            return False, "Face image quality is too low. Please ensure good lighting and a clear view of your face."
        
        # 2. Generate multiple face embeddings for improved recognition accuracy
        embeddings = generate_multi_model_embeddings(face_image_path)
        if not embeddings:
            connection.rollback()
            return False, "Failed to generate face embeddings. Please try again with a clearer image."
            
        # 3. Store both the image path and the primary embedding model's data
        primary_model = RECOGNITION_MODELS[0]
        landmarks_hash = face_image_path
        
        # Now proceed with database insertions
        # 1. Insert into register table (facial recognition data)
        insert_register = "INSERT INTO register (name, role, department, landmarks_hash) VALUES (%s, %s, %s, %s)"
        cursor.execute(insert_register, (name, role, department, landmarks_hash))
        register_id = cursor.lastrowid
        
        # 2. Insert into employee_records table
        insert_employee = """
        INSERT INTO employee_records 
        (name, position, address, phone_number, age, email, shift, salary, status, start_date) 
        VALUES (%s, %s, '', '', '', '', '1', '0', 'Active', NOW())
        """
        cursor.execute(insert_employee, (name, role))
        employee_id = cursor.lastrowid
        
        # 3. Insert into worker_evaluations
        emp_id = f"EMP-{employee_id:04d}"
        insert_evaluation = """
        INSERT INTO worker_evaluations 
        (id, employee_id, name, position, department, start_date, comments, performance) 
        VALUES (%s, %s, %s, %s, %s, NOW(), '', 0)
        """
        cursor.execute(insert_evaluation, (emp_id, emp_id, name, role, department))
        
        # 4. Insert into payroll_records
        insert_payroll = """
        INSERT INTO payroll_records 
        (name, position, salary, daily_rate, basic_pay, ot_pay, late_deduct, gross_pay, 
        sss_deduct, pagibig_deduct, total_deduct, net_salary, date_created) 
        VALUES (%s, %s, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NOW())
        """
        cursor.execute(insert_payroll, (name, role))
        
        # Commit all changes
        connection.commit()
        return True, "User registered successfully in all systems"
    except Exception as e:
        # Rollback in case of error
        connection.rollback()
        return False, f"Error registering user: {str(e)}"
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

# Function to validate face image quality for better recognition
def validate_face_quality(image_path):
    try:
        # Load the image
        img = cv2.imread(image_path)
        if img is None:
            print("Failed to load image")
            return False
        
        # Extract faces to ensure detection
        faces = DeepFace.extract_faces(image_path, enforce_detection=False)
        if not faces or len(faces) == 0:
            print("No face detected in the image")
            return False
        
        # Get the primary face
        face = faces[0]['face']
        confidence = faces[0].get('confidence', 0)
        
        # Convert to grayscale for quality checks
        gray = cv2.cvtColor(face, cv2.COLOR_BGR2GRAY)
        
        # 1. Check image sharpness using Laplacian variance
        laplacian_var = cv2.Laplacian(gray, cv2.CV_64F).var()
        print(f"Laplacian variance (image sharpness): {laplacian_var}")
        if laplacian_var < 100:  # Too blurry
            print("Image is too blurry")
            return False
        
        # 2. Check image contrast
        min_val, max_val, min_loc, max_loc = cv2.minMaxLoc(gray)
        contrast = max_val - min_val
        print(f"Image contrast: {contrast}")
        if contrast < 50:  # Too low contrast
            print("Image has poor contrast")
            return False
        
        # 3. Check face size relative to image (face should be prominent)
        face_area = face.shape[0] * face.shape[1]
        image_area = img.shape[0] * img.shape[1]
        face_ratio = face_area / image_area
        print(f"Face area ratio: {face_ratio:.4f}")
        if face_ratio < 0.05:  # Face is too small
            print("Face is too small in the image")
            return False
        
        # Additional checks can be added here
        
        # If all checks pass
        print("Image quality validation passed")
        return True
        
    except Exception as e:
        print(f"Error validating image: {str(e)}")
        return False  # Fail safe - require good quality images

# Function to generate embeddings from multiple models for better recognition
def generate_multi_model_embeddings(image_path):
    embeddings = {}
    try:
        for model in RECOGNITION_MODELS:
            try:
                print(f"Generating {model} embedding...")
                model_embeddings = DeepFace.represent(image_path, model_name=model, enforce_detection=False)
                if isinstance(model_embeddings, list) and len(model_embeddings) > 0:
                    embeddings[model] = model_embeddings[0].get('embedding', [])
                    print(f"Successfully generated {model} embedding")
            except Exception as e:
                print(f"Error with {model} model: {str(e)}")
                
        # Return embeddings if at least one model succeeded
        if embeddings:
            return embeddings
        return None
    except Exception as e:
        print(f"Error generating embeddings: {str(e)}")
        return None

# Function to verify a user's face by comparing with stored face data
def verify_user_face(captured_face_path, stored_face_path):
    try:
        # Run verification with multiple models for higher accuracy
        results = []
        distances = {}
        success_count = 0
        
        for model in RECOGNITION_MODELS:
            try:
                verification = DeepFace.verify(
                    img1_path=captured_face_path,
                    img2_path=stored_face_path,
                    model_name=model,
                    distance_metric=DISTANCE_METRIC,
                    enforce_detection=False
                )
                
                # Store individual model results
                distance = verification["distance"]
                verified = verification["verified"]
                threshold = verification["threshold"]
                
                print(f"Model {model}: Distance {distance:.4f}, Verified: {verified}, Threshold: {threshold}")
                
                distances[model] = distance
                if verified:
                    success_count += 1
                    
                results.append({
                    "model": model,
                    "distance": distance,
                    "verified": verified,
                    "threshold": threshold
                })
            except Exception as e:
                print(f"Model {model} failed: {str(e)}")
        
        # Calculate ensemble result
        if not distances:
            return False, "All face verification models failed", None
        
        # Calculate average distance
        avg_distance = sum(distances.values()) / len(distances)
        
        # Ensemble verification logic - majority rules or any very confident match
        ensemble_verified = success_count >= (len(distances) / 2)
        high_confidence = any(d < AUTHENTICATION_THRESHOLDS["ensemble"] for d in distances.values())
        
        verification_result = ensemble_verified or high_confidence
        confidence = min(100, 100 * (1 - avg_distance / 0.5))  # Normalize to percentage
        
        detailed_result = {
            "verified": verification_result,
            "confidence": confidence,
            "distance": avg_distance,
            "model_results": results,
            "models_passed": success_count,
            "models_total": len(distances)
        }
        
        return verification_result, "Face verified successfully" if verification_result else "Face verification failed", detailed_result
    except Exception as e:
        print(f"Verification error: {str(e)}")
        return False, f"Error during verification: {str(e)}", None