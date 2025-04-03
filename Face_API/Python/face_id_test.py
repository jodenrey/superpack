import cv2
from deepface import DeepFace
import matplotlib.pyplot as plt
import os

# Open webcam
cap = cv2.VideoCapture(0)

while cap.isOpened():
    success, image = cap.read()
    if not success:
        print("Ignoring empty camera frame.")
        continue

    try:
        # Detect faces using DeepFace
        face_analysis = DeepFace.analyze(image, actions=['detection'], enforce_detection=False)
        
        if len(face_analysis) > 0:
            # Get face region
            face_data = face_analysis[0]['region']
            x, y, w, h = face_data['x'], face_data['y'], face_data['w'], face_data['h']
            
            # Draw rectangle around the face
            cv2.rectangle(image, (x, y), (x+w, y+h), (0, 255, 0), 2)
            
            # Add text
            cv2.putText(image, "Face Detected", (x, y-10), cv2.FONT_HERSHEY_SIMPLEX, 0.9, (0, 255, 0), 2)
    
    except Exception as e:
        print(f"No face detected: {str(e)}")

    # Display the image
    cv2.imshow('DeepFace Face Detection', image)

    if cv2.waitKey(5) & 0xFF == 27:  # Press ESC to exit
        break

cap.release()
cv2.destroyAllWindows()