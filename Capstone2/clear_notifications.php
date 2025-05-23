<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get current user info
$current_user_role = $_SESSION['role'];
$current_user_id = isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : 
                  (isset($_SESSION['username']) ? $_SESSION['username'] : '');

// Database connection
$host = "localhost";
$user = "root";
$password = "password";
$database = "superpack_database";
$port = 3306;

// mysqli connection
$conn = new mysqli($host, $user, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Delete notifications based on user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($current_user_role === 'Admin') {
        // Admin clears admin notifications only
        $query = "DELETE FROM notifications WHERE user_role = 'Admin'";
        $stmt = $conn->prepare($query);
    } else {
        // Employees clear their specific notifications only
        $query = "DELETE FROM notifications WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $current_user_id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'All notifications cleared']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to clear notifications: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?> 