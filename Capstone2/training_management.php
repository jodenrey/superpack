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

// Check if candidates_training table exists, if not create it
$createTrainingTable = "CREATE TABLE IF NOT EXISTS candidates_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    candidate_name VARCHAR(255) NOT NULL,
    position VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    offer_status ENUM('Pending', 'Accepted', 'Rejected') NOT NULL DEFAULT 'Pending',
    training_status ENUM('Not Started', 'In Progress', 'Completed') NOT NULL DEFAULT 'Not Started',
    scheduled_date DATE,
    completion_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$conn->query($createTrainingTable);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update offer status
    if (isset($_POST['update_offer'])) {
        $candidateId = $_POST['candidate_id'];
        $offerStatus = $_POST['offer_status'];
        
        $updateQuery = "UPDATE candidates_training SET offer_status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $offerStatus, $candidateId);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Offer status updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating offer status: " . $stmt->error;
        }
    }
    
    // Update training status
    if (isset($_POST['update_training'])) {
        $candidateId = $_POST['candidate_id'];
        $trainingStatus = $_POST['training_status'];
        $scheduledDate = !empty($_POST['scheduled_date']) ? $_POST['scheduled_date'] : NULL;
        $completionDate = !empty($_POST['completion_date']) ? $_POST['completion_date'] : NULL;
        $notes = $_POST['notes'];
        
        $updateQuery = "UPDATE candidates_training SET 
                      training_status = ?, 
                      scheduled_date = ?, 
                      completion_date = ?,
                      notes = ? 
                      WHERE id = ?";
                      
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssssi", $trainingStatus, $scheduledDate, $completionDate, $notes, $candidateId);
        
        if ($stmt->execute()) {
            // If training is completed, move to deployment
            if ($trainingStatus === 'Completed' && $completionDate) {
                // Get candidate details
                $candidateQuery = "SELECT candidate_name, position, email, phone, application_id FROM candidates_training WHERE id = ?";
                $candidateStmt = $conn->prepare($candidateQuery);
                $candidateStmt->bind_param("i", $candidateId);
                $candidateStmt->execute();
                $candidateResult = $candidateStmt->get_result();
                $candidate = $candidateResult->fetch_assoc();
                
                // Get more details from job_applications
                $applicationQuery = "SELECT gender, address, education FROM job_applications WHERE id = ?";
                $applicationStmt = $conn->prepare($applicationQuery);
                $applicationStmt->bind_param("i", $candidate['application_id']);
                $applicationStmt->execute();
                $applicationResult = $applicationStmt->get_result();
                $application = $applicationResult->fetch_assoc();
                
                // Check if employee_records table exists, if not, create it
                $createEmployeeRecords = "CREATE TABLE IF NOT EXISTS employee_records (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    position VARCHAR(100) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    address TEXT,
                    gender VARCHAR(20),
                    education TEXT,
                    department VARCHAR(100),
                    hire_date DATE,
                    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                
                $conn->query($createEmployeeRecords);
                
                // Check if deployments table exists, if not, create it
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
                
                // Insert into employee_records
                $departmentQuery = "SELECT department FROM job_positions WHERE title = ?";
                $departmentStmt = $conn->prepare($departmentQuery);
                $departmentStmt->bind_param("s", $candidate['position']);
                $departmentStmt->execute();
                $departmentResult = $departmentStmt->get_result();
                $department = $departmentResult->fetch_assoc()['department'] ?? 'General';
                
                // Get actual columns from employee_records table
                $tableColumnsQuery = "SHOW COLUMNS FROM employee_records";
                $columnsResult = $conn->query($tableColumnsQuery);
                $existingColumns = [];
                
                if ($columnsResult) {
                    while ($column = $columnsResult->fetch_assoc()) {
                        $existingColumns[] = $column['Field'];
                    }
                }
                
                // Define all possible columns and their values
                $columnData = [
                    'name' => $candidate['candidate_name'],
                    'position' => $candidate['position'],
                    'email' => $candidate['email'],
                    'phone' => $candidate['phone'],
                    'address' => $application['address'],
                    'gender' => $application['gender'],
                    'education' => $application['education'],
                    'department' => $department,
                    'hire_date' => date('Y-m-d')
                ];
                
                // Build the query dynamically based on existing columns
                $columns = [];
                $placeholders = [];
                $values = [];
                $types = '';
                
                foreach ($columnData as $column => $value) {
                    if (in_array($column, $existingColumns)) {
                        $columns[] = $column;
                        $placeholders[] = '?';
                        $values[] = $value;
                        $types .= 's'; // Assuming all values are strings
                    }
                }
                
                if (empty($columns)) {
                    $_SESSION['error_message'] = "No matching columns found in employee_records table.";
                } else {
                    $insertEmployeeQuery = "INSERT INTO employee_records (" . implode(', ', $columns) . ") 
                                          VALUES (" . implode(', ', $placeholders) . ")";
                                          
                    $stmt = $conn->prepare($insertEmployeeQuery);
                    
                    // Use call_user_func_array to pass dynamic parameters to bind_param
                    $bindParams = array_merge([$types], $values);
                    $bindParamRef = [];
                    
                    // Create references for bind_param
                    foreach ($bindParams as $key => $value) {
                        $bindParamRef[$key] = &$bindParams[$key];
                    }
                    
                    call_user_func_array([$stmt, 'bind_param'], $bindParamRef);
                    
                    if ($stmt->execute()) {
                        $employeeId = $conn->insert_id;
                        
                        // Insert into deployments (with default values for now)
                        $insertDeploymentQuery = "INSERT INTO deployments (employee_id, department, position, salary, ot_rate, leave_entitlement, start_date) 
                                               VALUES (?, ?, ?, 0, 0, 0, ?)";
                                               
                        $deploymentStmt = $conn->prepare($insertDeploymentQuery);
                        $deploymentStmt->bind_param("isss", $employeeId, $department, $candidate['position'], $bindParams[0]);
                        $deploymentStmt->execute();
                        
                        $_SESSION['success_message'] = "Training completed and employee deployed successfully!";
                    }
                }
            } else {
                $_SESSION['success_message'] = "Training details updated successfully!";
            }
        } else {
            $_SESSION['error_message'] = "Error updating training details: " . $stmt->error;
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: training_management.php');
    exit();
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Initialize search term
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Base query to count total candidates
$countQuery = "SELECT COUNT(*) as total FROM candidates_training";

// Base query to retrieve candidates
$query = "SELECT * FROM candidates_training";

// Add search and filter conditions if provided
$whereConditions = [];

if (!empty($searchTerm)) {
    $whereConditions[] = "(candidate_name LIKE ? OR position LIKE ? OR email LIKE ?)";
}

if (!empty($statusFilter)) {
    if ($statusFilter === 'offer_pending') {
        $whereConditions[] = "offer_status = 'Pending'";
    } elseif ($statusFilter === 'offer_accepted') {
        $whereConditions[] = "offer_status = 'Accepted'";
    } elseif ($statusFilter === 'offer_rejected') {
        $whereConditions[] = "offer_status = 'Rejected'";
    } elseif ($statusFilter === 'training_not_started') {
        $whereConditions[] = "training_status = 'Not Started'";
    } elseif ($statusFilter === 'training_in_progress') {
        $whereConditions[] = "training_status = 'In Progress'";
    } elseif ($statusFilter === 'training_completed') {
        $whereConditions[] = "training_status = 'Completed'";
    }
}

// Build the WHERE clause
if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(' AND ', $whereConditions);
    $countQuery .= " WHERE " . implode(' AND ', $whereConditions);
}

// Add ORDER BY and LIMIT clauses
$query .= " ORDER BY created_at DESC LIMIT ?, ?";

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
$candidates = [];

while ($row = $result->fetch_assoc()) {
    $candidates[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Management</title>
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
        
        .status-pending {
            background-color: #fff3cd;
            color: #664d03;
        }
        
        .status-accepted {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .status-not-started {
            background-color: #e2e3e5;
            color: #41464b;
        }
        
        .status-in-progress {
            background-color: #cff4fc;
            color: #055160;
        }
        
        .status-completed {
            background-color: #198754;
            color: white;
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
        
        .dropdown-menu {
            min-width: 250px;
            padding: 15px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        /* Fix for dropdown menus in tables with few records */
        .table-responsive {
            overflow: visible !important;
        }
        
        .dropdown-menu-end {
            right: 0;
            left: auto !important;
        }
        
        .action-buttons button {
            margin-right: 5px;
        }
        
        .modal-header {
            background-color: #3a7bd5;
            color: white;
        }
        
        .modal-header .btn-close {
            color: white;
            filter: brightness(0) invert(1);
        }
    </style>
</head>
<body>
    <?php include 'sidebar_small.php'; ?>
    <div class="container">
        <h1 class="page-title text-center mb-5">Training Management</h1>
        
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
                'total' => $conn->query("SELECT COUNT(*) as count FROM candidates_training")->fetch_assoc()['count'],
                'offer_pending' => $conn->query("SELECT COUNT(*) as count FROM candidates_training WHERE offer_status = 'Pending'")->fetch_assoc()['count'],
                'offer_accepted' => $conn->query("SELECT COUNT(*) as count FROM candidates_training WHERE offer_status = 'Accepted'")->fetch_assoc()['count'],
                'offer_rejected' => $conn->query("SELECT COUNT(*) as count FROM candidates_training WHERE offer_status = 'Rejected'")->fetch_assoc()['count'],
                'training_not_started' => $conn->query("SELECT COUNT(*) as count FROM candidates_training WHERE training_status = 'Not Started'")->fetch_assoc()['count'],
                'training_in_progress' => $conn->query("SELECT COUNT(*) as count FROM candidates_training WHERE training_status = 'In Progress'")->fetch_assoc()['count'],
                'training_completed' => $conn->query("SELECT COUNT(*) as count FROM candidates_training WHERE training_status = 'Completed'")->fetch_assoc()['count']
            ];
            ?>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card bg-light">
                    <h5>Total Candidates</h5>
                    <h3><?php echo $stats['total']; ?></h3>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card bg-warning bg-opacity-10">
                    <h5>Pending Offers</h5>
                    <h3><?php echo $stats['offer_pending']; ?></h3>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card bg-info bg-opacity-10">
                    <h5>In Training</h5>
                    <h3><?php echo $stats['training_not_started'] + $stats['training_in_progress']; ?></h3>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card bg-success bg-opacity-10">
                    <h5>Training Completed</h5>
                    <h3><?php echo $stats['training_completed']; ?></h3>
                </div>
            </div>
        </div>
        
        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-filter me-2"></i> Filter Candidates
            </div>
            <div class="card-body">
                <form action="training_management.php" method="get" class="row g-3 filter-form">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, position, or email" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Filter by Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <optgroup label="Offer Status">
                                <option value="offer_pending" <?php echo $statusFilter === 'offer_pending' ? 'selected' : ''; ?>>Offer Pending</option>
                                <option value="offer_accepted" <?php echo $statusFilter === 'offer_accepted' ? 'selected' : ''; ?>>Offer Accepted</option>
                                <option value="offer_rejected" <?php echo $statusFilter === 'offer_rejected' ? 'selected' : ''; ?>>Offer Rejected</option>
                            </optgroup>
                            <optgroup label="Training Status">
                                <option value="training_not_started" <?php echo $statusFilter === 'training_not_started' ? 'selected' : ''; ?>>Training Not Started</option>
                                <option value="training_in_progress" <?php echo $statusFilter === 'training_in_progress' ? 'selected' : ''; ?>>Training In Progress</option>
                                <option value="training_completed" <?php echo $statusFilter === 'training_completed' ? 'selected' : ''; ?>>Training Completed</option>
                            </optgroup>
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
        
        <!-- Candidates Table -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-graduate me-2"></i> Candidates in Training
            </div>
            <div class="card-body">
                <?php if (empty($candidates)): ?>
                    <div class="alert alert-info">No candidates found matching your criteria.</div>
                <?php else: ?>
                    <div class="table-responsive" style="overflow: visible">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Contact</th>
                                    <th>Offer Status</th>
                                    <th>Training Status</th>
                                    <th>Scheduled Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($candidate['candidate_name']); ?></td>
                                        <td><?php echo htmlspecialchars($candidate['position']); ?></td>
                                        <td>
                                            <div><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($candidate['email']); ?></div>
                                            <div><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($candidate['phone']); ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($candidate['offer_status']); ?>">
                                                <?php echo $candidate['offer_status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo str_replace(' ', '-', strtolower($candidate['training_status'])); ?>">
                                                <?php echo $candidate['training_status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $candidate['scheduled_date'] ? date('M d, Y', strtotime($candidate['scheduled_date'])) : 'Not Scheduled'; ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" data-bs-popper="static">
                                                    <li>
                                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#updateOfferModal<?php echo $candidate['id']; ?>">
                                                            <i class="fas fa-clipboard-check me-2"></i> Update Offer Status
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#updateTrainingModal<?php echo $candidate['id']; ?>">
                                                            <i class="fas fa-user-graduate me-2"></i> Update Training Status
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Update Offer Status Modal -->
                                    <div class="modal fade" id="updateOfferModal<?php echo $candidate['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update Offer Status</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="training_management.php" method="post">
                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                        <input type="hidden" name="update_offer" value="1">
                                                        
                                                        <div class="mb-3">
                                                            <label for="offerStatus<?php echo $candidate['id']; ?>" class="form-label">Offer Status</label>
                                                            <select class="form-select" id="offerStatus<?php echo $candidate['id']; ?>" name="offer_status" required>
                                                                <option value="Pending" <?php echo $candidate['offer_status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                                <option value="Accepted" <?php echo $candidate['offer_status'] === 'Accepted' ? 'selected' : ''; ?>>Accepted</option>
                                                                <option value="Rejected" <?php echo $candidate['offer_status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="d-grid">
                                                            <button type="submit" class="btn btn-primary">Update Status</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Update Training Status Modal -->
                                    <div class="modal fade" id="updateTrainingModal<?php echo $candidate['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update Training Status</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form action="training_management.php" method="post">
                                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                                        <input type="hidden" name="update_training" value="1">
                                                        
                                                        <div class="mb-3">
                                                            <label for="trainingStatus<?php echo $candidate['id']; ?>" class="form-label">Training Status</label>
                                                            <select class="form-select" id="trainingStatus<?php echo $candidate['id']; ?>" name="training_status" required>
                                                                <option value="Not Started" <?php echo $candidate['training_status'] === 'Not Started' ? 'selected' : ''; ?>>Not Started</option>
                                                                <option value="In Progress" <?php echo $candidate['training_status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                                <option value="Completed" <?php echo $candidate['training_status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="scheduledDate<?php echo $candidate['id']; ?>" class="form-label">Scheduled Date</label>
                                                            <input type="date" class="form-control" id="scheduledDate<?php echo $candidate['id']; ?>" name="scheduled_date" value="<?php echo $candidate['scheduled_date'] ?? ''; ?>">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="completionDate<?php echo $candidate['id']; ?>" class="form-label">Completion Date</label>
                                                            <input type="date" class="form-control" id="completionDate<?php echo $candidate['id']; ?>" name="completion_date" value="<?php echo $candidate['completion_date'] ?? ''; ?>">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="notes<?php echo $candidate['id']; ?>" class="form-label">Notes</label>
                                                            <textarea class="form-control" id="notes<?php echo $candidate['id']; ?>" name="notes" rows="3"><?php echo htmlspecialchars($candidate['notes'] ?? ''); ?></textarea>
                                                        </div>
                                                        
                                                        <div class="d-grid">
                                                            <button type="submit" class="btn btn-primary">Update Training Details</button>
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
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchTerm); ?>&status=<?php echo urlencode($statusFilter); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>&status=<?php echo urlencode($statusFilter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchTerm); ?>&status=<?php echo urlencode($statusFilter); ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fix for dropdowns in tables with few records
        document.addEventListener('DOMContentLoaded', function() {
            // Check if the table has only a few rows
            const tableRows = document.querySelectorAll('.table-hover tbody tr');
            
            if (tableRows.length < 5) {
                // If there are few rows, ensure the table container has enough height
                const tableContainer = document.querySelector('.table-responsive');
                if (tableContainer) {
                    tableContainer.style.minHeight = '400px';
                }
                
                // Add event listeners to dropdowns to ensure they open correctly
                document.querySelectorAll('.dropdown-toggle').forEach(button => {
                    button.addEventListener('click', function (e) {
                        const dropdown = this.nextElementSibling;
                        
                        // Calculate if there's enough space below
                        const buttonRect = this.getBoundingClientRect();
                        const spaceBelow = window.innerHeight - buttonRect.bottom;
                        
                        // If there's not enough space below, add dropup class
                        if (spaceBelow < 250 && !dropdown.classList.contains('dropdown-menu-up')) {
                            dropdown.style.transform = 'translate3d(0px, -260px, 0px)';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html> 