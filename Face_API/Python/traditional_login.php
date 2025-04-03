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
        $_SESSION['username'] = $user['username']; // Changed from name to username
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_department'] = $user['department'];
        $_SESSION['loggedin'] = true;
        
        // Record attendance
        $stmt = $conn->prepare("INSERT INTO attendance (name, role, time_in) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $user['username'], $user['role']); // Changed from name to username
        $stmt->execute();
        
        // Redirect to dashboard
        header("Location: /superpack/Capstone2/dashboardnew.php");
        exit;
    } else {
        // Password is incorrect
        echo "<script>
            alert('Incorrect password. Please try again.');
            window.location.href = 'login.php';
        </script>";
    }
} else {
    // User not found
    echo "<script>
        alert('User not found. Please check your username or register.');
        window.location.href = 'login.php';
    </script>";
}

$stmt->close();
$conn->close();
?>