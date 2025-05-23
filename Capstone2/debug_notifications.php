<?php
session_start();
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

echo "<h2>Notification System Debug</h2>";

// Check current session
echo "<h3>Current Session Info:</h3>";
echo "Logged in: " . (isset($_SESSION['loggedin']) ? 'Yes' : 'No') . "<br>";
echo "Role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'Not set') . "<br>";
echo "Username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'Not set') . "<br>";
echo "Name: " . (isset($_SESSION['name']) ? $_SESSION['name'] : 'Not set') . "<br>";
echo "Employee ID: " . (isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : 'Not set') . "<br>";

// Get current user info like the notification system does
$current_user_role = $_SESSION['role'] ?? 'Unknown';
$current_user_id = isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : 
                  (isset($_SESSION['username']) ? $_SESSION['username'] : '');

echo "<br><h3>Computed User Info for Notifications:</h3>";
echo "Current User Role: $current_user_role<br>";
echo "Current User ID: $current_user_id<br>";

// Test username resolution
if (isset($_GET['test_user'])) {
    $test_username = $_GET['test_user'];
    echo "<br><h3>Testing Username Resolution for: '$test_username'</h3>";
    
    $resolved_id = getUserIdFromUsername($conn, $test_username);
    echo "Resolved Employee ID: $resolved_id<br>";
    
    $resolved_role = getUserRoleFromUsername($conn, $test_username);
    echo "Resolved Role: $resolved_role<br>";
}

// Show all leave requests
echo "<br><h3>Current Leave Requests:</h3>";
$result = $conn->query("SELECT * FROM leave_request ORDER BY id DESC LIMIT 10");
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Leave Type</th><th>Start Date</th><th>End Date</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . " <a href='?test_user=" . urlencode($row['username']) . "'>[Test Resolution]</a></td>";
        echo "<td>" . $row['leave_type'] . "</td>";
        echo "<td>" . $row['start_date'] . "</td>";
        echo "<td>" . $row['end_date'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No leave requests found.";
}

// Show all notifications
echo "<br><h3>All Notifications in Database:</h3>";
$result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 20");
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>User ID</th><th>User Role</th><th>Type</th><th>Message</th><th>Read</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . ($row['user_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['user_role'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['type'] . "</td>";
        echo "<td>" . $row['message'] . "</td>";
        echo "<td>" . ($row['is_read'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No notifications found.";
}

// Show user tables for reference
echo "<br><h3>Users Table (first 10 rows):</h3>";
$result = $conn->query("SELECT * FROM users LIMIT 10");
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Employee ID</th><th>Username</th><th>Role</th><th>Department</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['employee_id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . $row['department'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No users found.";
}

echo "<br><h3>Register Table (first 10 rows):</h3>";
$result = $conn->query("SELECT * FROM register LIMIT 10");
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Employee ID</th><th>First Name</th><th>Middle Name</th><th>Last Name</th><th>Role</th><th>Department</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['employee_id'] . "</td>";
        echo "<td>" . $row['first_name'] . "</td>";
        echo "<td>" . ($row['middle_name'] ?? '') . "</td>";
        echo "<td>" . $row['last_name'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . $row['department'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No register records found.";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notification Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <p><a href="attendance_check.php">← Back to Attendance & Leave</a></p>
</body>
</html> 