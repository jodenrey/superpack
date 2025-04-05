<?php 

session_start(); 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperPack Enterprise - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modern CSS Reset and Base Styles */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            background-color: #f8f9fa;
        }
        
        /* Main container with gradient background */
        .container-all {
            min-height: 100vh;
            width: 100%;
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 40px 20px;
        }
        
        /* Background pattern */
        .container-all::before {
            content: "";
            position: absolute;
            width: 200%;
            height: 200%;
            top: -50%;
            left: -50%;
            background: radial-gradient(rgba(255, 255, 255, 0.1) 8%, transparent 8%);
            background-position: 0 0;
            background-size: 30px 30px;
            transform: rotate(30deg);
            z-index: 1;
        }
        
        /* Login content container */
        .login-content {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: stretch;
            max-width: 1000px;
            width: 100%;
            position: relative;
            z-index: 2;
        }
        
        /* Left section - webcam */
        .left-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px 0 0 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        /* Right section - form */
        .right-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 0 20px 20px 0;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        /* Responsive layout for mobile */
        @media (max-width: 768px) {
            .login-content {
                flex-direction: column;
                width: 100%;
            }
            
            .left-container {
                border-radius: 20px 20px 0 0;
                padding: 15px;
            }
            
            .right-container {
                border-radius: 0 0 20px 20px;
                padding: 20px;
            }
        }
        
        /* Video container with scanning overlay */
        .video-container {
            position: relative;
            width: 100%;
            max-width: 360px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        video {
            width: 100%;
            height: 360px;
            object-fit: cover;
            border-radius: 15px;
            display: block;
        }
        
        #scanning-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 3px solid #36D1DC;
            border-radius: 15px;
            box-sizing: border-box;
            animation: scanning 2s infinite linear;
            pointer-events: none;
        }
        
        @keyframes scanning {
            0% {
                border-color: rgba(54, 209, 220, 0.5);
                box-shadow: 0 0 0 0 rgba(54, 209, 220, 0.7);
            }
            50% {
                border-color: rgba(54, 209, 220, 1);
                box-shadow: 0 0 0 5px rgba(54, 209, 220, 0.3);
            }
            100% {
                border-color: rgba(54, 209, 220, 0.5);
                box-shadow: 0 0 0 0 rgba(54, 209, 220, 0.7);
            }
        }
        
        .face-scan-indicator {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            z-index: 100;
        }
        
        /* Form styling */
        input {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: none;
            border-bottom: 2px solid #ddd;
            background-color: transparent;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            transition: border-color 0.3s ease;
            outline: none;
        }
        
        input:focus {
            border-bottom-color: #3a7bd5;
        }
        
        input::placeholder {
            color: #999;
        }
        
        /* Modern button styling */
        button {
            cursor: pointer;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 16px;
            padding: 12px 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            width: 100%;
        }
        
        button::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.2));
            z-index: 1;
        }
        
        #toggle-login-method {
            background: linear-gradient(135deg, #36D1DC, #5B86E5);
            color: white;
        }
        
        #capture-button {
            background: linear-gradient(135deg, #64A651, #90EE90);
            color: white;
        }
        
        #register-button {
            background: linear-gradient(135deg, #36D1DC, #5B86E5);
            color: white;
        }
        
        #back-button {
            background: linear-gradient(135deg, #FF9966, #FF5E62);
            color: white;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        /* Toggle button and forms */
        #facial-recognition-container {
            display: block;
        }
        
        .login-container {
            display: none;
            flex-direction: column;
            width: 100%;
        }
        
        #traditional-login-btn {
            background: linear-gradient(135deg, #64A651, #90EE90);
            color: white;
        }
        
        #register-button-traditional {
            background: linear-gradient(135deg, #36D1DC, #5B86E5);
            color: white;
        }
        
        #back-button-traditional {
            background: linear-gradient(135deg, #FF9966, #FF5E62);
            color: white;
        }
        
        /* Status message */
        .bottom-container {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(100, 166, 81, 0.9);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            font-weight: 500;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: none;
            text-align: center;
            max-width: 80%;
        }
        
        /* Canvas for capturing webcam */
        canvas {
            display: none;
        }
    </style>
</head>

<body>
    <?php include '../../Capstone2/header.php'; ?>
    
    <div class="container-all">
        <div class="login-content">
            <!-- Left section with webcam -->
            <div class="left-container">
                <div class="video-container">
                    <video id="webcam" autoplay></video>
                    <div id="scanning-overlay"></div>
                    <div class="face-scan-indicator">Scanning for face...</div>
                </div>
                <!-- Hidden canvas for processing -->
                <canvas id="canvas" style="display:none;"></canvas>
            </div>

            <!-- Right section with login forms -->
            <div class="right-container">
                <h2>Sign In</h2>
                <p>Use facial recognition or username/password to access your account</p>
                
                <button id="toggle-login-method">Switch to Username/Password</button>
                
                <!-- Facial Recognition Login Form -->
                <div id="facial-recognition-container">
                    <input type="text" id="username" placeholder="Enter your username" style="display: none;">
                    <button id="register-button">Register New Account</button>
                    <button id="capture-button">Manual Submit</button>
                    <button id="back-button">Back to Home</button>
                </div>
                
                <!-- Traditional Login Form -->
                <div id="traditional-login-container" class="login-container">
                    <form id="traditional-login-form" method="post" action="traditional_login.php">
                        <div class="form-group">
                            <input type="text" name="username" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" placeholder="Password" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" id="traditional-login-btn">Login</button>
                        </div>
                        <div class="form-group">
                            <button type="button" id="register-button-traditional">Register</button>
                        </div>
                        <div class="form-group">
                            <button type="button" id="back-button-traditional">Back to Home</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="bottom-container">
            <p id="status">Login status: Waiting for input...</p>
        </div>
    </div>
    
    <script>
        // Toggle between facial recognition and traditional login
        document.getElementById('toggle-login-method').addEventListener('click', function() {
            const facialRecognitionContainer = document.getElementById('facial-recognition-container');
            const traditionalLoginContainer = document.getElementById('traditional-login-container');
            const toggleButton = document.getElementById('toggle-login-method');
            const webcamElement = document.getElementById('webcam');
            const scanningOverlay = document.getElementById('scanning-overlay');
            const faceScanIndicator = document.querySelector('.face-scan-indicator');
            
            if (traditionalLoginContainer.style.display === 'none' || traditionalLoginContainer.style.display === '') {
                facialRecognitionContainer.style.display = 'none';
                traditionalLoginContainer.style.display = 'flex';
                toggleButton.textContent = 'Switch to Facial Recognition';
                webcamElement.style.display = 'none';
                scanningOverlay.style.display = 'none';
                faceScanIndicator.style.display = 'none';
                clearInterval(faceDetectionInterval); // Stop face detection
            } else {
                facialRecognitionContainer.style.display = 'block';
                traditionalLoginContainer.style.display = 'none';
                toggleButton.textContent = 'Switch to Username/Password';
                webcamElement.style.display = 'block';
                scanningOverlay.style.display = 'block';
                faceScanIndicator.style.display = 'block';
                initWebcam();
            }
        });
    
        // Return to the welcome page when the back button is clicked
        document.getElementById('back-button').addEventListener('click', function() {
            window.location.href = '../../welcome.php';
        });
        
        document.getElementById('back-button-traditional').addEventListener('click', function() {
            window.location.href = '../../welcome.php';
        });

        // Redirect to the register page when the register button is clicked
        document.getElementById('register-button').addEventListener('click', function() {
            window.location.href = 'register.php';
        });
        
        document.getElementById('register-button-traditional').addEventListener('click', function() {
            window.location.href = 'register.php';
            // Automatically switch to traditional registration
            setTimeout(() => {
                if (document.getElementById('toggle-login-method')) {
                    document.getElementById('toggle-login-method').click();
                }
            }, 500);
        });

        // Get elements from the DOM
        const webcamElement = document.getElementById('webcam');
        const canvasElement = document.getElementById('canvas');
        const captureButton = document.getElementById('capture-button');
        const canvasContext = canvasElement.getContext('2d');
        let faceDetectionInterval;
        let processingFace = false;
        let lastDetectionTime = 0;
        
        // Set the minimum time between face detections (in milliseconds)
        const minTimeBetweenDetections = 3000;

        // Initialize webcam stream
        function initWebcam() {
            // First, show a message to the user about webcam permissions
            document.getElementById('status').textContent = "Please allow camera access when prompted";
            document.querySelector('.bottom-container').style.display = 'block';
            
            // Better error classification
            navigator.mediaDevices.getUserMedia({ video: true })
                .then((stream) => {
                    webcamElement.srcObject = stream;
                    // Start automatic face detection after webcam is initialized
                    startFaceDetection();
                    
                    // Hide the permission message
                    setTimeout(() => {
                        document.querySelector('.bottom-container').style.display = 'none';
                    }, 1500);
                })
                .catch((error) => {
                    console.error("Error accessing webcam: ", error);
                    
                    let errorMessage = "";
                    
                    // Provide specific error messages based on the error type
                    if (error.name === "NotAllowedError") {
                        errorMessage = "Camera access denied. Please allow camera access in your browser settings and refresh the page.";
                    } else if (error.name === "NotFoundError") {
                        errorMessage = "No camera found. Please connect a camera and refresh the page.";
                    } else if (error.name === "NotReadableError") {
                        errorMessage = "Camera is in use by another application. Please close other apps using the camera.";
                    } else {
                        errorMessage = "Camera error: " + error.message;
                    }
                    
                    // Show error message to user
                    document.getElementById('status').textContent = errorMessage;
                    document.querySelector('.bottom-container').style.display = 'block';
                    document.querySelector('.bottom-container').style.backgroundColor = 'rgba(255, 76, 76, 0.9)';
                    
                    // Disable scanning overlay and indicator
                    document.getElementById('scanning-overlay').style.display = 'none';
                    document.querySelector('.face-scan-indicator').textContent = "Camera not available";
                    document.querySelector('.face-scan-indicator').style.backgroundColor = '#FF4C4C';
                    
                    // Auto switch to traditional login after delay
                    setTimeout(() => {
                        document.getElementById('toggle-login-method').click();
                    }, 3000);
                });
        }
        
        // Start periodic face detection
        function startFaceDetection() {
            // Clear any existing interval
            if (faceDetectionInterval) {
                clearInterval(faceDetectionInterval);
            }
            
            // Set face detection to run every 1 second
            faceDetectionInterval = setInterval(() => {
                // Only process if not already processing and enough time has passed
                const currentTime = Date.now();
                if (!processingFace && (currentTime - lastDetectionTime) >= minTimeBetweenDetections) {
                    captureAndDetectFace();
                }
            }, 1000);
        }
        
        // Capture and detect face automatically
        function captureAndDetectFace() {
            if (!webcamElement.videoWidth) return; // Skip if video is not ready
            
            processingFace = true;
            document.querySelector('.face-scan-indicator').textContent = "Processing...";
            
            // Set canvas dimensions
            canvasElement.width = webcamElement.videoWidth;
            canvasElement.height = webcamElement.videoHeight;
            
            // Draw video frame to canvas
            canvasContext.drawImage(webcamElement, 0, 0, canvasElement.width, canvasElement.height);
            
            // Convert to base64
            const image = canvasElement.toDataURL('image/png');
            
            // Send to server for face recognition (without a username)
            sendImageForRecognition(image.split(',')[1]);
        }

        // Capture image function (for manual button)
        function captureImage() {
            // Set canvas width and height to video element's width and height
            canvasElement.width = webcamElement.videoWidth;
            canvasElement.height = webcamElement.videoHeight;

            // Draw the current frame from the video to the canvas
            canvasContext.drawImage(webcamElement, 0, 0, canvasElement.width, canvasElement.height);
            
            // Convert the canvas to a base64-encoded PNG image
            const image = canvasElement.toDataURL('image/png');
            
            // Get username (if available, but not required anymore)
            const username = document.getElementById('username').value;
            
            // Send image for recognition
            sendImageForRecognition(image.split(',')[1], username);
        }
        
        // Function to send image to server for face recognition
        function sendImageForRecognition(imageBase64, username = null) {
            // Prepare the data payload to send to the Python script
            const dataPayload = { 
                image: imageBase64
            };
            
            // Add username to payload if provided (optional)
            if (username) {
                dataPayload.username = username;
            }
            
            // Set last detection time to now
            lastDetectionTime = Date.now();
            
            // Send the image data to the Python script
            fetch('http://localhost:5000/Face_API/mark-attendance', {
                method: 'POST',
                body: JSON.stringify(dataPayload),
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then((response) => response.json())
            .then((data) => { 
                console.log("Server response:", data);
                
                // If face recognized successfully
                if (data.success) {
                    console.log("Attendance Marked!");
                    
                    document.getElementById('status').textContent = data.message;
                    document.querySelector('.bottom-container').style.backgroundColor = 'rgba(100, 166, 81, 0.9)';
                    document.querySelector('.bottom-container').style.display = 'block';

                    // Create a form element
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/superpack/Capstone2/dashboardnew.php';

                    // Hidden input for username (now the name from DeepFace identification)
                    const inputName = document.createElement('input');
                    inputName.type = 'hidden';
                    inputName.name = 'username';
                    inputName.value = data.name; // Using 'name' instead of 'username'
                    form.appendChild(inputName);

                    // Hidden input for role
                    const inputRole = document.createElement('input');
                    inputRole.type = 'hidden';
                    inputRole.name = 'role';
                    inputRole.value = data.role;
                    form.appendChild(inputRole);

                    // Hidden input for department
                    const inputDepartment = document.createElement('input');
                    inputDepartment.type = 'hidden';
                    inputDepartment.name = 'user_department';
                    inputDepartment.value = data.department;
                    form.appendChild(inputDepartment);

                    // Hidden input for loggedin variable
                    const inputLoggedIn = document.createElement('input');
                    inputLoggedIn.type = 'hidden';
                    inputLoggedIn.name = 'loggedin';
                    inputLoggedIn.value = true;
                    form.appendChild(inputLoggedIn);

                    // Append the form to the body
                    document.body.appendChild(form);

                    // Stop face detection before redirecting
                    clearInterval(faceDetectionInterval);

                    // Submit the form
                    form.submit();
                } else {
                    console.log("Error: ", data.message);
                    
                    document.getElementById('status').textContent = data.message;
                    document.querySelector('.bottom-container').style.display = 'block';
                    document.querySelector('.bottom-container').style.backgroundColor = 'rgba(255, 76, 76, 0.9)';

                    // Hide the error message after 2.5 seconds
                    setTimeout(() => {
                        document.querySelector('.bottom-container').style.display = 'none';
                    }, 2500);
                    
                    // Reset processing flag
                    processingFace = false;
                    document.querySelector('.face-scan-indicator').textContent = "Scanning for face...";
                }
            })
            .catch((error) => {
                console.error("Error sending image to server: ", error);
                
                document.getElementById('status').textContent = "Error connecting to face recognition server.";
                document.querySelector('.bottom-container').style.display = 'block';
                document.querySelector('.bottom-container').style.backgroundColor = 'rgba(255, 76, 76, 0.9)';

                setTimeout(() => {
                    document.querySelector('.bottom-container').style.display = 'none';
                }, 2500);
                
                // Reset processing flag
                processingFace = false;
                document.querySelector('.face-scan-indicator').textContent = "Scanning for face...";
            });
        }

        // Initialize webcam on page load
        initWebcam();

        // Capture image when button is clicked (manual fallback)
        captureButton.addEventListener('click', captureImage);
    </script>
</body>
</html>
