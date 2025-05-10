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

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $applicationId = $_POST['application_id'];
        $newStatus = $_POST['new_status'];
        
        $updateQuery = "UPDATE job_applications SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $newStatus, $applicationId);
        
        if ($stmt->execute()) {
            // If the new status is 'Hired', create a record in the training table
            if ($newStatus === 'Hired') {
                // First, get the applicant details
                $applicantQuery = "SELECT first_name, last_name, email, phone, address, gender, education, position_id FROM job_applications WHERE id = ?";
                $applicantStmt = $conn->prepare($applicantQuery);
                $applicantStmt->bind_param("i", $applicationId);
                $applicantStmt->execute();
                $applicantResult = $applicantStmt->get_result();
                $applicant = $applicantResult->fetch_assoc();
                
                // Get the position title
                $positionQuery = "SELECT title FROM job_positions WHERE id = ?";
                $positionStmt = $conn->prepare($positionQuery);
                $positionStmt->bind_param("i", $applicant['position_id']);
                $positionStmt->execute();
                $positionResult = $positionStmt->get_result();
                $position = $positionResult->fetch_assoc();
                
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
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE
                )";
                
                $conn->query($createTrainingTable);
                
                // Insert the candidate into training table
                $fullName = $applicant['first_name'] . ' ' . $applicant['last_name'];
                $trainingInsertQuery = "INSERT INTO candidates_training (application_id, candidate_name, position, email, phone) VALUES (?, ?, ?, ?, ?)";
                $trainingStmt = $conn->prepare($trainingInsertQuery);
                $trainingStmt->bind_param("issss", $applicationId, $fullName, $position['title'], $applicant['email'], $applicant['phone']);
                $trainingStmt->execute();
            }
            
            $_SESSION['success_message'] = "Application status updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating status: " . $stmt->error;
        }
        
        // Redirect to prevent form resubmission
        header('Location: selection.php');
        exit();
    }
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Initialize search term
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Base query to count total applications
$countQuery = "SELECT COUNT(*) as total FROM job_applications";

// Base query to retrieve applications
$query = "SELECT a.*, p.title as position_title, p.department 
          FROM job_applications a
          LEFT JOIN job_positions p ON a.position_id = p.id";

// Add search and filter conditions if provided
$whereConditions = [];

if (!empty($searchTerm)) {
    $whereConditions[] = "(a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ? OR p.title LIKE ?)";
}

if (!empty($statusFilter)) {
    $whereConditions[] = "a.status = ?";
}

// Build the WHERE clause
if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(' AND ', $whereConditions);
    $countQuery .= " as a LEFT JOIN job_positions p ON a.position_id = p.id WHERE " . implode(' AND ', $whereConditions);
}

// Add ORDER BY and LIMIT clauses
$query .= " ORDER BY a.application_date DESC LIMIT ?, ?";

// Prepare the count statement
$countStmt = $conn->prepare($countQuery);

// Bind parameters for the count query
$bindTypes = "";
$bindParams = [];

if (!empty($searchTerm)) {
    $searchParam = '%' . $searchTerm . '%';
    $bindTypes .= "ssss";
    $bindParams[] = $searchParam;
    $bindParams[] = $searchParam;
    $bindParams[] = $searchParam;
    $bindParams[] = $searchParam;
}

if (!empty($statusFilter)) {
    $bindTypes .= "s";
    $bindParams[] = $statusFilter;
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
$applications = [];

while ($row = $result->fetch_assoc()) {
    $applications[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Selection</title>
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
        
        .status-new {
            background-color: #e2e3e5;
            color: #41464b;
        }
        
        .status-screening {
            background-color: #cff4fc;
            color: #055160;
        }
        
        .status-interview {
            background-color: #fff3cd;
            color: #664d03;
        }
        
        .status-offer {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-hired {
            background-color: #198754;
            color: white;
        }
        
        .status-rejected {
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
        
        .stats-card.new {
            background-color: #cff4fc;
        }
        
        .stats-card.screening {
            background-color: #fff3cd;
        }
        
        .stats-card.hired {
            background-color: #d1e7dd;
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
    </style>
</head>
<body>
    <?php include 'sidebar_small.php'; ?>
    <div class="container">
        <h1 class="page-title text-center mb-5">Applicant Selection</h1>
        
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
                'total' => $conn->query("SELECT COUNT(*) as count FROM job_applications")->fetch_assoc()['count'],
                'new' => $conn->query("SELECT COUNT(*) as count FROM job_applications WHERE status = 'New'")->fetch_assoc()['count'],
                'screening' => $conn->query("SELECT COUNT(*) as count FROM job_applications WHERE status = 'Screening'")->fetch_assoc()['count'],
                'interview' => $conn->query("SELECT COUNT(*) as count FROM job_applications WHERE status = 'Interview'")->fetch_assoc()['count'],
                'offer' => $conn->query("SELECT COUNT(*) as count FROM job_applications WHERE status = 'Offer'")->fetch_assoc()['count'],
                'hired' => $conn->query("SELECT COUNT(*) as count FROM job_applications WHERE status = 'Hired'")->fetch_assoc()['count'],
                'rejected' => $conn->query("SELECT COUNT(*) as count FROM job_applications WHERE status = 'Rejected'")->fetch_assoc()['count']
            ];
            ?>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card total">
                    <h5>Total Applicants</h5>
                    <h3><?php echo $stats['total']; ?></h3>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card new">
                    <h5>New Applications</h5>
                    <h3><?php echo $stats['new']; ?></h3>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card screening">
                    <h5>In Process</h5>
                    <h3><?php echo $stats['screening'] + $stats['interview'] + $stats['offer']; ?></h3>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card hired">
                    <h5>Hired</h5>
                    <h3><?php echo $stats['hired']; ?></h3>
                </div>
            </div>
        </div>
        
        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-filter me-2"></i> Filter Applicants
            </div>
            <div class="card-body">
                <form action="selection.php" method="get" class="row g-3 filter-form">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, email, or position" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Application Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="New" <?php echo $statusFilter === 'New' ? 'selected' : ''; ?>>New</option>
                            <option value="Screening" <?php echo $statusFilter === 'Screening' ? 'selected' : ''; ?>>Screening</option>
                            <option value="Interview" <?php echo $statusFilter === 'Interview' ? 'selected' : ''; ?>>Interview</option>
                            <option value="Offer" <?php echo $statusFilter === 'Offer' ? 'selected' : ''; ?>>Offer</option>
                            <option value="Hired" <?php echo $statusFilter === 'Hired' ? 'selected' : ''; ?>>Hired</option>
                            <option value="Rejected" <?php echo $statusFilter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Applicants Table -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-users me-2"></i> Applicants List
            </div>
            <div class="card-body">
                <?php if (empty($applications)): ?>
                    <div class="alert alert-info">No applications found matching your criteria.</div>
                <?php else: ?>
                    <div class="table-responsive" style="overflow: visible">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Contact</th>
                                    <th>Applied On</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($app['position_title'] . ' (' . $app['department'] . ')'); ?>
                                        </td>
                                        <td>
                                            <div><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($app['email']); ?></div>
                                            <div><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($app['phone']); ?></div>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($app['application_date'])); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                                <?php echo $app['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="actionDropdown<?php echo $app['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionDropdown<?php echo $app['id']; ?>" data-bs-popper="static">
                                                    <li><h6 class="dropdown-header">View Details</h6></li>
                                                    <li>
                                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#viewDetailsModal<?php echo $app['id']; ?>">
                                                            <i class="fas fa-eye me-2"></i> View Full Profile
                                                        </button>
                                                    </li>
                                                    <?php if (!empty($app['resume_path'])): ?>
                                                        <li>
                                                            <a class="dropdown-item" href="<?php 
                                                                $resumePath = $app['resume_path'];
                                                                // Check if path starts with "uploads/" - if not, add "../" to reference from parent directory
                                                                if (strpos($resumePath, 'uploads/') === 0) {
                                                                    echo "../" . htmlspecialchars($resumePath);
                                                                } else {
                                                                    echo htmlspecialchars($resumePath);
                                                                }
                                                            ?>" target="_blank">
                                                                <i class="fas fa-file-alt me-2"></i> View Resume
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><h6 class="dropdown-header">Update Status</h6></li>
                                                    <li>
                                                        <form action="selection.php" method="post">
                                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                            <input type="hidden" name="update_status" value="1">
                                                            <div class="mb-3">
                                                                <select class="form-select form-select-sm" name="new_status" required>
                                                                    <option value="">Select new status</option>
                                                                    <option value="New" <?php echo $app['status'] === 'New' ? 'selected' : ''; ?>>New</option>
                                                                    <option value="Screening" <?php echo $app['status'] === 'Screening' ? 'selected' : ''; ?>>Screening</option>
                                                                    <option value="Interview" <?php echo $app['status'] === 'Interview' ? 'selected' : ''; ?>>Interview</option>
                                                                    <option value="Offer" <?php echo $app['status'] === 'Offer' ? 'selected' : ''; ?>>Offer</option>
                                                                    <option value="Hired" <?php echo $app['status'] === 'Hired' ? 'selected' : ''; ?>>Hired</option>
                                                                    <option value="Rejected" <?php echo $app['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                                </select>
                                                            </div>
                                                            <button type="submit" class="btn btn-sm btn-primary w-100">Update Status</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- View Details Modal -->
                                    <div class="modal fade" id="viewDetailsModal<?php echo $app['id']; ?>" tabindex="-1" aria-labelledby="viewDetailsModalLabel<?php echo $app['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewDetailsModalLabel<?php echo $app['id']; ?>">
                                                        Applicant Profile: <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6 class="fw-bold">Personal Information</h6>
                                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></p>
                                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($app['email']); ?></p>
                                                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($app['phone']); ?></p>
                                                            <p><strong>Gender:</strong> <?php echo htmlspecialchars($app['gender']); ?></p>
                                                            <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($app['address'])); ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6 class="fw-bold">Application Information</h6>
                                                            <p><strong>Position:</strong> <?php echo htmlspecialchars($app['position_title']); ?></p>
                                                            <p><strong>Department:</strong> <?php echo htmlspecialchars($app['department']); ?></p>
                                                            <p><strong>Applied On:</strong> <?php echo date('F d, Y', strtotime($app['application_date'])); ?></p>
                                                            <p><strong>Status:</strong> 
                                                                <span class="status-badge status-<?php echo strtolower($app['status']); ?>"><?php echo $app['status']; ?></span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <h6 class="fw-bold">Educational Background</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($app['education'])); ?></p>
                                                    
                                                    <h6 class="fw-bold">Work Experience</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($app['experience'])); ?></p>
                                                    
                                                    <?php if (!empty($app['resume_path'])): ?>
                                                        <div class="mt-3">
                                                            <a href="<?php 
                                                                $resumePath = $app['resume_path'];
                                                                // Check if path starts with "uploads/" - if not, add "../" to reference from parent directory
                                                                if (strpos($resumePath, 'uploads/') === 0) {
                                                                    echo "../" . htmlspecialchars($resumePath);
                                                                } else {
                                                                    echo htmlspecialchars($resumePath);
                                                                }
                                                            ?>" class="btn btn-outline-primary" target="_blank">
                                                                <i class="fas fa-file-alt me-2"></i> View Resume
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                        <nav aria-label="Page navigation">
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