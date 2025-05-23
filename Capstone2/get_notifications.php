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

// Create notifications table if it doesn't exist with updated structure
$createNotificationsTable = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50),
    user_role VARCHAR(50),
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createNotificationsTable);

// Handle marking notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notificationId = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
    
    if ($notificationId) {
        if ($current_user_role === 'Admin') {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_role = 'Admin'");
            $stmt->bind_param("i", $notificationId);
        } else {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("is", $notificationId, $current_user_id);
        }
        
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

// Get notifications based on user role and ID
$unreadOnly = isset($_GET['unread']) && $_GET['unread'] === '1';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Build query based on user role
if ($current_user_role === 'Admin') {
    // Admin sees notifications for admin role only, not employee-specific ones
    $query = "SELECT * FROM notifications WHERE user_role = 'Admin'";
} else {
    // Employees see notifications for their specific user_id only
    $query = "SELECT * FROM notifications WHERE user_id = ?";
}

if ($unreadOnly) {
    $query .= " AND is_read = 0";
}
$query .= " ORDER BY created_at DESC LIMIT ?";

$stmt = $conn->prepare($query);

if ($current_user_role === 'Admin') {
    $stmt->bind_param("i", $limit);
} else {
    $stmt->bind_param("si", $current_user_id, $limit);
}

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
if ($current_user_role === 'Admin') {
    $countQuery = "SELECT COUNT(*) as count FROM notifications WHERE user_role = 'Admin' AND is_read = 0";
    $countStmt = $conn->prepare($countQuery);
} else {
    $countQuery = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param("s", $current_user_id);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
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