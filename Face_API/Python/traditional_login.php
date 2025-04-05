<?php
session_start();

// Connect to the database
$host = "localhost";
$user = "root";
$password = "password";
$database = "superpack_database";

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$username = $_POST['username'];
$password = $_POST['password'];

// Prepare SQL statement to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // User found
    $user = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Password is correct, set session variables
        $_SESSION['username'] = $user['username']; 
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_department'] = $user['department'];
        $_SESSION['loggedin'] = true;
        
        // Record attendance
        $stmt = $conn->prepare("INSERT INTO attendance (name, role, time_in) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $user['username'], $user['role']);
        $stmt->execute();
        
        // Redirect to dashboard
        header("Location: /superpack/Capstone2/dashboardnew.php");
        exit;
    } else {
        // Password is incorrect - using modern alert style
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Login Error</title>
            <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
            <style>
                body {
                    font-family: 'Poppins', sans-serif;
                    background: linear-gradient(135deg, #3a7bd5, #00d2ff);
                    margin: 0;
                    padding: 0;
                    height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
                .alert-container {
                    background-color: white;
                    border-radius: 10px;
                    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                    padding: 30px;
                    text-align: center;
                    max-width: 500px;
                    width: 90%;
                }
                h2 {
                    color: #e74c3c;
                    margin-top: 0;
                }
                p {
                    color: #333;
                    margin-bottom: 25px;
                }
                .btn {
                    display: inline-block;
                    padding: 12px 24px;
                    background: linear-gradient(135deg, #3a7bd5, #00d2ff);
                    color: white;
                    border-radius: 8px;
                    text-decoration: none;
                    font-weight: 500;
                    transition: all 0.3s ease;
                }
                .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                }
            </style>
        </head>
        <body>
            <div class='alert-container'>
                <h2>Login Failed</h2>
                <p>Incorrect password. Please try again with the correct password.</p>
                <a href='login.php' class='btn'>Back to Login</a>
            </div>
            <script>
                // Auto redirect after 3 seconds
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 3000);
            </script>
        </body>
        </html>";
    }
} else {
    // User not found - using modern alert style
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Login Error</title>
        <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background: linear-gradient(135deg, #3a7bd5, #00d2ff);
                margin: 0;
                padding: 0;
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .alert-container {
                background-color: white;
                border-radius: 10px;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                padding: 30px;
                text-align: center;
                max-width: 500px;
                width: 90%;
            }
            h2 {
                color: #e74c3c;
                margin-top: 0;
            }
            p {
                color: #333;
                margin-bottom: 25px;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                margin: 0 10px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }
            .btn-primary {
                background: linear-gradient(135deg, #3a7bd5, #00d2ff);
                color: white;
            }
            .btn-secondary {
                background: linear-gradient(135deg, #36D1DC, #5B86E5);
                color: white;
            }
        </style>
    </head>
    <body>
        <div class='alert-container'>
            <h2>User Not Found</h2>
            <p>The username you entered does not exist in our system. Please check your username or register for a new account.</p>
            <a href='login.php' class='btn btn-primary'>Back to Login</a>
            <a href='register.php' class='btn btn-secondary'>Register</a>
        </div>
        <script>
            // Auto redirect after 5 seconds
            setTimeout(function() {
                window.location.href = 'login.php';
            }, 5000);
        </script>
    </body>
    </html>";
}

$stmt->close();
$conn->close();
?>