<?php
session_start();

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

if (isset($_GET['department'])) {
    $department = $_GET['department'];

    switch ($department) {
        case 'sales':
            // Use the sales_tasks table
            $tasksTable = 'sales_tasks';
            break;
        case 'purchasing':
            // Use the purchasing_tasks table
            $tasksTable = 'purchasing_tasks';
            break;
        case 'proddev':
            // Use the proddev_tasks table
            $tasksTable = 'proddev_tasks';
            break;
        case 'warehouse':
            // Use the warehouse_tasks table
            $tasksTable = 'warehouse_tasks';
            break;
        case 'logistics':
            // Use the logistics_tasks table
            $tasksTable = 'logistics_tasks';
            break;
        case 'accounting':
            // Use the accounting_tasks table
            $tasksTable = 'accounting_tasks';
            break;
        default:
            die("Invalid department specified.");
    }
} else {
    die("Department not specified.");
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
    $stmt->bind_param("i", $searchTerm);
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);

// update the table after search


// Handling form submissions for adding, editing, and deleting tasks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addTask'])) {
        $newTask = [
            "id" => "PC-T" . rand(1000, 9999),
            "task" => $_POST['task'],
            "owner" => $_POST['owner'],
            "status" => $_POST['status'],
            "start_date" => $_POST['start_date'],
            "due_date" => $_POST['due_date'],
            "completion" => $_POST['completion'],
            "priority" => $_POST['priority'],
            "duration" => (strtotime($_POST['due_date']) - strtotime($_POST['start_date'])) / (60 * 60 * 24)
        ];

        // Insert new task into the database
        $stmt = $conn->prepare("INSERT INTO $tasksTable (id, task, owner, status, start_date, due_date, completion, priority, duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "ssssssisi",
            $newTask['id'],
            $newTask['task'],
            $newTask['owner'],
            $newTask['status'],
            $newTask['start_date'],
            $newTask['due_date'],
            $newTask['completion'],
            $newTask['priority'],
            $newTask['duration']
        );
        $stmt->execute();
    }

    if (isset($_POST['editTask'])) {
        // Update task in the database
        $stmt = $conn->prepare("UPDATE $tasksTable SET task = ?, owner = ?, status = ?, start_date = ?, due_date = ?, completion = ?, priority = ?, duration = ? WHERE id = ?");
        
        $stmt->bind_param(
            "sssssisii", // s = string, i = integer 
            $_POST['task'],
            $_POST['owner'],
            $_POST['status'],
            $_POST['start_date'],
            $_POST['due_date'],
            $_POST['completion'],
            $_POST['priority'],
            $_POST['duration'],
            $_POST['id']
        );
        
        if ($stmt->execute()) {
            echo "Task updated successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }
    }

    if (isset($_POST['deleteTask'])) {
        $selectedIds = $_POST['task_checkbox'] ?? [];
        if (!empty($selectedIds)) {
            $in = str_repeat('?,', count($selectedIds) - 1) . '?';
            $stmt = $conn->prepare("DELETE FROM $tasksTable WHERE id IN ($in)");
            $stmt->bind_param(str_repeat('s', count($selectedIds)), ...$selectedIds);
            $stmt->execute();
        }
    }

    header('Location: task_management.php?department=' . urlencode($department)); // Redirect to the same department page
    exit();
}

// Export to Excel if requested
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = strtolower($department) . "_tasks.xls"; // e.g., sales_tasks.xls

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Output Excel headers
    echo "ID\tTask\tAssigned\tStatus\tStart Date\tDue Date\tCompletion\tPriority\tDuration\n";

    // Output tasks data
    foreach ($tasks as $task) {
        echo "{$task['id']}\t{$task['task']}\t{$task['owner']}\t{$task['status']}\t{$task['start_date']}\t{$task['due_date']}\t{$task['completion']}\t{$task['priority']}\t{$task['duration']}\n";
    }
    exit();
}

// Initialize sort direction and sort field
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id'; // Default sort field
$direction = isset($_GET['dir']) && $_GET['dir'] === 'DESC' ? 'DESC' : 'ASC'; // Default direction

// Get filter parameters if provided
$assigned = isset($_GET['assigned']) ? $_GET['assigned'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$dueDateStart = isset($_GET['due_date_start']) ? $_GET['due_date_start'] : '';
$dueDateEnd = isset($_GET['due_date_end']) ? $_GET['due_date_end'] : '';
$startDateStart = isset($_GET['start_date_start']) ? $_GET['start_date_start'] : '';
$startDateEnd = isset($_GET['start_date_end']) ? $_GET['start_date_end'] : '';
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';
$duration = isset($_GET['duration']) ? $_GET['duration'] : '';

// Get current user info
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Build the query with filters and sorting
$query = "SELECT * FROM $tasksTable WHERE 1=1"; // Always true condition for appending WHERE clauses

// Filter by current user for non-admin roles
if ($role !== 'Admin' && !empty($username)) {
    $query .= " AND owner = ?";
    $bindTypes = 's';
    $bindValues = [$username];
}

// Append filters to the query if the user provided values
if (!empty($assigned)) {
    $query .= " AND owner = ?";
    $bindTypes .= 's';
    $bindValues[] = $assigned;
}
if (!empty($status)) {
    $query .= " AND status = ?";
    $bindTypes .= 's';
    $bindValues[] = $status;
}
if (!empty($dueDateStart)) {
    $query .= " AND due_date >= ?";
    $bindTypes .= 's';
    $bindValues[] = $dueDateStart;
}
if (!empty($dueDateEnd)) {
    $query .= " AND due_date <= ?";
    $bindTypes .= 's';
    $bindValues[] = $dueDateEnd;
}
if (!empty($startDateStart)) {
    $query .= " AND start_date >= ?";
    $bindTypes .= 's';
    $bindValues[] = $startDateStart;
}
if (!empty($startDateEnd)) {
    $query .= " AND start_date <= ?";
    $bindTypes .= 's';
    $bindValues[] = $startDateEnd;
}
if (!empty($priority)) {
    $query .= " AND priority = ?";
    $bindTypes .= 's';
    $bindValues[] = $priority;
}
if (!empty($duration)) {
    $query .= " AND duration = ?";
    $bindTypes .= 'i';
    $bindValues[] = $duration;
}

// Append sorting
if (in_array($sort, ['start_date', 'due_date', 'id', 'task', 'owner', 'status', 'completion', 'duration'])) {
    $query .= " ORDER BY $sort $direction";
}

// Prepare the statement
$stmt = $conn->prepare($query);

//Check if the statement was prepared successfully
try {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }
    // Additional logic...
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Bind the parameters if there are any
if (!empty($bindValues)) {
    $stmt->bind_param($bindTypes, ...$bindValues);
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);

// Get a list of all employees for the dropdown in the add task form
$employeeQuery = "SELECT name FROM employee_records WHERE status = 'Active'";
$employeeResult = $conn->query($employeeQuery);
$employees = [];
if ($employeeResult && $employeeResult->num_rows > 0) {
    while ($employeeRow = $employeeResult->fetch_assoc()) {
        $employees[] = $employeeRow['name'];
    }
}

// Filter tasks for non-admin users to only show their assigned tasks
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Modify the query to filter by assigned owner if not admin
if ($role !== 'Admin' && !empty($username)) {
    $query .= " AND owner = ?";
    $bindTypes .= 's';
    $bindValues[] = $username;
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
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
                <div class="search-bar">
                    <form method="GET" action="" class="form-inline">
                        <div class="input-group mb-3 flex-grow-1">
                            <!-- Search input and button -->
                            <input type="hidden" name="department" value="<?php echo htmlspecialchars($department); ?>"> 

                            <input type="text" class="form-control" name="search_id" placeholder="Search by ID" value="<?php echo htmlspecialchars($searchId); ?>" style="border-radius: 10px 0 0 10px; border: 3px solid #131313; height:42px;">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit" style="border-radius: 0; border: 3px solid #131313;">Search</button>
                            </div>
                        </div>
                        <!-- Add Task button aligned to the right - only visible to admins -->
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                        <button class="btn btn-primary mb-3" type="button" data-toggle="modal" data-target="#addTaskModal" style="border-radius: 0 10px 10px 0 ; border: 3px solid #131313;">Add Task</button>
                        <?php endif; ?>
                    </form>
                </div>
    
            </div>
            <div class="container-bottom">
                <div class="container-table">
                    <div class="tool-bar">
                        <div class="d-flex justify-content-between align-items-center mb-3" >
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                            <div style="color: #FFFAFA;">
                                <span id="selected-count">0</span> items selected
                            </div>
                            <?php else: ?>
                            <div></div>
                            <?php endif; ?>
                            
                            <div class="d-flex align-items-center" style="gap:10px;">
                                
                                <!-- Start the form for deletion - only visible to admins -->
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                                <form method="POST" id="deleteForm" style="display:inline;">
                                    <button type="submit" name="deleteTask" class="btn btn-danger" disabled>Del</button>
                                </form>
                                <button class="btn btn-primary" name="editTaskMod" data-toggle="modal" data-target="#editTaskModal" disabled data-id="<?php echo $task['id']; ?>">Edit</button>
                                <?php endif; ?>
                                <!-- <button class="btn btn-secondary" onclick="window.print()">Print</button> -->
                                
                                <div>
                                    <form method="get" action="task_management.php">
                                        <input type="hidden" name="department" value="<?php echo htmlspecialchars($department); ?>">
                                        <input type="hidden" name="export" value="excel">
                                        <button type="submit" class="btn btn-success">Export to Excel</button>
                                    </form>
                                </div>
                                <button class="btn btn-info" onclick="window.location.href='?department=<?php echo urlencode($department); ?>'">Reset</button>
                                <button class="btn btn-warning" onclick="toggle_filter()">Filter</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr> <!-- style="display: block; width: 100%; height: 100%; text-decoration: none; color: inherit;" -->
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                                    <th class="checkbox-col"></th> <!-- Empty column for the checkbox -->
                                    <?php endif; ?>
                                    <th><a class="sort-link" href="?department=<?php echo urlencode($department); ?>&sort=id&dir=<?php echo ($sort === 'id' && $direction === 'ASC') ? 'DESC' : 'ASC'; ?>" style="text-decoration:none;">ID</a></th>
                                    <th><a class="sort-link" href="?department=<?php echo urlencode($department); ?>&sort=task&dir=<?php echo ($sort === 'task' && $direction === 'ASC') ? 'DESC' : 'ASC'; ?>" style="text-decoration:none;">Task</a></th>
                                    <th><a class="sort-link" href="?department=<?php echo urlencode($department); ?>&sort=owner&dir=<?php echo ($sort === 'owner' && $direction === 'ASC') ? 'DESC' : 'ASC'; ?>" style="text-decoration:none;">Assigned</a></th>
                                    <th><a class="sort-link" href="?department=<?php echo urlencode($department); ?>&sort=status&dir=<?php echo ($sort === 'status' && $direction === 'ASC') ? 'DESC' : 'ASC'; ?>" style="text-decoration:none;">Status</a></th>
                                    <th><a class="sort-link" href="?department=<?php echo urlencode($department); ?>&sort=start_date&dir=<?php echo ($sort === 'start_date' && $direction === 'ASC') ? 'DESC' : 'ASC'; ?>" style="text-decoration:none;">Start Date</a></th>
                                    <th><a class="sort-link" href="?department=<?php echo urlencode($department); ?>&sort=due_date&dir=<?php echo ($sort === 'due_date' && $direction === 'ASC') ? 'DESC' : 'ASC'; ?>" style="text-decoration:none;">Due Date</a></th>
                                    <th><a class="sort-link" href="?department=<?php echo urlencode($department); ?>&sort=completion&dir=<?php echo ($sort === 'completion' && $direction === 'ASC') ? 'DESC' : 'ASC'; ?>" style="text-decoration:none;">Completion</a></th>
                                    <th><a class="sort-link" href="#" style="display: block; width: 100%; height: 100%; text-decoration: none; color: inherit;" style="text-decoration:none;">Priority</a></th>
                                    <th><a class="sort-link" href="?department=<?php echo urlencode($department); ?>&sort=duration&dir=<?php echo ($sort === 'duration' && $direction === 'ASC') ? 'DESC' : 'ASC'; ?>" style="text-decoration:none;">Duration</a></th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if(count($tasks) > 0): ?>
                                <?php foreach ($tasks as $task): ?>
                                <?php
                                // Determine classes for completion and status
                                $completionClass = 'completion-bar low';
                                if ($task['completion'] > 50) {
                                    $completionClass = 'completion-bar medium';
                                }
                                if ($task['completion'] > 80) {
                                    $completionClass = 'completion-bar high';
                                }

                                $statusClass = 'status-not-started';
                                if ($task['status'] === 'In Progress') {
                                    $statusClass = 'status-in-progress';
                                }
                                if ($task['status'] === 'Completed') {
                                    $statusClass = 'status-completed';
                                }
                                ?>
                                <tr>
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                                    <td>
                                        <!-- Make sure the checkbox is inside the form -->
                                        <input type="checkbox" id="chkbx" name="task_checkbox[]" form="deleteForm" value="<?php echo $task['id']; ?>" onclick="updateSelectedCount(this)">
                                    </td> <!-- Checkbox before ID -->
                                    <?php endif; ?>
                                    <td><?php echo $task['id']; ?></td>
                                    <td><?php echo $task['task']; ?></td>
                                    <td><?php echo $task['owner']; ?></td> <!-- Updated to 'Assigned' -->
                                    <td class="<?php echo $statusClass; ?>"><?php echo $task['status']; ?></td>
                                    <td><?php echo $task['start_date']; ?></td>
                                    <td><?php echo $task['due_date']; ?></td>
                                    <td>
                                        <div class="<?php echo $completionClass; ?>" style="width: <?php echo $task['completion']; ?>%;"></div>
                                        <?php echo $task['completion']; ?>%
                                    </td>
                                    <td><?php echo $task['priority']; ?></td>
                                    <td><?php echo $task['duration']; ?> days</td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') ? '10' : '9'; ?>" class="text-center">
                                        <?php if ($role === 'Admin'): ?>
                                            No tasks found. Add a new task to get started.
                                        <?php else: ?>
                                            You don't have any assigned tasks yet.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addTaskModal" tabindex="-1" role="dialog" aria-labelledby="addTaskModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addTaskModalLabel">New Task</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="modal-all">
                                <div class="form-group">
                                    <label for="task">Task</label>
                                    <input type="text" class="form-control" id="task" name="task" required>
                                </div>
                                <div class="modal-group">
                                    <div class="modal-left">
                                        <div class="form-group">
                                            <label for="owner">Assigned To</label>
                                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                                            <select class="form-control" id="owner" name="owner" required>
                                                <option value="">Select Employee</option>
                                                <?php foreach ($employees as $employee): ?>
                                                <option value="<?php echo htmlspecialchars($employee); ?>"><?php echo htmlspecialchars($employee); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php else: ?>
                                            <input type="text" class="form-control" id="owner" name="owner" value="<?php echo htmlspecialchars($username); ?>" readonly>
                                            <?php endif; ?>
                                        </div>

                                        <div class="form-group">
                                            <label for="start_date">Start</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="completion">Completion (%)</label>
                                            <input type="number" class="form-control" id="completion" name="completion" min="0" max="100" required>
                                        </div>
                                    </div>
                                    <div class="modal-right">
                                        <div class="form-group">
                                            <label for="status">Status</label>
                                            <select class="form-control" id="status" name="status" required>
                                                <option value="Not Started">Not Started</option>
                                                <option value="In Progress">In Progress</option>
                                                <option value="Completed">Completed</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="due_date">End</label>
                                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="priority">Priority</label>
                                            <select class="form-control" id="priority" name="priority" required>
                                                <option value="Low">Low</option>
                                                <option value="Medium">Medium</option>
                                                <option value="High">High</option>
                                            </select>
                                        </div>
                                    </div> 
                                       
                                    <input type="hidden" class="form-control" id="duration" name="duration" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="addTask" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal fade" id="editTaskModal" tabindex="-1" role="dialog" aria-labelledby="editTaskModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="POST" id="editTaskForm">
                        <div class="modal-body">
                            <div class="modal-all">
                                <div class="form-group">
                                    <label for="edit_task">Task</label>
                                    <input type="text" class="form-control" id="edit_task" name="task" required>
                                </div>
                                <div class="modal-group">
                                    <div class="modal-left">
                                        <div class="form-group">
                                            <label for="edit_owner">Assigned To</label>
                                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
                                            <select class="form-control" id="edit_owner" name="owner" required>
                                                <option value="">Select Employee</option>
                                                <?php foreach ($employees as $employee): ?>
                                                <option value="<?php echo htmlspecialchars($employee); ?>"><?php echo htmlspecialchars($employee); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php else: ?>
                                            <input type="text" class="form-control" id="edit_owner" name="owner" value="<?php echo htmlspecialchars($username); ?>" readonly>
                                            <?php endif; ?>
                                        </div>

                                        <div class="form-group">
                                            <label for="edit_start_date">Start</label>
                                            <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="edit_completion">Completion (%)</label>
                                            <input type="number" class="form-control" id="edit_completion" name="completion" min="0" max="100" required>
                                        </div>
                                    </div>
                                    <div class="modal-right">
                                        <div class="form-group">
                                            <label for="edit_status">Status</label>
                                            <select class="form-control" id="edit_status" name="status" required>
                                                <option value="Not Started">Not Started</option>
                                                <option value="In Progress">In Progress</option>
                                                <option value="Completed">Completed</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="edit_due_date">End</label>
                                            <input type="date" class="form-control" id="edit_due_date" name="due_date" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="edit_priority">Priority</label>
                                            <select class="form-control" id="edit_priority" name="priority" required>
                                                <option value="Low">Low</option>
                                                <option value="Medium">Medium</option>
                                                <option value="High">High</option>
                                            </select>
                                        </div>
                                    </div> 
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <input type="hidden" id="task_id" name="task_id">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="editTask" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        

        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script>
            $('#editTaskModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var taskId = button.data('id');
                var modal = $(this);
                var row = button.closest('tr');
                
                // Calculate offset based on whether there's an admin checkbox
                var isAdmin = <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') ? 'true' : 'false'; ?>;
                var offset = isAdmin ? 1 : 0;
                
                // Populate modal with task details
                modal.find('#edit_id').val(taskId); // Hidden input for task ID
                modal.find('#edit_task').val(row.find('td:eq(' + (1 + offset) + ')').text()); // Task Name
                
                // Set the owner/assigned dropdown
                var ownerText = row.find('td:eq(' + (2 + offset) + ')').text(); // Owner
                if (modal.find('#edit_owner').is('select')) {
                    // Find the option with the matching text and select it
                    modal.find('#edit_owner option').each(function() {
                        if ($(this).text() === ownerText) {
                            $(this).prop('selected', true);
                        }
                    });
                } else {
                    modal.find('#edit_owner').val(ownerText);
                }
                
                modal.find('#edit_status').val(row.find('td:eq(' + (3 + offset) + ')').text()); // Status
                modal.find('#edit_start_date').val(row.find('td:eq(' + (4 + offset) + ')').text()); // Start Date
                modal.find('#edit_due_date').val(row.find('td:eq(' + (5 + offset) + ')').text()); // Due Date
                
                // Get the completion percentage safely
                var completionBar = row.find('td:eq(' + (6 + offset) + ')').find('.completion-bar');
                var completionValue = 0; // Default value if no completion bar is found
                
                if (completionBar.length > 0 && completionBar.attr('style')) {
                    var match = completionBar.attr('style').match(/\d+/);
                    if (match) {
                        completionValue = match[0]; // Extract matched percentage
                    }
                }
                
                modal.find('#edit_completion').val(completionValue); // Set completion percentage
                
                modal.find('#edit_priority').val(row.find('td:eq(' + (7 + offset) + ')').text()); // Priority
                modal.find('#edit_duration').val(row.find('td:eq(' + (8 + offset) + ')').text().split(' ')[0]); // Duration
            });


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
            
            // Function to toggle the filter sidebar
            function toggle_filter() {
                const filterSidebar = document.querySelector('.filter-sidebar');
                filterSidebar.style.right = filterSidebar.style.right === '0px' ? '-300px' : '0px';
            }
            
        </script>
</body>
</html>
