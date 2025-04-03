<?php
session_start();

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';

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


// Define name
$name = isset($_GET['name']) ? $_GET['name'] : '';

// Initialize search ID
$searchId = isset($_GET['search_id']) ? $_GET['search_id'] : '';

// Base query to retrieve tasks from the database
$query = "SELECT * FROM payroll_records";

// If search_id is provided, add WHERE clause with LIKE
if (!empty($searchId)) {
    $query .= " WHERE name LIKE ?";
}
// Prepare the SQL statement
$stmt = $conn->prepare($query);

// Check if the statement was prepared successfully
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

// Bind the search ID with wildcard if a search term is provided
if (!empty($searchId)) {
    $searchTerm = '%' . $searchId . '%';
    $stmt->bind_param("s", $searchTerm);
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);

// Handling form submissions for adding, editing, and deleting tasks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new task
    if (isset($_POST['addPayroll'])) {

        if ($otPayResult->num_rows > 0) {
            $otPayRow = $otPayResult->fetch_assoc();
            $otPay = floatval($otPayRow['ot_pay']);
        } else {
            $otPay = 0; // Default value if no record is found
        }

        // for late deduct
        $lateDeductQuery = "SELECT late_deduct FROM additional_pay LIMIT 1";
        $lateDeductResult = $conn->query($lateDeductQuery);

        if ($lateDeductResult->num_rows > 0) {
            $lateDeductRow = $lateDeductResult->fetch_assoc();
            $lateDeduct = floatval($lateDeductRow['late_deduct']);
        } else {
            $lateDeduct = 0; // Default value if no record is found
        }

        $adjusted_basic_pay = round(floatval($_POST['basic_pay']) / 15, 2) * 15;
        $total_deduct = (floatval($lateDeduct) * $_POST['late_deduct']) + (floatval($_POST['sss_deduct']) + floatval($_POST['pagibig_deduct']));
        // Fetch the ot_pay from the additional_pay table
        $otPayQuery = "SELECT ot_pay FROM additional_pay LIMIT 1";
        $otPayResult = $conn->query($otPayQuery);

        
        $newTask = [
            'name' => $_POST['name'],
            'position' => $_POST['position'],
            'salary' => floatval($_POST['salary']), // Convert to float
            'daily_rate' => round(floatval($_POST['basic_pay']) / 15, 2), // Calculate daily rate as basic pay divided by 15 days and round to 2 decimal places
            'basic_pay' => floatval($_POST['basic_pay']), // Convert to float
            'ot_pay' => floatval($_POST['ot_pay']), // Convert to float
            'late_deduct' => floatval($_POST['late_deduct']), // Convert to float
            'gross_pay' => floatval($_POST['gross_pay']), // Convert to float
            'sss_deduct' => floatval($_POST['sss_deduct']), // Convert to float
            'pagibig_deduct' => floatval($_POST['pagibig_deduct']), // Convert to float
            'total_deduct' => $total_deduct, // Calculate and convert to float
            'net_salary' => ($adjusted_basic_pay - 0.05) + (floatval($_POST['ot_pay']) * $otPay) - $total_deduct, // Calculate and convert to float
            'date_created' => date('Y-m-d')
        ];

        // prepare and bind
        $stmt = $conn->prepare("INSERT INTO payroll_records (name, position, salary, daily_rate, basic_pay, ot_pay, late_deduct, gross_pay, sss_deduct, pagibig_deduct, total_deduct, net_salary, date_created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdddddddddds", $newTask['name'], $newTask['position'], $newTask['salary'], $newTask['daily_rate'], $newTask['basic_pay'], $newTask['ot_pay'], $newTask['late_deduct'], $newTask['gross_pay'], $newTask['sss_deduct'], $newTask['pagibig_deduct'], $newTask['total_deduct'], $newTask['net_salary'], $newTask['date_created']);
        $stmt->execute();

    }

    // Edit a task
    if (isset($_POST['editPayroll'])) {
        $editTask = [
            'id' => $_POST['id'],
            'name' => $_POST['name'],
            'position' => $_POST['position'],
            'salary' => floatval($_POST['salary']), // Convert to float
            'daily_rate' => floatval($_POST['basic_pay']) / 15, // Calculate daily rate as basic pay divided by 15 days
            'basic_pay' => floatval($_POST['basic_pay']), // Convert to float
            'ot_pay' => floatval($_POST['ot_pay']), // Convert to float
            'late_deduct' => floatval($_POST['late_deduct']), // Convert to float
            'gross_pay' => floatval($_POST['gross_pay']), // Convert to float
            'sss_deduct' => floatval($_POST['sss_deduct']), // Convert to float
            'pagibig_deduct' => floatval($_POST['pagibig_deduct']), // Convert to float
            'total_deduct' => floatval($_POST['sss_deduct']) + floatval($_POST['pagibig_deduct']), // Calculate and convert to float
            'net_salary' => floatval($_POST['basic_pay']) + floatval($_POST['ot_pay']) - (floatval($_POST['total_deduct'])), // Calculate and convert to float
            'date_created' => date('Y-m-d')
            ];

        // prepare and bind
        $stmt = $conn->prepare("UPDATE payroll_records SET name = ?, position = ?, salary = ?, daily_rate = ?, basic_pay = ?, ot_pay = ?, late_deduct = ?, gross_pay = ?, sss_deduct = ?, pagibig_deduct = ?, total_deduct = ?, net_salary = ?, date_created = ? WHERE id = ?");
        $stmt->bind_param("ssddddddddddsi", $editTask['name'], $editTask['position'], $editTask['salary'], $editTask['daily_rate'], $editTask['basic_pay'], $editTask['ot_pay'], $editTask['late_deduct'], $editTask['gross_pay'], $editTask['sss_deduct'], $editTask['pagibig_deduct'],  $editTask['total_deduct'], $editTask['net_salary'], $editTask['date_created'], $editTask['id']);
        $stmt->execute();
        
    }
    // Delete a task
    if (isset($_POST['deleteTask'])) {
        // Get the task IDs from the form
        $taskIds = $_POST['task_checkbox'];

        // Create a string with the same number of placeholders as the number of task IDs
        $placeholders = rtrim(str_repeat('?, ', count($taskIds)), ', ');

        // Prepare the SQL statement
        $stmt = $conn->prepare("DELETE FROM payroll_records WHERE id IN ($placeholders)");

        // Bind the task IDs to the placeholders
        $stmt->bind_param(str_repeat('i', count($taskIds)), ...$taskIds);

        // Execute the statement
        $stmt->execute();
    }

    if (isset($_POST['addPay'])) {
        $additional_pay = [
            'ot_pay' => $_POST['ot_pay'],
            'late_deduct' => $_POST['late_deduct'],
            'date_created' => date('Y-m-d')
        ];

        $stmt = $conn->prepare("INSERT INTO additional_pay (ot_pay, late_deduct, date_created) VALUES (?, ?, ?)");
        $stmt->bind_param("dds", $additional_pay['ot_pay'], $additional_pay['late_deduct'], $additional_pay['date_created']);
        $stmt->execute();
    }

    if (isset($_POST['editPay'])) {
        $editPay = [
            'id' => 1,
            'ot_pay' => $_POST['ot_pay'],
            'late_deduct' => $_POST['late_deduct'],
            'date_created' => date('Y-m-d')
        ];
    
        // Correct SQL with a placeholder for id
        $stmt = $conn->prepare("UPDATE additional_pay SET ot_pay = ?, late_deduct = ?, date_created = ? WHERE id = ?");
        
        if ($stmt) {
            // Bind the parameters: 'd' for double, 's' for string, 'i' for integer
            $stmt->bind_param("ddsi", $editPay['ot_pay'], $editPay['late_deduct'], $editPay['date_created'], $editPay['id']);
            $stmt->execute();
        } else {
            // Output an error if the statement failed to prepare
            echo "Error preparing statement: " . $conn->error;
        }
    }
    

    header('Location: payroll.php');
    exit();

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <title>Payroll</title>
    <link rel="stylesheet" href="style_index.css">
    <link rel="icon" type="image/x-icon" href="Superpack-Enterprise-Logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboardnew.css">
    <style>
    </style>

    
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
</head>
<body>
    <?php include 'sidebar_small.php'; ?>
    <div class="container-everything" style="height:100%;">
            <div class="container-all">
                <div class="container-top">
                    <?php include 'header_2.php'; ?>
                </div>
                <div class="container-search">
                    <div class="table-container">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>OT Pay</th>
                                    <th>Late deduct (In Hours)</th>
                                    <th>Date Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch payroll records from the database
                                $query = "SELECT * FROM additional_pay";
                                $result = $conn->query($query);

                                if ($result->num_rows > 0) {
                                    // Output data of each row
                                    while($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['ot_pay']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['late_deduct']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['date_created']) . "</td>";
                                        echo "<td><button class='btn btn-primary' data-toggle='modal' data-target='#editPayModal' data-id='" . htmlspecialchars($row['id']) . "'>Edit</button></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center'><button class='btn btn-primary' data-toggle='modal' data-target='#addPayModal'>Set a Value</button></td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div style="border-top:5px solid #131313; width:100%; height:1px;"></div>
                <div class="container-search"  style="height:100%;">
                    <div class="search-bar">
                        <form method="GET" action="" class="form-inline">
                            <div class="input-group mb-3 flex-grow-1">
                                <!-- Search input and button -->
                                <input type="hidden" name="department" value="<?php echo htmlspecialchars($department); ?>">
                                <input type="text" class="form-control" name="search_id" placeholder="Search by ID" value="<?php echo htmlspecialchars($searchId); ?>"style="border-radius: 10px 0 0 10px; border: 3px solid #131313; height:42px;">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit" style="border-radius: 0; border: 3px solid #131313;">Search</button>
                                </div>
                            </div>
                            <button class="btn btn-primary mb-3" type="button" data-toggle="modal" data-target="#addPayrollModal" style="border-radius: 0 10px 10px 0; border: 3px solid #131313;">Add Record</button>
                        </form>
                    </div>
                    <div class="tool-bar">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div style="color: #FFFAFA;">
                                <span id="selected-count">0</span> items selected
                            </div>
                            
                            <div class="d-flex align-items-center" style="gap:10px;">
                                
                                <!-- Start the form for deletion -->
                                <form method="POST" id="deleteForm" style="display:inline;">
                                    <button type="submit" name="deleteTask" class="btn btn-danger" disabled>Del</button>
                                </form>
                                <button class="btn btn-primary" name="editTaskMod" data-toggle="modal" data-target="#editTaskModal" disabled data-id="<?php echo $task['id']; ?>">Edit</button>
                                
                                <button class="btn btn-info" onclick="window.location.href='payroll.php'">Reset</button>
                            </div>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th class="checkbox-col"></th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Salary</th>
                                    <th>Daily Rate</th>
                                    <th>Basic Pay</th>
                                    <th>OT Pay (In Hours)</th>
                                    <th>Late deduct (In Hours)</th>
                                    <th>Gross Pay</th>
                                    <th>SSS deduct</th>
                                    <th>Pag-IBIG deduct</th>
                                    <th>Total deduct</th>
                                    <th>Net Salary</th>
                                    <th>Date Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($tasks as $row): ?>
                                <tr>
                                    <td><input type="checkbox" id="chkbx" name="task_checkbox[]" form="deleteForm" value="<?php echo $row['id']; ?>" onclick="updateSelectedCount(this)">
                                    </td>
                                    
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['position']); ?></td>
                                    <td><?php echo htmlspecialchars($row['salary']); ?></td>
                                    <td><?php echo htmlspecialchars($row['daily_rate']); ?></td>
                                    <td><?php echo htmlspecialchars($row['basic_pay']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ot_pay']); ?></td>
                                    <td><?php echo htmlspecialchars($row['late_deduct']); ?></td>
                                    <td><?php echo htmlspecialchars($row['gross_pay']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sss_deduct']); ?></td>
                                    <td><?php echo htmlspecialchars($row['pagibig_deduct']); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_deduct']); ?></td>
                                    <td><?php echo htmlspecialchars($row['net_salary']); ?></td>
                                    <td><?php echo htmlspecialchars($row['date_created']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info view-payslip" 
                                            data-id="<?php echo $row['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                            data-position="<?php echo htmlspecialchars($row['position']); ?>"
                                            data-salary="<?php echo htmlspecialchars($row['salary']); ?>"
                                            data-basicpay="<?php echo htmlspecialchars($row['basic_pay']); ?>"
                                            data-otpay="<?php echo htmlspecialchars($row['ot_pay']); ?>"
                                            data-latededuct="<?php echo htmlspecialchars($row['late_deduct']); ?>"
                                            data-grosspay="<?php echo htmlspecialchars($row['gross_pay']); ?>"
                                            data-sssdeduct="<?php echo htmlspecialchars($row['sss_deduct']); ?>"
                                            data-pagibigdeduct="<?php echo htmlspecialchars($row['pagibig_deduct']); ?>"
                                            data-totaldeduct="<?php echo htmlspecialchars($row['total_deduct']); ?>"
                                            data-netsalary="<?php echo htmlspecialchars($row['net_salary']); ?>"
                                            data-date="<?php echo htmlspecialchars($row['date_created']); ?>">
                                            <i class="fa fa-file-text-o"></i> Payslip
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
    </div>
    <!-- Additional Pay Modal -->
    <div class="modal fade" id="addPayModal" tabindex="-1" role="dialog" aria-labelledby="addPayModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPayModalLabel">Set New Values</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="ot_pay">OT Pay</label>
                            <input type="number" class="form-control" id="ot_pay" name="ot_pay" required>
                        </div>
                        <div class="form-group">
                            <label for="late_deduct">Late deduct (In Hours)</label>
                            <input type="number" class="form-control" id="late_deduct" name="late_deduct" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="addPay" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Edit Additional Pay Modal -->
    <div class="modal fade" id="editPayModal" tabindex="-1" role="dialog" aria-labelledby="editPayModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPayModalLabel">Edit Additional Pay</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_pay_id" name="id">
                        <div class="form-group">
                            <label for="edit_ot_pay">OT Pay</label>
                            <input type="number" class="form-control" id="edit_ot_pay" name="ot_pay" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_late_deduct">Late Deduct (In Hours)</label>
                            <input type="number" class="form-control" id="edit_late_deduct" name="late_deduct" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="editPay" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add Payroll Records Modal -->
    <div class="modal fade" id="addPayrollModal" tabindex="-1" role="dialog" aria-labelledby="addPayrollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPayrollModalLabel">New Payroll Record</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="position">Position</label>
                                    <input type="text" class="form-control" id="position" name="position" required>
                                </div>
                                <div class="form-group">
                                    <label for="salary">Monthly Salary</label>
                                    <input type="number" step="0.01" class="form-control" id="salary" name="salary" required>
                                </div>
                                <input type="hidden" name="daily_rate" value="">
                                <div class="form-group">
                                    <label for="basic_pay">Basic Pay</label>
                                    <input type="number" step="0.01" class="form-control" id="basic_pay" name="basic_pay" required>
                                </div>
                                <div class="form-group">
                                    <label for="ot_pay">Overtime Hours</label>
                                    <input type="number" step="0.01" class="form-control" id="ot_pay" name="ot_pay" value="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="late_deduct">Late Hours (Optional)</label>
                                    <input type="number" step="0.01" class="form-control" id="late_deduct" name="late_deduct" value="0">
                                </div>
                                <div class="form-group">
                                    <label for="gross_pay">Gross Pay</label>
                                    <input type="number" step="0.01" class="form-control" id="gross_pay" name="gross_pay" required>
                                </div>
                                <div class="form-group">
                                    <label for="sss_deduct">SSS deduction (Optional)</label>
                                    <input type="number" step="0.01" class="form-control" id="sss_deduct" name="sss_deduct" value="0">
                                </div>
                                <div class="form-group">
                                    <label for="pagibig_deduct">Pag-IBIG deduction (Optional)</label>
                                    <input type="number" step="0.01" class="form-control" id="pagibig_deduct" name="pagibig_deduct" value="0">
                                </div>
                                <input type="hidden" name="net_salary" value="">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="addPayroll" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Edit Payroll Records Modal -->
    <div class="modal fade" id="editTaskModal" tabindex="-1" role="dialog" aria-labelledby="editTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTaskModalLabel">Edit Payroll Record</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <input type="hidden" id="edit_id" name="id">
                                <div class="form-group">
                                    <label for="edit_name">Name</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_position">Position</label>
                                    <input type="text" class="form-control" id="edit_position" name="position" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_salary">Salary</label>
                                    <input type="number" class="form-control" id="edit_salary" name="salary" required>
                                </div>
                                <input type="hidden" name="daily_rate" value="">
                                <div class="form-group">
                                    <label for="edit_basic_pay">Basic Pay</label>
                                    <input type="number" class="form-control" id="edit_basic_pay" name="basic_pay" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_ot_pay">Overtime Pay (In Hours)</label>
                                    <input type="number" class="form-control" id="edit_ot_pay" name="ot_pay" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_late_deduct">Late deduct (In Hours)</label>
                                    <input type="number" class="form-control" id="edit_late_deduct" name="late_deduct" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_gross_pay">Gross Pay</label>
                                    <input type="number" class="form-control" id="edit_gross_pay" name="gross_pay" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_sss_deduct">SSS deduct</label>
                                    <input type="number" class="form-control" id="edit_sss_deduct" name="sss_deduct" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_pagibig_deduct">Pag-IBIG deduct</label>
                                    <input type="number" class="form-control" id="edit_pagibig_deduct" name="pagibig_deduct" required>
                                </div>
                                <div class="form-group">
                                    <label for="edit_total_deduct">Total Deductions</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_total_deduct" name="total_deduct" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="edit_net_salary">Net Salary</label>
                                    <input type="number" step="0.01" class="form-control" id="edit_net_salary" name="net_salary" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="editPayroll" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Payslip Modal -->
    <div class="modal fade" id="payslipModal" tabindex="-1" role="dialog" aria-labelledby="payslipModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="payslipModalLabel">Employee Payslip</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="payslip-content">
                    <div class="container" id="printable-payslip">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <img src="Superpack-Enterprise-Logo.png" alt="Company Logo" style="max-height: 80px;">
                            </div>
                            <div class="col-md-6 text-right">
                                <h3>SUPERPACK ENTERPRISE</h3>
                                <p>Employee Payslip</p>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <strong>Employee:</strong> <span id="payslip-name"></span><br>
                                <strong>Position:</strong> <span id="payslip-position"></span><br>
                                <strong>Monthly Salary:</strong> ₱<span id="payslip-salary"></span><br>
                            </div>
                            <div class="col-md-6 text-right">
                                <strong>Payslip Date:</strong> <span id="payslip-date"></span><br>
                                <strong>Pay Period:</strong> <span id="payslip-period"></span><br>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th colspan="2">Earnings</th>
                                            <th colspan="2">Deductions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Basic Pay</td>
                                            <td class="text-right">₱<span id="payslip-basicpay"></span></td>
                                            <td>SSS</td>
                                            <td class="text-right">₱<span id="payslip-sss"></span></td>
                                        </tr>
                                        <tr>
                                            <td>Overtime Pay</td>
                                            <td class="text-right">₱<span id="payslip-otpay"></span></td>
                                            <td>Pag-IBIG</td>
                                            <td class="text-right">₱<span id="payslip-pagibig"></span></td>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td>Late Deduction</td>
                                            <td class="text-right">₱<span id="payslip-late"></span></td>
                                        </tr>
                                        <tr>
                                            <th>Gross Pay</th>
                                            <th class="text-right">₱<span id="payslip-gross"></span></th>
                                            <th>Total Deductions</th>
                                            <th class="text-right">₱<span id="payslip-deductions"></span></th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body bg-light text-center">
                                        <h4>Net Salary: ₱<span id="payslip-net"></span></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-5">
                            <div class="col-md-6">
                                <p>________________________</p>
                                <p>Employee Signature</p>
                            </div>
                            <div class="col-md-6 text-right">
                                <p>________________________</p>
                                <p>Authorized Signature</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="print-payslip">Print Payslip</button>
                    <button type="button" class="btn btn-success" id="download-payslip">Download PDF</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Include html2pdf library for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
        logoName.textContent = 'Payroll';

        function updateSelectedCount() {
            var selectedCount = document.querySelectorAll('input[name="task_checkbox[]"]:checked').length;
            document.getElementById('selected-count').textContent = selectedCount;

            // Toggle buttons based on the number of selected checkboxes
            toggleButtons(selectedCount);
        }

        // Function to toggle the delete and edit buttons
        function toggleButtons(selectedCount) {
            // Get the delete and edit buttons
            var deleteButton = document.querySelector('button[name="deleteTask"]');
            var editButton = document.querySelector('button[name="editTaskMod"]');

            // Enable delete button if at least one checkbox is selected
            deleteButton.disabled = selectedCount === 0;

            // Enable edit button only if exactly one checkbox is selected
            editButton.disabled = selectedCount !== 1;
        }

        // Attach event listeners to all checkboxes
        document.querySelectorAll('input[name="task_checkbox[]"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                updateSelectedCount();
            });
        });

        // Calculate and update total deductions and net salary in edit modal
        function updateTotalsInEditModal() {
            const sssDeduct = parseFloat(document.getElementById('edit_sss_deduct').value) || 0;
            const pagibigDeduct = parseFloat(document.getElementById('edit_pagibig_deduct').value) || 0;
            const lateDeduct = parseFloat(document.getElementById('edit_late_deduct').value) || 0;
            const basicPay = parseFloat(document.getElementById('edit_basic_pay').value) || 0;
            const otPay = parseFloat(document.getElementById('edit_ot_pay').value) || 0;
            
            // Get the rate of OT per hour from the additional_pay table
            // For now, using a fixed value as we need AJAX to get it dynamically
            let otRatePerHour = 100; // Default value
            
            // Calculate totals
            const totalDeductions = sssDeduct + pagibigDeduct + lateDeduct;
            const netSalary = basicPay + (otPay * otRatePerHour) - totalDeductions;
            
            // Update fields
            document.getElementById('edit_total_deduct').value = totalDeductions.toFixed(2);
            document.getElementById('edit_net_salary').value = netSalary.toFixed(2);
        }

        // Add event listeners to fields that affect calculations
        document.getElementById('edit_sss_deduct').addEventListener('input', updateTotalsInEditModal);
        document.getElementById('edit_pagibig_deduct').addEventListener('input', updateTotalsInEditModal);
        document.getElementById('edit_late_deduct').addEventListener('input', updateTotalsInEditModal);
        document.getElementById('edit_basic_pay').addEventListener('input', updateTotalsInEditModal);
        document.getElementById('edit_ot_pay').addEventListener('input', updateTotalsInEditModal);

        // Payslip functionality
        $(document).ready(function() {
            // Open payslip modal with employee data
            $('.view-payslip').click(function() {
                const employeeData = {
                    name: $(this).data('name'),
                    position: $(this).data('position'),
                    salary: $(this).data('salary'),
                    basicPay: $(this).data('basicpay'),
                    otPay: $(this).data('otpay'),
                    lateDeduct: $(this).data('latededuct'),
                    grossPay: $(this).data('grosspay'),
                    sssDeduct: $(this).data('sssdeduct'),
                    pagibigDeduct: $(this).data('pagibigdeduct'),
                    totalDeduct: $(this).data('totaldeduct'),
                    netSalary: $(this).data('netsalary'),
                    date: $(this).data('date')
                };
                
                // Fill the payslip with employee data
                $('#payslip-name').text(employeeData.name);
                $('#payslip-position').text(employeeData.position);
                $('#payslip-salary').text(employeeData.salary);
                $('#payslip-date').text(employeeData.date);
                
                // Calculate pay period (assuming mid-month to mid-month)
                const payDate = new Date(employeeData.date);
                const startDate = new Date(payDate);
                startDate.setDate(1); // First day of the month
                const endDate = new Date(payDate.getFullYear(), payDate.getMonth() + 1, 0); // Last day of the month
                
                const formatDate = (date) => {
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                };
                
                $('#payslip-period').text(`${formatDate(startDate)} - ${formatDate(endDate)}`);
                
                // Fill in the earnings and deductions
                $('#payslip-basicpay').text(parseFloat(employeeData.basicPay).toFixed(2));
                $('#payslip-otpay').text(parseFloat(employeeData.otPay).toFixed(2));
                $('#payslip-gross').text(parseFloat(employeeData.grossPay).toFixed(2));
                $('#payslip-sss').text(parseFloat(employeeData.sssDeduct).toFixed(2));
                $('#payslip-pagibig').text(parseFloat(employeeData.pagibigDeduct).toFixed(2));
                $('#payslip-late').text(parseFloat(employeeData.lateDeduct).toFixed(2));
                $('#payslip-deductions').text(parseFloat(employeeData.totalDeduct).toFixed(2));
                $('#payslip-net').text(parseFloat(employeeData.netSalary).toFixed(2));
                
                // Show the modal
                $('#payslipModal').modal('show');
            });
            
            // Print payslip
            $('#print-payslip').click(function() {
                const printContent = document.getElementById('printable-payslip').innerHTML;
                const originalContent = document.body.innerHTML;
                
                document.body.innerHTML = `
                    <html>
                    <head>
                        <title>Payslip - ${$('#payslip-name').text()}</title>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .container { width: 100%; max-width: 800px; margin: 0 auto; padding: 20px; }
                            .row { display: flex; flex-wrap: wrap; margin-bottom: 20px; }
                            .col-md-6 { width: 50%; }
                            .text-right { text-align: right; }
                            table { width: 100%; border-collapse: collapse; }
                            table, th, td { border: 1px solid #ddd; padding: 8px; }
                            th { background-color: #f2f2f2; }
                            .card { border: 1px solid #ddd; border-radius: 4px; padding: 10px; }
                            .bg-light { background-color: #f8f9fa; }
                            .text-center { text-align: center; }
                            .mt-4 { margin-top: 20px; }
                            .mt-5 { margin-top: 30px; }
                            img { max-height: 80px; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            ${printContent}
                        </div>
                    </body>
                    </html>
                `;
                
                window.print();
                document.body.innerHTML = originalContent;
                location.reload();
            });
            
            // Download payslip as PDF
            $('#download-payslip').click(function() {
                const element = document.getElementById('printable-payslip');
                const employeeName = $('#payslip-name').text();
                const payDate = $('#payslip-date').text();
                const fileName = `Payslip_${employeeName.replace(/\s+/g, '_')}_${payDate}.pdf`;
                
                const opt = {
                    margin: 10,
                    filename: fileName,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };
                
                // Generate and download the PDF
                html2pdf().set(opt).from(element).save();
            });
            
            // Function to handle when Edit Task Modal is shown
            $('#editTaskModal').on('show.bs.modal', function (event) {
                const button = $(event.relatedTarget);
                const taskId = button.data('id');
                
                // Find the row with the matching task ID
                const checkbox = document.querySelector(`input[name="task_checkbox[]"][value="${taskId}"]`);
                if (checkbox) {
                    const row = checkbox.closest('tr');
                    const cells = row.querySelectorAll('td');
                    
                    // Fill the edit form with the task data
                    document.getElementById('edit_id').value = taskId;
                    document.getElementById('edit_name').value = cells[1].textContent.trim();
                    document.getElementById('edit_position').value = cells[2].textContent.trim();
                    document.getElementById('edit_salary').value = cells[3].textContent.trim();
                    document.getElementById('edit_basic_pay').value = cells[5].textContent.trim();
                    document.getElementById('edit_ot_pay').value = cells[6].textContent.trim();
                    document.getElementById('edit_late_deduct').value = cells[7].textContent.trim();
                    document.getElementById('edit_gross_pay').value = cells[8].textContent.trim();
                    document.getElementById('edit_sss_deduct').value = cells[9].textContent.trim();
                    document.getElementById('edit_pagibig_deduct').value = cells[10].textContent.trim();
                    document.getElementById('edit_total_deduct').value = cells[11].textContent.trim();
                    document.getElementById('edit_net_salary').value = cells[12].textContent.trim();
                }
                
                // Update total deductions and net salary calculations
                updateTotalsInEditModal();
            });
        });
    </script>
</body>
</html>
