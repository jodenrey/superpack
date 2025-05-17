<?php
session_start();
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';
$department = isset($_SESSION['user_department']) ? $_SESSION['user_department'] : 'Superpack Enterprise';

// Database connection details
$host = "localhost";
$user = "root";
$password = "password";
$database = "superpack_database";
$port = 3306;

// Connect to database
$conn = new mysqli($host, $user, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize search ID
$searchId = isset($_GET['search_id']) ? $_GET['search_id'] : '';

// Get employee data from the database
$query = "SELECT * FROM employee_records";
if (!empty($searchId)) {
    $query .= " WHERE id LIKE ? OR name LIKE ?";
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
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();
$employees = $result->fetch_all(MYSQLI_ASSOC);

// Create warning_notices table if it doesn't exist
$createTableQuery = "CREATE TABLE IF NOT EXISTS warning_notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50),
    date_of_warning DATE,
    employee_name VARCHAR(100),
    department VARCHAR(100),
    location VARCHAR(100),
    violation_attendance TINYINT(1),
    violation_procedures TINYINT(1),
    violation_insubordination TINYINT(1),
    violation_carelessness TINYINT(1),
    violation_company_policies TINYINT(1),
    violation_damage TINYINT(1),
    violation_other TEXT,
    warning_level VARCHAR(50),
    employer_statement TEXT,
    employee_statement TEXT,
    consequence_verbal TINYINT(1),
    consequence_written TINYINT(1),
    consequence_probation TINYINT(1),
    consequence_suspension TINYINT(1),
    consequence_dismissal TINYINT(1),
    consequence_other TEXT,
    employee_initials VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createTableQuery);

// Process form submission for warning notices
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addWarning'])) {
        // Get form data
        $employee_id = $_POST['employee_id'];
        $date_of_warning = $_POST['date_of_warning'];
        $employee_name = $_POST['employee_name'];
        $employee_department = $_POST['department'];
        $location = $_POST['location'];
        
        // Violation types
        $violation_attendance = isset($_POST['violation_attendance']) ? 1 : 0;
        $violation_procedures = isset($_POST['violation_procedures']) ? 1 : 0;
        $violation_insubordination = isset($_POST['violation_insubordination']) ? 1 : 0;
        $violation_carelessness = isset($_POST['violation_carelessness']) ? 1 : 0;
        $violation_company_policies = isset($_POST['violation_company_policies']) ? 1 : 0;
        $violation_damage = isset($_POST['violation_damage']) ? 1 : 0;
        $violation_other = isset($_POST['violation_other']) ? $_POST['other_violation_details'] : '';
        
        // Warning level
        $warning_level = $_POST['warning_level'];
        
        // Statements
        $employer_statement = $_POST['employer_statement'];
        $employee_statement = $_POST['employee_statement'];
        
        // Consequences
        $consequence_verbal = isset($_POST['consequence_verbal']) ? 1 : 0;
        $consequence_written = isset($_POST['consequence_written']) ? 1 : 0;
        $consequence_probation = isset($_POST['consequence_probation']) ? 1 : 0;
        $consequence_suspension = isset($_POST['consequence_suspension']) ? 1 : 0;
        $consequence_dismissal = isset($_POST['consequence_dismissal']) ? 1 : 0;
        $consequence_other = isset($_POST['consequence_other']) ? $_POST['other_consequence_details'] : '';
        
        // Employee initials
        $employee_initials = $_POST['employee_initials'];
        
        // Prepare SQL statement to insert data
        $sql = "INSERT INTO warning_notices (
            employee_id, date_of_warning, employee_name, department, location, 
            violation_attendance, violation_procedures, violation_insubordination, 
            violation_carelessness, violation_company_policies, violation_damage, violation_other,
            warning_level, employer_statement, employee_statement,
            consequence_verbal, consequence_written, consequence_probation, 
            consequence_suspension, consequence_dismissal, consequence_other,
            employee_initials
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssiiiiisssssiiiiiis", 
            $employee_id, $date_of_warning, $employee_name, $employee_department, $location,
            $violation_attendance, $violation_procedures, $violation_insubordination, 
            $violation_carelessness, $violation_company_policies, $violation_damage, $violation_other,
            $warning_level, $employer_statement, $employee_statement,
            $consequence_verbal, $consequence_written, $consequence_probation, 
            $consequence_suspension, $consequence_dismissal, $consequence_other,
            $employee_initials
        );
        
        if ($stmt->execute()) {
            // Redirect to a success page or display a success message
            $success_message = "Warning notice saved successfully!";
        } else {
            // Display error message
            $error_message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Get existing warnings for the warning history tab
$warningsQuery = "SELECT * FROM warning_notices";

// If user is not admin, only show warnings for the current user
if ($role !== 'Admin') {
    // Get current logged in username
    $current_username = isset($_SESSION['name']) ? $_SESSION['name'] : 
                       (isset($_SESSION['username']) ? $_SESSION['username'] : '');
    
    // Only show warnings for the current employee
    $warningsQuery .= " WHERE employee_name LIKE ?";
    $stmt = $conn->prepare($warningsQuery);
    $searchPattern = "%" . $current_username . "%";
    $stmt->bind_param("s", $searchPattern);
    $stmt->execute();
    $warningsResult = $stmt->get_result();
} else {
    // Admin can see all warnings
    $warningsQuery .= " ORDER BY date_of_warning DESC";
$warningsResult = $conn->query($warningsQuery);
}

$warnings = [];
if ($warningsResult) {
    $warnings = $warningsResult->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Warning Notice</title>
    <link rel="stylesheet" href="style_index.css">
    <link rel="icon" type="image/x-icon" href="Superpack-Enterprise-Logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="dashboardnew.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .warning-form {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .section-title {
            background-color: #64A651;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .checkbox-group {
            margin-bottom: 10px;
        }
        
        .checkbox-group label {
            display: inline-block;
            margin-left: 5px;
        }
        
        .warning-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
        }
        
        .warning-1 {
            background-color: #ffc107;
        }
        
        .warning-2 {
            background-color: #fd7e14;
        }
        
        .warning-3 {
            background-color: #dc3545;
        }
        
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        /* Fix for white space below tables */
        .container-everything {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container-all {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .container-bottom {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .container-table {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .tab-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .tab-pane {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .table-container {
            flex: 1;
            background-color: #FFFFFF;
        }
        
        .table-bordered, .table-striped {
            background-color: #FFFFFF;
        }
        
        /* Ensure table headers have good contrast */
        .table thead th {
            background-color: #f8f9fa;
            color: #212529;
        }
        
        /* Style table rows for better readability */
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.05);
        }
        
        /* Ensure text in table is visible */
        .table {
            color: #212529;
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            .print-section, .print-section * {
                visibility: visible;
            }
            .print-section {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_small.php'; ?>
    
    <div class="container-everything">
        <div class="container-all">
            <div class="container-top">
                <?php include 'header_2.php'; ?>
            </div>
            
            <div class="container-bottom">
                <div class="container-table">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <div class="container-search">
                        <div class="search-bar">
                            <form method="GET" action="" class="form-inline">
                                <div class="input-group mb-3 flex-grow-1">
                                    <input type="text" class="form-control" name="search_id" placeholder="Search by ID or Name" value="<?php echo htmlspecialchars($searchId); ?>" style="border-radius: 10px 0 0 10px; border: 3px solid #131313; height:42px;">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit" style="border-radius: 0; border: 3px solid #131313;">Search</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" role="tablist">
                        <?php if ($role === 'Admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#employeesTab">Employees</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#warningsTab">Warning History</a>
                        </li>
                        <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#warningsTab">My Warnings</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <!-- Tab content -->
                    <div class="tab-content">
                        <?php if ($role === 'Admin'): ?>
                        <div id="employeesTab" class="tab-pane active">
                            <div class="tool-bar">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div style="color: #FFFAFA;">
                                        <span id="selected-count">0</span> employee(s) selected
                                    </div>
                                    
                                    <div class="d-flex align-items-center" style="gap:10px;">
                                        <button id="addWarningBtn" class="btn btn-primary" disabled>Add Warning</button>
                                        <button class="btn btn-info" onclick="window.location.href='warning_notice.php'">Reset</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-container">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th class="checkbox-col"></th>
                                            <th>Employee ID</th>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Department</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($employees as $employee): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="employee-checkbox" 
                                                    data-id="<?php echo $employee['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($employee['name']); ?>"
                                                    data-position="<?php echo htmlspecialchars($employee['position']); ?>"
                                                    data-department="<?php echo htmlspecialchars(isset($employee['department']) ? $employee['department'] : $department); ?>"
                                                    onclick="updateSelectedCount(this)">
                                            </td>
                                            <td><?php echo $employee['id']; ?></td>
                                            <td><?php echo $employee['name']; ?></td>
                                            <td><?php echo $employee['position']; ?></td>
                                            <td><?php echo isset($employee['department']) ? $employee['department'] : $department; ?></td>
                                            <td><?php echo isset($employee['status']) ? $employee['status'] : 'Active'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="openWarningModal('<?php echo $employee['id']; ?>', '<?php echo htmlspecialchars($employee['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($employee['position'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars(isset($employee['department']) ? $employee['department'] : $department, ENT_QUOTES); ?>')">Add Warning</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div id="warningsTab" class="tab-pane fade">
                        <?php else: ?>
                        <div id="warningsTab" class="tab-pane active">
                        <?php endif; ?>
                            <div class="table-container">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Employee</th>
                                            <th>Department</th>
                                            <th>Warning Level</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($warnings as $warning): ?>
                                            <?php 
                                                // Determine warning badge class
                                                $warningClass = '';
                                                if ($warning['warning_level'] == '1st Warning') {
                                                    $warningClass = 'warning-1';
                                                } else if ($warning['warning_level'] == '2nd Warning') {
                                                    $warningClass = 'warning-2';
                                                } else if ($warning['warning_level'] == '3rd Warning') {
                                                    $warningClass = 'warning-3';
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($warning['date_of_warning'])); ?></td>
                                                <td><?php echo $warning['employee_name']; ?></td>
                                                <td><?php echo $warning['department']; ?></td>
                                                <td><span class="warning-badge <?php echo $warningClass; ?>"><?php echo $warning['warning_level']; ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" onclick="viewWarningDetails(<?php echo $warning['id']; ?>)">View</button>
                                                    <button class="btn btn-sm btn-success" onclick="printWarning(<?php echo $warning['id']; ?>)">Print</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (count($warnings) === 0): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No warning notices found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Warning Notice Modal -->
    <div class="modal fade" id="warningModal" tabindex="-1" role="dialog" aria-labelledby="warningModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="warningModalLabel">Employee Warning Notice</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="warningForm" method="POST">
                        <input type="hidden" id="employee_id" name="employee_id">
                        
                        <div class="form-group">
                            <label for="date_of_warning">Date of Warning:</label>
                            <input type="date" class="form-control" id="date_of_warning" name="date_of_warning" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="employee_name">Employee Name:</label>
                            <input type="text" class="form-control" id="employee_name" name="employee_name" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="department">Department:</label>
                            <input type="text" class="form-control" id="department" name="department" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Location:</label>
                            <input type="text" class="form-control" id="location" name="location" value="Superpack Enterprise Office" required>
                        </div>
                        
                        <h3 class="section-title">Type of Violation</h3>
                        <p>(Check all that apply)</p>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="violation_attendance" name="violation_attendance">
                            <label for="violation_attendance">Attendance (e.g., Tardiness, Absenteeism)</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="violation_procedures" name="violation_procedures">
                            <label for="violation_procedures">Failure to Follow Procedures</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="violation_insubordination" name="violation_insubordination">
                            <label for="violation_insubordination">Insubordination</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="violation_carelessness" name="violation_carelessness">
                            <label for="violation_carelessness">Carelessness</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="violation_company_policies" name="violation_company_policies">
                            <label for="violation_company_policies">Violation of Company Policies/Procedures</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="violation_damage" name="violation_damage">
                            <label for="violation_damage">Damage to Equipment or Property</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="violation_other" name="violation_other">
                            <label for="violation_other">Other (Specify):</label>
                            <input type="text" class="form-control" id="other_violation_details" name="other_violation_details" style="display:none;">
                        </div>
                        
                        <h3 class="section-title">Previous Warnings</h3>
                        
                        <div class="form-group">
                            <select class="form-control" id="warning_level" name="warning_level" required>
                                <option value="">Select Warning Level</option>
                                <option value="1st Warning">1st Warning</option>
                                <option value="2nd Warning">2nd Warning</option>
                                <option value="3rd Warning">3rd Warning</option>
                            </select>
                        </div>
                        
                        <h3 class="section-title">Employer's Statement</h3>
                        <div class="form-group">
                            <p>Describe the incident or violation in detail (dates, times, witnesses, etc.). Also note any relevant company policies.</p>
                            <textarea class="form-control" id="employer_statement" name="employer_statement" rows="5" required></textarea>
                        </div>
                        
                        <h3 class="section-title">Employee's Statement</h3>
                        <div class="form-group">
                            <p>Employee may provide their explanation or perspective on the incident.</p>
                            <textarea class="form-control" id="employee_statement" name="employee_statement" rows="5"></textarea>
                        </div>
                        
                        <h3 class="section-title">Consequence/Action Taken</h3>
                        <p>(Check one or more)</p>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="consequence_verbal" name="consequence_verbal">
                            <label for="consequence_verbal">Verbal Warning</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="consequence_written" name="consequence_written" checked>
                            <label for="consequence_written">Written Warning</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="consequence_probation" name="consequence_probation">
                            <label for="consequence_probation">Probation</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="consequence_suspension" name="consequence_suspension">
                            <label for="consequence_suspension">Suspension</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="consequence_dismissal" name="consequence_dismissal">
                            <label for="consequence_dismissal">Dismissal</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="consequence_other" name="consequence_other">
                            <label for="consequence_other">Other (Specify):</label>
                            <input type="text" class="form-control" id="other_consequence_details" name="other_consequence_details" style="display:none;">
                        </div>
                        
                        <h3 class="section-title">Signatures</h3>
                        <div class="form-group">
                            <p>I have read this warning notice and understand it.</p>
                            <label for="employee_initials">Employee Initial Here:</label>
                            <input type="text" class="form-control" id="employee_initials" name="employee_initials" required>
                        </div>
                        
                        <input type="hidden" name="addWarning" value="1">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('warningForm').submit()">Save Warning</button>
                    <button type="button" class="btn btn-success" onclick="printWarningForm()">Print</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Warning Details Modal -->
    <div class="modal fade" id="warningDetailsModal" tabindex="-1" role="dialog" aria-labelledby="warningDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="warningDetailsModalLabel">Warning Notice Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="warningDetails" class="print-section">
                        <!-- Warning details will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" onclick="printWarningDetails()">Print</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Update page title in header
        document.addEventListener('DOMContentLoaded', function() {
            const logoName = document.querySelector('.logo_name');
            if (logoName) {
                logoName.textContent = 'Warning Notice';
            }
            
            // Initialize Bootstrap components
            $('[data-toggle="tab"]').tab();
            
            // Toggle violation other details
            document.getElementById('violation_other').addEventListener('change', function() {
                document.getElementById('other_violation_details').style.display = this.checked ? 'block' : 'none';
            });
            
            // Toggle consequence other details
            document.getElementById('consequence_other').addEventListener('change', function() {
                document.getElementById('other_consequence_details').style.display = this.checked ? 'block' : 'none';
            });
            
            // Add warning button click
            document.getElementById('addWarningBtn').addEventListener('click', function() {
                const checkbox = document.querySelector('.employee-checkbox:checked');
                if (checkbox) {
                    openWarningModal(
                        checkbox.dataset.id,
                        checkbox.dataset.name,
                        checkbox.dataset.position,
                        checkbox.dataset.department
                    );
                }
            });
            
            // Ensure jQuery and Bootstrap are properly loaded
            if (typeof $ === 'function' && typeof $.fn.modal === 'function') {
                console.log('jQuery and Bootstrap Modal are available');
            } else {
                console.error('jQuery or Bootstrap Modal is not available');
            }
        });
        
        // Update selected count
        function updateSelectedCount() {
            const selectedCount = document.querySelectorAll('.employee-checkbox:checked').length;
            document.getElementById('selected-count').textContent = selectedCount;
            
            // Enable/disable add warning button
            document.getElementById('addWarningBtn').disabled = selectedCount !== 1;
        }
        
        // Open warning modal
        function openWarningModal(id, name, position, department) {
            console.log('Opening modal for:', id, name);
            document.getElementById('employee_id').value = id;
            document.getElementById('employee_name').value = name;
            document.getElementById('department').value = department;
            
            // Use jQuery to show the modal
            $('#warningModal').modal('show');
        }
        
        // View warning details
        function viewWarningDetails(id) {
            // Show loading message
            document.getElementById('warningDetails').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p>Loading warning details...</p>
                </div>
            `;
            
            // Show the modal first
            $('#warningDetailsModal').modal('show');
            
            // Fetch warning details using AJAX
            fetch(`get_warning_details.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Format date
                        const warningDate = new Date(data.warning.date_of_warning);
                        const formattedDate = warningDate.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                        
                        // Format violations
                        const violations = [];
                        if (data.warning.violation_attendance == 1) violations.push('Attendance');
                        if (data.warning.violation_procedures == 1) violations.push('Failure to Follow Procedures');
                        if (data.warning.violation_insubordination == 1) violations.push('Insubordination');
                        if (data.warning.violation_carelessness == 1) violations.push('Carelessness');
                        if (data.warning.violation_company_policies == 1) violations.push('Violation of Company Policies');
                        if (data.warning.violation_damage == 1) violations.push('Damage to Equipment');
                        if (data.warning.violation_other) violations.push(`Other: ${data.warning.violation_other}`);
                        
                        // Format consequences
                        const consequences = [];
                        if (data.warning.consequence_verbal == 1) consequences.push('Verbal Warning');
                        if (data.warning.consequence_written == 1) consequences.push('Written Warning');
                        if (data.warning.consequence_probation == 1) consequences.push('Probation');
                        if (data.warning.consequence_suspension == 1) consequences.push('Suspension');
                        if (data.warning.consequence_dismissal == 1) consequences.push('Dismissal');
                        if (data.warning.consequence_other) consequences.push(`Other: ${data.warning.consequence_other}`);
                        
                        // Create warning display HTML
                        document.getElementById('warningDetails').innerHTML = `
                            <div class="p-3">
                                <div class="row mb-4">
                                    <div class="col-12 text-center p-3 bg-primary text-white">
                                        <h3>EMPLOYEE WARNING NOTICE</h3>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>Employee:</strong> ${data.warning.employee_name}</p>
                                        <p><strong>Department:</strong> ${data.warning.department}</p>
                                        <p><strong>Location:</strong> ${data.warning.location}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Date:</strong> ${formattedDate}</p>
                                        <p><strong>Warning Level:</strong> <span class="badge ${getWarningBadgeClass(data.warning.warning_level)}">${data.warning.warning_level}</span></p>
                                    </div>
                                </div>
                                
                                <div class="mb-3 p-2 border rounded">
                                    <h5>Type of Violation:</h5>
                                    <p>${violations.join(', ') || 'None specified'}</p>
                                </div>
                                
                                <div class="mb-3 p-2 border rounded">
                                    <h5>Employer's Statement:</h5>
                                    <p>${data.warning.employer_statement}</p>
                                </div>
                                
                                <div class="mb-3 p-2 border rounded">
                                    <h5>Employee's Statement:</h5>
                                    <p>${data.warning.employee_statement || 'No statement provided'}</p>
                                </div>
                                
                                <div class="mb-3 p-2 border rounded">
                                    <h5>Consequences:</h5>
                                    <p>${consequences.join(', ') || 'None specified'}</p>
                                </div>
                                
                                <div class="text-right mt-3">
                                    <p>Employee Initials: ${data.warning.employee_initials || 'Not signed'}</p>
                                </div>
                            </div>
                        `;
                    } else {
                        document.getElementById('warningDetails').innerHTML = `
                            <div class="alert alert-danger">
                                <p>Error loading warning details: ${data.message}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('warningDetails').innerHTML = `
                        <div class="alert alert-danger">
                            <p>Error: Unable to load warning details. Please try again later.</p>
                            <p>If this page is in development, please create the get_warning_details.php file to handle API requests.</p>
                        </div>
                    `;
                });
        }
        
        // Helper function to get badge class for warning level
        function getWarningBadgeClass(warningLevel) {
            switch(warningLevel) {
                case '1st Warning': return 'bg-warning text-dark';
                case '2nd Warning': return 'bg-orange text-white';
                case '3rd Warning': return 'bg-danger text-white';
                default: return 'bg-secondary text-white';
            }
        }
        
        // Print warning notice from the form
        function printWarningForm() {
            const printWindow = window.open('', '_blank');
            
            const employee = document.getElementById('employee_name').value;
            const department = document.getElementById('department').value;
            const date = document.getElementById('date_of_warning').value;
            const warningLevel = document.getElementById('warning_level').value;
            const employerStatement = document.getElementById('employer_statement').value;
            
            let violationTypes = [];
            if (document.getElementById('violation_attendance').checked) violationTypes.push('Attendance');
            if (document.getElementById('violation_procedures').checked) violationTypes.push('Procedures');
            if (document.getElementById('violation_insubordination').checked) violationTypes.push('Insubordination');
            if (document.getElementById('violation_carelessness').checked) violationTypes.push('Carelessness');
            if (document.getElementById('violation_company_policies').checked) violationTypes.push('Company Policies');
            if (document.getElementById('violation_damage').checked) violationTypes.push('Damage');
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>Warning Notice - ${employee}</title>
                    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
                    <style>
                        body { padding: 20px; font-family: Arial, sans-serif; }
                        .warning-header { background-color: #64A651; color: white; padding: 10px; }
                        .section { margin-bottom: 20px; }
                        .section-title { font-weight: bold; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid #ccc; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="row mb-4">
                            <div class="col-12 text-center warning-header">
                                <h2>SUPERPACK ENTERPRISE</h2>
                                <h3>EMPLOYEE WARNING NOTICE</h3>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Employee Name:</strong> ${employee}</p>
                                <p><strong>Department:</strong> ${department}</p>
                            </div>
                            <div class="col-md-6 text-right">
                                <p><strong>Date:</strong> ${date}</p>
                                <p><strong>Warning Level:</strong> ${warningLevel}</p>
                            </div>
                        </div>
                        
                        <div class="section">
                            <h4 class="section-title">Type of Violation</h4>
                            <p>${violationTypes.join(', ') || 'None specified'}</p>
                        </div>
                        
                        <div class="section">
                            <h4 class="section-title">Employer's Statement</h4>
                            <p>${employerStatement}</p>
                        </div>
                        
                        <div class="section">
                            <h4 class="section-title">Signatures</h4>
                            <div class="row mt-5">
                                <div class="col-md-6">
                                    <p>________________________</p>
                                    <p>Employee Signature</p>
                                </div>
                                <div class="col-md-6 text-right">
                                    <p>________________________</p>
                                    <p>Supervisor Signature</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => { printWindow.print(); }, 500);
        }
        
        // Print specific warning notice
        function printWarning(id) {
            // In a real implementation, you would fetch the warning details from the database
            // and generate HTML for printing
            const printWindow = window.open('', '_blank');
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>Warning Notice #${id}</title>
                    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
                    <style>
                        body { padding: 20px; font-family: Arial, sans-serif; }
                        .warning-header { background-color: #64A651; color: white; padding: 10px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="row mb-4">
                            <div class="col-12 text-center warning-header">
                                <h2>SUPERPACK ENTERPRISE</h2>
                                <h3>EMPLOYEE WARNING NOTICE</h3>
                                <p>Warning ID: ${id}</p>
                            </div>
                        </div>
                        <div class="text-center">
                            <p>This is a placeholder for the warning notice details.</p>
                            <p>In a production environment, this would display the full warning notice details.</p>
                        </div>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => { printWindow.print(); }, 500);
        }
        
        // Print warning details from the modal
        function printWarningDetails() {
            const content = document.getElementById('warningDetails').innerHTML;
            const printWindow = window.open('', '_blank');
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>Warning Notice Details</title>
                    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
                    <style>
                        body { padding: 20px; font-family: Arial, sans-serif; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        ${content}
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => { printWindow.print(); }, 500);
        }
    </script>
</body>
</html>