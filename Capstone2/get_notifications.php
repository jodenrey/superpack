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

// Handle marking notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notificationId = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
    
    if ($notificationId) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $notificationId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    }
    exit;
}

// Get unread notifications by default
$unreadOnly = isset($_GET['unread']) && $_GET['unread'] === '1';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

$query = "SELECT * FROM notifications";
if ($unreadOnly) {
    $query .= " WHERE is_read = 0";
}
$query .= " ORDER BY created_at DESC LIMIT ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
$unreadCount = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
        if ($row['is_read'] == 0) {
            $unreadCount++;
        }
    }
}

// Get total unread count
$countQuery = "SELECT COUNT(*) as count FROM notifications WHERE is_read = 0";
$countResult = $conn->query($countQuery);
if ($countResult && $countResult->num_rows > 0) {
    $countRow = $countResult->fetch_assoc();
    $unreadCount = $countRow['count'];
}

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => $unreadCount
]);

$conn->close();
?> 