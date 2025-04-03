<?php 

session_start(); 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Reset margins and paddings */
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
        }
        .container-all {
            height: 100vh;
            width: 100vw;
            background: #6f9947;
            display: flex; /* Add flexbox to center content */
            flex-direction: column; /* Stack the elements vertically */
            justify-content: space-around; /* Center horizontally */
            align-items: center; /* Center vertically */
        }

        .left-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: auto;
        }

        .right-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: left;
            margin-left: 64px;
        }

        .bottom-container {
            background-color: #64A651;
            font-size: 24px;
            font-family: 'Roboto', sans-serif;
            font-weight: bold;
            border-radius: 10px;
            color: #ffffff;
            padding-left: 24px;
            padding-top: 1px;
            padding-bottom: 1px;
            position:fixed;
            bottom: 0;
            display: none;
            z-index: 1000;
        }

        .name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        button {
            padding: 10px 20px;
            margin-top: 20px;
            font-size: 16px;
            cursor: pointer;
        }

        #capture-button {
            background-color: #64A651;
            color: #131313;
            border: 5px solid #131313;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 40px;
            font-family: 'Roboto', sans-serif;
            font-weight: bold;
        }

        #back-button {
            background-color: #FF9933;
            color: #131313;
            border: 5px solid #131313;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 40px;
            font-family: 'Roboto', sans-serif;
            font-weight: bold;
        }
        
        #register-button {
            background-color: #0099CC;
            color: #131313;
            border: 5px solid #131313;
            border-radius: 4px;
            margin-top: 5px;
            font-size: 40px;
            font-family: 'Roboto', sans-serif;
            font-weight: bold;
        }
        
        #toggle-login-method {
            background-color: #0099CC;
            color: #131313;
            border: 5px solid #131313;
            border-radius: 10px;
            font-size: 24px;
            font-family: 'Roboto', sans-serif;
            font-weight: bold;
            margin-bottom: 20px;
            width: 100%;
        }

        input {
            font-size: 32px;
            font-family: 'Roboto', sans-serif;
            font-weight: bold;
            padding: 10px;
            background-color: transparent; /* Removes background */
            border: none; /* Removes all borders */
            border-bottom: 2px solid #000; /* Adds a bottom border */
            outline: none; /* Removes the default focus outline */
            margin-bottom: 10px;
        }
        
        input::placeholder {
            color: #131313; /* Change this to your desired color */
        }
        
        #username {
            margin-bottom: 22px;
            display: none; /* Hide username by default */
        }

        #capture-button:hover {
            background-color: #90EE90;
        }
        
        #back-button:hover {
            background-color: #FFCC66;
        }
        
        #register-button:hover {
            background-color: #66CCFF;
        }

        video {
            border: 1px solid #000000;
            border-radius: 4px;
            width: 400px;
            height: 400px;
            object-fit: cover;
        }
        
        .login-container {
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            border: 5px solid #131313;
            border-radius: 10px;
            background-color: #fff;
            margin-top: 20px;
        }
        
        .form-group {
            width: 100%;
            margin-bottom: 15px;
        }
        
        .face-scan-indicator {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: 'Roboto', sans-serif;
            z-index: 100;
            font-size: 16px;
        }
        
        .video-container {
            position: relative;
        }
        
        #scanning-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 4px solid #64A651;
            border-radius: 4px;
            box-sizing: border-box;
            animation: scanning 2s infinite linear;
            pointer-events: none;
            display: block;
        }
        
        @keyframes scanning {
            0% {
                border-color: rgba(100, 166, 81, 0.5);
                box-shadow: 0 0 0 0 rgba(100, 166, 81, 0.7);
            }
            50% {
                border-color: rgba(100, 166, 81, 1);
                box-shadow: 0 0 0 5px rgba(100, 166, 81, 0.3);
            }
            100% {
                border-color: rgba(100, 166, 81, 0.5);
                box-shadow: 0 0 0 0 rgba(100, 166, 81, 0.7);
            }
        }

    </style>
</head>

<body>
    <?php include '../../Capstone2/header.php'; ?>
    <div class="container-all">
        <div class="left-container">
            <!-- Video element to display webcam stream with scanning overlay -->
            <div class="video-container">
                <video id="webcam" autoplay></video>
                <div id="scanning-overlay"></div>
                <div class="face-scan-indicator">Scanning for face...</div>
            </div>

            <div class="right-container">
                <button id="toggle-login-method">Switch to Username/Password</button>
                
                <!-- Facial Recognition Login Form -->
                <div id="facial-recognition-container">
                    <input type="text" id="username" placeholder="Enter your username">
                    <button id="register-button">Register</button>
                    <button id="capture-button">Manual Submit</button>
                    <button id="back-button">Back</button>
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
                            <button type="submit" id="traditional-login-btn" 
                                    style="background-color: #64A651; border: 5px solid #131313; 
                                           border-radius: 10px; font-size: 32px; width: 100%;
                                           color: #131313; font-weight: bold;">
                                Login
                            </button>
                        </div>
                        <div class="form-group">
                            <button type="button" id="register-button-traditional" 
                                    style="background-color: #0099CC; border: 5px solid #131313; 
                                           border-radius: 10px; font-size: 32px; width: 100%;
                                           color: #131313; font-weight: bold;">
                                Register
                            </button>
                        </div>
                        <div class="form-group">
                            <button type="button" id="back-button-traditional"
                                   style="background-color: #FCA61D; border: 5px solid #131313; 
                                          border-radius: 10px; font-size: 32px; width: 100%;
                                          color: #131313; font-weight: bold;">
                                Back
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Canvas element to capture and draw image -->
            <canvas id="canvas" style="display:none;"></canvas>
        </div>

        <div class="bottom-container">
            <!-- Text that changes to show the user the registration status -->
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
            document.querySelector('.bottom-container').style.backgroundColor = '#64A651';
            
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
                    document.querySelector('.bottom-container').style.backgroundColor = '#FF4C4C';
                    
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
                    document.querySelector('.bottom-container').style.backgroundColor = '#64A651';
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
                    document.querySelector('.bottom-container').style.backgroundColor = '#FF4C4C';

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
                document.querySelector('.bottom-container').style.backgroundColor = '#FF4C4C';

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
