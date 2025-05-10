<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get the current user's role and name
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'User';
$current_username = isset($_SESSION['name']) ? $_SESSION['name'] : 
                   (isset($_SESSION['username']) ? $_SESSION['username'] : '');

// Get the warning ID from the request
$warning_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($warning_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid warning ID']);
    exit;
}

// Database connection details
$host = "localhost";
$user = "root";
$password = "password";
$database = "superpack_database";
$port = 3306;

// Connect to database
$conn = new mysqli($host, $user, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Query to get warning details
$query = "SELECT * FROM warning_notices WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $warning_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Warning not found']);
    exit;
}

$warning = $result->fetch_assoc();

// Security check: Regular users can only view their own warnings
if ($role !== 'Admin' && strpos($warning['employee_name'], $current_username) === false) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to view this warning']);
    exit;
}

// Return the warning details
echo json_encode(['success' => true, 'warning' => $warning]);

// Close the database connection
$conn->close();
?> 