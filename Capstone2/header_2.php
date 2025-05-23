<?php
// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include user functions
require_once('user_functions.php');

// Get current username
$username = getCurrentUsername();

// Get department name from URL or set default
$department_name = isset($_GET['department']) ? $_GET['department'] : 
                  (isset($_SESSION['department']) ? $_SESSION['department'] : 'Superpack Enterprise');

// Get user's profile picture
$uploadDir = 'uploads/profile_pictures/';
$defaultImage = $uploadDir . 'default.png';
$userImage = $defaultImage;

// Database connection
$host = "localhost";
$user = "root";
$password = "password";
$database = "superpack_database";
$port = 3306;

// Connect to database
$header_conn = new mysqli($host, $user, $password, $database, $port);

// Get user's profile picture
$userImage = getUserProfilePicture($username, $header_conn);

// Close connection
$header_conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<style>
    .header {
        position: relative;
        width: 100%;
        height: 70px;
        background-color: var(--light);
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-bottom: 1px solid var(--gray);
    }

    .header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: 100%;
        padding: 0 25px;
    }
    
    .company-info {
        display: flex;
        align-items: center;
    }
    
    .company-title {
        font-size: 20px;
        font-weight: 600;
        color: var(--primary);
        margin-right: 20px;
    }

    .user_info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .current-time {
        font-size: 16px;
        font-weight: 500;
        color: var(--gray-dark);
        padding: 8px 15px;
        background-color: var(--gray-light);
        border-radius: 20px;
    }

    .user_logo {
        height: 40px;
        width: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .user_logo:hover {
        transform: scale(1.1);
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }

    .user_name_text {
        font-size: 16px;
        font-weight: 500;
        color: var(--dark);
    }
    
    .user-dropdown {
        position: relative;
        display: inline-block;
    }
    
    .user-dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: var(--light);
        min-width: 160px;
        box-shadow: var(--shadow);
        border-radius: var(--radius-sm);
        z-index: 1000;
        overflow: hidden;
    }
    
    .user-dropdown-content a {
        color: var(--dark);
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        transition: var(--transition);
        font-size: 14px;
    }
    
    .user-dropdown-content a:hover {
        background-color: var(--gray-light);
    }
    
    .user-dropdown:hover .user-dropdown-content {
        display: block;
    }
    
    .user-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 5px 10px;
        border-radius: 25px;
        transition: var(--transition);
    }
    
    .user-profile:hover {
        background-color: var(--gray-light);
    }
    
    @media screen and (max-width: 768px) {
        .company-title {
            display: none;
        }
        
        .current-time {
            font-size: 14px;
            padding: 5px 10px;
        }
        
        .user_name_text {
            display: none;
        }
    }
</style>
<body>
    <div class="header">
        <div class="header-bar">
            <div class="company-info">
                <div class="company-title"><?php echo htmlspecialchars($department_name); ?></div>
            </div>
            
            <div class="user_info">
                <div class="current-time" id="current-time"></div>
                
                <?php 
                // Include notifications for all logged-in users
                if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                    include 'notifications.php'; 
                }
                ?>
            
            <div class="user-dropdown">
                <div class="user-profile">
                    <span class="user_name_text"><?php echo htmlspecialchars($username); ?></span>
                    <img src="<?php echo htmlspecialchars($userImage) . '?v=' . time(); ?>" alt="User Logo" class="user_logo">
                </div>
                <div class="user-dropdown-content">
                    <a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Display current time
        function updateTime() {
            const clockElement = document.getElementById('current-time');
            const now = new Date();
            const options = { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true
            };
            clockElement.textContent = now.toLocaleTimeString('en-US', options);
        }
        
        // Update time every second
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>
</html>