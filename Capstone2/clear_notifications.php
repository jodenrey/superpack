<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

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

// Delete all notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = "DELETE FROM notifications";
    
    if ($conn->query($query)) {
        echo json_encode(['success' => true, 'message' => 'All notifications cleared']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to clear notifications: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?> 