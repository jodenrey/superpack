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

// Get the correct username from session
$username = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

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

// Check if evaluator column exists, if not add it
$checkEvaluatorColumn = $conn->query("SHOW COLUMNS FROM worker_evaluations LIKE 'evaluator'");
if ($checkEvaluatorColumn->num_rows === 0) {
    // Column doesn't exist, add it
    $addColumnQuery = "ALTER TABLE worker_evaluations ADD COLUMN evaluator VARCHAR(255) DEFAULT 'Anonymous'";
    if ($conn->query($addColumnQuery)) {
        echo "<div class='alert alert-success'>Added 'evaluator' column to track which admin performed each evaluation.</div>";
    } else {
        echo "<div class='alert alert-danger'>Error adding evaluator column: " . $conn->error . "</div>";
    }
}

// Create promotions table if it doesn't exist
$createPromotionsTable = "CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) NOT NULL,
    employee_name VARCHAR(255) NOT NULL,
    current_position VARCHAR(255) NOT NULL,
    promoted_to_position VARCHAR(255) NOT NULL,
    promotion_date DATE NOT NULL,
    promoted_by VARCHAR(255) NOT NULL,
    evaluation_id VARCHAR(50) DEFAULT NULL,
    reason TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($createPromotionsTable)) {
    // Table created or already exists
} else {
    echo "<div class='alert alert-danger'>Error creating promotions table: " . $conn->error . "</div>";
}

// Initialize variables
$tasksTable = 'worker_evaluations';

// Bind the search ID with wildcard if a search term is provided
if (!empty($searchId)) {
    $searchTerm = '%' . $searchId . '%';
    $stmt->bind_param("s", $searchTerm);
}

// Initialize search ID
$searchId = isset($_GET['search_id']) ? $_GET['search_id'] : '';

// Base query to retrieve tasks from the database
$query = "SELECT * FROM $tasksTable";

// If search_id is provided, add WHERE clause with LIKE
if (!empty($searchId)) {
    $query .= " WHERE id LIKE ?";
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

// Handling form submissions for adding, editing, and deleting evaluations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addEvaluation'])) {
        $newEvaluation = [
            "id" => "EV" . rand(1000, 9999),
            "employee_id" => $_POST['employee_id'],
            "name" => $_POST['name'],
            "position" => $_POST['position'],
            "department" => $_POST['department'],
            "start_date" => $_POST['start_date'],
            "comments" => $_POST['comments'],
            // Sum the first 10 criteria ratings, assuming they're named 'criteria_1' to 'criteria_10' in the form
            "performance" => array_sum(array_map('intval', array_slice($_POST, array_search('criteria_1', array_keys($_POST)), 10))),
            "evaluator" => $username // Add the current admin username as the evaluator
        ];

        // Insert new evaluation into the database
        $stmt = $conn->prepare("INSERT INTO worker_evaluations (id, employee_id, name, position, department, start_date, comments, performance, evaluator) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "sssssssis",
            $newEvaluation['id'],
            $newEvaluation['employee_id'],
            $newEvaluation['name'],
            $newEvaluation['position'],
            $newEvaluation['department'],
            $newEvaluation['start_date'],
            $newEvaluation['comments'],
            $newEvaluation['performance'],
            $newEvaluation['evaluator']
        );
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>New evaluation added successfully.</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
    }

    if (isset($_POST['editEvaluation'])) {
        // Get the specific evaluation ID to update
        $evaluationId = trim($_POST['id']);
        
        // Validate that we have a proper ID
        if (empty($evaluationId)) {
            die("<div class='alert alert-danger'>Error: No evaluation ID provided!</div>");
        }
        
        // Debug output - only display temporarily for troubleshooting
        echo "<div class='alert alert-info'>Processing edit for evaluation ID: " . htmlspecialchars($evaluationId) . "</div>";
        
        // Calculate the performance score from the ratings
        $performance = 0;
        for ($i = 1; $i <= 10; $i++) {
            if (isset($_POST['criteria_' . $i])) {
                $performance += intval($_POST['criteria_' . $i]);
            }
        }
        
        // Check if the ID exists in the database before updating
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM worker_evaluations WHERE id = ?");
        $checkStmt->bind_param("s", $evaluationId); // Bind as string
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();
        
        if ($count != 1) {
            echo "<div class='alert alert-danger'>Error: Evaluation ID '$evaluationId' not found in database!</div>";
        } else {
            // Prepare the update statement with WHERE clause that targets only the specific ID
            $stmt = $conn->prepare("UPDATE worker_evaluations SET start_date = ?, comments = ?, performance = ?, evaluator = ? WHERE id = ?");
            
            if (!$stmt) {
                die("<div class='alert alert-danger'>Error in preparing statement: " . $conn->error . "</div>");
            }
            
            // Get the form values to update
            $startDate = $_POST['start_date'];
            $comments = $_POST['comments'];
            
            // Only bind the fields that should be editable (not employee info)
            $stmt->bind_param("ssiss", $startDate, $comments, $performance, $username, $evaluationId);
            
            if ($stmt->execute()) {
                // Check how many rows were affected - should be exactly 1
                $rowsAffected = $stmt->affected_rows;
                if ($rowsAffected === 1) {
                    echo "<div class='alert alert-success'>Evaluation updated successfully. (1 record updated)</div>";
                } else {
                    echo "<div class='alert alert-warning'>Warning: Expected to update 1 record, but updated " . $rowsAffected . " records.</div>";
                }
            } else {
                echo "<div class='alert alert-danger'>Error updating evaluation: " . $stmt->error . "</div>";
            }
        }
    }
    

    // Delete evaluations
    if (isset($_POST['deleteTask'])) {
        $taskIds = $_POST['task_checkbox'];
        $placeholders = rtrim(str_repeat('?,', count($taskIds)), ',');
        $stmt = $conn->prepare("DELETE FROM worker_evaluations WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('s', count($taskIds)), ...$taskIds);
        if ($stmt->execute()) {
            echo "Selected evaluations deleted successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }
    }

    // Handle promotion
    if (isset($_POST['promoteEmployee'])) {
        $employeeId = $_POST['employee_id'];
        $employeeName = $_POST['employee_name'];
        $currentPosition = $_POST['current_position'];
        $promotedToPosition = $_POST['promoted_to_position'];
        $promotionDate = $_POST['promotion_date'];
        $reason = $_POST['reason'];
        $evaluationId = $_POST['evaluation_id'];
        
        // Insert promotion record
        $stmt = $conn->prepare("INSERT INTO promotions (employee_id, employee_name, current_position, promoted_to_position, promotion_date, promoted_by, evaluation_id, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $employeeId, $employeeName, $currentPosition, $promotedToPosition, $promotionDate, $username, $evaluationId, $reason);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Employee promoted successfully!</div>";
            
            // Optionally update the employee's position in the employee_records table
            $updateStmt = $conn->prepare("UPDATE employee_records SET position = ? WHERE id = ? OR name = ?");
            $updateStmt->bind_param("sss", $promotedToPosition, $employeeId, $employeeName);
            
            if ($updateStmt->execute()) {
                echo "<div class='alert alert-info'>Employee record updated with new position.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Error promoting employee: " . $stmt->error . "</div>";
        }
    }

    // Redirect to avoid resubmitting the form on page refresh
    header('Location: worker_eval.php');
    exit();
}

// Initialize sort direction and sort field
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id'; // Default sort field
$direction = isset($_GET['dir']) && $_GET['dir'] === 'DESC' ? 'DESC' : 'ASC'; // Default direction

// Get all the parameters
$employee_id = $_GET['employee_id'] ?? '';
$name = $_GET['name'] ?? '';
$position = $_GET['position'] ?? '';
$department = $_GET['department'] ?? '';
$start_date = $_GET['start_date'] ?? '';

// Build the query with filters and sorting
$query = "SELECT * FROM worker_evaluations WHERE 1=1"; // Always true condition for appending WHERE clauses

// Initialize arrays for binding parameters
$bindTypes = '';
$bindValues = [];

// Append filters to the query if the user provided values
if (!empty($employee_id)) {
    $query .= " AND employee_id = ?";
    $bindTypes .= 's';
    $bindValues[] = $employee_id;
}
if (!empty($name)) {
    $query .= " AND name = ?";
    $bindTypes .= 's';
    $bindValues[] = $name;
}
if (!empty($position)) {
    $query .= " AND position = ?";
    $bindTypes .= 's';
    $bindValues[] = $position;
}
if (!empty($department)) {
    $query .= " AND department = ?";
    $bindTypes .= 's';
    $bindValues[] = $department;
}
if (!empty($start_date)) {
    $query .= " AND start_date = ?";
    $bindTypes .= 's';
    $bindValues[] = $start_date;
}

// Append sorting
if (in_array($sort, ['id', 'employee_id', 'name', 'position', 'department', 'start_date'])) {
    $query .= " ORDER BY $sort $direction";
}

// Prepare the statement
$stmt = $conn->prepare($query);

// Bind the parameters if there are any
if (!empty($bindValues)) {
    $stmt->bind_param($bindTypes, ...$bindValues);
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Employee Evaluations</title>
        <link rel="stylesheet" href="style_index.css">
        <link rel="icon" type="image/x-icon" href="Superpack-Enterprise-Logo.png">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <link rel="stylesheet" href="dashboardnew.css">
        <style>

        .carousel-item {
            height: auto;
            padding: 20px;
        }
        .carousel-item img {
            object-fit: cover;
            width: 100%;
        }
        .rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            font-size: 24px;
            margin: 10px 0;
        }
        .rating input {
            display: none;
        }
        .rating label {
            color: #ddd;
            cursor: pointer;
        }
        .rating input:checked ~ label {
            color: gold;
        }
        .form-content {
            max-height: 500px;
            overflow-y: auto;
        }
        .carousel-control-prev, .carousel-control-next {
            width: 5%;
        }
        .form-group textarea {
            height: 150px;
        }
        .form-group input, .form-group select, .form-group textarea {
            font-size: 16px;
            padding: 10px;
        }
        .carousel-control-prev-icon, .carousel-control-next-icon {
            background-color: rgba(0,0,0,0.5);
            border-radius: 50%;
        }
        .print-btn {
            margin-bottom: 20px;
        }
        /* Style for read-only fields */
        input[readonly] {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            color: #6c757d;
            cursor: not-allowed;
        }
        /* Make it clear these fields cannot be edited */
        input[readonly]::after {
            content: " (Cannot be edited)";
            font-size: 12px;
            color: #dc3545;
        }
        </style>
    </head>

    <body>
        <?php include 'sidebar_small.php'; ?>
        <?php include 'eval_sidebar.php'; ?>
        <div class="container-everything" style="height:100%;">
            <div class="container-all">
                <div class="container-top">
                    <?php include 'header_2.php'; ?>
                </div>
                <div class="container-search">
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
                        </form>
                    </div>
                    
                    </div>
                    <div class="container-bottom">
                        <div class="container-table">
                                <div class="tool-bar">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div style="color: #FFFAFA;">
                                        <span id="selected-count">0</span> items selected
                                    </div>
                                    
                                    <div class="d-flex align-items-center" style="gap:10px;">
                                        
                                        <!-- Start the form for deletion -->

                                        <button class="btn btn-primary" id="editButton" name="editTaskMod" data-toggle="modal" data-target="#editEvaluationModal" disabled>Edit Evaluation</button>
                                        <button class="btn btn-success" id="promoteButton" name="promoteEmployee" data-toggle="modal" data-target="#promoteModal" disabled>Promote Employee</button>
                                        <!-- <button class="btn btn-secondary" onclick="window.print()">Print</button> -->
                                        
                                        <div>
                                            <form method="get" action="task_management.php">
                                                <input type="hidden" name="department" value="<?php echo htmlspecialchars($department); ?>">
                                                <input type="hidden" name="export" value="excel">
                                                <button type="submit" class="btn btn-success">Export to Excel</button>
                                            </form>
                                        </div>
                                        <button class="btn btn-info" onclick="window.location.href='worker_eval.php'">Reset</button>
                                        <button class="btn btn-warning" type="button" onclick="toggle_filter()">Filter</button>
                                        <a href="promotion_history.php" class="btn btn-success">
                                            <i class="fas fa-history"></i> View Promotions
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="table-container">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr> <!-- style="display: block; width: 100%; height: 100%; text-decoration: none; color: inherit;" -->
                                            <th class="checkbox-col"></th> <!-- Empty column for the checkbox -->
                                            
                                            <!-- Sorting by ID -->
                                            <th>
                                                <a class="sort-link" href="?sort=id&dir=<?php echo ($sort === 'id' && $direction === 'ASC') ? 'DESC' : 'ASC'; ?>" style="text-decoration:none;">
                                                    ID
                                                </a>
                                            </th>
                                            
                                            <!-- Sorting by Employee ID -->
                                            <th>
                                                <a class="sort-link" href="?sort=employee_id&dir=<?php echo ($sort === 'employee_id' && $direction === 'ASC') ? 'DESC' : 'ASC'; ?>" style="text-decoration:none;">
                                                    Employee ID
                                                </a>
                                            </th>
                                            
                                            <!-- Sorting by Name -->
                                            <th>
                                                <a class="sort-link" href="?sort=name&dir=<?php echo ($sort === 'name' && $direction === 'ASC') ? 'DESC' : 'ASC'; ?>" style="text-decoration:none;">
                                                    Name
                                                </a>
                                            </th>
                                            
                                            <!-- Sorting by Position -->
                                            <th>
                                                <a class="sort-link" href="?sort=position&dir=<?php echo ($sort === 'position' && $direction === 'ASC') ? 'DESC' : 'ASC'; ?>" style="text-decoration:none;">
                                                    Position
                                                </a>
                                            </th>
                                            
                                            <!-- Sorting by Department -->
                                            <th>
                                                <a class="sort-link" href="?sort=department&dir=<?php echo ($sort === 'department' && $direction === 'ASC') ? 'DESC' : 'ASC'; ?>" style="text-decoration:none;">
                                                    Department
                                                </a>
                                            </th>
                                            
                                            <!-- Sorting by Start Date -->
                                            <th>
                                                <a class="sort-link" href="?sort=start_date&dir=<?php echo ($sort === 'start_date' && $direction === 'ASC') ? 'DESC' : 'ASC'; ?>" style="text-decoration:none;">
                                                    Start Date
                                                </a>
                                            </th>
                                            
                                            <!-- Comments (No sorting) -->
                                            <th>Comments</th>
                                            
                                            <!-- Performance (No sorting) -->
                                            <th>Performance</th> 
                                            
                                            <!-- Evaluator Column (No sorting) -->
                                            <th>Evaluated By</th>
                                            
                                            <!-- Promotion Status Column -->
                                            <th>Promotion Status</th>
                                        </tr>

                                    </thead>

                                    <tbody>
                                        <?php foreach ($tasks as $task): ?>
                                        <tr data-id="<?php echo $task['id']; ?>" 
                                           data-employee-id="<?php echo $task['employee_id']; ?>"
                                           data-name="<?php echo $task['name']; ?>"
                                           data-position="<?php echo $task['position']; ?>"
                                           data-department="<?php echo $task['department']; ?>"
                                           data-start-date="<?php echo $task['start_date']; ?>"
                                           data-comments="<?php echo $task['comments']; ?>"
                                           data-performance="<?php echo $task['performance']; ?>"
                                           data-evaluator="<?php echo $task['evaluator'] ?? 'Unknown'; ?>">
                                            <td>
                                                <!-- Make sure the checkbox is inside the form -->
                                                <input type="checkbox" id="chkbx" name="task_checkbox[]" form="deleteForm" value="<?php echo $task['id']; ?>" onclick="updateSelectedCount(this)">
                                            </td> <!-- Checkbox before ID -->
                                            <td><?php echo $task['id']; ?></td>
                                            <td><?php echo $task['employee_id']; ?></td>
                                            <td><?php echo $task['name']; ?></td> <!-- Updated to 'Assigned' -->
                                            <td><?php echo $task['position']; ?></td>
                                            <td><?php echo $task['department']; ?></td>
                                            <td><?php echo $task['start_date']; ?></td>
                                            <td><?php echo $task['comments']; ?></td>
                                            <td><?php echo $task['performance']; ?></td>
                                            <td><?php echo !empty($task['evaluator']) ? $task['evaluator'] : 'Unknown'; ?></td>
                                            <td>
                                                <?php
                                                // Check if this evaluation led to a promotion
                                                $promotionCheck = $conn->prepare("SELECT * FROM promotions WHERE evaluation_id = ?");
                                                $promotionCheck->bind_param("s", $task['id']);
                                                $promotionCheck->execute();
                                                $promotionResult = $promotionCheck->get_result();
                                                
                                                if ($promotionResult->num_rows > 0) {
                                                    $promotion = $promotionResult->fetch_assoc();
                                                    echo "<span class='badge badge-success'>Promoted to " . $promotion['promoted_to_position'] . "</span>";
                                                } else {
                                                    echo "<span class='badge badge-secondary'>Not Promoted</span>";
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Evaluation Modal -->
        <div class="modal fade" id="addEvaluationModal" tabindex="-1" role="dialog" aria-labelledby="addEvaluationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addEvaluationModalLabel">Add Evaluation</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="POST">
                        <div class="modal-body form-content">
                            <div class="row">
                                <!-- Left Column: Worker Information -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="employee_id">Employee ID</label>
                                        <input type="text" class="form-control" id="employee_id" name="employee_id" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="position">Position</label>
                                        <input type="text" class="form-control" id="position" name="position" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="department">Department</label>
                                        <select class="form-control" id="department" name="department" required>
                                            <option value="">Select Department</option>
                                            <option value="Sales">Sales</option>
                                            <option value="Purchasing">Purchasing</option>
                                            <option value="Purchase Development">Purchase Development</option>
                                            <option value="Warehouse">Warehouse</option>
                                            <option value="Logistics">Logistics</option>
                                            <option value="Accounting">Accounting</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="start_date">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="comments">Additional Comments</label>
                                        <textarea class="form-control" id="comments" name="comments"></textarea>
                                    </div>
                                </div>
                                
                                <!-- Right Column: Performance Evaluation -->
                                <div class="col-md-6">
                                    <h5 class="mb-4">Performance Ratings</h5>
                                    <?php
                                    $questions = [
                                        "Quality of Work",
                                        "Punctuality",
                                        "Team Collaboration",
                                        "Problem Solving",
                                        "Communication Skills",
                                        "Leadership Skills",
                                        "Technical Skills",
                                        "Adaptability",
                                        "Creativity",
                                        "Overall Performance"
                                    ];
                                    
                                    foreach ($questions as $index => $question): ?>
                                    <div class="form-group">
                                        <label for="criteria_<?php echo $index + 1; ?>"><?php echo $question; ?></label>
                                        <div class="rating">
                                            <?php for ($j = 5; $j >= 1; $j--): ?>
                                            <input type="radio" id="criteria_<?php echo $index + 1; ?>_<?php echo $j; ?>" name="criteria_<?php echo $index + 1; ?>" value="<?php echo $j; ?>" required>
                                            <label for="criteria_<?php echo $index + 1; ?>_<?php echo $j; ?>">&#9733;</label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="addEvaluation" class="btn btn-primary">Save Evaluation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
         <!-- Edit Evaluation Modal -->
        <div class="modal fade" id="editEvaluationModal" tabindex="-1" role="dialog" aria-labelledby="editEvaluationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editEvaluationModalLabel">Edit Evaluation</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="POST">
                        <div class="modal-body form-content">
                            <div class="row">
                                <!-- Left Column: Worker Information -->
                                <div class="col-md-6">
                                    <!-- ID field - critical for identifying which record to update -->
                                    <div class="form-group">
                                        <label for="edit_id"><strong>Evaluation ID (Do Not Change)</strong></label>
                                        <input type="text" class="form-control bg-warning" id="edit_id" name="id" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_employee_id">Employee ID</label>
                                        <input type="text" class="form-control" id="edit_employee_id" name="employee_id" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_name">Name</label>
                                        <input type="text" class="form-control" id="edit_name" name="name" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_position">Position</label>
                                        <input type="text" class="form-control" id="edit_position" name="position" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_department">Department</label>
                                        <input type="text" class="form-control" id="edit_department" name="department" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_start_date">Start Date</label>
                                        <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_comments">Additional Comments</label>
                                        <textarea class="form-control" id="edit_comments" name="comments"></textarea>
                                    </div>
                                    <!-- Show who originally evaluated this employee -->
                                    <div class="form-group">
                                        <label for="edit_evaluator">Originally Evaluated By</label>
                                        <input type="text" class="form-control" id="edit_evaluator" name="original_evaluator" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="current_evaluator">Current Evaluator (You)</label>
                                        <input type="text" class="form-control" id="current_evaluator" value="<?php echo htmlspecialchars($username); ?>" readonly>
                                        <small class="form-text text-muted">Your username will be recorded as the latest evaluator.</small>
                                    </div>
                                </div>
                                
                                <!-- Right Column: Performance Evaluation -->
                                <div class="col-md-6">
                                    <h5 class="mb-4">Performance Ratings</h5>
                                    <?php
                                    $questions = [
                                        "Quality of Work",
                                        "Punctuality",
                                        "Team Collaboration",
                                        "Problem Solving",
                                        "Communication Skills",
                                        "Leadership Skills",
                                        "Technical Skills",
                                        "Adaptability",
                                        "Creativity",
                                        "Overall Performance"
                                    ];
                                    
                                    foreach ($questions as $index => $question): ?>
                                    <div class="form-group">
                                        <label for="edit_criteria_<?php echo $index + 1; ?>"><?php echo $question; ?></label>
                                        <div class="rating">
                                            <?php for ($j = 5; $j >= 1; $j--): ?>
                                            <input type="radio" id="edit_criteria_<?php echo $index + 1; ?>_<?php echo $j; ?>" name="criteria_<?php echo $index + 1; ?>" value="<?php echo $j; ?>" required>
                                            <label for="edit_criteria_<?php echo $index + 1; ?>_<?php echo $j; ?>">&#9733;</label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="editEvaluation" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
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
            logoName.textContent = 'Employee Evaluation';

            function printTable() {
            var printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Print Evaluation</title>');
            printWindow.document.write('<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">');
            printWindow.document.write('</head><body >');
            printWindow.document.write(document.querySelector('table').outerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            }

            function updateSelectedCount(checkbox) {
                var selectedCount = $('input[name="task_checkbox[]"]:checked').length;
                $('#selected-count').text(selectedCount);
            }

            // Get the checkbox and delete button
            var checkbox = document.querySelector('input[name="task_checkbox[]"]');
            var deleteButton = document.querySelector('button[name="deleteTask"]');

            // Function to toggle the delete button based on checkbox state
            function toggleDeleteButton() {
                var selectedCount = document.querySelectorAll('input[name="task_checkbox[]"]:checked').length;
                deleteButton.disabled = selectedCount === 0;
            }

            // Add event listener to the checkbox
            checkbox.addEventListener('change', toggleDeleteButton);

            // Get the checkbox and edit button
            var editButton = document.querySelector('button[name="editTaskMod"]');

            // Edit Button toggling based on checkbox state
            document.querySelectorAll('input[name="task_checkbox[]"]').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    var selectedCount = document.querySelectorAll('input[name="task_checkbox[]"]:checked').length;
                    var editButton = document.querySelector('button[name="editTaskMod"]');
                    editButton.disabled = selectedCount !== 1; // Only enable if one task is selected
                });
            });

            function toggle_filter() {
                var sidebar = document.querySelector('.filter-sidebar');
                if (sidebar.style.right === '-300px') {
                    sidebar.style.right = '0';
                } else {
                    sidebar.style.right = '-300px';
                }
            }
            
            // JavaScript to handle populating the edit form with selected employee data
            document.addEventListener('DOMContentLoaded', function() {
                const editButton = document.getElementById('editButton');
                const promoteButton = document.getElementById('promoteButton');
                const checkboxes = document.querySelectorAll('input[name="task_checkbox[]"]');
                
                // Update selected count and toggle buttons
                function updateSelectedCount() {
                    const selectedCheckboxes = document.querySelectorAll('input[name="task_checkbox[]"]:checked');
                    const selectedCount = selectedCheckboxes.length;
                    
                    // Update count display
                    document.getElementById('selected-count').textContent = selectedCount;
                    
                    // Toggle delete button
                    const deleteButton = document.querySelector('button[name="deleteTask"]');
                    if (deleteButton) {
                        deleteButton.disabled = selectedCount === 0;
                    }
                    
                    // Toggle edit and promote buttons - only enable if exactly one checkbox is selected
                    if (editButton) {
                        editButton.disabled = selectedCount !== 1;
                    }
                    if (promoteButton) {
                        promoteButton.disabled = selectedCount !== 1;
                    }
                }
                
                // Add event listeners to all checkboxes
                checkboxes.forEach(function(checkbox) {
                    checkbox.addEventListener('change', updateSelectedCount);
                });
                
                // Handle edit button click to populate the form
                editButton.addEventListener('click', function() {
                    // Find the selected checkbox
                    const selectedCheckbox = document.querySelector('input[name="task_checkbox[]"]:checked');
                    
                    if (selectedCheckbox) {
                        // Get the parent row
                        const selectedRow = selectedCheckbox.closest('tr');
                        
                        if (selectedRow) {
                            // Get data from the row
                            const id = selectedRow.getAttribute('data-id');
                            const employeeId = selectedRow.getAttribute('data-employee-id');
                            const name = selectedRow.getAttribute('data-name');
                            const position = selectedRow.getAttribute('data-position');
                            const department = selectedRow.getAttribute('data-department');
                            const startDate = selectedRow.getAttribute('data-start-date');
                            const comments = selectedRow.getAttribute('data-comments');
                            const performance = selectedRow.getAttribute('data-performance');
                            const evaluator = selectedRow.getAttribute('data-evaluator');
                            
                            console.log("Loading data for employee ID:", id, "Employee data:", {
                                employeeId, name, position, department, startDate, comments, performance, evaluator
                            });
                            
                            // Ensure the hidden ID field is populated (this is the critical field for the update)
                            const idField = document.getElementById('edit_id');
                            idField.value = id;
                            
                            // Verify ID is set correctly
                            console.log("Set edit_id field value to:", idField.value);
                            
                            // Populate the rest of the edit form
                            document.getElementById('edit_employee_id').value = employeeId;
                            document.getElementById('edit_name').value = name;
                            document.getElementById('edit_position').value = position;
                            document.getElementById('edit_department').value = department;
                            document.getElementById('edit_start_date').value = startDate;
                            document.getElementById('edit_comments').value = comments;
                            document.getElementById('edit_evaluator').value = evaluator || 'Unknown';
                        }
                    }
                });
                
                // Handle promote button click to populate the promotion form
                promoteButton.addEventListener('click', function() {
                    // Find the selected checkbox
                    const selectedCheckbox = document.querySelector('input[name="task_checkbox[]"]:checked');
                    
                    if (selectedCheckbox) {
                        // Get the parent row
                        const selectedRow = selectedCheckbox.closest('tr');
                        
                        if (selectedRow) {
                            // Get data from the row
                            const id = selectedRow.getAttribute('data-id');
                            const employeeId = selectedRow.getAttribute('data-employee-id');
                            const name = selectedRow.getAttribute('data-name');
                            const position = selectedRow.getAttribute('data-position');
                            const department = selectedRow.getAttribute('data-department');
                            const performance = selectedRow.getAttribute('data-performance');
                            
                            console.log("Loading promotion data for employee:", {
                                id, employeeId, name, position, department, performance
                            });
                            
                            // Populate the promotion form
                            document.getElementById('promote_evaluation_id').value = id;
                            document.getElementById('promote_employee_id').value = employeeId;
                            document.getElementById('promote_employee_name').value = name;
                            document.getElementById('promote_current_position').value = position;
                            document.getElementById('promote_current_department').value = department;
                            document.getElementById('promote_performance_score').value = performance + '/50';
                            
                            // Clear previous selections
                            document.getElementById('promote_to_position').value = '';
                            document.getElementById('promote_reason').value = '';
                        }
                    }
                });
                
                // Initial call to set button states
                updateSelectedCount();
            });
        </script>

        <!-- Promote Employee Modal -->
        <div class="modal fade" id="promoteModal" tabindex="-1" role="dialog" aria-labelledby="promoteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="promoteModalLabel">Promote Employee</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-3 text-info">Employee Information</h6>
                                    
                                    <input type="hidden" id="promote_evaluation_id" name="evaluation_id">
                                    
                                    <div class="form-group">
                                        <label for="promote_employee_id">Employee ID</label>
                                        <input type="text" class="form-control" id="promote_employee_id" name="employee_id" readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="promote_employee_name">Employee Name</label>
                                        <input type="text" class="form-control" id="promote_employee_name" name="employee_name" readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="promote_current_position">Current Position</label>
                                        <input type="text" class="form-control" id="promote_current_position" name="current_position" readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="promote_current_department">Current Department</label>
                                        <input type="text" class="form-control" id="promote_current_department" name="current_department" readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="promote_performance_score">Performance Score</label>
                                        <input type="text" class="form-control" id="promote_performance_score" name="performance_score" readonly>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6 class="mb-3 text-success">Promotion Details</h6>
                                    
                                    <div class="form-group">
                                        <label for="promote_to_position">Promote To Position *</label>
                                        <select class="form-control" id="promote_to_position" name="promoted_to_position" required>
                                            <option value="">Select New Position</option>
                                            <optgroup label="Management Positions">
                                                <option value="Team Leader">Team Leader</option>
                                                <option value="Supervisor">Supervisor</option>
                                                <option value="Manager">Manager</option>
                                                <option value="Senior Manager">Senior Manager</option>
                                                <option value="Assistant Director">Assistant Director</option>
                                                <option value="Director">Director</option>
                                            </optgroup>
                                            <optgroup label="Senior Positions">
                                                <option value="Senior Sales Representative">Senior Sales Representative</option>
                                                <option value="Senior Purchaser">Senior Purchaser</option>
                                                <option value="Senior Developer">Senior Developer</option>
                                                <option value="Senior Warehouse Staff">Senior Warehouse Staff</option>
                                                <option value="Senior Logistics Coordinator">Senior Logistics Coordinator</option>
                                                <option value="Senior Accountant">Senior Accountant</option>
                                            </optgroup>
                                            <optgroup label="Specialist Positions">
                                                <option value="Sales Specialist">Sales Specialist</option>
                                                <option value="Purchasing Specialist">Purchasing Specialist</option>
                                                <option value="Development Specialist">Development Specialist</option>
                                                <option value="Logistics Specialist">Logistics Specialist</option>
                                                <option value="Financial Analyst">Financial Analyst</option>
                                                <option value="Quality Assurance Specialist">Quality Assurance Specialist</option>
                                            </optgroup>
                                            <optgroup label="Other Positions">
                                                <option value="Trainer">Trainer</option>
                                                <option value="Coordinator">Coordinator</option>
                                                <option value="Assistant Manager">Assistant Manager</option>
                                                <option value="Department Head">Department Head</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="promote_date">Promotion Date *</label>
                                        <input type="date" class="form-control" id="promote_date" name="promotion_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="promote_reason">Reason for Promotion *</label>
                                        <textarea class="form-control" id="promote_reason" name="reason" rows="4" placeholder="Enter the reason for this promotion..." required></textarea>
                                        <small class="form-text text-muted">Explain why this employee deserves the promotion based on their evaluation.</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="promote_promoted_by">Promoted By</label>
                                        <input type="text" class="form-control" id="promote_promoted_by" value="<?php echo htmlspecialchars($username); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <strong>Note:</strong> This promotion will be recorded and the employee's position will be updated in their record.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" name="promoteEmployee" class="btn btn-success">
                                <i class="fas fa-arrow-up"></i> Confirm Promotion
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    </body>
</html>