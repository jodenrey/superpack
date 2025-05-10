<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<style>
    :root {
        --primary: #4361ee;
        --primary-dark: #3a56d4;
        --secondary: #7209b7;
        --white: #ffffff;
        --gray-100: #f8f9fa;
        --gray-200: #e9ecef;
        --gray-800: #343a40;
        --header-height: 70px;
        --shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        --transition: all 0.3s ease;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', sans-serif;
    }

    .header {
        position: relative;
        width: 100%;
        height: var(--header-height);
        background: linear-gradient(to right, var(--primary), var(--secondary));
        box-shadow: var(--shadow);
        z-index: 10;
    }

    .header-container {
        max-width: 1200px;
        height: 100%;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo-container {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .logo-image {
        height: 40px;
        width: 40px;
        border-radius: 50%;
        object-fit: contain;
    }
    
    .logo-text {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--white);
        letter-spacing: 0.5px;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .header-icon {
        color: var(--white);
        font-size: 1.25rem;
        cursor: pointer;
        transition: var(--transition);
    }

    .header-icon:hover {
        transform: translateY(-2px);
        color: var(--gray-200);
    }

    .header-button {
        background-color: rgba(255, 255, 255, 0.15);
        color: var(--white);
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .header-button:hover {
        background-color: rgba(255, 255, 255, 0.25);
    }

    @media (max-width: 768px) {
        .header-container {
            padding: 0 15px;
        }
        
        .logo-text {
            font-size: 1.25rem;
        }
        
        .logo-image {
            height: 35px;
            width: 35px;
        }
    }
</style>
<body>
    <div class="header">
        <div class="header-container">
            <div class="logo-container">
                <img src="../Superpack-Enterprise-Logo.png" alt="Superpack Enterprise Logo" class="logo-image">
                <span class="logo-text">Superpack</span>
            </div>
            
            <div class="header-actions">
                <a href="#" class="header-icon"><i class="fas fa-question-circle"></i></a>
                <a href="#" class="header-icon"><i class="fas fa-bell"></i></a>
                <button class="header-button">
                    <i class="fas fa-globe"></i>
                    <span>English</span>
                </button>
            </div>
        </div>
    </div>
</body>
</html>