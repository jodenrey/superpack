<?php
session_start();
header('Content-Type: application/json');

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
    die(json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]));
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Warning ID is required']);
    exit;
}

$warning_id = intval($_GET['id']);

// Get warning details
$sql = "SELECT * FROM warning_notices WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $warning_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $warning = $result->fetch_assoc();
    echo json_encode(['success' => true, 'warning' => $warning]);
} else {
    echo json_encode(['success' => false, 'message' => 'Warning not found']);
}

$stmt->close();
$conn->close();
?> 