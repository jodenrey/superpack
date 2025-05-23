<?php
session_start();

// Include notification helper functions
require_once('notification_helper.php');

// Database connection
$host = "localhost";
$user = "root";
$password = "password";
$database = "superpack_database";
$port = 3306;

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Testing Notification System</h2>";

// Test 1: Create notifications table
echo "<h3>Test 1: Creating notifications table</h3>";
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

if ($conn->query($createNotificationsTable)) {
    echo "✓ Notifications table created/verified successfully<br>";
} else {
    echo "✗ Error creating notifications table: " . $conn->error . "<br>";
}

// Test 2: Test admin notification for leave request
echo "<h3>Test 2: Creating admin notification for leave request</h3>";
$test_result = notifyAdminLeaveRequest($conn, "John Doe", "Vacation Leave", "2024-01-15", "2024-01-17");
if ($test_result) {
    echo "✓ Admin notification created successfully<br>";
} else {
    echo "✗ Failed to create admin notification<br>";
}

// Test 3: Test employee notification for approved leave
echo "<h3>Test 3: Creating employee notification for approved leave</h3>";
$test_result = notifyEmployeeLeaveStatus($conn, "EMP001", "Approved", "Sick Leave", "2024-01-10", "2024-01-12");
if ($test_result) {
    echo "✓ Employee notification created successfully<br>";
} else {
    echo "✗ Failed to create employee notification<br>";
}

// Test 4: Display all notifications
echo "<h3>Test 4: Displaying all notifications</h3>";
$query = "SELECT * FROM notifications ORDER BY created_at DESC";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>User ID</th><th>User Role</th><th>Type</th><th>Message</th><th>Link</th><th>Is Read</th><th>Created At</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . ($row['user_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['user_role'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['type'] . "</td>";
        echo "<td>" . $row['message'] . "</td>";
        echo "<td>" . $row['link'] . "</td>";
        echo "<td>" . ($row['is_read'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No notifications found in database.<br>";
}

// Test 5: Clean up test data
echo "<h3>Test 5: Cleaning up test data</h3>";
$cleanup = $conn->query("DELETE FROM notifications WHERE message LIKE '%John Doe%' OR message LIKE '%Sick Leave%'");
if ($cleanup) {
    echo "✓ Test data cleaned up successfully<br>";
} else {
    echo "✗ Failed to clean up test data<br>";
}

$conn->close();

echo "<br><a href='attendance_check.php'>← Back to Attendance & Leave</a>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notification System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { margin: 10px 0; }
        th, td { padding: 8px; text-align: left; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
</body>
</html> 