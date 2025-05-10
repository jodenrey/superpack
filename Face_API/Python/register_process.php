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
    
    // Handle profile picture upload
    $photo = 'default.png'; // Default image
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $fileInfo = pathinfo($_FILES['profile_picture']['name']);
        $extension = strtolower($fileInfo['extension']);
        
        // Only allow image files
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($extension, $allowedExtensions)) {
            // Create upload directory if it doesn't exist
            $uploadDir = '../../Capstone2/uploads/profile_pictures/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $newFileName = strtolower(str_replace(' ', '_', $name)) . '_' . time() . '.' . $extension;
            $targetFile = $uploadDir . $newFileName;
            
            // Move the uploaded file
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                $photo = $newFileName; // Update filename to the new name
            } else {
                // Continue with default image on upload failure
                error_log("Failed to move uploaded profile picture for user: " . $name);
            }
        } else {
            // Continue with default image on invalid file type
            error_log("Invalid profile picture file type for user: " . $name);
        }
    }

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

    // Start a transaction to ensure all inserts are successful or none
    $conn->begin_transaction();

    try {
    // Insert into register table
    $stmt = $conn->prepare("INSERT INTO register (name, role, department, employee_id, password, qr_code_data) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $role, $department, $employee_id, $hashed_password, $qr_data);
        $stmt->execute();
        $stmt->close();

        // Insert into users table
        $username = strtolower(str_replace(' ', '.', $name)); // Generate username from name
        $usersStmt = $conn->prepare("INSERT INTO users (employee_id, username, password, role, department) VALUES (?, ?, ?, ?, ?)");
        $usersStmt->bind_param("sssss", $employee_id, $username, $hashed_password, $role, $department);
        $usersStmt->execute();
        $usersStmt->close();
        
        // Insert into employee_records table
        $position = $role; // Using role as position
        $address = ""; // Default value
        $phone_number = ""; // Default value
        $age = "0"; // Default value
        $email = ""; // Default value
        
        $employeeRecordsStmt = $conn->prepare("INSERT INTO employee_records (id, name, position, address, phone_number, age, email, photo, shift, salary, status, start_date) 
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, '1', '0', 'Active', NOW())");
        $employeeRecordsStmt->bind_param("ssssssss", $employee_id, $name, $position, $address, $phone_number, $age, $email, $photo);
        $employeeRecordsStmt->execute();
        $employeeRecordsStmt->close();
        
        // Insert into worker_evaluations table
        $evalId = "EMP-" . str_pad($employee_id, 4, '0', STR_PAD_LEFT);
        $comments = "";
        $performance = "0";
        
        $evalStmt = $conn->prepare("INSERT INTO worker_evaluations (id, employee_id, name, position, department, start_date, comments, performance) 
                                  VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)");
        $evalStmt->bind_param("sssssss", $evalId, $employee_id, $name, $position, $department, $comments, $performance);
        $evalStmt->execute();
        $evalStmt->close();
        
        // Insert into payroll_records table
        $payrollStmt = $conn->prepare("INSERT INTO payroll_records (id, name, position, salary, daily_rate, basic_pay, ot_pay, late_deduct, gross_pay, sss_deduct, pagibig_deduct, total_deduct, net_salary, date_created) 
                                      VALUES (?, ?, ?, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NOW())");
        $payrollStmt->bind_param("sss", $employee_id, $name, $position);
        $payrollStmt->execute();
        $payrollStmt->close();
        
        // Check if time_out column exists in attendance table
        $checkTimeOutColumn = $conn->query("SHOW COLUMNS FROM attendance LIKE 'time_out'");
        $timeOutColumnExists = $checkTimeOutColumn->num_rows > 0;
        
        // Check if date column exists in attendance table
        $checkDateColumn = $conn->query("SHOW COLUMNS FROM attendance LIKE 'date'");
        $dateColumnExists = $checkDateColumn->num_rows > 0;
        
        // Check if role column exists in attendance table
        $checkRoleColumn = $conn->query("SHOW COLUMNS FROM attendance LIKE 'role'");
        $roleColumnExists = $checkRoleColumn->num_rows > 0;
        
        // Insert into attendance table based on available columns
        if ($timeOutColumnExists && $dateColumnExists && $roleColumnExists) {
            $attendanceStmt = $conn->prepare("INSERT INTO attendance (name, time_in, time_out, date, role) VALUES (?, NOW(), NULL, CURDATE(), ?)");
            $attendanceStmt->bind_param("ss", $name, $role);
        } else if ($timeOutColumnExists && $dateColumnExists) {
            $attendanceStmt = $conn->prepare("INSERT INTO attendance (name, time_in, time_out, date) VALUES (?, NOW(), NULL, CURDATE())");
            $attendanceStmt->bind_param("s", $name);
        } else if ($timeOutColumnExists && $roleColumnExists) {
            $attendanceStmt = $conn->prepare("INSERT INTO attendance (name, time_in, time_out, role) VALUES (?, NOW(), NULL, ?)");
            $attendanceStmt->bind_param("ss", $name, $role);
        } else if ($dateColumnExists && $roleColumnExists) {
            $attendanceStmt = $conn->prepare("INSERT INTO attendance (name, time_in, date, role) VALUES (?, NOW(), CURDATE(), ?)");
            $attendanceStmt->bind_param("ss", $name, $role);
        } else if ($timeOutColumnExists) {
            $attendanceStmt = $conn->prepare("INSERT INTO attendance (name, time_in, time_out) VALUES (?, NOW(), NULL)");
            $attendanceStmt->bind_param("s", $name);
        } else if ($dateColumnExists) {
            $attendanceStmt = $conn->prepare("INSERT INTO attendance (name, time_in, date) VALUES (?, NOW(), CURDATE())");
            $attendanceStmt->bind_param("s", $name);
        } else if ($roleColumnExists) {
            $attendanceStmt = $conn->prepare("INSERT INTO attendance (name, time_in, role) VALUES (?, NOW(), ?)");
            $attendanceStmt->bind_param("ss", $name, $role);
        } else {
            $attendanceStmt = $conn->prepare("INSERT INTO attendance (name, time_in) VALUES (?, NOW())");
            $attendanceStmt->bind_param("s", $name);
        }
        $attendanceStmt->execute();
        $attendanceStmt->close();
        
        // Map department to task management department
        $taskDept = strtolower(trim($department));
        // Direct matches
        $validDepartments = ['sales', 'purchasing', 'proddev', 'warehouse', 'logistics', 'accounting'];
        
        // Check for direct match
        if (!in_array($taskDept, $validDepartments)) {
            // Mapping for similar or partial matches
            if (strpos($taskDept, 'sale') !== false || strpos($taskDept, 'market') !== false) {
                $taskDept = 'sales';
            } else if (strpos($taskDept, 'purchas') !== false || strpos($taskDept, 'procure') !== false) {
                $taskDept = 'purchasing';
            } else if (strpos($taskDept, 'prod') !== false || strpos($taskDept, 'dev') !== false || strpos($taskDept, 'r&d') !== false) {
                $taskDept = 'proddev';
            } else if (strpos($taskDept, 'ware') !== false || strpos($taskDept, 'storage') !== false) {
                $taskDept = 'warehouse';
            } else if (strpos($taskDept, 'log') !== false || strpos($taskDept, 'ship') !== false || strpos($taskDept, 'transport') !== false) {
                $taskDept = 'logistics';
            } else if (strpos($taskDept, 'account') !== false || strpos($taskDept, 'financ') !== false) {
                $taskDept = 'accounting';
            } else {
                $taskDept = 'sales'; // Default to sales if no match
            }
        }
        
        // Create a sample task for the employee in their department's task table
        $taskTable = $taskDept . '_tasks';
        $taskId = "PC-T" . rand(1000, 9999);
        $taskName = "Initial task for $name";
        $today = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+7 days'));
        $duration = 7; // 7 days
        
        // Check if the task table exists before trying to insert
        $checkTableQuery = "SHOW TABLES LIKE '$taskTable'";
        $tableExists = $conn->query($checkTableQuery);
        
        if ($tableExists && $tableExists->num_rows > 0) {
            try {
                $taskStmt = $conn->prepare("INSERT INTO $taskTable (id, task, owner, status, start_date, due_date, completion, priority, duration) VALUES (?, ?, ?, 'Not Started', ?, ?, 0, 1, ?)");
                $taskStmt->bind_param("sssssi", $taskId, $taskName, $name, $today, $dueDate, $duration);
                $taskStmt->execute();
                $taskStmt->close();
            } catch (Exception $e) {
                // Silently continue if there's an error with task creation
                // This ensures the employee registration isn't affected by task issues
            }
        }
        
        // Commit the transaction if everything succeeded
        $conn->commit();
        
        $response = [
            'success' => true,
            'message' => 'Registration successful!',
            'qr_data' => $qr_data
        ];
    } catch (Exception $e) {
        // Rollback transaction if any error occurs
        $conn->rollback();
        throw $e;
    }

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