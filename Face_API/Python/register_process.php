<?php
// Start session
session_start();

// Error handling to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

try {
    // Connect to the database
    $host = "localhost";
    $db_username = "root";
    $db_password = "password"; // Empty password for XAMPP default
    $db_name = "superpack_database";

    $conn = new mysqli($host, $db_username, $db_password, $db_name);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Get form data
    $name = $_POST['name'] ?? '';
    $employee_id = $_POST['employee_id'] ?? '';
    $password = $_POST['password'] ?? '';
    $department = $_POST['department'] ?? '';
    $role = $_POST['role'] ?? '';

    // Validate inputs
    if (empty($name) || empty($employee_id) || empty($password) || empty($department) || empty($role)) {
        throw new Exception('All fields are required');
    }

    // Check if employee ID already exists
    $checkStmt = $conn->prepare("SELECT employee_id FROM register WHERE employee_id = ?");
    $checkStmt->bind_param("s", $employee_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        throw new Exception('Employee ID already exists. Please use a different ID.');
    }
    $checkStmt->close();

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Generate QR code data
    $qr_data = generateQRData($employee_id, $name, $role, $department);

    // Insert into register table
    $stmt = $conn->prepare("INSERT INTO register (name, role, department, employee_id, password, qr_code_data) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $role, $department, $employee_id, $hashed_password, $qr_data);

    if ($stmt->execute()) {
        // Insert into users table as well for consistency
        $username = strtolower(str_replace(' ', '.', $name)); // Generate username from name
        $usersStmt = $conn->prepare("INSERT INTO users (employee_id, username, password, role, department) VALUES (?, ?, ?, ?, ?)");
        $usersStmt->bind_param("sssss", $employee_id, $username, $hashed_password, $role, $department);
        $usersStmt->execute();
        $usersStmt->close();
        
        $response = [
            'success' => true,
            'message' => 'Registration successful!',
            'qr_data' => $qr_data
        ];
    } else {
        throw new Exception('Registration failed: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();

    // Output JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    // Handle any exceptions and return a proper JSON error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;

// Function to generate QR code data
function generateQRData($employee_id, $name, $role, $department) {
    // Create a more compact data structure to reduce QR code size
    // Use shorter key names and only include essential information
    $data = [
        'id' => $employee_id,              // Shorter key name
        'n' => substr($name, 0, 20),       // Truncate name to 20 chars max
        'r' => substr($role, 0, 10),       // Shorter key and truncate role
        'd' => substr($department, 0, 10), // Shorter key and truncate department
        't' => time()                      // Timestamp with shorter key
    ];
    
    // Encrypt the data for security using a shorter method
    $encryption_key = 'SuperPackKey2023'; // Shorter key
    
    // Use a more compact encryption approach
    $json_data = json_encode($data);
    
    // Create a shorter signature instead of full encryption
    $signature = hash_hmac('sha256', $json_data, $encryption_key);
    $signature = substr($signature, 0, 16); // Use only first 16 chars of signature
    
    // Combine data and signature in a compact format
    $result = base64_encode($json_data) . '.' . $signature;
    
    return $result;
}
?> 