<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$host = "localhost";
$user = "root";
$password = "password";
$database = "superpack_database";
$port = 3306;

// mysqli connection
$conn = new mysqli($host, $user, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if employee name is provided
if (!isset($_POST['employee_name']) || empty($_POST['employee_name'])) {
    echo json_encode(['success' => false, 'message' => 'Employee name is required']);
    exit();
}

$employeeName = $_POST['employee_name'];

// Prepare and execute query to get all payroll records for the employee, ordered by date descending
$query = "SELECT * FROM payroll_records WHERE name = ? ORDER BY date_created DESC";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
    exit();
}

$stmt->bind_param("s", $employeeName);
$stmt->execute();
$result = $stmt->get_result();

$payrollHistory = [];
while ($row = $result->fetch_assoc()) {
    $payrollHistory[] = $row;
}

$stmt->close();
$conn->close();

// Return JSON response
echo json_encode([
    'success' => true,
    'data' => $payrollHistory,
    'count' => count($payrollHistory)
]);
?> 