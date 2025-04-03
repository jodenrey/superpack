<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New User Registration</title>
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
            background: #6f9947 ;
            display: flex; /* Add flexbox to center content */
            justify-content: center; /* Center horizontally */
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
            font-size: 16px;
            font-family: 'Roboto', sans-serif;
            font-weight: bold;
            border-radius: 10px;
            color: #ffffff;
            padding-left: 16px;
            padding-top: 1px;
            padding-bottom: 1px;
            margin-right: 400px;
            margin-left: 400px;
            position:fixed;
            bottom: 0;
            display: none;
            z-index: 1000;
        }

        /* Added error message container */
        .error-container {
            background-color: #FF4C4C;
            color: white;
            font-size: 18px;
            font-family: 'Roboto', sans-serif;
            font-weight: bold;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
            display: none;
        }

        .name {
            color: #131313;
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
            border: none;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 40px;
            font-family: 'Roboto', sans-serif;
            font-weight: bold;
        }

        #back-button {
            background-color: #FF9933;
            color:  #131313;
            border: none;
            border-radius: 4px;
            margin-top: 10px;
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

        input, select {
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

        select:hover {
            cursor: pointer;
        }

        input::placeholder {
            color: #131313; /* Change this to your desired color */
        }

        #capture-button{
            background-color: #2589BD;
            font-size: 32px;
            font-family: 'Roboto', sans-serif;
            font-weight: bold;
            border: 5px solid #131313;
            border-radius: 10px;
        }
        #capture-button:hover {
            background-color: #227BAA;
            cursor: pointer;
        }

        #back-button {
            background-color: #FCA61D;
            font-size: 32px;
            font-family: 'Roboto', sans-serif;
            font-weight: bold;
            border: 5px solid #131313;
            border-radius: 10px;
        }
        #back-button:hover {
            background-color: #FFCC66;
            cursor: pointer;
        }

        video {
            border: 1px solid #000000;
            border-radius: 4px;
            width: 300px;
            height: 300px;
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

    </style>
</head>
<body>
    <?php include '../../Capstone2/header.php'; ?>

    <div class="container-all">
        <div class="left-container">
            <!-- Video element to display webcam stream -->
            <video id="webcam" autoplay></video>
            <!-- Canvas element to capture and draw image -->
            <canvas id="canvas" style="display:none;"></canvas>
        </div>
        
        <div class="right-container">
            <button id="toggle-login-method">Switch to Username/Password</button>
            
            <!-- Facial Recognition Registration Form -->
            <div id="facial-recognition-container">
                <input type="text" id="username" placeholder="Create Username" required>
                <input type="password" id="password" placeholder="Create Password" required>
                
                <br>
                <select id="role" required>
                    <option value="" disabled selected>Select Position</option>
                    <option value="Admin">Admin</option>
                    <option value="Employee">Employee</option>
                </select>
                <br>
                <select id="department" required>
                    <option value="" disabled selected>Select Department</option>
                    <option value="sales">Sales</option>
                    <option value="purchasing">Purchasing</option>
                    <option value="proddev">Product Development</option>
                    <option value="warehouse">Warehouse</option>
                    <option value="logistics">Logistics</option>
                    <option value="accounting">Accounting</option>
                    <option value="admin">Admin</option>
                </select>
                <!-- Added error message container -->
                <div class="error-container" id="error-message"></div>
                <button id="capture-button">Submit</button>
                <button id="back-button">Back</button>
            </div>
            
            <!-- Traditional Login Registration Form -->
            <div id="traditional-login-container" class="login-container">
                <form id="traditional-register-form" method="post" action="traditional_register.php">
                    <div class="form-group">
                        <input type="text" name="trad_username" placeholder="Create Username" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="trad_password" placeholder="Create Password" required>
                    </div>
                    <div class="form-group">
                        <select name="trad_role" required>
                            <option value="" disabled selected>Select Position</option>
                            <option value="Admin">Admin</option>
                            <option value="Employee">Employee</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="trad_department" required>
                            <option value="" disabled selected>Select Department</option>
                            <option value="sales">Sales</option>
                            <option value="purchasing">Purchasing</option>
                            <option value="proddev">Product Development</option>
                            <option value="warehouse">Warehouse</option>
                            <option value="logistics">Logistics</option>
                            <option value="accounting">Accounting</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" id="traditional-register-btn" 
                                style="background-color: #2589BD; border: 5px solid #131313; 
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

        <div class="bottom-container">
            <!-- Text that changes to show the user the registration status -->
            <p id="status">Registration status: Waiting for capture...</p>
        </div>
    </div>

    <!-- JavaScript code to capture the image and send it to the Python script -->
    <script>
        // Toggle between facial recognition and traditional login
        document.getElementById('toggle-login-method').addEventListener('click', function() {
            const facialRecognitionContainer = document.getElementById('facial-recognition-container');
            const traditionalLoginContainer = document.getElementById('traditional-login-container');
            const toggleButton = document.getElementById('toggle-login-method');
            const webcamElement = document.getElementById('webcam');
            
            if (traditionalLoginContainer.style.display === 'none' || traditionalLoginContainer.style.display === '') {
                facialRecognitionContainer.style.display = 'none';
                traditionalLoginContainer.style.display = 'flex';
                toggleButton.textContent = 'Switch to Facial Recognition';
                webcamElement.style.display = 'none';
            } else {
                facialRecognitionContainer.style.display = 'block';
                traditionalLoginContainer.style.display = 'none';
                toggleButton.textContent = 'Switch to Username/Password';
                webcamElement.style.display = 'block';
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
        
        // Get elements from the DOM
        const webcamElement = document.getElementById('webcam');
        const canvasElement = document.getElementById('canvas');
        const captureButton = document.getElementById('capture-button');
        const canvasContext = canvasElement.getContext('2d');

        // Initialize webcam stream
        function initWebcam() {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then((stream) => {
                    webcamElement.srcObject = stream;
                })
                .catch((error) => {
                    console.error("Error accessing webcam: ", error);
                    // Show error message to user
                    document.getElementById('status').textContent = "Could not access webcam. Please use username/password login instead.";
                    document.querySelector('.bottom-container').style.display = 'block';
                    document.querySelector('.bottom-container').style.backgroundColor = '#FF4C4C';
                    
                    // Auto switch to traditional login if webcam fails
                    setTimeout(() => {
                        document.getElementById('toggle-login-method').click();
                    }, 1500);
                });
        }

        // Capture image function
        function captureImage() {
            // Set canvas width and height to video element's width and height
            canvasElement.width = webcamElement.videoWidth;
            canvasElement.height = webcamElement.videoHeight;

            // Draw the current frame from the video to the canvas
            canvasContext.drawImage(webcamElement, 0, 0, canvasElement.width, canvasElement.height);
            
            // Convert the canvas to a base64-encoded PNG image
            const image = canvasElement.toDataURL('image/png');

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const role = document.getElementById('role').value;
            const department = document.getElementById('department').value;
            
            // Prepare the data payload to send to the Python script
            const dataPayload = { 
                image: image.split(',')[1], // Extract base64 string without the data URL prefix
                name: username, // Send username as 'name' to match what the backend expects
                username: username, // Keep username as well for user creation
                password: password,
                role: role,
                department: department 
            };
            
            // Send the image data to the Python script
            fetch('http://localhost:5000/Face_API/register', {  // Adjust the URL to your Python script's path
                method: 'POST',
                body: JSON.stringify(dataPayload),
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then((response) => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw errorData;
                    });
                }
                return response.json();
            })
            .then((data) => { 
                console.log("Server response:", data); // Log the server response to the console 
                if (data.message) {
                    document.getElementById('status').textContent = data.message;
                    document.querySelector('.bottom-container').style.backgroundColor = '#64A651';
                    document.querySelector('.bottom-container').style.display = 'block';

                    // Hide any error messages
                    document.getElementById('error-message').style.display = 'none';

                    // Redirect to login.php after successful registration
                    setTimeout(() => { // Delay the redirect for 2.5 seconds
                        window.location.href = 'login.php';
                    }, 2500);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                
                // Clear any previous error messages
                document.getElementById('error-message').innerHTML = '';
                
                // Handle specific error types
                if (error && error.error) {
                    if (error.error === 'User already registered') {
                        document.getElementById('error-message').innerHTML = 
                            `<strong>User already registered!</strong><br>
                             The username "${error.details.name}" is already taken.<br>
                             Please choose a different username.`;
                    } 
                    else if (error.error === 'Face already registered') {
                        document.getElementById('error-message').innerHTML = 
                            `<strong>Face already registered!</strong><br>
                             Your face matches an existing user: ${error.details.existing_user}<br>
                             Each person can only register once.`;
                    }
                    else if (error.error === 'Username already exists') {
                        document.getElementById('error-message').innerHTML = 
                            `<strong>Username already exists!</strong><br>
                             The username "${error.details.username}" is already taken.<br>
                             Please choose a different username.`;
                    }
                    else {
                        document.getElementById('error-message').innerHTML = 
                            `<strong>Registration Error:</strong><br>${error.error}`;
                    }
                    
                    // Display the error
                    document.getElementById('error-message').style.display = 'block';
                    
                    // Also show in bottom container
                    document.getElementById('status').textContent = error.error || "Registration failed";
                    document.querySelector('.bottom-container').style.backgroundColor = '#FF4C4C';
                    document.querySelector('.bottom-container').style.display = 'block';
                    
                    // Hide bottom container after delay
                    setTimeout(() => {
                        document.querySelector('.bottom-container').style.display = 'none';
                    }, 2500);
                } else {
                    document.getElementById('error-message').innerHTML = 
                        `<strong>Registration Error:</strong><br>
                         An unexpected error occurred. Please try again.`;
                    document.getElementById('error-message').style.display = 'block';
                    
                    document.getElementById('status').textContent = "Error sending image to server.";
                    document.querySelector('.bottom-container').style.display = 'block';
                    document.querySelector('.bottom-container').style.backgroundColor = '#FF4C4C';
                    
                    setTimeout(() => {
                        document.querySelector('.bottom-container').style.display = 'none';
                    }, 2500);
                }
            });
        }

        // Initialize webcam on page load
        initWebcam();

        // Capture image when button is clicked
        captureButton.addEventListener('click', captureImage);
    </script>
</body>
</html>