import cv2
import numpy as np
from deepface import DeepFace
import matplotlib.pyplot as plt
import os
import time

# Constants for recognition
RECOGNITION_MODELS = ["VGG-Face"]  # Only use one model for testing to improve performance
CONFIDENCE_THRESHOLD = 0.6  # Higher is more confident

# Create face embeddings directory if it doesn't exist
os.makedirs('face_embeddings', exist_ok=True)

# Function to detect liveness (to prevent photo attacks) - adjusted thresholds for better detection
def detect_liveness(image):
    try:
        # Convert to grayscale
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        
        # Calculate texture using Laplacian variance (higher for real faces)
        laplacian_var = cv2.Laplacian(gray, cv2.CV_64F).var()
        
        # Calculate gradient magnitude
        sobelx = cv2.Sobel(gray, cv2.CV_64F, 1, 0, ksize=3)
        sobely = cv2.Sobel(gray, cv2.CV_64F, 0, 1, ksize=3)
        gradient_magnitude = np.sqrt(sobelx**2 + sobely**2)
        gradient_mean = gradient_magnitude.mean()
        
        # Calculate histogram of oriented gradients (simple version)
        gx = cv2.Sobel(gray, cv2.CV_32F, 1, 0)
        gy = cv2.Sobel(gray, cv2.CV_32F, 0, 1)
        mag, ang = cv2.cartToPolar(gx, gy)
        hog_mean = mag.mean()
        
        # Print values for calibration
        print(f"Liveness metrics - Laplacian: {laplacian_var:.1f}, Gradient: {gradient_mean:.1f}, HOG: {hog_mean:.1f}")
        
        # LOWERED THRESHOLDS based on real-world testing
        is_live = (laplacian_var > 50) and (gradient_mean > 5) and (hog_mean > 10)
        confidence = min(100, (laplacian_var / 100 * 30) + (gradient_mean / 10 * 30) + (hog_mean / 20 * 40))
        
        return is_live, confidence
    except Exception as e:
        print(f"Liveness detection error: {str(e)}")
        return True, 50  # Default to true on error for testing

# Function to detect face with better precision but optimized for speed
def detect_face_advanced(image):
    try:
        # First use DeepFace extract_faces which is more robust
        faces = DeepFace.extract_faces(
            img_path=image,
            detector_backend='opencv',  # OpenCV is faster than other backends
            enforce_detection=False,
            align=True
        )
        
        if len(faces) == 0:
            return None, None, 0
        
        # Get the face with highest confidence if available
        best_face = None
        best_confidence = 0
        
        for face in faces:
            confidence = face.get('confidence', 0)
            if best_face is None or confidence > best_confidence:
                best_face = face
                best_confidence = confidence
        
        if best_face is None:
            return None, None, 0
            
        face_img = best_face['face']
        region = best_face['facial_area']
        confidence = best_confidence
        
        return region, face_img, confidence
    except Exception as e:
        print(f"Face detection error: {str(e)}")
        return None, None, 0

# Open webcam
cap = cv2.VideoCapture(0)

# Set resolution lower for better performance
cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)

# For FPS calculation
prev_frame_time = 0
new_frame_time = 0
smooth_fps = 0

# Processing every n frames to improve performance
process_every_n_frames = 3
frame_counter = 0

# Main loop
while cap.isOpened():
    success, frame = cap.read()
    if not success:
        print("Ignoring empty camera frame.")
        continue
    
    # Calculate FPS
    new_frame_time = time.time()
    fps = 1/(new_frame_time-prev_frame_time) if prev_frame_time > 0 else 30
    prev_frame_time = new_frame_time
    # Smooth the FPS value
    smooth_fps = 0.9 * smooth_fps + 0.1 * fps if smooth_fps > 0 else fps
    
    # Create a copy of the frame for display
    display_frame = frame.copy()
    
    # Only process every n frames to improve performance
    frame_counter += 1
    if frame_counter % process_every_n_frames == 0:
        try:
            # Scale down image for faster processing
            small_frame = cv2.resize(frame, (0, 0), fx=0.5, fy=0.5)
            
            # Detect faces using advanced method
            result = detect_face_advanced(small_frame)
            
            if result[0] is not None:
                region, face_img, confidence = result
                
                # Scale coordinates back up
                x, y, w, h = region['x']*2, region['y']*2, region['w']*2, region['h']*2
                
                # Check if face is real (liveness detection)
                is_live, live_confidence = detect_liveness(frame[y:y+h, x:x+w])
                
                # Draw rectangle around face - green if live, red if not
                color = (0, 255, 0) if is_live else (0, 0, 255)
                cv2.rectangle(display_frame, (x, y), (x+w, y+h), color, 2)
                
                # Add text for face detection confidence
                cv2.putText(display_frame, f"Face Confidence: {confidence:.2f}", 
                          (x, y-35), cv2.FONT_HERSHEY_SIMPLEX, 0.6, color, 2)
                
                # Add text for liveness
                live_status = "Real Face" if is_live else "Fake Face?"
                cv2.putText(display_frame, f"{live_status} ({live_confidence:.1f}%)", 
                          (x, y-10), cv2.FONT_HERSHEY_SIMPLEX, 0.6, color, 2)
        
        except Exception as e:
            print(f"Error in face processing: {str(e)}")

    # Display FPS
    cv2.putText(display_frame, f"FPS: {smooth_fps:.1f}", (10, 30), 
                cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 255), 2)
    
    # Display instructions
    cv2.putText(display_frame, "Press ESC to Exit", (10, display_frame.shape[0]-10), 
                cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2)

    # Display the image
    cv2.imshow('Advanced Face Detection', display_frame)

    if cv2.waitKey(5) & 0xFF == 27:  # Press ESC to exit
        break

cap.release()
cv2.destroyAllWindows()