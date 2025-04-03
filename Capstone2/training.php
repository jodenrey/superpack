<?php
session_start([
    'cookie_lifetime' => 86400, // Set cookie to last for 1 day (86400 seconds)
    'read_and_close'  => false, // Keep session active
]);

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

// Create training_programs table if it doesn't exist
$createTrainingTable = "CREATE TABLE IF NOT EXISTS training_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_name VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Upcoming', 'Ongoing', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Upcoming',
    location VARCHAR(255),
    type ENUM('In-Person', 'Online', 'Hybrid') NOT NULL DEFAULT 'In-Person',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$conn->query($createTrainingTable);

// Create employee_training table for tracking enrollments if it doesn't exist
$createEmployeeTrainingTable = "CREATE TABLE IF NOT EXISTS employee_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    employee_name VARCHAR(255) NOT NULL,
    training_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    completion_date DATE,
    certification_status ENUM('Not Started', 'In Progress', 'Completed', 'Failed') NOT NULL DEFAULT 'Not Started',
    certificate_path VARCHAR(255),
    score FLOAT,
    feedback TEXT,
    FOREIGN KEY (training_id) REFERENCES training_programs(id) ON DELETE CASCADE
)";

$conn->query($createEmployeeTrainingTable);

// Create employees table if it doesn't exist
$createEmployeesTable = "CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    position VARCHAR(100),
    department VARCHAR(100),
    hire_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$conn->query($createEmployeesTable);

// Check if employees table is empty, add some sample employees if needed
$checkEmployeesQuery = "SELECT COUNT(*) as count FROM employees";
$result = $conn->query($checkEmployeesQuery);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Add sample employees
    $sampleEmployees = [
        ['John', 'Doe', 'john.doe@example.com', '1234567890', 'Manager', 'Operations', '2022-01-15'],
        ['Jane', 'Smith', 'jane.smith@example.com', '0987654321', 'Supervisor', 'Production', '2022-02-20'],
        ['Michael', 'Johnson', 'michael.j@example.com', '1122334455', 'Team Lead', 'Quality Control', '2022-03-10'],
        ['Sarah', 'Williams', 'sarah.w@example.com', '5566778899', 'HR Specialist', 'Human Resources', '2022-01-05'],
        ['Robert', 'Brown', 'robert.b@example.com', '9988776655', 'Technician', 'Maintenance', '2022-04-12']
    ];
    
    $stmt = $conn->prepare("INSERT INTO employees (first_name, last_name, email, phone, position, department, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($sampleEmployees as $employee) {
        $stmt->bind_param("sssssss", $employee[0], $employee[1], $employee[2], $employee[3], $employee[4], $employee[5], $employee[6]);
        $stmt->execute();
    }
}

// Initialize search term
$searchTerm = isset($_GET['search_term']) ? $_GET['search_term'] : '';

// Base query to retrieve training programs
$query = "SELECT * FROM training_programs";

// If search term is provided, add WHERE clause with LIKE
if (!empty($searchTerm)) {
    $query .= " WHERE program_name LIKE ? OR description LIKE ? OR location LIKE ?";
}

// Add ORDER BY clause
$query .= " ORDER BY start_date DESC";

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
$trainingPrograms = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all employees for the enrollment form
$employeeQuery = "SELECT id, CONCAT(first_name, ' ', last_name) as employee_name FROM employees ORDER BY first_name";
$employeeResult = $conn->query($employeeQuery);
$employees = [];

if ($employeeResult) {
    while ($row = $employeeResult->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new training program
    if (isset($_POST['addTraining'])) {
        $newTraining = [
            'program_name' => $_POST['program_name'],
            'description' => $_POST['description'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'status' => $_POST['status'],
            'location' => $_POST['location'],
            'type' => $_POST['type']
        ];

        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO training_programs (program_name, description, start_date, end_date, status, location, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $newTraining['program_name'], $newTraining['description'], $newTraining['start_date'], $newTraining['end_date'], $newTraining['status'], $newTraining['location'], $newTraining['type']);
        $stmt->execute();
    }

    // Edit a training program
    if (isset($_POST['editTraining'])) {
        $editTraining = [
            'id' => $_POST['id'],
            'program_name' => $_POST['program_name'],
            'description' => $_POST['description'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'status' => $_POST['status'],
            'location' => $_POST['location'],
            'type' => $_POST['type']
        ];

        // Prepare and bind
        $stmt = $conn->prepare("UPDATE training_programs SET program_name = ?, description = ?, start_date = ?, end_date = ?, status = ?, location = ?, type = ? WHERE id = ?");
        $stmt->bind_param("sssssssi", $editTraining['program_name'], $editTraining['description'], $editTraining['start_date'], $editTraining['end_date'], $editTraining['status'], $editTraining['location'], $editTraining['type'], $editTraining['id']);
        $stmt->execute();
    }
    
    // Enroll employee in training
    if (isset($_POST['enrollEmployee'])) {
        $enrollment = [
            'employee_id' => $_POST['employee_id'],
            'employee_name' => $_POST['employee_name'],
            'training_id' => $_POST['training_id'],
            'enrollment_date' => date('Y-m-d'),
            'certification_status' => 'Not Started'
        ];
        
        // Check if employee is already enrolled in this training
        $checkStmt = $conn->prepare("SELECT id FROM employee_training WHERE employee_id = ? AND training_id = ?");
        $checkStmt->bind_param("ii", $enrollment['employee_id'], $enrollment['training_id']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            // Prepare and bind for new enrollment
            $stmt = $conn->prepare("INSERT INTO employee_training (employee_id, employee_name, training_id, enrollment_date, certification_status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isiss", $enrollment['employee_id'], $enrollment['employee_name'], $enrollment['training_id'], $enrollment['enrollment_date'], $enrollment['certification_status']);
            $stmt->execute();
        }
    }
    
    // Update certification status
    if (isset($_POST['updateCertification'])) {
        $certification = [
            'id' => $_POST['cert_id'],
            'completion_date' => $_POST['completion_date'] ? $_POST['completion_date'] : null,
            'certification_status' => $_POST['certification_status'],
            'score' => $_POST['score'] ? $_POST['score'] : null,
            'feedback' => $_POST['feedback']
        ];
        
        $certificate_path = '';
        
        // Handle certificate upload if provided
        if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] == 0) {
            $uploadDir = 'uploads/certificates/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['certificate']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['certificate']['tmp_name'], $targetFile)) {
                $certificate_path = $targetFile;
            }
        }
        
        // Prepare and bind
        if ($certificate_path) {
            $stmt = $conn->prepare("UPDATE employee_training SET completion_date = ?, certification_status = ?, certificate_path = ?, score = ?, feedback = ? WHERE id = ?");
            $stmt->bind_param("sssdsi", $certification['completion_date'], $certification['certification_status'], $certificate_path, $certification['score'], $certification['feedback'], $certification['id']);
        } else {
            $stmt = $conn->prepare("UPDATE employee_training SET completion_date = ?, certification_status = ?, score = ?, feedback = ? WHERE id = ?");
            $stmt->bind_param("ssdsi", $certification['completion_date'], $certification['certification_status'], $certification['score'], $certification['feedback'], $certification['id']);
        }
        
        $stmt->execute();
    }
    
    // Delete training programs
    if (isset($_POST['deleteTraining'])) {
        if (isset($_POST['training_checkbox'])) {
            $trainingIds = $_POST['training_checkbox'];
            
            // Create a string with placeholders for each ID
            $placeholders = rtrim(str_repeat('?, ', count($trainingIds)), ', ');
            
            $stmt = $conn->prepare("DELETE FROM training_programs WHERE id IN ($placeholders)");
            
            // Create array of types for bind_param
            $types = str_repeat('i', count($trainingIds));
            
            // Bind the training IDs
            $stmt->bind_param($types, ...$trainingIds);
            $stmt->execute();
        }
    }

    header('Location: training.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Training & Development</title>
    <link rel="stylesheet" href="style_index.css">
    <link rel="icon" type="image/x-icon" href="Superpack-Enterprise-Logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboardnew.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .status-upcoming { background-color: #e2f0ff; }
        .status-ongoing { background-color: #fff3cd; }
        .status-completed { background-color: #d4edda; }
        .status-cancelled { background-color: #f8d7da; }
        
        .cert-completed { 
            color: green;
            font-weight: bold;
        }
        
        .cert-inprogress { 
            color: #ffc107;
            font-weight: bold;
        }
        
        .cert-notstarted {
            color: #dc3545;
        }
        
        .nav-tabs .nav-link.active {
            background-color: #4CAF50;
            color: white;
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
                <h2 class="mt-3 mb-4">Training & Development</h2>
                <p>Develop and implement training programs to enhance employee skills and knowledge, ensuring they have the necessary competencies for their roles.</p>
                
                <!-- Tabs for Training Programs and Certifications -->
                <ul class="nav nav-tabs mb-4" id="trainingTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="programs-tab" data-toggle="tab" href="#programs" role="tab">Training Programs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="enrollments-tab" data-toggle="tab" href="#enrollments" role="tab">Employee Enrollments</a>
                    </li>
                </ul>
                
                <div class="tab-content" id="trainingTabContent">
                    <!-- Training Programs Tab -->
                    <div class="tab-pane fade show active" id="programs" role="tabpanel">
                        <!-- Search and add new training -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <form method="GET" action="training.php" class="form-inline">
                                    <input type="text" name="search_term" class="form-control mr-2" placeholder="Search training programs..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                                    <button type="submit" class="btn btn-primary">Search</button>
                                </form>
                            </div>
                            <div class="col-md-6 text-right">
                                <button class="btn btn-success" data-toggle="modal" data-target="#addTrainingModal">Add New Training</button>
                            </div>
                        </div>
                        
                        <!-- Training programs table -->
                        <form method="POST" action="training.php">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="selectAll"></th>
                                            <th>Program Name</th>
                                            <th>Description</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <th>Location</th>
                                            <th>Type</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($trainingPrograms) > 0): ?>
                                            <?php foreach ($trainingPrograms as $program): ?>
                                                <tr class="status-<?php echo strtolower($program['status']); ?>">
                                                    <td><input type="checkbox" name="training_checkbox[]" value="<?php echo $program['id']; ?>"></td>
                                                    <td><?php echo htmlspecialchars($program['program_name']); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($program['description'], 0, 100)) . (strlen($program['description']) > 100 ? '...' : ''); ?></td>
                                                    <td><?php echo htmlspecialchars($program['start_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($program['end_date']); ?></td>
                                                    <td><?php echo htmlspecialchars($program['status']); ?></td>
                                                    <td><?php echo htmlspecialchars($program['location']); ?></td>
                                                    <td><?php echo htmlspecialchars($program['type']); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-primary" 
                                                                    onclick="editTraining(<?php echo $program['id']; ?>, 
                                                                    '<?php echo addslashes($program['program_name']); ?>', 
                                                                    '<?php echo addslashes($program['description']); ?>', 
                                                                    '<?php echo $program['start_date']; ?>', 
                                                                    '<?php echo $program['end_date']; ?>', 
                                                                    '<?php echo $program['status']; ?>', 
                                                                    '<?php echo addslashes($program['location']); ?>', 
                                                                    '<?php echo $program['type']; ?>')">
                                                                Edit
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-success ml-1" 
                                                                    onclick="enrollEmployee(<?php echo $program['id']; ?>, '<?php echo addslashes($program['program_name']); ?>')">
                                                                Enroll
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center">No training programs found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="text-right mb-4">
                                <button type="submit" name="deleteTraining" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete selected training programs?')">Delete Selected</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Employee Enrollments Tab -->
                    <div class="tab-pane fade" id="enrollments" role="tabpanel">
                        <?php
                        // Fetch all enrollments with employee and training details
                        $enrollmentQuery = "
                            SELECT et.*, tp.program_name 
                            FROM employee_training et 
                            JOIN training_programs tp ON et.training_id = tp.id 
                            ORDER BY et.enrollment_date DESC";
                        $enrollmentResult = $conn->query($enrollmentQuery);
                        $enrollments = [];
                        
                        if ($enrollmentResult) {
                            while ($row = $enrollmentResult->fetch_assoc()) {
                                $enrollments[] = $row;
                            }
                        }
                        ?>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Training Program</th>
                                        <th>Enrollment Date</th>
                                        <th>Completion Date</th>
                                        <th>Status</th>
                                        <th>Score</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($enrollments) > 0): ?>
                                        <?php foreach ($enrollments as $enrollment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($enrollment['employee_name']); ?></td>
                                                <td><?php echo htmlspecialchars($enrollment['program_name']); ?></td>
                                                <td><?php echo htmlspecialchars($enrollment['enrollment_date']); ?></td>
                                                <td><?php echo $enrollment['completion_date'] ? htmlspecialchars($enrollment['completion_date']) : 'Not completed'; ?></td>
                                                <td>
                                                    <?php 
                                                    $statusClass = '';
                                                    switch($enrollment['certification_status']) {
                                                        case 'Completed':
                                                            $statusClass = 'cert-completed';
                                                            break;
                                                        case 'In Progress':
                                                            $statusClass = 'cert-inprogress';
                                                            break;
                                                        default:
                                                            $statusClass = 'cert-notstarted';
                                                    }
                                                    ?>
                                                    <span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($enrollment['certification_status']); ?></span>
                                                </td>
                                                <td><?php echo $enrollment['score'] !== null ? htmlspecialchars($enrollment['score']) : 'N/A'; ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-primary" 
                                                                onclick="updateCertification(<?php echo $enrollment['id']; ?>, 
                                                                '<?php echo addslashes($enrollment['employee_name']); ?>', 
                                                                '<?php echo addslashes($enrollment['program_name']); ?>', 
                                                                '<?php echo $enrollment['certification_status']; ?>', 
                                                                '<?php echo $enrollment['completion_date']; ?>', 
                                                                '<?php echo $enrollment['score']; ?>', 
                                                                '<?php echo addslashes($enrollment['feedback'] ?? ''); ?>')">
                                                            Update Status
                                                        </button>
                                                        <?php if (!empty($enrollment['certificate_path'])): ?>
                                                            <a href="<?php echo htmlspecialchars($enrollment['certificate_path']); ?>" class="btn btn-sm btn-info ml-1" target="_blank">Certificate</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No employee enrollments found.</td>
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
    
    <!-- Add Training Program Modal -->
    <div class="modal fade" id="addTrainingModal" tabindex="-1" role="dialog" aria-labelledby="addTrainingModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTrainingModalLabel">Add New Training Program</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="training.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="program_name">Program Name</label>
                            <input type="text" class="form-control" id="program_name" name="program_name" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="Upcoming">Upcoming</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" class="form-control" id="location" name="location">
                        </div>
                        <div class="form-group">
                            <label for="type">Type</label>
                            <select class="form-control" id="type" name="type" required>
                                <option value="In-Person">In-Person</option>
                                <option value="Online">Online (E-Learning)</option>
                                <option value="Hybrid">Hybrid</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="addTraining" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Training Program Modal -->
    <div class="modal fade" id="editTrainingModal" tabindex="-1" role="dialog" aria-labelledby="editTrainingModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTrainingModalLabel">Edit Training Program</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="training.php">
                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="form-group">
                            <label for="edit_program_name">Program Name</label>
                            <input type="text" class="form-control" id="edit_program_name" name="program_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_description">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_start_date">Start Date</label>
                            <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_end_date">End Date</label>
                            <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_status">Status</label>
                            <select class="form-control" id="edit_status" name="status" required>
                                <option value="Upcoming">Upcoming</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_location">Location</label>
                            <input type="text" class="form-control" id="edit_location" name="location">
                        </div>
                        <div class="form-group">
                            <label for="edit_type">Type</label>
                            <select class="form-control" id="edit_type" name="type" required>
                                <option value="In-Person">In-Person</option>
                                <option value="Online">Online (E-Learning)</option>
                                <option value="Hybrid">Hybrid</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="editTraining" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Enroll Employee Modal -->
    <div class="modal fade" id="enrollEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="enrollEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="enrollEmployeeModalLabel">Enroll Employee</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="training.php">
                    <div class="modal-body">
                        <input type="hidden" id="training_id" name="training_id">
                        <div class="form-group">
                            <label for="training_name">Training Program</label>
                            <input type="text" class="form-control" id="training_name" readonly>
                        </div>
                        <div class="form-group">
                            <label for="employee_id">Select Employee</label>
                            <select class="form-control" id="employee_id" name="employee_id" required>
                                <option value="">-- Select Employee --</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>" data-name="<?php echo htmlspecialchars($employee['employee_name']); ?>">
                                        <?php echo htmlspecialchars($employee['employee_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="employee_name" name="employee_name">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="enrollEmployee" class="btn btn-primary">Enroll</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Update Certification Modal -->
    <div class="modal fade" id="certificationModal" tabindex="-1" role="dialog" aria-labelledby="certificationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="certificationModalLabel">Update Certification Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="training.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="cert_id" name="cert_id">
                        <div class="form-group">
                            <label>Employee: <span id="cert_employee"></span></label>
                        </div>
                        <div class="form-group">
                            <label>Training Program: <span id="cert_program"></span></label>
                        </div>
                        <div class="form-group">
                            <label for="certification_status">Certification Status</label>
                            <select class="form-control" id="certification_status" name="certification_status" required>
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Failed">Failed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="completion_date">Completion Date</label>
                            <input type="date" class="form-control" id="completion_date" name="completion_date">
                        </div>
                        <div class="form-group">
                            <label for="score">Score/Grade (optional)</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" id="score" name="score" placeholder="e.g. 85.5">
                        </div>
                        <div class="form-group">
                            <label for="feedback">Feedback/Notes</label>
                            <textarea class="form-control" id="feedback" name="feedback" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="certificate">Upload Certificate (PDF)</label>
                            <input type="file" class="form-control-file" id="certificate" name="certificate">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="updateCertification" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Edit training program function
        function editTraining(id, name, description, startDate, endDate, status, location, type) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_program_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_start_date').value = startDate;
            document.getElementById('edit_end_date').value = endDate;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_location').value = location;
            document.getElementById('edit_type').value = type;
            
            $('#editTrainingModal').modal('show');
        }
        
        // Enroll employee function
        function enrollEmployee(trainingId, trainingName) {
            document.getElementById('training_id').value = trainingId;
            document.getElementById('training_name').value = trainingName;
            
            $('#enrollEmployeeModal').modal('show');
        }
        
        // Update certification function
        function updateCertification(certId, employeeName, programName, status, completionDate, score, feedback) {
            document.getElementById('cert_id').value = certId;
            document.getElementById('cert_employee').textContent = employeeName;
            document.getElementById('cert_program').textContent = programName;
            document.getElementById('certification_status').value = status;
            document.getElementById('completion_date').value = completionDate || '';
            document.getElementById('score').value = score || '';
            document.getElementById('feedback').value = feedback || '';
            
            $('#certificationModal').modal('show');
        }
        
        // Select all checkboxes
        document.getElementById('selectAll').addEventListener('change', function() {
            var checkboxes = document.getElementsByName('training_checkbox[]');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = this.checked;
            }
        });
        
        // Update hidden employee name field when selection changes
        document.getElementById('employee_id').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var employeeName = selectedOption.getAttribute('data-name');
            document.getElementById('employee_name').value = employeeName;
        });
    </script>
</body>
</html>