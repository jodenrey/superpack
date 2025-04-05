<?php session_start();?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperPack Enterprise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
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
        height: 100vh;
        width: 100vw;
        background: linear-gradient(135deg, #3a7bd5, #00d2ff);
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        overflow: hidden;
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
    
    /* Welcome content wrapper */
    .welcome-content {
        position: relative;
        z-index: 2;
        background-color: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        width: 90%;
        max-width: 1000px;
        padding: 40px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    /* Company logo and name */
    .company-branding {
        margin-bottom: 30px;
    }
    
    .company-logo {
        font-size: 50px;
        color: #3a7bd5;
        margin-bottom: 15px;
    }
    
    .company-name {
        font-size: 32px;
        font-weight: 700;
        color: #333;
        margin-bottom: 10px;
    }
    
    .company-tagline {
        font-size: 16px;
        color: #666;
        margin-bottom: 30px;
    }
    
    /* Button container */
    .action-buttons {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 30px;
        width: 100%;
    }
    
    /* Button styles */
    .action-button {
        flex: 1;
        min-width: 250px;
        max-width: 300px;
        padding: 30px;
        border-radius: 15px;
        border: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    
    .action-button::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.2));
        z-index: 1;
    }
    
    .login-btn {
        background: linear-gradient(135deg, #FF9966, #FF5E62);
        color: white;
    }
    
    .register-btn {
        background: linear-gradient(135deg, #36D1DC, #5B86E5);
        color: white;
    }
    
    .action-button:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
    }
    
    .action-button:active {
        transform: translateY(0);
    }
    
    .button-icon {
        font-size: 48px;
        margin-bottom: 15px;
        position: relative;
        z-index: 2;
    }
    
    .button-text {
        font-size: 20px;
        font-weight: 600;
        position: relative;
        z-index: 2;
    }
    
    /* Divider is now visual spacing via gap property */
    
    /* Footer section */
    .welcome-footer {
        margin-top: 40px;
        font-size: 14px;
        color: #666;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .welcome-content {
            padding: 30px 20px;
        }
        
        .action-buttons {
            flex-direction: column;
            align-items: center;
        }
        
        .action-button {
            width: 100%;
        }
    }
</style>
<body>
    <?php include 'Capstone2/header.php'?>
    <div class="container-all">
        <div class="welcome-content">
            <div class="company-branding">
                <div class="company-logo">
                    <i class="fas fa-box-open"></i>
                </div>
                <h1 class="company-name">SuperPack Enterprise</h1>
                <p class="company-tagline">Streamline your workflow with our comprehensive management system</p>
            </div>
            
            <div class="action-buttons">
                <button class="action-button login-btn">
                    <span class="button-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </span>
                    <span class="button-text">Login</span>
                </button>
                
                <button class="action-button register-btn">
                    <span class="button-icon">
                        <i class="fas fa-user-plus"></i>
                    </span>
                    <span class="button-text">Register</span>
                </button>
            </div>
            
            <div class="welcome-footer">
                <p>&copy; 2025 SuperPack Enterprise. All rights reserved.</p>
            </div>
        </div>
    </div>
    
    <script>
        document.querySelector('.login-btn').addEventListener('click', function() {
            const button = this;
            button.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                button.style.transform = 'scale(1)';
                setTimeout(() => {
                    window.location.href = 'Face_API/Python/login.php';
                }, 200);
            }, 200);
        });
        
        document.querySelector('.register-btn').addEventListener('click', function() {
            const button = this;
            button.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                button.style.transform = 'scale(1)';
                setTimeout(() => {
                    window.location.href = 'Face_API/Python/register.php';
                }, 200);
            }, 200);
        });
    </script>
</body>
</html>