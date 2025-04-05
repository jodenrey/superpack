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
        displayError("Error creating table: " . $conn->error);
        exit;
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
    displayError("Username already exists. Please choose a different username.");
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
        displaySuccess("Registration successful! You can now log in with your username and password.");
    }
    catch (Exception $e) {
        // An error occurred, rollback the transaction
        $conn->rollback();
        
        // Registration failed
        displayError("Registration failed: " . $e->getMessage());
    }
}

$stmt->close();
$conn->close();

// Function to display success message
function displaySuccess($message) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Registration Success</title>
        <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background: linear-gradient(135deg, #3a7bd5, #00d2ff);
                margin: 0;
                padding: 0;
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                -webkit-font-smoothing: antialiased;
            }
            .success-container {
                background-color: white;
                border-radius: 15px;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                padding: 40px;
                text-align: center;
                max-width: 500px;
                width: 90%;
            }
            .icon {
                font-size: 64px;
                color: #64A651;
                margin-bottom: 20px;
            }
            h2 {
                color: #333;
                margin-top: 0;
                font-weight: 600;
            }
            p {
                color: #555;
                margin-bottom: 30px;
                font-size: 16px;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                padding: 12px 30px;
                background: linear-gradient(135deg, #64A651, #90EE90);
                color: white;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 500;
                transition: all 0.3s ease;
                border: none;
                cursor: pointer;
                font-size: 16px;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }
            .countdown {
                font-size: 14px;
                color: #777;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class='success-container'>
            <div class='icon'><i class='fas fa-check-circle'></i></div>
            <h2>Registration Successful!</h2>
            <p>" . $message . "</p>
            <a href='login.php' class='btn'>Go to Login</a>
            <div class='countdown'>Redirecting in <span id='timer'>5</span> seconds...</div>
        </div>
        <script>
            // Countdown timer
            let seconds = 5;
            const countdown = setInterval(function() {
                seconds--;
                document.getElementById('timer').textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(countdown);
                    window.location.href = 'login.php';
                }
            }, 1000);
        </script>
    </body>
    </html>";
}

// Function to display error message
function displayError($message) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Registration Error</title>
        <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background: linear-gradient(135deg, #3a7bd5, #00d2ff);
                margin: 0;
                padding: 0;
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                -webkit-font-smoothing: antialiased;
            }
            .error-container {
                background-color: white;
                border-radius: 15px;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                padding: 40px;
                text-align: center;
                max-width: 500px;
                width: 90%;
            }
            .icon {
                font-size: 64px;
                color: #FF5E62;
                margin-bottom: 20px;
            }
            h2 {
                color: #e74c3c;
                margin-top: 0;
                font-weight: 600;
            }
            p {
                color: #555;
                margin-bottom: 30px;
                font-size: 16px;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                padding: 12px 30px;
                margin: 0 8px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 500;
                transition: all 0.3s ease;
                cursor: pointer;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }
            .btn-primary {
                background: linear-gradient(135deg, #36D1DC, #5B86E5);
                color: white;
            }
            .btn-secondary {
                background-color: #f8f9fa;
                color: #333;
                border: 1px solid #ddd;
            }
        </style>
    </head>
    <body>
        <div class='error-container'>
            <div class='icon'><i class='fas fa-exclamation-circle'></i></div>
            <h2>Registration Failed</h2>
            <p>" . $message . "</p>
            <a href='register.php' class='btn btn-primary'>Try Again</a>
            <a href='login.php' class='btn btn-secondary'>Back to Login</a>
        </div>
    </body>
    </html>";
}
?>