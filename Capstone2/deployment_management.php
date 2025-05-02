<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../welcome.php');
    exit();
}

// Check if the user has admin privileges
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    echo '<script>alert("You do not have permission to access this page."); window.location.href = "../welcome.php";</script>';
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

// Check if deployments table exists, if not create it
$createDeploymentsTable = "CREATE TABLE IF NOT EXISTS deployments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    department VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    salary DECIMAL(10,2) NOT NULL DEFAULT 0,
    ot_rate DECIMAL(10,2) NOT NULL DEFAULT 0,
    leave_entitlement INT NOT NULL DEFAULT 0,
    start_date DATE,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$conn->query($createDeploymentsTable);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update deployment details
    if (isset($_POST['update_deployment'])) {
        $deploymentId = $_POST['deployment_id'];
        $department = $_POST['department'];
        $position = $_POST['position'];
        $salary = $_POST['salary'];
        $otRate = $_POST['ot_rate'];
        $leaveEntitlement = $_POST['leave_entitlement'];
        $status = $_POST['status'];
        $startDate = $_POST['start_date'];
        
        $updateQuery = "UPDATE deployments SET 
                      department = ?, 
                      position = ?, 
                      salary = ?,
                      ot_rate = ?,
                      leave_entitlement = ?,
                      status = ?,
                      start_date = ?
                      WHERE id = ?";
                      
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssdddssi", $department, $position, $salary, $otRate, $leaveEntitlement, $status, $startDate, $deploymentId);
        
        if ($stmt->execute()) {
            // Update employee record with new position and department
            $getEmployeeIdQuery = "SELECT employee_id FROM deployments WHERE id = ?";
            $employeeIdStmt = $conn->prepare($getEmployeeIdQuery);
            $employeeIdStmt->bind_param("i", $deploymentId);
            $employeeIdStmt->execute();
            $employeeIdResult = $employeeIdStmt->get_result();
            $employeeId = $employeeIdResult->fetch_assoc()['employee_id'];
            
            // Get actual columns from employee_records table
            $tableColumnsQuery = "SHOW COLUMNS FROM employee_records";
            $columnsResult = $conn->query($tableColumnsQuery);
            $existingColumns = [];
            
            if ($columnsResult) {
                while ($column = $columnsResult->fetch_assoc()) {
                    $existingColumns[] = $column['Field'];
                }
            }
            
            // Define possible columns and their values for update
            $columnData = [
                'position' => $position,
                'department' => $department,
                'status' => ($status === 'Active') ? 'Active' : 'Inactive'
            ];
            
            // Build the SET clause dynamically based on existing columns
            $setClause = [];
            $updateValues = [];
            $updateTypes = '';
            
            foreach ($columnData as $column => $value) {
                if (in_array($column, $existingColumns)) {
                    $setClause[] = "$column = ?";
                    $updateValues[] = $value;
                    $updateTypes .= 's'; // Assuming all values are strings
                }
            }
            
            if (empty($setClause)) {
                $_SESSION['error_message'] = "No matching columns found in employee_records table for update.";
            } else {
                // Add the WHERE condition value
                $updateValues[] = $employeeId;
                $updateTypes .= 'i'; // id is integer
                
                // Build and prepare the update query
                $updateEmployeeQuery = "UPDATE employee_records SET " . implode(', ', $setClause) . " WHERE id = ?";
                $employeeStmt = $conn->prepare($updateEmployeeQuery);
                
                // Use call_user_func_array to pass dynamic parameters to bind_param
                $bindParams = array_merge([$updateTypes], $updateValues);
                $bindParamRef = [];
                
                // Create references for bind_param
                foreach ($bindParams as $key => $value) {
                    $bindParamRef[$key] = &$bindParams[$key];
                }
                
                call_user_func_array([$employeeStmt, 'bind_param'], $bindParamRef);
                $employeeStmt->execute();
            
                // Check if payroll_records table exists and has employee_id column
                $checkPayrollTableQuery = "SHOW TABLES LIKE 'payroll_records'";
                $payrollTableExists = $conn->query($checkPayrollTableQuery)->num_rows > 0;
                
                if ($payrollTableExists) {
                    $checkPayrollColumnsQuery = "SHOW COLUMNS FROM payroll_records LIKE 'employee_id'";
                    $employeeIdColumnExists = $conn->query($checkPayrollColumnsQuery)->num_rows > 0;
                    
                    if ($employeeIdColumnExists) {
                        // Also update payroll records if they exist
                        $checkPayrollQuery = "SELECT id FROM payroll_records WHERE employee_id = ?";
                        $checkStmt = $conn->prepare($checkPayrollQuery);
                        $checkStmt->bind_param("i", $employeeId);
                        $checkStmt->execute();
                        $checkResult = $checkStmt->get_result();
                        
                        if ($checkResult->num_rows > 0) {
                            $updatePayrollQuery = "UPDATE payroll_records SET 
                                                position = ?,
                                                salary = ?,
                                                ot_rate = ?
                                                WHERE employee_id = ?";
                                                
                            $payrollStmt = $conn->prepare($updatePayrollQuery);
                            $payrollStmt->bind_param("sddi", $position, $salary, $otRate, $employeeId);
                            $payrollStmt->execute();
                        } else {
                            // Create a new payroll record
                            $insertPayrollQuery = "INSERT INTO payroll_records (employee_id, position, salary, ot_rate) 
                                                VALUES (?, ?, ?, ?)";
                                                
                            $payrollStmt = $conn->prepare($insertPayrollQuery);
                            $payrollStmt->bind_param("isdd", $employeeId, $position, $salary, $otRate);
                            $payrollStmt->execute();
                        }
                    } else {
                        // Try using name instead of employee_id if it exists
                        $getEmployeeNameQuery = "SELECT name FROM employee_records WHERE id = ?";
                        $nameStmt = $conn->prepare($getEmployeeNameQuery);
                        $nameStmt->bind_param("i", $employeeId);
                        $nameStmt->execute();
                        $nameResult = $nameStmt->get_result();
                        
                        if ($nameResult->num_rows > 0) {
                            $employeeName = $nameResult->fetch_assoc()['name'];
                            
                            // Check if there's a payroll record with this name
                            $checkPayrollByNameQuery = "SELECT id FROM payroll_records WHERE name = ?";
                            $checkNameStmt = $conn->prepare($checkPayrollByNameQuery);
                            $checkNameStmt->bind_param("s", $employeeName);
                            $checkNameStmt->execute();
                            $checkNameResult = $checkNameStmt->get_result();
                            
                            if ($checkNameResult->num_rows > 0) {
                                // Update existing payroll record by name
                                $updatePayrollQuery = "UPDATE payroll_records SET 
                                                    position = ?,
                                                    salary = ?
                                                    WHERE name = ?";
                                                    
                                $payrollStmt = $conn->prepare($updatePayrollQuery);
                                $payrollStmt->bind_param("sds", $position, $salary, $employeeName);
                                $payrollStmt->execute();
                            }
                        }
                    }
                }
            
                $_SESSION['success_message'] = "Deployment details updated successfully!";
            }
        } else {
            $_SESSION['error_message'] = "Error updating deployment details: " . $stmt->error;
        }
    }
    
    // Add new deployment
    if (isset($_POST['add_deployment'])) {
        $employeeId = $_POST['employee_id'];
        $department = $_POST['department'];
        $position = $_POST['position'];
        $salary = $_POST['salary'];
        $otRate = $_POST['ot_rate'];
        $leaveEntitlement = $_POST['leave_entitlement'];
        $startDate = $_POST['start_date'];
        
        // Check if this employee already has a deployment
        $checkQuery = "SELECT id FROM deployments WHERE employee_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $employeeId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $_SESSION['error_message'] = "This employee already has a deployment record. Please update the existing record.";
        } else {
            $insertQuery = "INSERT INTO deployments (employee_id, department, position, salary, ot_rate, leave_entitlement, start_date) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
                          
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("issdids", $employeeId, $department, $position, $salary, $otRate, $leaveEntitlement, $startDate);
            
            if ($stmt->execute()) {
                // Get actual columns from employee_records table
                $tableColumnsQuery = "SHOW COLUMNS FROM employee_records";
                $columnsResult = $conn->query($tableColumnsQuery);
                $existingColumns = [];
                
                if ($columnsResult) {
                    while ($column = $columnsResult->fetch_assoc()) {
                        $existingColumns[] = $column['Field'];
                    }
                }
                
                // Define possible columns and their values for update
                $columnData = [
                    'position' => $position,
                    'department' => $department
                ];
                
                // Build the SET clause dynamically based on existing columns
                $setClause = [];
                $updateValues = [];
                $updateTypes = '';
                
                foreach ($columnData as $column => $value) {
                    if (in_array($column, $existingColumns)) {
                        $setClause[] = "$column = ?";
                        $updateValues[] = $value;
                        $updateTypes .= 's'; // Assuming all values are strings
                    }
                }
                
                if (empty($setClause)) {
                    $_SESSION['warning_message'] = "Deployment created but employee record wasn't updated (no matching columns).";
                } else {
                    // Add the WHERE condition value
                    $updateValues[] = $employeeId;
                    $updateTypes .= 'i'; // id is integer
                    
                    // Build and prepare the update query
                    $updateEmployeeQuery = "UPDATE employee_records SET " . implode(', ', $setClause) . " WHERE id = ?";
                    $employeeStmt = $conn->prepare($updateEmployeeQuery);
                    
                    // Use call_user_func_array to pass dynamic parameters to bind_param
                    $bindParams = array_merge([$updateTypes], $updateValues);
                    $bindParamRef = [];
                    
                    // Create references for bind_param
                    foreach ($bindParams as $key => $value) {
                        $bindParamRef[$key] = &$bindParams[$key];
                    }
                    
                    call_user_func_array([$employeeStmt, 'bind_param'], $bindParamRef);
                    $employeeStmt->execute();
                }
                
                // Check if payroll_records table exists and has employee_id column
                $checkPayrollTableQuery = "SHOW TABLES LIKE 'payroll_records'";
                $payrollTableExists = $conn->query($checkPayrollTableQuery)->num_rows > 0;
                
                if ($payrollTableExists) {
                    $checkPayrollColumnsQuery = "SHOW COLUMNS FROM payroll_records LIKE 'employee_id'";
                    $employeeIdColumnExists = $conn->query($checkPayrollColumnsQuery)->num_rows > 0;
                    
                    if ($employeeIdColumnExists) {
                        // Create a new payroll record with employee_id
                        $insertPayrollQuery = "INSERT INTO payroll_records (employee_id, position, salary, ot_rate) 
                                            VALUES (?, ?, ?, ?)";
                                            
                        $payrollStmt = $conn->prepare($insertPayrollQuery);
                        $payrollStmt->bind_param("isdd", $employeeId, $position, $salary, $otRate);
                        $payrollStmt->execute();
                    } else {
                        // Try using name instead if employee_id doesn't exist
                        $getEmployeeNameQuery = "SELECT name FROM employee_records WHERE id = ?";
                        $nameStmt = $conn->prepare($getEmployeeNameQuery);
                        $nameStmt->bind_param("i", $employeeId);
                        $nameStmt->execute();
                        $nameResult = $nameStmt->get_result();
                        
                        if ($nameResult->num_rows > 0) {
                            $employeeName = $nameResult->fetch_assoc()['name'];
                            
                            // Check if the required columns exist in payroll_records
                            $payrollColumnsQuery = "SHOW COLUMNS FROM payroll_records";
                            $columnsResult = $conn->query($payrollColumnsQuery);
                            $payrollColumns = [];
                            
                            if ($columnsResult) {
                                while ($column = $columnsResult->fetch_assoc()) {
                                    $payrollColumns[] = $column['Field'];
                                }
                            }
                            
                            // If name and position columns exist, create payroll record
                            if (in_array('name', $payrollColumns) && in_array('position', $payrollColumns)) {
                                // Check if there's already a record with this name
                                $checkExistingQuery = "SELECT id FROM payroll_records WHERE name = ?";
                                $checkStmt = $conn->prepare($checkExistingQuery);
                                $checkStmt->bind_param("s", $employeeName);
                                $checkStmt->execute();
                                $checkResult = $checkStmt->get_result();
                                
                                if ($checkResult->num_rows == 0) {
                                    // Create a new payroll record with name
                                    $columnsToInsert = ['name', 'position'];
                                    $values = [$employeeName, $position];
                                    $types = 'ss';
                                    
                                    if (in_array('salary', $payrollColumns)) {
                                        $columnsToInsert[] = 'salary';
                                        $values[] = $salary;
                                        $types .= 'd';
                                    }
                                    
                                    if (in_array('daily_rate', $payrollColumns)) {
                                        $columnsToInsert[] = 'daily_rate';
                                        $values[] = $salary / 22; // Approximate daily rate
                                        $types .= 'd';
                                    }
                                    
                                    $insertQuery = "INSERT INTO payroll_records (" . implode(', ', $columnsToInsert) . ") 
                                                VALUES (" . implode(', ', array_fill(0, count($columnsToInsert), '?')) . ")";
                                    
                                    $stmt = $conn->prepare($insertQuery);
                                    
                                    // Create references for bind_param
                                    $bindParams = array_merge([$types], $values);
                                    $bindParamRef = [];
                                    
                                    foreach ($bindParams as $key => $value) {
                                        $bindParamRef[$key] = &$bindParams[$key];
                                    }
                                    
                                    call_user_func_array([$stmt, 'bind_param'], $bindParamRef);
                                    $stmt->execute();
                                }
                            }
                        }
                    }
                }
            
                $_SESSION['success_message'] = "New deployment added successfully!";
            } else {
                $_SESSION['error_message'] = "Error adding deployment: " . $stmt->error;
            }
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: deployment_management.php');
    exit();
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Initialize search term
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : '';

// Base query to count total deployments
$countQuery = "SELECT COUNT(*) as total FROM deployments d
              JOIN employee_records e ON d.employee_id = e.id";

// Base query to retrieve deployments - without phone field which caused issues
$query = "SELECT d.*, e.name as employee_name, e.email 
          FROM deployments d
          JOIN employee_records e ON d.employee_id = e.id";

// Add search and filter conditions if provided
$whereConditions = [];

if (!empty($searchTerm)) {
    $whereConditions[] = "(e.name LIKE ? OR d.position LIKE ? OR e.email LIKE ?)";
}

if (!empty($statusFilter)) {
    $whereConditions[] = "d.status = ?";
}

if (!empty($departmentFilter)) {
    $whereConditions[] = "d.department = ?";
}

// Build the WHERE clause
if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(' AND ', $whereConditions);
    $countQuery .= " WHERE " . implode(' AND ', $whereConditions);
}

// Add ORDER BY and LIMIT clauses
$query .= " ORDER BY d.start_date DESC LIMIT ?, ?";

// Prepare the count statement
$countStmt = $conn->prepare($countQuery);

// Bind parameters for the count query
$bindTypes = "";
$bindParams = [];

if (!empty($searchTerm)) {
    $searchParam = '%' . $searchTerm . '%';
    $bindTypes .= "sss";
    $bindParams[] = $searchParam;
    $bindParams[] = $searchParam;
    $bindParams[] = $searchParam;
}

if (!empty($statusFilter)) {
    $bindTypes .= "s";
    $bindParams[] = $statusFilter;
}

if (!empty($departmentFilter)) {
    $bindTypes .= "s";
    $bindParams[] = $departmentFilter;
}

// Execute count query with bound parameters
if (!empty($bindParams)) {
    $countStmt->bind_param($bindTypes, ...$bindParams);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Prepare the data fetch statement
$stmt = $conn->prepare($query);

// Bind parameters for the main query
if (!empty($bindParams)) {
    $bindTypes .= "ii";
    $bindParams[] = $offset;
    $bindParams[] = $recordsPerPage;
    $stmt->bind_param($bindTypes, ...$bindParams);
} else {
    $stmt->bind_param("ii", $offset, $recordsPerPage);
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();
$deployments = [];

while ($row = $result->fetch_assoc()) {
    $deployments[] = $row;
}

// Get departments for filter
$departmentsQuery = "SELECT DISTINCT department FROM deployments ORDER BY department";
$departmentsResult = $conn->query($departmentsQuery);
$departments = [];

if ($departmentsResult) {
    while ($row = $departmentsResult->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// Get employees without deployment for adding new deployment
$employeesQuery = "SELECT id, name FROM employee_records e 
                  WHERE NOT EXISTS (
                    SELECT 1 FROM deployments d WHERE d.employee_id = e.id
                  )
                  ORDER BY name";
$employeesResult = $conn->query($employeesQuery);
$employees = [];

if ($employeesResult) {
    while ($row = $employeesResult->fetch_assoc()) {
        $employees[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deployment Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            padding-top: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: #3a7bd5;
            color: white;
            font-weight: 600;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            color: #333;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 30px;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #3a7bd5;
            border-color: #3a7bd5;
        }
        
        .pagination .page-link {
            color: #3a7bd5;
        }
        
        .filter-form {
            margin-bottom: 20px;
        }
        
        .btn-primary {
            background-color: #3a7bd5;
            border-color: #3a7bd5;
        }
        
        .btn-primary:hover {
            background-color: #2d62a8;
            border-color: #2d62a8;
        }
        
        .page-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
        }
        
        .modal-header {
            background-color: #3a7bd5;
            color: white;
        }
        
        .modal-header .btn-close {
            color: white;
            filter: brightness(0) invert(1);
        }
        
        .stats-card {
            padding: 15px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .stats-card h5 {
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stats-card h3 {
            font-size: 24px;
            margin-bottom: 0;
        }
        
        .stats-card.total {
            background-color: #e2e3e5;
        }
        
        .stats-card.active {
            background-color: #d1e7dd;
        }
        
        .stats-card.inactive {
            background-color: #f8d7da;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_small.php'; ?>
    <div class="container">
        <h1 class="page-title text-center mb-5">Deployment Management</h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php
            $stats = [
                'total' => $conn->query("SELECT COUNT(*) as count FROM deployments")->fetch_assoc()['count'],
                'active' => $conn->query("SELECT COUNT(*) as count FROM deployments WHERE status = 'Active'")->fetch_assoc()['count'],
                'inactive' => $conn->query("SELECT COUNT(*) as count FROM deployments WHERE status = 'Inactive'")->fetch_assoc()['count']
            ];
            ?>
            <div class="col-md-4">
                <div class="stats-card total">
                    <h5>Total Deployments</h5>
                    <h3><?php echo $stats['total']; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card active">
                    <h5>Active Employees</h5>
                    <h3><?php echo $stats['active']; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card inactive">
                    <h5>Inactive Employees</h5>
                    <h3><?php echo $stats['inactive']; ?></h3>
                </div>
            </div>
        </div>
        
        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-filter me-2"></i> Filter Deployments
            </div>
            <div class="card-body">
                <form action="deployment_management.php" method="get" class="row g-3 filter-form">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, position, or email" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="Active" <?php echo $statusFilter === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $statusFilter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" id="department" name="department">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $departmentFilter === $dept ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i> Apply
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Add Deployment Button -->
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDeploymentModal">
                <i class="fas fa-plus me-2"></i> Add New Deployment
            </button>
        </div>
        
        <!-- Deployments Table -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-briefcase me-2"></i> Employee Deployments
            </div>
            <div class="card-body">
                <?php if (empty($deployments)): ?>
                    <div class="alert alert-info">No deployments found matching your criteria.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Contact</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Salary</th>
                                    <th>OT Rate</th>
                                    <th>Leave Days</th>
                                    <th>Status</th>
                                    <th>Start Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deployments as $deployment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($deployment['employee_name']); ?></td>
                                        <td>
                                            <div><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($deployment['email']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($deployment['department']); ?></td>
                                        <td><?php echo htmlspecialchars($deployment['position']); ?></td>
                                        <td><?php echo number_format($deployment['salary'], 2); ?></td>
                                        <td><?php echo number_format($deployment['ot_rate'], 2); ?></td>
                                        <td><?php echo $deployment['leave_entitlement']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($deployment['status']); ?>">
                                                <?php echo $deployment['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($deployment['start_date'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editDeploymentModal<?php echo $deployment['id']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Deployment Modal -->
                                    <div class="modal fade" id="editDeploymentModal<?php echo $deployment['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Deployment Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="deployment_management.php" method="post">
                                                        <input type="hidden" name="deployment_id" value="<?php echo $deployment['id']; ?>">
                                                        <input type="hidden" name="update_deployment" value="1">
                                                        
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <label for="department<?php echo $deployment['id']; ?>" class="form-label">Department</label>
                                                                <input type="text" class="form-control" id="department<?php echo $deployment['id']; ?>" name="department" value="<?php echo htmlspecialchars($deployment['department']); ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="position<?php echo $deployment['id']; ?>" class="form-label">Position</label>
                                                                <input type="text" class="form-control" id="position<?php echo $deployment['id']; ?>" name="position" value="<?php echo htmlspecialchars($deployment['position']); ?>" required>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="row mb-3">
                                                            <div class="col-md-4">
                                                                <label for="salary<?php echo $deployment['id']; ?>" class="form-label">Salary</label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text">₱</span>
                                                                    <input type="number" step="0.01" min="0" class="form-control" id="salary<?php echo $deployment['id']; ?>" name="salary" value="<?php echo $deployment['salary']; ?>" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label for="otRate<?php echo $deployment['id']; ?>" class="form-label">Overtime Rate</label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text">₱</span>
                                                                    <input type="number" step="0.01" min="0" class="form-control" id="otRate<?php echo $deployment['id']; ?>" name="ot_rate" value="<?php echo $deployment['ot_rate']; ?>" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label for="leaveEntitlement<?php echo $deployment['id']; ?>" class="form-label">Leave Entitlement</label>
                                                                <div class="input-group">
                                                                    <input type="number" min="0" class="form-control" id="leaveEntitlement<?php echo $deployment['id']; ?>" name="leave_entitlement" value="<?php echo $deployment['leave_entitlement']; ?>" required>
                                                                    <span class="input-group-text">days</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="status<?php echo $deployment['id']; ?>" class="form-label">Status</label>
                                                            <select class="form-select" id="status<?php echo $deployment['id']; ?>" name="status" required>
                                                                <option value="Active" <?php echo $deployment['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                                                <option value="Inactive" <?php echo $deployment['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="startDate<?php echo $deployment['id']; ?>" class="form-label">Start Date</label>
                                                            <input type="date" class="form-control" id="startDate<?php echo $deployment['id']; ?>" name="start_date" value="<?php echo date('Y-m-d', strtotime($deployment['start_date'])); ?>" required>
                                                        </div>
                                                        
                                                        <div class="d-grid">
                                                            <button type="submit" class="btn btn-primary">Update Deployment</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchTerm); ?>&status=<?php echo urlencode($statusFilter); ?>&department=<?php echo urlencode($departmentFilter); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>&status=<?php echo urlencode($statusFilter); ?>&department=<?php echo urlencode($departmentFilter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchTerm); ?>&status=<?php echo urlencode($statusFilter); ?>&department=<?php echo urlencode($departmentFilter); ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Add Deployment Modal -->
        <div class="modal fade" id="addDeploymentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Deployment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (empty($employees)): ?>
                            <div class="alert alert-info">All employees already have deployment records.</div>
                        <?php else: ?>
                            <form action="deployment_management.php" method="post">
                                <input type="hidden" name="add_deployment" value="1">
                                
                                <div class="mb-3">
                                    <label for="employeeId" class="form-label">Employee</label>
                                    <select class="form-select" id="employeeId" name="employee_id" required>
                                        <option value="">Select Employee</option>
                                        <?php foreach ($employees as $employee): ?>
                                            <option value="<?php echo $employee['id']; ?>">
                                                <?php echo htmlspecialchars($employee['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="department" class="form-label">Department</label>
                                        <input type="text" class="form-control" id="department" name="department" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="position" class="form-label">Position</label>
                                        <input type="text" class="form-control" id="position" name="position" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="salary" class="form-label">Salary</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" step="0.01" min="0" class="form-control" id="salary" name="salary" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="otRate" class="form-label">Overtime Rate</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" step="0.01" min="0" class="form-control" id="otRate" name="ot_rate" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="leaveEntitlement" class="form-label">Leave Entitlement</label>
                                        <div class="input-group">
                                            <input type="number" min="0" class="form-control" id="leaveEntitlement" name="leave_entitlement" required>
                                            <span class="input-group-text">days</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="startDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="startDate" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success">Add Deployment</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 