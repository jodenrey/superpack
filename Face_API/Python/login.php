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
        
        /* Left section - QR scanner */
        .left-container {
            flex: 1;
            display: flex;
            flex-direction: column;
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
        
        /* QR scanner container styling */
        .qr-scanner-container {
            position: relative;
            width: 100%;
            max-width: 360px;
            height: 360px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            background-color: #000;
        }
        
        #qr-video {
            width: 100%;
            height: 100%;
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
        
        .scan-indicator {
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
        
        #login-button {
            background: linear-gradient(135deg, #36D1DC, #5B86E5);
            color: white;
        }
        
        #toggle-login-method {
            background: linear-gradient(135deg, #FF9966, #FF5E62);
            color: white;
        }
        
        #register-link {
            margin-top: 20px;
            color: #3a7bd5;
            text-align: center;
            text-decoration: none;
            font-weight: 500;
        }
        
        #register-link:hover {
            text-decoration: underline;
        }
        
        /* Login methods toggle */
        .login-method {
            display: none;
        }
        
        .login-method.active {
            display: block;
        }
        
        /* Error message styling */
        .error-message {
            background-color: rgba(255, 94, 98, 0.1);
            color: #ff5e62;
            border-left: 4px solid #ff5e62;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
            display: none;
        }
        
        /* Logo styling */
        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .logo {
            height: 80px;
        }
        
        /* Validation error styling */
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
    </style>
</head>

<body>
    <div class="container-all">
        <div class="login-content">
            <!-- Left section with QR scanner -->
            <div class="left-container">
                <div class="logo-container">
                    <img src="Superpack-Enterprise-Logo.png" alt="SuperPack Enterprise Logo" class="logo">
                </div>
                <div class="qr-scanner-container">
                    <video id="qr-video" autoplay playsinline></video>
                    <div id="scanning-overlay"></div>
                    <div class="scan-indicator">
                        <i class="fas fa-qrcode"></i> Scanning for QR Code
                    </div>
                </div>
                <p style="text-align: center; margin-top: 20px; color: #666;">
                    <i class="fas fa-info-circle"></i> Point your QR code at the camera to login
                </p>
            </div>

            <!-- Right section with login forms -->
            <div class="right-container">
                <h2>Welcome Back</h2>
                <div class="error-message" id="error-message"></div>
                
                <button id="toggle-login-method">
                    <i class="fas fa-keyboard"></i> Use Manual Login
                </button>
                
                <!-- QR Code Login Instructions -->
                <div id="qr-login-method" class="login-method active">
                    <p>Scan your QR code using the camera on the left to login instantly.</p>
                    
                    <!-- New QR Code Upload feature -->
                    <div style="margin-top: 20px; margin-bottom: 20px; padding: 15px; border: 1px dashed #3a7bd5; border-radius: 10px; background-color: rgba(58, 123, 213, 0.05);">
                        <p style="font-weight: 500; margin-top: 0;"><i class="fas fa-upload"></i> Or upload your QR code:</p>
                        <input type="file" id="qr-upload" accept="image/*" style="display: none;">
                        <label for="qr-upload" style="display: block; cursor: pointer; background: linear-gradient(135deg, #36D1DC, #5B86E5); color: white; text-align: center; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                            <i class="fas fa-file-upload"></i> Choose QR Code Image
                        </label>
                        <div id="qr-upload-status" style="font-size: 14px; text-align: center; display: none;"></div>
                    </div>
                    
                    <p style="font-weight: 500; margin-top: 20px;">Don't have a QR code?</p>
                    <p>You can use manual login with your Employee ID and password by clicking the button above.</p>
                </div>
                
                <!-- Manual Login Form -->
                <form id="manual-login-form" class="login-method">
                    <input type="text" name="employee_id" id="employee_id" placeholder="Employee ID" required>
                    <div class="validation-error" id="employee-id-error">Please enter your Employee ID</div>
                    
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <div class="validation-error" id="password-error">Please enter your password</div>
                    
                    <button type="submit" id="login-button">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                
                <a href="register.php" id="register-link">Don't have an account? Register</a>
            </div>
        </div>
    </div>
    
    <!-- Include QR Code reader library -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <!-- Add jsQR library for processing QR code images -->
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    
    <script>
        // DOM elements
        const toggleLoginMethodBtn = document.getElementById('toggle-login-method');
        const qrLoginMethod = document.getElementById('qr-login-method');
        const manualLoginForm = document.getElementById('manual-login-form');
        const employeeIdInput = document.getElementById('employee_id');
        const passwordInput = document.getElementById('password');
        const errorMessage = document.getElementById('error-message');
        const qrVideoContainer = document.querySelector('.qr-scanner-container');
        const scanIndicator = document.querySelector('.scan-indicator');
        let html5QrCode = null;
        
        // Toggle between QR and manual login
        toggleLoginMethodBtn.addEventListener('click', function() {
            if (qrLoginMethod.classList.contains('active')) {
                // Switch to manual login
                qrLoginMethod.classList.remove('active');
                manualLoginForm.classList.add('active');
                toggleLoginMethodBtn.innerHTML = '<i class="fas fa-qrcode"></i> Use QR Code Login';
                
                // Stop scanning if active
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().catch(err => console.error("Error stopping scanner:", err));
                }
            } else {
                // Switch to QR login
                manualLoginForm.classList.remove('active');
                qrLoginMethod.classList.add('active');
                toggleLoginMethodBtn.innerHTML = '<i class="fas fa-keyboard"></i> Use Manual Login';
                
                // Restart QR scanner
                if (html5QrCode && !html5QrCode.isScanning) {
                    initializeQRScanner();
                }
            }
        });
        
        // Form validation
        function validateForm() {
            let isValid = true;
            
            // Employee ID validation
            if (employeeIdInput.value.trim() === '') {
                document.getElementById('employee-id-error').style.display = 'block';
                employeeIdInput.classList.add('input-error');
                isValid = false;
            } else {
                document.getElementById('employee-id-error').style.display = 'none';
                employeeIdInput.classList.remove('input-error');
            }
            
            // Password validation
            if (passwordInput.value.trim() === '') {
                document.getElementById('password-error').style.display = 'block';
                passwordInput.classList.add('input-error');
                isValid = false;
            } else {
                document.getElementById('password-error').style.display = 'none';
                passwordInput.classList.remove('input-error');
            }
            
            return isValid;
        }
        
        // Manual login form submission
        manualLoginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                // Prepare form data for AJAX submission
                const formData = new FormData(manualLoginForm);
                formData.append('login_method', 'manual');
                
                // AJAX request to login_process.php
                fetch('login_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Redirect to dashboard or home page
                        window.location.href = data.redirect || '../../welcome.php';
                    } else {
                        // Show error message
                        errorMessage.textContent = data.message || 'Login failed. Please check your credentials.';
                        errorMessage.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    errorMessage.textContent = 'An error occurred. Please try again later.';
                    errorMessage.style.display = 'block';
                });
            }
        });
        
        // Initialize QR Code Scanner with error handling
        function initializeQRScanner() {
            try {
                // Clear any previous instances
                if (html5QrCode) {
                    if (html5QrCode.isScanning) {
                        html5QrCode.stop().catch(err => console.error("Error stopping scanner:", err));
                    }
                    html5QrCode = null;
                }
                
                // Clear scanner container
                const scannerDiv = document.getElementById('qr-scanner');
                if (!scannerDiv) {
                    // Create a new div for the scanner
                    const newDiv = document.createElement('div');
                    newDiv.id = 'qr-scanner';
                    newDiv.style.width = '100%';
                    newDiv.style.height = '100%';
                    qrVideoContainer.appendChild(newDiv);
                }
                
                // Create a new instance
                html5QrCode = new Html5Qrcode("qr-scanner");
                
                // Start scanning
                html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    qrCodeSuccessCallback,
                    qrCodeErrorCallback
                ).catch(error => {
                    console.error("Error starting QR scanner:", error);
                    handleCameraError(error);
                });
            } catch (error) {
                console.error("Error initializing QR scanner:", error);
                handleCameraError(error);
            }
        }
        
        // Handle camera access errors
        function handleCameraError(error) {
            // Update UI to show camera error
            scanIndicator.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Camera unavailable';
            scanIndicator.style.backgroundColor = 'rgba(255, 94, 98, 0.8)';
            
            // Show error message and switch to manual login
            errorMessage.textContent = 'Camera access error: ' + (error.message || 'Device unavailable or in use by another application');
            errorMessage.style.display = 'block';
            
            // Automatically switch to manual login after a short delay
            setTimeout(() => {
                if (qrLoginMethod.classList.contains('active')) {
                    toggleLoginMethodBtn.click();
                }
            }, 1000);
        }
        
        // QR Code success callback
        function qrCodeSuccessCallback(decodedText) {
            // Stop scanning
            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    // Send QR code data to server for authentication
                    const formData = new FormData();
                    formData.append('qr_code_data', decodedText);
                    formData.append('login_method', 'qr');
                    
                    // Show processing message
                    errorMessage.textContent = 'Processing QR code...';
                    errorMessage.style.display = 'block';
                    errorMessage.style.backgroundColor = 'rgba(54, 209, 220, 0.1)';
                    errorMessage.style.color = '#36D1DC';
                    errorMessage.style.borderLeftColor = '#36D1DC';
                    
                    // AJAX request to login_process.php
                    fetch('login_process.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message before redirect
                            errorMessage.textContent = 'Login successful! Redirecting...';
                            errorMessage.style.backgroundColor = 'rgba(46, 213, 115, 0.1)';
                            errorMessage.style.color = '#2ed573';
                            errorMessage.style.borderLeftColor = '#2ed573';
                            
                            // Redirect to dashboard or home page after brief delay
                            setTimeout(() => {
                                window.location.href = data.redirect || '../../welcome.php';
                            }, 1000);
                        } else {
                            // Show error message
                            errorMessage.textContent = data.message || 'Invalid QR Code. Please try again.';
                            errorMessage.style.backgroundColor = 'rgba(255, 94, 98, 0.1)';
                            errorMessage.style.color = '#ff5e62';
                            errorMessage.style.borderLeftColor = '#ff5e62';
                            
                            // Restart scanning after a delay
                            setTimeout(initializeQRScanner, 2000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        errorMessage.textContent = 'An error occurred. Please try again later.';
                        errorMessage.style.backgroundColor = 'rgba(255, 94, 98, 0.1)';
                        errorMessage.style.color = '#ff5e62';
                        errorMessage.style.borderLeftColor = '#ff5e62';
                        errorMessage.style.display = 'block';
                        
                        // Restart scanning
                        setTimeout(initializeQRScanner, 2000);
                    });
                }).catch(err => {
                    console.error("Error stopping QR scanner:", err);
                    setTimeout(initializeQRScanner, 2000);
                });
            }
        }
        
        // QR Code error callback (errors are normal when no QR is detected)
        function qrCodeErrorCallback(error) {
            // We can ignore certain errors as they occur when no QR code is in view
            // Only log them for debugging purposes
            // console.log(error);
        }
        
        // Initialize QR scanner when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Modify the QR scanner container by removing the video element
            const videoElement = document.getElementById('qr-video');
            if (videoElement) {
                videoElement.parentNode.removeChild(videoElement);
            }
            
            // Create a div for the scanner
            const scannerDiv = document.createElement('div');
            scannerDiv.id = 'qr-scanner';
            scannerDiv.style.width = '100%';
            scannerDiv.style.height = '100%';
            qrVideoContainer.appendChild(scannerDiv);
            
            // Give a slight delay to ensure the DOM is fully loaded
            setTimeout(initializeQRScanner, 500);
            
            // QR Code Upload functionality
            const qrUploadInput = document.getElementById('qr-upload');
            const qrUploadStatus = document.getElementById('qr-upload-status');
            
            qrUploadInput.addEventListener('change', function(e) {
                if (!this.files || !this.files[0]) return;
                
                const file = this.files[0];
                
                // Update status
                qrUploadStatus.textContent = 'Processing QR code...';
                qrUploadStatus.style.display = 'block';
                
                // Create a FileReader to read the image
                const reader = new FileReader();
                
                reader.onload = function(event) {
                    // Create an image element to load the file
                    const img = new Image();
                    img.onload = function() {
                        try {
                            // Create a canvas to draw the image
                            const canvas = document.createElement('canvas');
                            const context = canvas.getContext('2d');
                            canvas.width = img.width;
                            canvas.height = img.height;
                            context.drawImage(img, 0, 0, img.width, img.height);
                            
                            // Use the QR code scanner to process the image
                            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                            
                            // Try to decode the QR code from the image
                            const code = jsQR(imageData.data, imageData.width, imageData.height);
                            
                            if (code) {
                                // QR code detected, send data for authentication
                                qrUploadStatus.textContent = 'QR code found! Authenticating...';
                                
                                // Prepare form data
                                const formData = new FormData();
                                formData.append('qr_code_data', code.data);
                                formData.append('login_method', 'qr');
                                
                                // Send to server
                                fetch('login_process.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        qrUploadStatus.textContent = 'Authentication successful! Redirecting...';
                                        qrUploadStatus.style.color = '#2ed573';
                                        
                                        // Redirect after a short delay
                                        setTimeout(() => {
                                            window.location.href = data.redirect || '../../welcome.php';
                                        }, 1000);
                                    } else {
                                        qrUploadStatus.textContent = data.message || 'Invalid QR code. Please try again.';
                                        qrUploadStatus.style.color = '#ff5e62';
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    qrUploadStatus.textContent = 'An error occurred. Please try again.';
                                    qrUploadStatus.style.color = '#ff5e62';
                                });
                            } else {
                                qrUploadStatus.textContent = 'No QR code found in image. Please try another image.';
                                qrUploadStatus.style.color = '#ff5e62';
                            }
                        } catch (error) {
                            console.error('Error processing image:', error);
                            qrUploadStatus.textContent = 'Error processing image. Please try again.';
                            qrUploadStatus.style.color = '#ff5e62';
                        }
                    };
                    
                    img.onerror = function() {
                        qrUploadStatus.textContent = 'Error loading image. Please try a different file.';
                        qrUploadStatus.style.color = '#ff5e62';
                    };
                    
                    // Set the image source to the FileReader result
                    img.src = event.target.result;
                };
                
                // Read the file as a data URL
                reader.readAsDataURL(file);
            });
        });
    </script>
</body>
</html>
