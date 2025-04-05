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

// Create recruitment_applications table if it doesn't exist
$createTableQuery = "CREATE TABLE IF NOT EXISTS recruitment_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_name VARCHAR(255) NOT NULL,
    position VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    resume_path VARCHAR(255),
    application_date DATE NOT NULL,
    status ENUM('New', 'Screening', 'Interview', 'Offer', 'Hired', 'Rejected') NOT NULL DEFAULT 'New',
    contract_signed BOOLEAN DEFAULT 0,
    contract_date DATE,
    notes TEXT
)";

$conn->query($createTableQuery);

// Define name for search
$name = isset($_GET['name']) ? $_GET['name'] : '';

// Initialize search term
$searchTerm = isset($_GET['search_term']) ? $_GET['search_term'] : '';

// Base query to retrieve applications from the database
$query = "SELECT * FROM recruitment_applications";

// If search term is provided, add WHERE clause with LIKE
if (!empty($searchTerm)) {
    $query .= " WHERE applicant_name LIKE ? OR position LIKE ? OR email LIKE ?";
}

// Add ORDER BY clause
$query .= " ORDER BY application_date DESC";

// Prepare the SQL statement
$stmt = $conn->prepare($query);

// Check if the statement was prepared successfully
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

// Bind the search term with wildcard if provided
if (!empty($searchTerm)) {
    $searchParam = '%' . $searchTerm . '%';
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();
$applications = $result->fetch_all(MYSQLI_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new application
    if (isset($_POST['addApplication'])) {
        $resumePath = '';
        
        // Handle resume upload if provided
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
            $uploadDir = 'uploads/resumes/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['resume']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetFile)) {
                $resumePath = $targetFile;
            }
        }
        
        $newApplication = [
            'applicant_name' => $_POST['applicant_name'],
            'position' => $_POST['position'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'resume_path' => $resumePath,
            'application_date' => date('Y-m-d'),
            'status' => $_POST['status'],
            'notes' => $_POST['notes']
        ];

        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO recruitment_applications (applicant_name, position, email, phone, resume_path, application_date, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $newApplication['applicant_name'], $newApplication['position'], $newApplication['email'], $newApplication['phone'], $newApplication['resume_path'], $newApplication['application_date'], $newApplication['status'], $newApplication['notes']);
        $stmt->execute();
    }

    // Edit an application
    if (isset($_POST['editApplication'])) {
        $editApplication = [
            'id' => $_POST['id'],
            'applicant_name' => $_POST['applicant_name'],
            'position' => $_POST['position'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'status' => $_POST['status'],
            'notes' => $_POST['notes']
        ];

        // Prepare and bind
        $stmt = $conn->prepare("UPDATE recruitment_applications SET applicant_name = ?, position = ?, email = ?, phone = ?, status = ?, notes = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $editApplication['applicant_name'], $editApplication['position'], $editApplication['email'], $editApplication['phone'], $editApplication['status'], $editApplication['notes'], $editApplication['id']);
        $stmt->execute();
    }
    
    // Update contract status
    if (isset($_POST['signContract'])) {
        $applicationId = $_POST['application_id'];
        $contractSigned = 1;
        $contractDate = date('Y-m-d');
        
        $stmt = $conn->prepare("UPDATE recruitment_applications SET contract_signed = ?, contract_date = ? WHERE id = ?");
        $stmt->bind_param("isi", $contractSigned, $contractDate, $applicationId);
        $stmt->execute();
    }
    
    // Delete applications
    if (isset($_POST['deleteApplications'])) {
        if (isset($_POST['application_checkbox'])) {
            $applicationIds = $_POST['application_checkbox'];
            
            // Create a string with placeholders for each ID
            $placeholders = rtrim(str_repeat('?, ', count($applicationIds)), ', ');
            
            $stmt = $conn->prepare("DELETE FROM recruitment_applications WHERE id IN ($placeholders)");
            
            // Create array of types for bind_param
            $types = str_repeat('i', count($applicationIds));
            
            // Bind the application IDs
            $stmt->bind_param($types, ...$applicationIds);
            $stmt->execute();
        }
    }

    header('Location: recruitment.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recruitment & Selection</title>
    <link rel="stylesheet" href="style_index.css">
    <link rel="icon" type="image/x-icon" href="Superpack-Enterprise-Logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboardnew.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .status-new { background-color: #f8f9fa; }
        .status-screening { background-color: #e2f0ff; }
        .status-interview { background-color: #fff3cd; }
        .status-offer { background-color: #d1ecf1; }
        .status-hired { background-color: #d4edda; }
        .status-rejected { background-color: #f8d7da; }
        
        .contract-signed { 
            color: green;
            font-weight: bold;
        }
        
        .contract-unsigned {
            color: #dc3545;
        }
        
        .resume-link {
            color: #007bff;
            text-decoration: underline;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_small.php'; ?>
    <div class="container-everything" style="height:100%;">
        <div class="container-all">
            <div class="container-top">
                <?php include 'header_2.php'; ?>
            </div>
            <div class="container-search">
                <h2 class="mt-3 mb-4">Recruitment & Selection</h2>
                <p>Plan, implement, and manage recruitment processes, identify job openings, attract qualified candidates, and conduct selection processes.</p>
                
                <!-- Search and add new application -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form method="GET" action="recruitment.php" class="form-inline">
                            <input type="text" name="search_term" class="form-control mr-2" placeholder="Search applications..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </form>
                    </div>
                    <div class="col-md-6 text-right">
                        <button class="btn btn-success" data-toggle="modal" data-target="#addApplicationModal">Add New Application</button>
                    </div>
                </div>
                
                <!-- Applications table -->
                <form method="POST" action="recruitment.php">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Application Date</th>
                                    <th>Status</th>
                                    <th>Contract</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($applications) > 0): ?>
                                    <?php foreach ($applications as $application): ?>
                                        <tr class="status-<?php echo strtolower($application['status']); ?>">
                                            <td><input type="checkbox" name="application_checkbox[]" value="<?php echo $application['id']; ?>"></td>
                                            <td><?php echo htmlspecialchars($application['applicant_name']); ?></td>
                                            <td><?php echo htmlspecialchars($application['position']); ?></td>
                                            <td><?php echo htmlspecialchars($application['email']); ?></td>
                                            <td><?php echo htmlspecialchars($application['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($application['application_date']); ?></td>
                                            <td><?php echo htmlspecialchars($application['status']); ?></td>
                                            <td>
                                                <?php if ($application['contract_signed']): ?>
                                                    <span class="contract-signed">Signed (<?php echo htmlspecialchars($application['contract_date']); ?>)</span>
                                                <?php else: ?>
                                                    <span class="contract-unsigned">Not Signed</span>
                                                    <?php if ($application['status'] == 'Offer' || $application['status'] == 'Hired'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-primary ml-2" 
                                                                onclick="signContract(<?php echo $application['id']; ?>)">Sign</button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        onclick="editApplication(<?php echo $application['id']; ?>, 
                                                        '<?php echo addslashes($application['applicant_name']); ?>', 
                                                        '<?php echo addslashes($application['position']); ?>', 
                                                        '<?php echo addslashes($application['email']); ?>', 
                                                        '<?php echo addslashes($application['phone']); ?>', 
                                                        '<?php echo addslashes($application['status']); ?>', 
                                                        '<?php echo addslashes($application['notes']); ?>')">
                                                    Edit
                                                </button>
                                                <?php if (!empty($application['resume_path'])): ?>
                                                    <a href="<?php echo htmlspecialchars($application['resume_path']); ?>" class="btn btn-sm btn-info ml-1" target="_blank">Resume</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No applications found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-right mb-4">
                        <button type="submit" name="deleteApplications" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete selected applications?')">Delete Selected</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add Application Modal -->
    <div class="modal fade" id="addApplicationModal" tabindex="-1" role="dialog" aria-labelledby="addApplicationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addApplicationModalLabel">Add New Application</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="recruitment.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="applicant_name">Applicant Name</label>
                            <input type="text" class="form-control" id="applicant_name" name="applicant_name" required>
                        </div>
                        <div class="form-group">
                            <label for="position">Position</label>
                            <input type="text" class="form-control" id="position" name="position" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="resume">Resume (PDF or Doc)</label>
                            <input type="file" class="form-control-file" id="resume" name="resume">
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="New">New</option>
                                <option value="Screening">Screening</option>
                                <option value="Interview">Interview</option>
                                <option value="Offer">Offer</option>
                                <option value="Hired">Hired</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="addApplication" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Application Modal -->
    <div class="modal fade" id="editApplicationModal" tabindex="-1" role="dialog" aria-labelledby="editApplicationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editApplicationModalLabel">Edit Application</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="recruitment.php">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="form-group">
                            <label for="edit_applicant_name">Applicant Name</label>
                            <input type="text" class="form-control" id="edit_applicant_name" name="applicant_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_position">Position</label>
                            <input type="text" class="form-control" id="edit_position" name="position" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_phone">Phone</label>
                            <input type="text" class="form-control" id="edit_phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_status">Status</label>
                            <select class="form-control" id="edit_status" name="status" required>
                                <option value="New">New</option>
                                <option value="Screening">Screening</option>
                                <option value="Interview">Interview</option>
                                <option value="Offer">Offer</option>
                                <option value="Hired">Hired</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_notes">Notes</label>
                            <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="editApplication" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sign Contract Form (Hidden) -->
    <form id="signContractForm" method="POST" action="recruitment.php" style="display: none;">
        <input type="hidden" id="application_id" name="application_id">
        <input type="hidden" name="signContract" value="1">
    </form>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Edit application function
        function editApplication(id, name, position, email, phone, status, notes) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_applicant_name').value = name;
            document.getElementById('edit_position').value = position;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_notes').value = notes;
            
            $('#editApplicationModal').modal('show');
        }
        
        // Sign contract function
        function signContract(id) {
            if (confirm('Are you sure you want to mark this contract as signed?')) {
                document.getElementById('application_id').value = id;
                document.getElementById('signContractForm').submit();
            }
        }
        
        // Select all checkboxes
        document.getElementById('selectAll').addEventListener('change', function() {
            var checkboxes = document.getElementsByName('application_checkbox[]');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = this.checked;
            }
        });
    </script>
</body>
</html>
