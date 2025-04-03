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

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';

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
            "performance" => array_sum(array_map('intval', array_slice($_POST, array_search('criteria_1', array_keys($_POST)), 10)))
        ];

        // Insert new evaluation into the database
        $stmt = $conn->prepare("INSERT INTO worker_evaluations (id, employee_id, name, position, department, start_date, comments, performance) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "sssssssi",
            $newEvaluation['id'],
            $newEvaluation['employee_id'],
            $newEvaluation['name'],
            $newEvaluation['position'],
            $newEvaluation['department'],
            $newEvaluation['start_date'],
            $newEvaluation['comments'],
            $newEvaluation['performance']
        );
        if ($stmt->execute()) {
            echo "New evaluation added successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }
    }

    if (isset($_POST['editEvaluation'])) {
        // Debugging - check what is being passed
        var_dump($_POST);
    
        // Update evaluation in the database
        $stmt = $conn->prepare("UPDATE worker_evaluations SET employee_id = ?, name = ?, position = ?, department = ?, start_date = ?, comments = ?, performance = ? WHERE id = ?");

        // Get the sum of the 10 criteria ratings
        $performance = array_sum(array_map('intval', array_slice($_POST, array_search('criteria_1', array_keys($_POST)), 10)));
    
        $stmt->bind_param(
            "ssssssii", // Change to "ssssssii" because both performance and id are integers
            $_POST['employee_id'],
            $_POST['name'],
            $_POST['position'],
            $_POST['department'],
            $_POST['start_date'],
            $_POST['comments'],
            $performance, // Make sure performance is correctly handled
            $_POST['id']
        );
        
        if ($stmt->execute()) {
            echo "Evaluation updated successfully.";
        } else {
            echo "Error: " . $stmt->error;
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
                            <button class="btn btn-primary mb-3" type="button" data-toggle="modal" data-target="#addEvaluationModal" style="border-radius: 0 10px 10px 0; border: 3px solid #131313;">Add Evaluation</button>
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
                                        <form method="POST" id="deleteForm" style="display:inline;">
                                            <button type="submit" name="deleteTask" class="btn btn-danger" disabled>Del</button>
                                        </form>
                                        <button class="btn btn-primary" name="editTaskMod" data-toggle="modal" data-target="#editEvaluationModal" disabled data-id="<?php echo $task['id']; ?>">Edit</button>
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
                                        </tr>

                                    </thead>

                                    <tbody>
                                        <?php foreach ($tasks as $task): ?>
                                        <tr>
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
                                    <input type="hidden" id="edit_id" name="id">
                                    <div class="form-group">
                                        <label for="edit_employee_id">Employee ID</label>
                                        <input type="text" class="form-control" id="edit_employee_id" name="employee_id" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_name">Name</label>
                                        <input type="text" class="form-control" id="edit_name" name="name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_position">Position</label>
                                        <input type="text" class="form-control" id="edit_position" name="position" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_department">Department</label>
                                        <select class="form-control" id="edit_department" name="department" required>
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
                                        <label for="edit_start_date">Start Date</label>
                                        <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_comments">Additional Comments</label>
                                        <textarea class="form-control" id="edit_comments" name="comments"></textarea>
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
                                    shuffle($questions);
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
        </script>
    </body>
</html>