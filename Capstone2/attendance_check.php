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

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addLeave'])) {
        $leave = [
            'leaveType' => $_POST['leaveType'],
            'startDate' => $_POST['startDate'],
            'endDate' => $_POST['endDate'],
            'status' => 'Pending',
        ];

        $stmt = $conn->prepare("INSERT INTO leave_request (leave_type, start_date, end_date, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $leave['leaveType'], $leave['startDate'], $leave['endDate'], $leave['status']);
        $stmt->execute();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style_index.css">
    <link rel="icon" type="image/x-icon" href="Superpack-Enterprise-Logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboardnew.css">
    <style>
        
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
                                <th>Leave Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM leave_request";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr><td>" . $row['leave_type'] . "</td><td>" . $row['start_date'] . "</td><td>" . $row['end_date'] . "</td><td>" . $row['status'] . "</td></tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>No leave requests found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="container-bottom">
                <div class="container-table">
                    <div class="table-container">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Time In (Breaks)</th>
                                    <th>Time Out (Breaks)</th>
                                    <th>Time In (Lunch)</th>
                                    <th>Time Out (Lunch)</th>
                                    <th>End of Day</th>
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
                                        $timeIn = date('h:i:s A', strtotime($row['time_in']));
                                        $timeOut = isset($row['time_out']) ? date('h:i:s A', strtotime($row['time_out'])) : 'Not clocked out';
                                        $date = date('Y-m-d', strtotime($row['time_in']));
                                        echo "<tr><td>" . $row['name'] . "</td><td>" . $row['role'] . "</td><td>" . $timeIn . "</td><td>" . $timeOut .  "</td><td>" . "None" . "</td><td>" . $date . "</td></tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8'>No data found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
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
        logoName.textContent = 'Attendance Check';
    </script>
</body>
</html>
