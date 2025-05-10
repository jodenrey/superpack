<?php session_start();?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperPack Enterprise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<style>
    /* Modern CSS Reset and Base Styles */
    *, *::before, *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    
    :root {
        --primary: #4361ee;
        --primary-dark: #3a56d4;
        --secondary: #7209b7;
        --accent: #4cc9f0;
        --success: #2ec4b6;
        --warning: #ff9f1c;
        --danger: #e71d36;
        --gray-100: #f8f9fa;
        --gray-200: #e9ecef;
        --gray-300: #dee2e6;
        --gray-400: #ced4da;
        --gray-500: #adb5bd;
        --gray-600: #6c757d;
        --gray-700: #495057;
        --gray-800: #343a40;
        --gray-900: #212529;
        --transition: all 0.3s ease;
        --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
        --shadow: 0 4px 6px rgba(0,0,0,0.1);
        --shadow-md: 0 6px 12px rgba(67, 97, 238, 0.15);
        --shadow-lg: 0 15px 25px rgba(67, 97, 238, 0.2);
        --radius-sm: 4px;
        --radius: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;
    }
    
    html, body {
        height: 100%;
        width: 100%;
        font-family: 'Inter', sans-serif;
        -webkit-font-smoothing: antialiased;
        background-color: var(--gray-100);
        color: var(--gray-800);
        line-height: 1.5;
    }
    
    /* Main layout */
    .app-container {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .main-content {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        position: relative;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        flex: 1;
    }
    
    /* Welcome section */
    .welcome-card {
        width: 95%;
        max-width: 1200px;
        background-color: white;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        overflow: hidden;
        display: grid;
        grid-template-columns: 1fr 1fr;
    }
    
    /* Left side - visual */
    .welcome-visual {
        background: linear-gradient(145deg, var(--primary), var(--secondary));
        padding: 3rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }
    
    .welcome-visual::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('data:image/svg+xml;utf8,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="25" fill="rgba(255,255,255,0.1)"/></svg>');
        background-size: 150px 150px;
        opacity: 0.5;
    }
    
    .company-logo {
        font-size: 4rem;
        color: white;
        margin-bottom: 1rem;
        position: relative;
        z-index: 2;
    }
    
    .company-name {
        font-size: 2.5rem;
        font-weight: 700;
        color: white;
        margin-bottom: 1rem;
        position: relative;
        z-index: 2;
    }
    
    .company-tagline {
        font-size: 1.125rem;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 2rem;
        position: relative;
        z-index: 2;
        max-width: 400px;
    }
    
    .welcome-features {
        position: relative;
        z-index: 2;
        margin-top: 1rem;
    }
    
    .feature-item {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        color: white;
    }
    
    .feature-icon {
        margin-right: 0.75rem;
        background-color: rgba(255, 255, 255, 0.2);
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Right side - actions */
    .welcome-actions {
        padding: 3rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .actions-title {
        font-size: 1.75rem;
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: 1.5rem;
    }
    
    .actions-subtitle {
        font-size: 1rem;
        color: var(--gray-600);
        margin-bottom: 2.5rem;
    }
    
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    
    .action-button {
        padding: 1.25rem;
        border-radius: var(--radius-md);
        border: none;
        display: flex;
        align-items: center;
        cursor: pointer;
        transition: var(--transition);
        font-size: 1rem;
        font-weight: 500;
        justify-content: flex-start;
        position: relative;
    }
    
    .action-button::after {
        content: "\f054";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        position: absolute;
        right: 1.25rem;
        opacity: 0;
        transition: var(--transition);
    }
    
    .action-button:hover::after {
        opacity: 1;
        transform: translateX(4px);
    }
    
    .login-btn {
        background-color: var(--primary);
        color: white;
        box-shadow: var(--shadow-md);
    }
    
    .login-btn:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }
    
    .register-btn {
        background-color: white;
        color: var(--gray-800);
        border: 1px solid var(--gray-300);
        box-shadow: var(--shadow-sm);
    }
    
    .register-btn:hover {
        background-color: var(--gray-100);
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }
    
    .recruitment-btn {
        background-color: var(--success);
        color: white;
        box-shadow: var(--shadow-md);
    }
    
    .recruitment-btn:hover {
        background-color: #25b5a7;
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }
    
    .button-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: var(--radius);
        margin-right: 1rem;
        background-color: rgba(255, 255, 255, 0.2);
        font-size: 1.125rem;
    }
    
    .register-btn .button-icon {
        background-color: rgba(73, 80, 87, 0.1);
    }
    
    /* Footer section */
    .welcome-footer {
        margin-top: 2.5rem;
        font-size: 0.875rem;
        color: var(--gray-500);
        text-align: center;
    }
    
    /* Responsive design */
    @media (max-width: 992px) {
        .welcome-card {
            grid-template-columns: 1fr;
        }
        
        .welcome-visual {
            padding: 2rem;
        }
    }
    
    @media (max-width: 576px) {
        .welcome-card {
            width: 100%;
            border-radius: 0;
            box-shadow: none;
        }
        
        .main-content {
            padding: 0;
        }
        
        .welcome-visual, .welcome-actions {
            padding: 1.5rem;
        }
    }
</style>
<body>
    <div class="app-container">
        <main class="main-content">
            <div class="welcome-card">
                <div class="welcome-visual">
                    <div class="company-logo">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h1 class="company-name">SuperPack Enterprise</h1>
                    <p class="company-tagline">Streamline your workflow with our comprehensive management system</p>
                    
                    <div class="welcome-features">
                        <div class="feature-item">
                            <span class="feature-icon"><i class="fas fa-chart-line"></i></span>
                            <span>Advanced analytics & reporting</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon"><i class="fas fa-users"></i></span>
                            <span>Complete HR management</span>
                        </div>
                        <div class="feature-item">
                            <span class="feature-icon"><i class="fas fa-shield-alt"></i></span>
                            <span>Secure & reliable platform</span>
                        </div>
                    </div>
                </div>
                
                <div class="welcome-actions">
                    <h2 class="actions-title">Welcome to SuperPack</h2>
                    <p class="actions-subtitle">Access your account or apply for new opportunities</p>
                    
                    <div class="action-buttons">
                        <button class="action-button login-btn" id="login-btn">
                            <span class="button-icon">
                                <i class="fas fa-sign-in-alt"></i>
                            </span>
                            <span class="button-text">Sign in to your account</span>
                        </button>
                        
                        <button class="action-button register-btn" id="register-btn">
                            <span class="button-icon">
                                <i class="fas fa-user-plus"></i>
                            </span>
                            <span class="button-text">Create new account</span>
                        </button>
                        
                        <button class="action-button recruitment-btn" id="recruitment-btn">
                            <span class="button-icon">
                                <i class="fas fa-user-tie"></i>
                            </span>
                            <span class="button-text">Browse job opportunities</span>
                        </button>
                    </div>
                    
                    <div class="welcome-footer">
                        <p>&copy; 2025 SuperPack Enterprise. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        document.getElementById('login-btn').addEventListener('click', function() {
            const button = this;
            button.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                button.style.transform = 'scale(1)';
                setTimeout(() => {
                    window.location.href = 'Face_API/Python/login.php';
                }, 200);
            }, 200);
        });
        
        document.getElementById('register-btn').addEventListener('click', function() {
            const button = this;
            button.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                button.style.transform = 'scale(1)';
                setTimeout(() => {
                    window.location.href = 'Face_API/Python/register.php';
                }, 200);
            }, 200);
        });
        
        document.getElementById('recruitment-btn').addEventListener('click', function() {
            const button = this;
            button.style.transform = 'scale(0.95)';
            
            setTimeout(() => {
                button.style.transform = 'scale(1)';
                setTimeout(() => {
                    window.location.href = 'job_application.php';
                }, 200);
            }, 200);
        });
    </script>
</body>
</html>