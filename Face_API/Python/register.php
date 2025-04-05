<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperPack Enterprise - Register</title>
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
        
        /* Registration content container */
        .registration-content {
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
            .registration-content {
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
        
        /* Webcam styling */
        video {
            width: 100%;
            max-width: 300px;
            height: 300px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        /* Form input styling */
        input, select {
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
        
        input:focus, select:focus {
            border-bottom-color: #3a7bd5;
        }
        
        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 40px;
        }
        
        input::placeholder, select::placeholder {
            color: #999;
        }
        
        /* Input validation styling */
        .validation-error {
            color: #FF5E62;
            font-size: 13px;
            margin-top: -10px;
            margin-bottom: 15px;
            font-weight: 500;
            display: none;
        }
        
        .input-error {
            border-bottom: 2px solid #FF5E62 !important;
        }
        
        /* Button styling */
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
        
        /* Error display */
        .error-container {
            background-color: rgba(255, 94, 98, 0.1);
            border-left: 4px solid #FF5E62;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
            color: #d63031;
            font-size: 14px;
            display: none;
        }
        
        /* Processing indicator */
        .processing-indicator {
            display: none;
            text-align: center;
            margin: 15px 0;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-left-color: #36D1DC;
            animation: spin 1s ease-in-out infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Toggle forms */
        #facial-recognition-container {
            display: block;
        }
        
        .login-container {
            display: none;
            flex-direction: column;
            width: 100%;
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
    </style>
</head>
<body>
    <?php include '../../Capstone2/header.php'; ?>

    <div class="container-all">
        <div class="registration-content">
            <!-- Left section with webcam -->
            <div class="left-container">
                <video id="webcam" autoplay></video>
                <!-- Canvas element to capture and draw image -->
                <canvas id="canvas" style="display:none;"></canvas>
            </div>
            
            <!-- Right section with forms -->
            <div class="right-container">
                <h2>Create Account</h2>
                <p>Join SuperPack Enterprise with facial recognition or traditional registration</p>
                
                <button id="toggle-login-method">Switch to Username/Password</button>
                
                <!-- Facial Recognition Registration Form -->
                <div id="facial-recognition-container">
                    <input type="text" id="username" placeholder="Create Username" required minlength="3">
                    <div id="username-error" class="validation-error">Username must be at least 3 characters</div>
                    
                    <input type="password" id="password" placeholder="Create Password" required minlength="6">
                    <div id="password-error" class="validation-error">Password must be at least 6 characters</div>
                    
                    <select id="role" required>
                        <option value="" disabled selected>Select Position</option>
                        <option value="Admin">Admin</option>
                        <option value="Employee">Employee</option>
                    </select>
                    <div id="role-error" class="validation-error">Please select a position</div>
                    
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
                    <div id="department-error" class="validation-error">Please select a department</div>
                    
                    <!-- Processing indicator -->
                    <div class="processing-indicator" id="processing-indicator">
                        <div class="spinner"></div>
                        <p>Processing registration...</p>
                    </div>
                    
                    <!-- Error message container -->
                    <div class="error-container" id="error-message"></div>
                    
                    <button id="capture-button">Submit</button>
                    <button id="back-button">Back to Home</button>
                </div>
                
                <!-- Traditional Registration Form -->
                <div id="traditional-login-container" class="login-container">
                    <form id="traditional-register-form" method="post" action="traditional_register.php" onsubmit="return validateTraditionalForm()">
                        <div class="form-group">
                            <input type="text" name="trad_username" id="trad_username" placeholder="Create Username" required minlength="3">
                            <div id="trad-username-error" class="validation-error">Username must be at least 3 characters</div>
                        </div>
                        <div class="form-group">
                            <input type="password" name="trad_password" id="trad_password" placeholder="Create Password" required minlength="6">
                            <div id="trad-password-error" class="validation-error">Password must be at least 6 characters</div>
                        </div>
                        <div class="form-group">
                            <select name="trad_role" id="trad_role" required>
                                <option value="" disabled selected>Select Position</option>
                                <option value="Admin">Admin</option>
                                <option value="Employee">Employee</option>
                            </select>
                            <div id="trad-role-error" class="validation-error">Please select a position</div>
                        </div>
                        <div class="form-group">
                            <select name="trad_department" id="trad_department" required>
                                <option value="" disabled selected>Select Department</option>
                                <option value="sales">Sales</option>
                                <option value="purchasing">Purchasing</option>
                                <option value="proddev">Product Development</option>
                                <option value="warehouse">Warehouse</option>
                                <option value="logistics">Logistics</option>
                                <option value="accounting">Accounting</option>
                                <option value="admin">Admin</option>
                            </select>
                            <div id="trad-department-error" class="validation-error">Please select a department</div>
                        </div>
                        <div class="form-group">
                            <button type="submit" id="traditional-register-btn" 
                                    style="background: linear-gradient(135deg, #36D1DC, #5B86E5); color: white;">
                                Register
                            </button>
                        </div>
                        <div class="form-group">
                            <button type="button" id="back-button-traditional"
                                   style="background: linear-gradient(135deg, #FF9966, #FF5E62); color: white;">
                                Back to Home
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="bottom-container">
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
                    document.querySelector('.bottom-container').style.backgroundColor = 'rgba(255, 76, 76, 0.9)';
                    
                    // Auto switch to traditional login if webcam fails
                    setTimeout(() => {
                        document.getElementById('toggle-login-method').click();
                    }, 1500);
                });
        }

        // Add input validation listeners
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const roleSelect = document.getElementById('role');
        const departmentSelect = document.getElementById('department');
        
        // Real-time validation for facial recognition form
        usernameInput.addEventListener('input', function() {
            validateInput(this, 'username-error', this.value.length >= 3);
        });
        
        passwordInput.addEventListener('input', function() {
            validateInput(this, 'password-error', this.value.length >= 6);
        });
        
        roleSelect.addEventListener('change', function() {
            validateInput(this, 'role-error', this.value !== '');
        });
        
        departmentSelect.addEventListener('change', function() {
            validateInput(this, 'department-error', this.value !== '');
        });
        
        // Real-time validation for traditional form
        document.getElementById('trad_username').addEventListener('input', function() {
            validateInput(this, 'trad-username-error', this.value.length >= 3);
        });
        
        document.getElementById('trad_password').addEventListener('input', function() {
            validateInput(this, 'trad-password-error', this.value.length >= 6);
        });
        
        document.getElementById('trad_role').addEventListener('change', function() {
            validateInput(this, 'trad-role-error', this.value !== '');
        });
        
        document.getElementById('trad_department').addEventListener('change', function() {
            validateInput(this, 'trad-department-error', this.value !== '');
        });
        
        // Helper function for validation
        function validateInput(inputElement, errorId, isValid) {
            const errorElement = document.getElementById(errorId);
            
            if (!isValid) {
                inputElement.classList.add('input-error');
                errorElement.style.display = 'block';
                return false;
            } else {
                inputElement.classList.remove('input-error');
                errorElement.style.display = 'none';
                return true;
            }
        }
        
        // Validate the entire facial recognition form
        function validateFacialRecognitionForm() {
            const isUsernameValid = validateInput(usernameInput, 'username-error', usernameInput.value.length >= 3);
            const isPasswordValid = validateInput(passwordInput, 'password-error', passwordInput.value.length >= 6);
            const isRoleValid = validateInput(roleSelect, 'role-error', roleSelect.value !== '');
            const isDepartmentValid = validateInput(departmentSelect, 'department-error', departmentSelect.value !== '');
            
            // Check if face is visible in webcam
            const faceVisible = true; // This would need actual face detection logic
            
            return isUsernameValid && isPasswordValid && isRoleValid && isDepartmentValid && faceVisible;
        }
        
        // Validate the traditional form
        function validateTraditionalForm() {
            const tradUsername = document.getElementById('trad_username');
            const tradPassword = document.getElementById('trad_password');
            const tradRole = document.getElementById('trad_role');
            const tradDepartment = document.getElementById('trad_department');
            
            const isUsernameValid = validateInput(tradUsername, 'trad-username-error', tradUsername.value.length >= 3);
            const isPasswordValid = validateInput(tradPassword, 'trad-password-error', tradPassword.value.length >= 6);
            const isRoleValid = validateInput(tradRole, 'trad-role-error', tradRole.value !== '');
            const isDepartmentValid = validateInput(tradDepartment, 'trad-department-error', tradDepartment.value !== '');
            
            return isUsernameValid && isPasswordValid && isRoleValid && isDepartmentValid;
        }

        // Capture image function
        function captureImage() {
            // First validate the form
            if (!validateFacialRecognitionForm()) {
                // Show validation errors
                document.getElementById('status').textContent = "Please fix the form errors before submitting";
                document.querySelector('.bottom-container').style.backgroundColor = 'rgba(255, 76, 76, 0.9)';
                document.querySelector('.bottom-container').style.display = 'block';
                
                setTimeout(() => {
                    document.querySelector('.bottom-container').style.display = 'none';
                }, 3000);
                
                return; // Stop execution if validation fails
            }
            
            // Show processing indicator
            document.getElementById('processing-indicator').style.display = 'block';
            
            // Disable submit button while processing
            captureButton.disabled = true;
            captureButton.style.opacity = '0.7';
            
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
                // Hide processing indicator
                document.getElementById('processing-indicator').style.display = 'none';
                
                // Re-enable submit button
                captureButton.disabled = false;
                captureButton.style.opacity = '1';
                
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
                    document.querySelector('.bottom-container').style.backgroundColor = 'rgba(100, 166, 81, 0.9)';
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
                
                // Re-enable submit button in case of error
                captureButton.disabled = false;
                captureButton.style.opacity = '1';
                
                // Clear any previous error messages
                document.getElementById('error-message').innerHTML = '';
                
                // Handle specific error types
                if (error && error.error) {
                    if (error.error === 'User already registered') {
                        document.getElementById('error-message').innerHTML = 
                            `<strong>User already registered!</strong><br>
                             The username "${error.details.name}" is already taken.<br>
                             Please choose a different username.`;
                             
                        // Also highlight the username field
                        validateInput(usernameInput, 'username-error', false);
                        document.getElementById('username-error').textContent = 'Username already taken';
                        document.getElementById('username-error').style.display = 'block';
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
                             
                        // Also highlight the username field
                        validateInput(usernameInput, 'username-error', false);
                        document.getElementById('username-error').textContent = 'Username already taken';
                        document.getElementById('username-error').style.display = 'block';
                    }
                    else if (error.error === 'No face detected in the image') {
                        document.getElementById('error-message').innerHTML = 
                            `<strong>No face detected!</strong><br>
                             Please make sure your face is clearly visible in the camera<br>
                             and try again.`;
                    }
                    else {
                        document.getElementById('error-message').innerHTML = 
                            `<strong>Registration Error:</strong><br>${error.error}`;
                    }
                    
                    // Display the error
                    document.getElementById('error-message').style.display = 'block';
                    
                    // Also show in bottom container
                    document.getElementById('status').textContent = error.error || "Registration failed";
                    document.querySelector('.bottom-container').style.backgroundColor = 'rgba(255, 76, 76, 0.9)';
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
                    document.querySelector('.bottom-container').style.backgroundColor = 'rgba(255, 76, 76, 0.9)';
                    
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