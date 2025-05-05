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
        
        /* Left section - QR Code */
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
        
        /* QR Code container styling */
        #qr-code-container {
            width: 300px;
            height: 300px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
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
        
        #register-button {
            background: linear-gradient(135deg, #36D1DC, #5B86E5);
            color: white;
        }
        
        #download-qr-button {
            background: linear-gradient(135deg, #FF5E62, #FF9966);
            color: white;
            display: none;
        }
        
        #login-link {
            margin-top: 20px;
            color: #3a7bd5;
            text-align: center;
            text-decoration: none;
            font-weight: 500;
        }
        
        #login-link:hover {
            text-decoration: underline;
        }
        
        /* Success message styling */
        .success-message {
            background-color: rgba(46, 213, 115, 0.1);
            color: #2ed573;
            border-left: 4px solid #2ed573;
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

        /* QR Code buttons container */
        .qr-buttons {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container-all">
        <!-- Registration container -->
        <div class="registration-content">
            <!-- Left section - QR Code Display -->
            <div class="left-container">
                <div class="logo-container">
                    <img src="Superpack-Enterprise-Logo.png" alt="SuperPack Enterprise Logo" class="logo">
                </div>
                <div id="qr-code-container">
                    <img id="qr-placeholder" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDIwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjIwMCIgaGVpZ2h0PSIyMDAiIGZpbGw9IiNGNUY1RjUiLz48cGF0aCBkPSJNODAgODBIMTIwVjEyMEg4MFY4MFoiIGZpbGw9IiNDQ0NDQ0MiLz48cGF0aCBkPSJNNDAgNDBINjBWNjBINDBWNDBaIiBmaWxsPSIjQ0NDQ0NDIi8+PHBhdGggZD0iTTE0MCA0MEgxNjBWNjBIMTQwVjQwWiIgZmlsbD0iI0NDQ0NDQyIvPjxwYXRoIGQ9Ik00MCAxNDBINjBWMTYwSDQwVjE0MFoiIGZpbGw9IiNDQ0NDQ0MiLz48L3N2Zz4=" alt="QR Code will appear here" style="width: 100%; height: 100%;">
                    <img id="qr-code" style="display: none; width: 100%; height: 100%;">
                </div>
                <div class="qr-buttons">
                    <button id="download-qr-button" onclick="downloadQRCode()">
                        <i class="fas fa-download"></i> Download QR Code
                    </button>
                </div>
            </div>
            
            <!-- Right section - Registration Form -->
            <div class="right-container">
                <h2>Create Your Account</h2>
                <div class="success-message" id="success-message">
                    Registration successful! You can now log in with your QR code.
                </div>
                
                <form id="register-form">
                    <input type="text" name="name" id="name" placeholder="Full Name" required>
                    <div class="validation-error" id="name-error">Please enter your full name</div>
                    
                    <input type="text" name="employee_id" id="employee_id" placeholder="Employee ID" required>
                    <div class="validation-error" id="employee-id-error">Please enter a valid Employee ID</div>
                    
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <div class="validation-error" id="password-error">Password must be at least 8 characters</div>
                    
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                    <div class="validation-error" id="confirm-password-error">Passwords do not match</div>
                    
                    <select name="department" id="department" required>
                        <option value="" disabled selected>Select Department</option>
                        <option value="HR">HR</option>
                        <option value="IT">IT</option>
                        <option value="Finance">Finance</option>
                        <option value="Sales">Sales</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Operations">Operations</option>
                        <option value="Admin">Admin</option>
                    </select>
                    <div class="validation-error" id="department-error">Please select your department</div>
                    
                    <select name="role" id="role" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="Employee">Employee</option>
                        <option value="Manager">Manager</option>
                        <option value="Director">Director</option>
                        <option value="Admin">Admin</option>
                    </select>
                    <div class="validation-error" id="role-error">Please select your role</div>
                    
                    <button type="submit" id="register-button">
                        <i class="fas fa-user-plus"></i> Register
                    </button>
                </form>
                
                <a href="login.php" id="login-link">Already have an account? Login</a>
            </div>
        </div>
    </div>

    <!-- Include QR Code JS library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <script>
        // Initialize form validation
        const form = document.getElementById('register-form');
        const nameInput = document.getElementById('name');
        const employeeIdInput = document.getElementById('employee_id');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const departmentSelect = document.getElementById('department');
        const roleSelect = document.getElementById('role');
        const qrCodeContainer = document.getElementById('qr-code-container');
        const qrPlaceholder = document.getElementById('qr-placeholder');
        const qrCode = document.getElementById('qr-code');
        const downloadQrButton = document.getElementById('download-qr-button');
        const successMessage = document.getElementById('success-message');
        
        // Form validation
        function validateForm() {
            let isValid = true;
            
            // Name validation
            if (nameInput.value.trim() === '') {
                document.getElementById('name-error').style.display = 'block';
                nameInput.classList.add('input-error');
                isValid = false;
            } else {
                document.getElementById('name-error').style.display = 'none';
                nameInput.classList.remove('input-error');
            }
            
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
            if (passwordInput.value.length < 8) {
                document.getElementById('password-error').style.display = 'block';
                passwordInput.classList.add('input-error');
                isValid = false;
            } else {
                document.getElementById('password-error').style.display = 'none';
                passwordInput.classList.remove('input-error');
            }
            
            // Confirm password validation
            if (passwordInput.value !== confirmPasswordInput.value) {
                document.getElementById('confirm-password-error').style.display = 'block';
                confirmPasswordInput.classList.add('input-error');
                isValid = false;
            } else {
                document.getElementById('confirm-password-error').style.display = 'none';
                confirmPasswordInput.classList.remove('input-error');
            }
            
            // Department validation
            if (departmentSelect.value === '') {
                document.getElementById('department-error').style.display = 'block';
                departmentSelect.classList.add('input-error');
                isValid = false;
            } else {
                document.getElementById('department-error').style.display = 'none';
                departmentSelect.classList.remove('input-error');
            }
            
            // Role validation
            if (roleSelect.value === '') {
                document.getElementById('role-error').style.display = 'block';
                roleSelect.classList.add('input-error');
                isValid = false;
            } else {
                document.getElementById('role-error').style.display = 'none';
                roleSelect.classList.remove('input-error');
            }
            
            return isValid;
        }
        
        // Form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                // Prepare form data for AJAX submission
                const formData = new FormData(form);
                
                // AJAX request to register.php
                fetch('register_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        successMessage.style.display = 'block';
                        
                        // Hide placeholder and generate QR code
                        qrPlaceholder.style.display = 'none';
                        qrCode.style.display = 'block';
                        
                        // Generate QR code with employee ID
                        generateQRCode(data.qr_data);
                        
                        // Show download button
                        downloadQrButton.style.display = 'block';
                        
                        // Disable form fields
                        const formElements = form.elements;
                        for (let i = 0; i < formElements.length; i++) {
                            formElements[i].disabled = true;
                        }
                    } else {
                        // Handle registration error
                        alert(data.message || 'Registration failed. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again later.');
                });
            }
        });
        
        // Generate QR code
        function generateQRCode(data) {
            // Clear previous QR code if any
            qrCodeContainer.innerHTML = '';
            
            try {
                // Create new QR code with optimized settings for smaller data
                new QRCode(qrCodeContainer, {
                    text: data,
                    width: 256,
                    height: 256,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.L, // Use lower error correction for smaller QR code
                    quietZone: 10 // Add quiet zone for better scanning
                });
                
                // Store QR data for download
                qrCodeContainer.setAttribute('data-qr', data);
                
                // Get the generated QR code image
                setTimeout(() => {
                    const qrImg = qrCodeContainer.querySelector('img');
                    if (qrImg) {
                        qrCode.src = qrImg.src;
                    } else {
                        console.error('QR code image not found');
                    }
                }, 200);
            } catch (error) {
                console.error('Error generating QR code:', error);
                alert('There was an error generating your QR code. Please try again or contact support.');
                
                // Display error message in QR container
                qrCodeContainer.innerHTML = `
                    <div style="text-align: center; padding: 20px; color: #ff5e62;">
                        <i class="fas fa-exclamation-circle" style="font-size: 48px;"></i>
                        <p>QR Code generation failed</p>
                        <p style="font-size: 14px;">Error: ${error.message}</p>
                    </div>
                `;
            }
        }
        
        // Download QR code function
        function downloadQRCode() {
            const qrImage = qrCodeContainer.querySelector('img');
            if (qrImage) {
                const a = document.createElement('a');
                a.href = qrImage.src;
                a.download = 'SuperPack-QRCode-' + employeeIdInput.value + '.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        }
    </script>
</body>
</html>