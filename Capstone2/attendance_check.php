<?php
// Set the default timezone for Philippines
date_default_timezone_set('Asia/Manila');

session_start();

// Get username from the correct session variable - check all possible sources
if (isset($_SESSION['name'])) {
    $username = $_SESSION['name']; // From login_process.php
} elseif (isset($_SESSION['username'])) {
    $username = $_SESSION['username']; // From other login sources
} else {
    $username = 'Guest';
}

if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
} else {
    $role = 'User';
}

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../welcome.php');
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
    die("Connection failed: " . $conn->connect_error);
}

// Check if date column exists in attendance table
$checkDateColumn = $conn->query("SHOW COLUMNS FROM attendance LIKE 'date'");
$dateColumnExists = $checkDateColumn->num_rows > 0;

// Check if time_out column exists in attendance table - use a more thorough approach
try {
    // First, check with exact case
    $checkTimeOutColumn = $conn->query("SHOW COLUMNS FROM attendance LIKE 'time_out'");
    $timeOutColumnExists = $checkTimeOutColumn->num_rows > 0;
    
    // If not found, also check for any case variation
    if (!$timeOutColumnExists) {
        $allColumns = $conn->query("SHOW COLUMNS FROM attendance");
        while ($column = $allColumns->fetch_assoc()) {
            if (strtolower($column['Field']) === 'time_out') {
                $timeOutColumnExists = true;
                break;
            }
        }
    }
} catch (Exception $e) {
    // If there's an error, assume column doesn't exist
    $timeOutColumnExists = false;
}

// Handle QR code attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance_action'])) {
    $action = $_POST['attendance_action'];
    $today = date('Y-m-d');
    $currentTime = date('H:i:s');
    $response = ['success' => false, 'message' => ''];
    
    // Check if QR code was uploaded
    if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === 0) {
        // Process the uploaded QR code image
        // In a real implementation, we'd use a proper QR code library
        // Here we'll simulate processing by reading the file
        $qr_file = $_FILES['qr_code']['tmp_name'];
        
        // Get the QR code content - in production, you'd use a real QR decoder
        // For this example, we'll use a mock function that would be replaced with actual QR reading code
        $qr_data = file_get_contents($qr_file);
        
        // Try to decode the QR data
        $employee_data = checkAndConvertOldFormat($qr_data);
        
        if ($employee_data === false) {
            $response['message'] = 'Invalid QR code. Please use your assigned QR code.';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        
        // Get the employee ID and name from the QR code
        $qr_employee_id = $employee_data['employee_id'] ?? '';
        $qr_name = $employee_data['name'] ?? '';
        
        // Get username from the correct session variable
        if (isset($_SESSION['name'])) {
            $username = $_SESSION['name']; // From login_process.php
        } elseif (isset($_SESSION['username'])) {
            $username = $_SESSION['username']; // From other login sources
        } else {
            $username = 'Guest';
        }
        
        // Get the logged-in user's data from database
        $userStmt = $conn->prepare("SELECT * FROM register WHERE name = ?");
        $userStmt->bind_param("s", $username);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult->num_rows > 0) {
            $userData = $userResult->fetch_assoc();
            $employee_id = $userData['employee_id'];
            $empName = $userData['name'];
            $empRole = $userData['role'];
            
            // IMPORTANT: Verify the QR code belongs to the logged-in user
            if ($qr_employee_id != $employee_id && $qr_name != $empName) {
                $response['message'] = 'This QR code does not belong to you. Please use your own QR code.';
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
            
            // User verified, now update attendance
            if ($action === 'time_in') {
                // Check if already timed in today
                $today = date('Y-m-d');
                
                // Use DATE() function on time_in to filter by today's date
                $checkStmt = $conn->prepare("SELECT * FROM attendance WHERE name = ? AND DATE(time_in) = ?");
                $checkStmt->bind_param("ss", $empName, $today);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    // Already has an entry for today
                    $response['message'] = 'You have already timed in today.';
                } else {
                    // Create new attendance entry
                    $currentTime = date('Y-m-d H:i:s'); // Format datetime for MySQL
                    
                    // Your attendance table only has name, role, and time_in
                    $insertStmt = $conn->prepare("INSERT INTO attendance (name, role, time_in) VALUES (?, ?, ?)");
                    $insertStmt->bind_param("sss", $empName, $empRole, $currentTime);
                    
                    if ($insertStmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Time in recorded successfully.';
                        
                        // Check if late
                        $eightAM = strtotime($today . ' 08:00:00');
                        $nowTime = strtotime($currentTime);
                        $isLate = $nowTime > $eightAM;
                        
                        $response['status'] = $isLate ? 'late' : 'on-time';
                        $response['time'] = date('h:i:s A', $nowTime);
                    } else {
                        $response['message'] = 'Error recording time in: ' . $insertStmt->error;
                    }
                }
            } else if ($action === 'time_out') {
                $today = date('Y-m-d');
                $currentTime = date('Y-m-d H:i:s');
                
                if ($timeOutColumnExists) {
                    // Update the existing attendance record for today with time_out
                    $updateStmt = $conn->prepare("UPDATE attendance SET time_out = ? WHERE name = ? AND DATE(time_in) = ?");
                    $updateStmt->bind_param("sss", $currentTime, $empName, $today);
                    
                    if ($updateStmt->execute() && $updateStmt->affected_rows > 0) {
                        $response['success'] = true;
                        $response['message'] = 'Time out recorded successfully.';
                        
                        // Check if overtime
                        $fivePM = strtotime($today . ' 17:00:00');
                        $nowTime = strtotime($currentTime);
                        $isOvertime = $nowTime > $fivePM;
                        
                        $response['status'] = $isOvertime ? 'overtime' : 'regular';
                        $response['time'] = date('h:i:s A', $nowTime);
                    } else {
                        // No attendance record found for today
                        $response['message'] = 'No time-in record found for today. Please time in first.';
                    }
                } else {
                    // Fallback to old method if time_out column doesn't exist
                    // Create a timeout record with a note in the name field
                    $timeOutName = $empName . " (Time Out)"; 
                    $insertStmt = $conn->prepare("INSERT INTO attendance (name, role, time_in) VALUES (?, ?, ?)");
                    $insertStmt->bind_param("sss", $timeOutName, $empRole, $currentTime);
                    
                    if ($insertStmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Time out recorded successfully.';
                        
                        // Check if overtime
                        $fivePM = strtotime($today . ' 17:00:00');
                        $nowTime = strtotime($currentTime);
                        $isOvertime = $nowTime > $fivePM;
                        
                        $response['status'] = $isOvertime ? 'overtime' : 'regular';
                        $response['time'] = date('h:i:s A', $nowTime);
                    } else {
                        $response['message'] = 'Error recording time out: ' . $insertStmt->error;
                    }
                }
            }
        } else {
            $response['message'] = 'Could not find your employee ID. Please contact system administrator.';
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    } else {
        $response['message'] = 'Please upload your QR code image.';
    }
    
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Create leave_request table if it doesn't exist
$createTableQuery = "CREATE TABLE IF NOT EXISTS leave_request (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255),
    leave_type VARCHAR(255),
    start_date DATE,
    end_date DATE,
    status VARCHAR(50)
)";
$conn->query($createTableQuery);

// Check if username column exists in leave_request table
$checkColumn = $conn->query("SHOW COLUMNS FROM leave_request LIKE 'username'");
$usernameColumnExists = $checkColumn->num_rows > 0;

// If username column doesn't exist, add it
if (!$usernameColumnExists) {
    $conn->query("ALTER TABLE leave_request ADD COLUMN username VARCHAR(255) AFTER id");
    $usernameColumnExists = true; // Now it exists
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addLeave'])) {
        $leave = [
            'leaveType' => $_POST['leaveType'],
            'startDate' => $_POST['startDate'],
            'endDate' => $_POST['endDate'],
            'status' => 'Pending',
        ];
        
        if ($usernameColumnExists) {
            $leave['username'] = $username;
            $stmt = $conn->prepare("INSERT INTO leave_request (username, leave_type, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $leave['username'], $leave['leaveType'], $leave['startDate'], $leave['endDate'], $leave['status']);
        } else {
            $stmt = $conn->prepare("INSERT INTO leave_request (leave_type, start_date, end_date, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $leave['leaveType'], $leave['startDate'], $leave['endDate'], $leave['status']);
        }
        
        $stmt->execute();
    }
    
    // Handle admin approval/denial of leave requests
    if (isset($_POST['approveLeave'])) {
        $leaveId = $_POST['leaveId'];
        $status = 'Approved';
        
        $stmt = $conn->prepare("UPDATE leave_request SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $leaveId);
        $stmt->execute();
    }
    
    if (isset($_POST['denyLeave'])) {
        $leaveId = $_POST['leaveId'];
        $status = 'Denied';
        
        $stmt = $conn->prepare("UPDATE leave_request SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $leaveId);
        $stmt->execute();
    }
    
    // Handle delete leave request
    if (isset($_POST['deleteLeave'])) {
        $leaveId = $_POST['leaveId'];
        
        $stmt = $conn->prepare("DELETE FROM leave_request WHERE id = ?");
        $stmt->bind_param("i", $leaveId);
        $stmt->execute();
    }
    
    // Handle edit leave request - this just shows the edit modal
    if (isset($_POST['editLeave'])) {
        $leaveId = $_POST['leaveId'];
        // We'll use JavaScript to show the edit modal and populate it
    }
    
    // Handle update leave request from edit modal
    if (isset($_POST['updateLeave'])) {
        $leaveId = $_POST['leaveId'];
        $leaveType = $_POST['leaveType'];
        $startDate = $_POST['startDate'];
        $endDate = $_POST['endDate'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE leave_request SET leave_type = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $leaveType, $startDate, $endDate, $status, $leaveId);
        $stmt->execute();
    }
}

// Get leave request data for editing
$editLeaveData = null;
if (isset($_POST['editLeave'])) {
    $leaveId = $_POST['leaveId'];
    $stmt = $conn->prepare("SELECT * FROM leave_request WHERE id = ?");
    $stmt->bind_param("i", $leaveId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $editLeaveData = $result->fetch_assoc();
    }
}

// If admin user, show database update buttons if needed columns don't exist
if ($role === 'Admin'):
?>
    <?php if (!$dateColumnExists): ?>
    <div class="alert alert-warning">
        <strong>Database Update Required:</strong> The 'date' column is missing from the attendance table.
        <form method="POST" style="display: inline;">
            <input type="hidden" name="add_date_column" value="1">
            <button type="submit" class="btn btn-sm btn-primary">Add Date Column Now</button>
        </form>
    </div>
    <?php endif; ?>
    
    <?php if (!$timeOutColumnExists): ?>
    <div class="alert alert-warning">
        <strong>Database Update Required:</strong> The 'time_out' column is missing from the attendance table.
        <form method="POST" style="display: inline;">
            <input type="hidden" name="add_timeout_column" value="1">
            <button type="submit" class="btn btn-sm btn-primary">Add Time Out Column Now</button>
        </form>
    </div>
    <?php endif; ?>
<?php 
endif;

// Handle date column addition
if ($role === 'Admin' && isset($_POST['add_date_column'])) {
    $alterQuery = "ALTER TABLE attendance ADD COLUMN date DATE NULL";
    if ($conn->query($alterQuery)) {
        $updateQuery = "UPDATE attendance SET date = DATE(time_in) WHERE time_in IS NOT NULL";
        $conn->query($updateQuery);
        echo '<div class="alert alert-success">Date column added successfully. Existing records have been updated.</div>';
        $dateColumnExists = true;
    } else {
        echo '<div class="alert alert-danger">Failed to add date column: ' . $conn->error . '</div>';
    }
}

// Handle time_out column addition
if ($role === 'Admin' && isset($_POST['add_timeout_column'])) {
    // Double-check if the column exists to avoid duplicate column error
    $checkAgain = $conn->query("SHOW COLUMNS FROM attendance LIKE 'time_out'");
    if ($checkAgain->num_rows > 0) {
        echo '<div class="alert alert-info">The time_out column already exists in the attendance table.</div>';
        $timeOutColumnExists = true;
    } else {
        try {
            $alterQuery = "ALTER TABLE attendance ADD COLUMN time_out DATETIME NULL";
            if ($conn->query($alterQuery)) {
                echo '<div class="alert alert-success">Time out column added successfully. Please note that existing time out records using the name format will remain unchanged.</div>';
                $timeOutColumnExists = true;
            } else {
                echo '<div class="alert alert-danger">Failed to add time_out column: ' . $conn->error . '</div>';
                
                // Show table structure for debugging
                echo '<div class="alert alert-info">Current attendance table structure:<br>';
                $result = $conn->query("DESCRIBE attendance");
                echo '<ul>';
                while ($row = $result->fetch_assoc()) {
                    echo '<li>' . $row['Field'] . ' - ' . $row['Type'] . '</li>';
                }
                echo '</ul></div>';
            }
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            
            // Show table structure for debugging
            echo '<div class="alert alert-info">Current attendance table structure:<br>';
            $result = $conn->query("DESCRIBE attendance");
            echo '<ul>';
            while ($row = $result->fetch_assoc()) {
                echo '<li>' . $row['Field'] . ' - ' . $row['Type'] . '</li>';
            }
            echo '</ul></div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <title>Attendance & Leave Management</title>
    <link rel="stylesheet" href="style_index.css">
    <link rel="icon" type="image/x-icon" href="Superpack-Enterprise-Logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboardnew.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .btn-action-group {
            display: flex;
            gap: 5px;
        }
        
        .action-icons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.85rem;
        }
    </style>

    
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<?php include 'filter_sidebar.php'?>
    <?php include 'sidebar_small.php'?>
    <div class="container-everything" style="height:100%;">
        <div class="container-all">
            <div class="container-top">
                <?php include 'header_2.php';?>
                
                <?php if ($role === 'Admin' && !$dateColumnExists): ?>
                <div class="alert alert-warning">
                    <strong>Database Update Required:</strong> The 'date' column is missing from the attendance table.
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="add_date_column" value="1">
                        <button type="submit" class="btn btn-sm btn-primary">Add Date Column Now</button>
                    </form>
                </div>
                <?php endif; ?>
                
                <?php 
                // Handle date column addition
                if ($role === 'Admin' && isset($_POST['add_date_column'])) {
                    $alterQuery = "ALTER TABLE attendance ADD COLUMN date DATE NULL";
                    if ($conn->query($alterQuery)) {
                        $updateQuery = "UPDATE attendance SET date = DATE(time_in) WHERE time_in IS NOT NULL";
                        $conn->query($updateQuery);
                        echo '<div class="alert alert-success">Date column added successfully. Existing records have been updated.</div>';
                        $dateColumnExists = true;
                    } else {
                        echo '<div class="alert alert-danger">Failed to add date column: ' . $conn->error . '</div>';
                    }
                }
                ?>
            </div>
            
            <!-- QR Code Attendance Section -->
            <div class="qr-attendance-section mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Mark Attendance with QR Code</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Current Status</h5>
                                <?php
                                // Check if user has already timed in today
                                $today = date('Y-m-d');
                                
                                // Get name from database for more accurate lookup
                                $lookupName = $username;
                                $nameStmt = $conn->prepare("SELECT * FROM register WHERE name LIKE ?");
                                $nameQuery = "%" . $username . "%";
                                $nameStmt->bind_param("s", $nameQuery);
                                $nameStmt->execute();
                                $nameResult = $nameStmt->get_result();
                                
                                if ($nameResult->num_rows > 0) {
                                    $nameData = $nameResult->fetch_assoc();
                                    $lookupName = $nameData['name'];
                                }
                                
                                $checkAttendanceStmt = $conn->prepare("SELECT * FROM attendance WHERE name = ? AND DATE(time_in) = ?");
                                $checkAttendanceStmt->bind_param("ss", $lookupName, $today);
                                $checkAttendanceStmt->execute();
                                $attendanceResult = $checkAttendanceStmt->get_result();
                                
                                $timeInStatus = "Not Clocked In";
                                $timeOutStatus = "Not Clocked Out";
                                $hasTimedIn = false;
                                $hasTimedOut = false;
                                
                                if ($attendanceResult->num_rows > 0) {
                                    $attendanceRow = $attendanceResult->fetch_assoc();
                                    
                                    if (!empty($attendanceRow['time_in'])) {
                                        $timeIn = strtotime($attendanceRow['time_in']);
                                        $timeInStatus = date('h:i:s A', $timeIn);
                                        $hasTimedIn = true;
                                        
                                        // Check if late
                                        $eightAM = strtotime(date('Y-m-d') . ' 08:00:00');
                                        $isLate = $timeIn > $eightAM;
                                        $timeInStatus .= $isLate ? " <span class='badge badge-danger'>Late</span>" : " <span class='badge badge-success'>On Time</span>";
                                    }
                                    
                                    // Check for time out
                                    if ($timeOutColumnExists && !empty($attendanceRow['time_out'])) {
                                        // If time_out column exists and has data, use it
                                        $timeOut = strtotime($attendanceRow['time_out']);
                                        $timeOutStatus = date('h:i:s A', $timeOut);
                                        $hasTimedOut = true;
                                        
                                        // Check if overtime
                                        $fivePM = strtotime(date('Y-m-d') . ' 17:00:00');
                                        $isOvertime = $timeOut > $fivePM;
                                        $timeOutStatus .= $isOvertime ? " <span class='badge badge-warning'>Overtime</span>" : "";
                                    } else {
                                        // Fallback to old method of checking for "(Time Out)" in name
                                        $timeoutName = $lookupName . " (Time Out)";
                                        $checkTimeoutStmt = $conn->prepare("SELECT * FROM attendance WHERE name = ? AND DATE(time_in) = ?");
                                        $checkTimeoutStmt->bind_param("ss", $timeoutName, $today);
                                        $checkTimeoutStmt->execute();
                                        $timeoutResult = $checkTimeoutStmt->get_result();
                                        
                                        if ($timeoutResult->num_rows > 0) {
                                            $timeoutRow = $timeoutResult->fetch_assoc();
                                            $timeOut = strtotime($timeoutRow['time_in']); // We're using time_in for the timeout record
                                            $timeOutStatus = date('h:i:s A', $timeOut);
                                            $hasTimedOut = true;
                                            
                                            // Check if overtime
                                            $fivePM = strtotime(date('Y-m-d') . ' 17:00:00');
                                            $isOvertime = $timeOut > $fivePM;
                                            $timeOutStatus .= $isOvertime ? " <span class='badge badge-warning'>Overtime</span>" : "";
                                        }
                                    }
                                }
                                ?>
                                <div class="attendance-status">
                                    <p><strong>Time In:</strong> <span id="time-in-status"><?php echo $timeInStatus; ?></span></p>
                                    <p><strong>Time Out:</strong> <span id="time-out-status"><?php echo $timeOutStatus; ?></span></p>
                                    <p><strong>Date:</strong> <?php echo date('F d, Y'); ?></p>
                                    <p><strong>Current Time:</strong> <span id="current-time"></span></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="qr-reader-section">
                                    <?php if (!$hasTimedOut): ?>
                                    <form method="POST" enctype="multipart/form-data" id="attendance-form">
                                        <div class="form-group">
                                            <label for="qr-code">Upload or Scan Your QR Code</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="qr-code" name="qr_code" accept="image/*" capture="environment">
                                                <label class="custom-file-label" for="qr-code">Choose file or take photo</label>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <?php if (!$hasTimedIn): ?>
                                            <button type="button" class="btn btn-success" id="time-in-btn" onclick="submitAttendance('time_in')">Time In</button>
                                            <?php elseif (!$hasTimedOut): ?>
                                            <button type="button" class="btn btn-danger" id="time-out-btn" onclick="submitAttendance('time_out')">Time Out</button>
                                            <?php endif; ?>
                                        </div>
                                        <input type="hidden" name="action" id="attendance-action" value="">
                                    </form>
                                    
                                    <div id="qr-message" class="mt-2"></div>
                                    <?php else: ?>
                                    <div class="alert alert-info">
                                        You have already completed your attendance for today.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="container-search">
                <div class="tool-bar">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <button class="btn btn-primary mb-3" type="button" data-toggle="modal" data-target="#addLeaveModal">Create Leave Request</button>
                        </div>
                    </div>
                </div>
                <div class="table-container">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <?php if ($role === 'Admin'): ?>
                                <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // If admin, show all leave requests, else show only the user's requests
                            if ($role === 'Admin') {
                                $sql = "SELECT * FROM leave_request";
                            } else {
                                if ($usernameColumnExists) {
                                    $sql = "SELECT * FROM leave_request WHERE username = '$username'";
                                } else {
                                    // If username column doesn't exist yet, show all requests
                                    $sql = "SELECT * FROM leave_request";
                                }
                            }
                            
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . ($row['username'] ?? 'Unknown') . "</td>";
                                    echo "<td>" . $row['leave_type'] . "</td>";
                                    echo "<td>" . $row['start_date'] . "</td>";
                                    echo "<td>" . $row['end_date'] . "</td>";
                                    echo "<td>" . $row['status'] . "</td>";
                                    
                                    // Add action buttons for admin
                                    if ($role === 'Admin') {
                                        echo "<td class='action-icons'>";
                                        echo "<div class='btn-action-group'>";
                                        
                                        // Status-based actions
                                        if ($row['status'] === 'Pending') {
                                            echo "<form method='POST' style='display: inline;'>";
                                            echo "<input type='hidden' name='leaveId' value='" . $row['id'] . "'>";
                                            echo "<button type='submit' name='approveLeave' class='btn btn-success btn-sm' title='Approve'><i class='fas fa-check'></i></button>";
                                            echo "</form> ";
                                            
                                            echo "<form method='POST' style='display: inline;'>";
                                            echo "<input type='hidden' name='leaveId' value='" . $row['id'] . "'>";
                                            echo "<button type='submit' name='denyLeave' class='btn btn-danger btn-sm' title='Deny'><i class='fas fa-times'></i></button>";
                                            echo "</form> ";
                                        }
                                        
                                        // Edit button - always shown
                                        echo "<form method='POST' style='display: inline;'>";
                                        echo "<input type='hidden' name='leaveId' value='" . $row['id'] . "'>";
                                        echo "<button type='button' class='btn btn-info btn-sm edit-btn' data-toggle='modal' data-target='#editLeaveModal' 
                                              data-id='" . $row['id'] . "' 
                                              data-username='" . ($row['username'] ?? 'Unknown') . "' 
                                              data-leavetype='" . $row['leave_type'] . "' 
                                              data-startdate='" . $row['start_date'] . "' 
                                              data-enddate='" . $row['end_date'] . "' 
                                              data-status='" . $row['status'] . "' 
                                              title='Edit'><i class='fas fa-edit'></i></button>";
                                        echo "</form> ";
                                        
                                        // Delete button - always shown
                                        echo "<form method='POST' style='display: inline;' onsubmit='return confirm(\"Are you sure you want to delete this leave request?\")'>";
                                        echo "<input type='hidden' name='leaveId' value='" . $row['id'] . "'>";
                                        echo "<button type='submit' name='deleteLeave' class='btn btn-danger btn-sm' title='Delete'><i class='fas fa-trash-alt'></i></button>";
                                        echo "</form>";
                                        
                                        echo "</div>";
                                        echo "</td>";
                                    }
                                    
                                    echo "</tr>";
                                }
                            } else {
                                $colspan = ($role === 'Admin') ? 6 : 5;
                                echo "<tr><td colspan='$colspan'>No leave requests found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="container-bottom">
                <div class="container-table">
                    <div class="table-container">
                        <!-- Color Legend -->
                        <div class="mb-3 p-2 border rounded">
                            <h5>Legend:</h5>
                            <span class="mr-3"><i class="fas fa-circle text-success"></i> <span class="text-success font-weight-bold">On Time</span> (Before 8:00 AM)</span>
                            <span class="mr-3"><i class="fas fa-circle text-danger"></i> <span class="text-danger font-weight-bold">Late</span> (After 8:00 AM)</span>
                            <span><i class="fas fa-circle text-warning"></i> <span class="text-warning font-weight-bold">Overtime</span> (After 5:00 PM)</span>
                        </div>
                        
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Date</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                // Assuming $username and $role are set from the session
                                $role = isset($_SESSION['role']) ? $_SESSION['role'] : 'User';

                                if ($role !== 'Admin') {
                                    $sql = "SELECT * FROM attendance WHERE name = '$username'";
                                } else {
                                    $sql = "SELECT * FROM attendance";
                                }

                                $result = $conn->query($sql);

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        // Parse times for comparison
                                        $timeIn = strtotime($row['time_in']);
                                        $timeInFormatted = date('h:i:s A', $timeIn);
                                        
                                        // Get the date part from time_in
                                        $datePart = date('Y-m-d', $timeIn);
                                        
                                        // Check if late (after 8am)
                                        $eightAM = strtotime($datePart . ' 08:00:00');
                                        $isLate = $timeIn > $eightAM;
                                        
                                        // Time in class based on lateness
                                        $timeInClass = $isLate ? 'text-danger font-weight-bold' : 'text-success font-weight-bold';
                                        
                                        // Format time out and check overtime
                                        $timeOutFormatted = 'Not clocked out';
                                        $timeOutClass = '';
                                        
                                        if (isset($row['time_out']) && !empty($row['time_out'])) {
                                            // Use time_out column if it exists and has data
                                            $timeOut = strtotime($row['time_out']);
                                            $timeOutFormatted = date('h:i:s A', $timeOut);
                                            
                                            // Check if overtime (after 5pm)
                                            $fivePM = strtotime($datePart . ' 17:00:00');
                                            $isOvertime = $timeOut > $fivePM;
                                            
                                            // Time out class based on overtime
                                            $timeOutClass = $isOvertime ? 'text-warning font-weight-bold' : '';
                                        } else if (strpos($row['name'], '(Time Out)') !== false) {
                                            // This is a time-out record using the old method
                                            continue; // Skip this row as it's a duplicate of a regular record
                                        }
                                        
                                        // Format date for display
                                        $date = isset($row['date']) ? $row['date'] : date('Y-m-d', $timeIn);
                                        
                                        echo "<tr>";
                                        echo "<td>" . $row['name'] . "</td>";
                                        echo "<td>" . $row['role'] . "</td>";
                                        echo "<td class='" . $timeInClass . "'>" . $timeInFormatted . ($isLate ? " <span class='badge badge-danger'>Late</span>" : " <span class='badge badge-success'>On Time</span>") . "</td>";
                                        echo "<td class='" . $timeOutClass . "'>" . $timeOutFormatted . (isset($row['time_out']) && !empty($row['time_out']) && $isOvertime ? " <span class='badge badge-warning'>Overtime</span>" : "") . "</td>";
                                        echo "<td>" . $date . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5'>No data found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add Leave Modal -->
    <div class="modal fade" id="addLeaveModal" tabindex="-1" role="dialog" aria-labelledby="addLeaveModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLeaveModalLabel">Create Leave Request</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="leaveType">Leave Type</label>
                            <select class="form-control" id="leaveType" name="leaveType">
                                <option value="Sick Leave">Sick Leave</option>
                                <option value="Vacation Leave">Vacation Leave</option>
                                <option value="Maternity Leave">Maternity Leave</option>
                                <option value="Paternity Leave">Paternity Leave</option>
                                <option value="Emergency Leave">Emergency Leave</option>
                            </select>
                        </div>
                        <div class="form-group row">
                            <div class="col">
                                <label for="startDate">Start Date</label>
                                <input type="date" class="form-control" id="startDate" name="startDate">
                            </div>
                            <div class="col">
                                <label for="endDate">End Date</label>
                                <input type="date" class="form-control" id="endDate" name="endDate">
                            </div>
                        </div>
                        <button type="submit" name="addLeave" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Leave Modal -->
    <div class="modal fade" id="editLeaveModal" tabindex="-1" role="dialog" aria-labelledby="editLeaveModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLeaveModalLabel">Edit Leave Request</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" id="edit-leaveId" name="leaveId">
                        <div class="form-group">
                            <label for="edit-username">Employee</label>
                            <input type="text" class="form-control" id="edit-username" name="username" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit-leaveType">Leave Type</label>
                            <select class="form-control" id="edit-leaveType" name="leaveType">
                                <option value="Sick Leave">Sick Leave</option>
                                <option value="Vacation Leave">Vacation Leave</option>
                                <option value="Maternity Leave">Maternity Leave</option>
                                <option value="Paternity Leave">Paternity Leave</option>
                                <option value="Emergency Leave">Emergency Leave</option>
                            </select>
                        </div>
                        <div class="form-group row">
                            <div class="col">
                                <label for="edit-startDate">Start Date</label>
                                <input type="date" class="form-control" id="edit-startDate" name="startDate">
                            </div>
                            <div class="col">
                                <label for="edit-endDate">End Date</label>
                                <input type="date" class="form-control" id="edit-endDate" name="endDate">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit-status">Status</label>
                            <select class="form-control" id="edit-status" name="status">
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Denied">Denied</option>
                            </select>
                        </div>
                        <button type="submit" name="updateLeave" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        const clock = document.querySelector('.current-time');
        const options = {hour: '2-digit', minute: '2-digit'};
        const locale = 'en-PH';
        setInterval(() => {
            const now = new Date();
            clock.textContent = now.toLocaleTimeString(locale, options);
        }, 1000);

        // Change logo name 
        const logoName = document.querySelector('.logo_name');
        logoName.textContent = 'Attendance & Leave';
        
        // Edit leave request - populate modal with leave request data
        $(document).on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            const username = $(this).data('username');
            const leaveType = $(this).data('leavetype');
            const startDate = $(this).data('startdate');
            const endDate = $(this).data('enddate');
            const status = $(this).data('status');
            
            $('#edit-leaveId').val(id);
            $('#edit-username').val(username);
            $('#edit-leaveType').val(leaveType);
            $('#edit-startDate').val(startDate);
            $('#edit-endDate').val(endDate);
            $('#edit-status').val(status);
        });
        
        // Update current time display
        setInterval(function() {
            const now = new Date();
            // Use Philippines timezone (GMT+8)
            const options = { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: true,
                timeZone: 'Asia/Manila'
            };
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-PH', options);
        }, 1000);
        
        // Handle file input display
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName || 'Choose file or take photo');
        });
        
        // QR code attendance submission
        function submitAttendance(action) {
            const qrCodeInput = document.getElementById('qr-code');
            const messageDiv = document.getElementById('qr-message');
            
            if (!qrCodeInput.files || qrCodeInput.files.length === 0) {
                messageDiv.innerHTML = '<div class="alert alert-danger">Please select a QR code image first.</div>';
                return;
            }
            
            const formData = new FormData();
            formData.append('qr_code', qrCodeInput.files[0]);
            formData.append('attendance_action', action);
            
            // Show loading message
            messageDiv.innerHTML = '<div class="alert alert-info">Processing your attendance... Please wait.</div>';
            
            fetch('attendance_check.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    
                    // Update status based on action type
                    if (action === 'time_in') {
                        const timeInStatus = document.getElementById('time-in-status');
                        const statusClass = data.status === 'late' ? 'badge-danger' : 'badge-success';
                        const statusText = data.status === 'late' ? 'Late' : 'On Time';
                        
                        timeInStatus.innerHTML = `${data.time} <span class="badge ${statusClass}">${statusText}</span>`;
                        
                        // Hide time in button, show time out button
                        document.getElementById('time-in-btn').style.display = 'none';
                        
                        // Create time out button if it doesn't exist
                        if (!document.getElementById('time-out-btn')) {
                            const timeOutBtn = document.createElement('button');
                            timeOutBtn.id = 'time-out-btn';
                            timeOutBtn.className = 'btn btn-danger';
                            timeOutBtn.onclick = function() { submitAttendance('time_out'); };
                            timeOutBtn.innerText = 'Time Out';
                            
                            document.getElementById('time-in-btn').insertAdjacentElement('afterend', timeOutBtn);
                        } else {
                            document.getElementById('time-out-btn').style.display = 'block';
                        }
                        
                        // Reload page after 2 seconds to ensure all data is refreshed
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else if (action === 'time_out') {
                        const timeOutStatus = document.getElementById('time-out-status');
                        const statusClass = data.status === 'overtime' ? 'badge-warning' : '';
                        const statusText = data.status === 'overtime' ? 'Overtime' : '';
                        
                        timeOutStatus.innerHTML = `${data.time} ${data.status === 'overtime' ? `<span class="badge ${statusClass}">${statusText}</span>` : ''}`;
                        
                        // Hide time out button
                        document.getElementById('time-out-btn').style.display = 'none';
                        
                        // Show completed message and reload page after 2 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                } else {
                    // Handle errors
                    messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    
                    // Clear file input so user can try again
                    qrCodeInput.value = '';
                    $('.custom-file-label').html('Choose file or take photo');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
                
                // Clear file input so user can try again
                qrCodeInput.value = '';
                $('.custom-file-label').html('Choose file or take photo');
            });
        }
    </script>
</body>
</html>

<?php
// Function to decrypt QR code data
function decryptQRData($qr_data) {
    // For debugging - skip first part if it's a direct test string
    if (strlen($qr_data) < 1000 && strpos($qr_data, '.') !== false) {
        // Split the data and signature
        $parts = explode('.', $qr_data);
        
        if (count($parts) !== 2) {
            return false; // Invalid format
        }
        
        $encoded_data = $parts[0];
        $received_signature = $parts[1];
        
        // Decode the base64 data
        $json_data = base64_decode($encoded_data);
        if ($json_data === false) {
            return false; // Invalid base64 data
        }
        
        // Verify the signature
        $encryption_key = 'SuperPackKey2023';
        $expected_signature = substr(hash_hmac('sha256', $json_data, $encryption_key), 0, 16);
        
        if ($received_signature !== $expected_signature) {
            return false; // Signature verification failed
        }
        
        // Parse the JSON data
        $data = json_decode($json_data, true);
        if (!$data) {
            return false; // Invalid JSON
        }
        
        // Convert back to the expected format
        $full_data = [
            'employee_id' => $data['id'] ?? '',
            'name' => $data['n'] ?? '',
            'role' => $data['r'] ?? '',
            'department' => $data['d'] ?? '',
            'timestamp' => $data['t'] ?? 0
        ];
        
        return $full_data;
    }
    
    // For binary data from an image file, we'd need a proper QR code reader library
    // For now, return false as we can't process image data directly
    return false;
}

// Check compatibility with old format
function checkAndConvertOldFormat($qr_code_data) {
    // First, check if this is binary image data
    if (strlen($qr_code_data) > 1000 || strpos($qr_code_data, "\0") !== false) {
        // This looks like binary data, not a text QR code
        // In a real implementation, you would use a QR code reading library here
        
        // For now, we'll simulate finding the employee data from a test QR code
        // This would be replaced with actual QR code reading in production
        global $conn;
        
        // Get currently logged in username
        $username = '';
        if (isset($_SESSION['name'])) {
            $username = $_SESSION['name'];
        } elseif (isset($_SESSION['username'])) {
            $username = $_SESSION['username'];
        }
        
        // Get employee data for the currently logged in user (for testing only)
        // In production, you would extract this from the QR code image
        if (!empty($username)) {
            $stmt = $conn->prepare("SELECT * FROM register WHERE name = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return [
                    'employee_id' => $row['employee_id'] ?? '',
                    'name' => $row['name'] ?? '',
                    'role' => $row['role'] ?? '',
                    'department' => $row['department'] ?? '',
                    'timestamp' => time()
                ];
            }
        }
        
        return false;
    }
    
    // Try the new format first
    $data = decryptQRData($qr_code_data);
    
    // If it fails, try the old format
    if ($data === false) {
        // Try to decrypt using the old method
        $encryption_key = 'SuperPackEnterpriseSecretKey2023';
        $decrypted_data = @openssl_decrypt(
            $qr_code_data,
            'AES-256-CBC',
            $encryption_key,
            0,
            substr(md5($encryption_key), 0, 16)
        );
        
        if ($decrypted_data !== false) {
            // Parse the JSON data
            $old_data = json_decode($decrypted_data, true);
            if ($old_data) {
                return $old_data;
            }
        }
        return false;
    }
    
    return $data;
} 