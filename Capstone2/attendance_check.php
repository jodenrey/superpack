<?php
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'User';

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
                                            $timeOut = strtotime($row['time_out']);
                                            $timeOutFormatted = date('h:i:s A', $timeOut);
                                            
                                            // Get the date part from time_out
                                            $datePartOut = date('Y-m-d', $timeOut);
                                            
                                            // Check if overtime (after 5pm)
                                            $fivePM = strtotime($datePartOut . ' 17:00:00');
                                            $isOvertime = $timeOut > $fivePM;
                                            
                                            // Time out class based on overtime
                                            $timeOutClass = $isOvertime ? 'text-warning font-weight-bold' : '';
                                        }
                                        
                                        // Format date for display
                                        $date = date('Y-m-d', $timeIn);
                                        
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
    </script>
</body>
</html> 