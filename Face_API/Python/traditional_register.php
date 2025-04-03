<?php
// Connect to the database
$host = "localhost";
$user = "root";
$password = "password";
$database = "superpack_database";

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// First, check if users table exists, if not create it
$check_table = "SHOW TABLES LIKE 'users'";
$table_result = $conn->query($check_table);

if ($table_result->num_rows == 0) {
    // Create users table
    $create_table = "CREATE TABLE users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL,
        department TEXT NOT NULL,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($create_table)) {
        die("Error creating table: " . $conn->error);
    }
}

// Get form data
$username = $_POST['trad_username'];
$password = password_hash($_POST['trad_password'], PASSWORD_DEFAULT); // Hash the password
$role = $_POST['trad_role'];
$department = $_POST['trad_department'];

// Check if the username already exists
$check_username = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($check_username);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Username already exists
    echo "<script>
        alert('Username already exists. Please choose a different username.');
        window.location.href = 'register.php';
    </script>";
} else {
    // Start a transaction to ensure all operations complete together
    $conn->begin_transaction();
    $success = true;
    
    try {
        // 1. Insert into users table
        $insert_user = "INSERT INTO users (username, password, role, department) 
                       VALUES (?, ?, ?, ?)";
                       
        $stmt = $conn->prepare($insert_user);
        $stmt->bind_param("ssss", $username, $password, $role, $department);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into users table: " . $stmt->error);
        }
        
        $user_id = $conn->insert_id;
        
        // 2. Insert into employee_records
        $insert_employee = "INSERT INTO employee_records (name, position, address, phone_number, age, email, shift, salary, status, start_date) 
                           VALUES (?, ?, '', '', '', '', '1', '0', 'Active', NOW())";
        
        $stmt = $conn->prepare($insert_employee);
        $stmt->bind_param("ss", $username, $role);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into employee_records table: " . $stmt->error);
        }
        
        $employee_id = $conn->insert_id;
        
        // 3. Insert into worker_evaluations (initialize with default values)
        $emp_id_string = "EMP-" . str_pad($employee_id, 4, '0', STR_PAD_LEFT);
        $insert_evaluation = "INSERT INTO worker_evaluations (id, employee_id, name, position, department, start_date, comments, performance) 
                             VALUES (?, ?, ?, ?, ?, NOW(), '', 0)";
        
        $stmt = $conn->prepare($insert_evaluation);
        $stmt->bind_param("sssss", $emp_id_string, $emp_id_string, $username, $role, $department);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into worker_evaluations table: " . $stmt->error);
        }
        
        // 4. Set up initial attendance record
        $insert_attendance = "INSERT INTO attendance (name, role, time_in) 
                             VALUES (?, ?, NOW())";
        
        $stmt = $conn->prepare($insert_attendance);
        $stmt->bind_param("ss", $username, $role);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into attendance table: " . $stmt->error);
        }
        
        // 5. Insert initial payroll record
        $insert_payroll = "INSERT INTO payroll_records (name, position, salary, daily_rate, basic_pay, ot_pay, late_deduct, gross_pay, sss_deduct, pagibig_deduct, total_deduct, net_salary, date_created) 
                          VALUES (?, ?, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NOW())";
        
        $stmt = $conn->prepare($insert_payroll);
        $stmt->bind_param("ss", $username, $role);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into payroll_records table: " . $stmt->error);
        }
        
        // Commit the transaction
        $conn->commit();
        
        // Registration successful
        echo "<script>
            alert('Registration successful! You can now log in with your username and password.');
            window.location.href = 'login.php';
        </script>";
    }
    catch (Exception $e) {
        // An error occurred, rollback the transaction
        $conn->rollback();
        
        // Registration failed
        echo "<script>
            alert('Registration failed: " . $e->getMessage() . "');
            window.location.href = 'register.php';
        </script>";
    }
}

$stmt->close();
$conn->close();
?>