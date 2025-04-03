<?php
session_start();

if (isset($_POST['loggedin'])) {
    $_SESSION['loggedin'] = filter_var($_POST['loggedin'], FILTER_VALIDATE_BOOLEAN); // Convert string "true" to boolean true
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

// Initialize variables
$tasksTable = 'employee_records';

// Initialize search ID
$searchId = isset($_GET['search_id']) ? $_GET['search_id'] : '';
// Base query to retrieve tasks from the database
$query = "SELECT * FROM $tasksTable";
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

// Add Employee
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['addTask'])) {
        $newTask = [
            'name' => $_POST['name'],
            'position' => $_POST['position'],
            'shift' => $_POST['shift'],
            'salary' => $_POST['salary'],
            'address' => $_POST['address'],
            'phone_number' => $_POST['phone_number'],
            'age' => $_POST['age'],
            'email' => $_POST['email'],
            'start_date' => $_POST['start_date'],
            'photo' => !empty($_FILES['photo']['name']) ? $_FILES['photo']['name'] : 'none',
            'status' => 'Active'
        ];
    
        // Prepare the SQL statement
        $stmt = $conn->prepare("INSERT INTO $tasksTable (name, position, shift, salary, address, phone_number, age, email, start_date, photo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
        $stmt->bind_param(
            "sssssssssbs",
            $newTask['name'],
            $newTask['position'],
            $newTask['shift'],
            $newTask['salary'],
            $newTask['address'],
            $newTask['phone_number'],
            $newTask['age'],
            $newTask['email'],
            $newTask['start_date'],
            $newTask['photo'],
            $newTask['status']
        );
    
        // Check if a file was uploaded and get the photo data
        if (!empty($_FILES['photo']['tmp_name'])) {
            $photo = file_get_contents($_FILES['photo']['tmp_name']);
            $stmt->send_long_data(9, $photo);
        } else {
            // Handle the case where no photo is uploaded, if necessary
            $stmt->send_long_data(9, null); // You can choose to send null or handle it as needed
        }
    
        // Execute the statement
        $stmt->execute();
    }

    // Edit Employee
    if (isset($_POST['editTask'])) {
        $editTask = [
            'id' => $_POST['id'],
            'name' => $_POST['name'],
            'position' => $_POST['position'],
            'shift' => $_POST['shift'],
            'salary' => $_POST['salary'],
            'address' => $_POST['address'],
            'phone_number' => $_POST['phone_number'],
            'age' => $_POST['age'],
            'email' => $_POST['email'],
            'start_date' => $_POST['start_date'],
            'photo' => $_POST['photo'],
            'status' => 'Active'
        ];
    
        // Prepare the SQL statement
        $stmt = $conn->prepare("UPDATE $tasksTable SET name = ?, position = ?, shift = ?, salary = ?, address = ?, phone_number = ?, age = ?, email = ?, start_date = ?, photo = ?, status = ? WHERE id = ?");
    
        $stmt->bind_param(
            "sssssssssbsi",
            $editTask['name'],
            $editTask['position'],
            $editTask['shift'],
            $editTask['salary'],
            $editTask['address'],
            $editTask['phone_number'],
            $editTask['age'],
            $editTask['email'],
            $editTask['start_date'],
            $editTask['photo'],
            $editTask['status'],
            $editTask['id']
        );
    
        // Check if a file was uploaded and get the photo data
        if (!empty($_FILES['photo']['tmp_name'])) {
            $photo = file_get_contents($_FILES['photo']['tmp_name']);
            $stmt->send_long_data(9, $photo);
        } else {
            // Handle the case where no photo is uploaded, if necessary
            $stmt->send_long_data(9, null); // You can choose to send null or handle it as needed
        }
    
        // Execute the statement
        $stmt->execute();
    }

    if (isset($_POST['deleteTask'])) {
        $taskIds = $_POST['task_checkbox'];
        $placeholders = rtrim(str_repeat('?,', count($taskIds)), ',');
        $stmt = $conn->prepare("DELETE FROM $tasksTable WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($taskIds)), ...$taskIds);
        $stmt->execute();
    }

    header('Location: employee_list.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
    <head>
        <meta charset="UTF-8">
        <title>Personnel Records</title>
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
        <?php include 'employee_filter_sidebar.php'; ?>
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
                                <input type="text" class="form-control" name="search_id" placeholder="Search by ID" value="<?php echo htmlspecialchars($searchId); ?>" style="border-radius: 10px 0 0 10px; border: 3px solid #131313; height:42px;">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit" style="border-radius: 0; border: 3px solid #131313;">Search</button>
                                </div>
                            </div>
                            <!-- Add Task button aligned to the right -->
                            <button class="btn btn-primary mb-3" type="button" data-toggle="modal" data-target="#addTaskModal" style="border-radius: 0 10px 10px 0 ; border: 3px solid #131313;">Add Employee</button>
                        </form>
                    </div>
            
                </div>

                <div class="container-bottom">
                    <div class="container-table">
                        <div class="tool-bar">
                            <div class="d-flex justify-content-between align-items-center mb-3" style="color:#fffafa;">
                                <div>
                                    <span id="selected-count">0</span> items selected
                                </div>
                                
                                <div class="d-flex align-items-center" style="gap:10px;">
                                    
                                    <!-- Start the form for deletion -->
                                    <form method="POST" id="deleteForm" style="display:inline;">
                                        <button type="submit" name="deleteTask" class="btn btn-danger" disabled>Del</button>
                                    </form>
                                    <button class="btn btn-primary" name="editTaskMod" data-toggle="modal" data-target="#editTaskModal" disabled data-id="<?php echo $row['id']; ?>">Edit</button>
                                    <!-- <button class="btn btn-secondary" onclick="window.print()">Print</button> -->
                                    
                                    <div>
                                        <form method="get" action="task_management.php">
                                            <input type="hidden" name="department" value="<?php echo htmlspecialchars($department); ?>">
                                            <input type="hidden" name="export" value="excel">
                                            <button type="submit" class="btn btn-success">Export to Excel</button>
                                        </form>
                                    </div>
                                    <button class="btn btn-info" onclick="window.location.href='employee_list.php'">Reset</button>
                                    <button class="btn btn-warning" onclick="toggle_filter()">Filter</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-container">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th class="checkbox-col"></th> <!-- Empty column for the checkbox -->
                                        <th>Employee No</th>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Shift</th>
                                        <th>Salary</th>
                                        <th>Start Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tasks as $row): ?>
                                        <tr>
                                            <td>
                                                <!-- Make sure the checkbox is inside the form -->
                                                <input type="checkbox" id="chkbx" name="task_checkbox[]" form="deleteForm" value="<?php echo $row['id']; ?>" onclick="updateSelectedCount(this)">
                                            </td>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo $row['name']; ?></td>
                                            <td><?php echo $row['position']; ?></td>
                                            <td><?php echo $row['shift']; ?></td>
                                            <td><?php echo $row['salary']; ?></td>
                                            <td><?php echo $row['start_date']; ?></td>
                                            <td><?php echo $row['status']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Add Task Modal -->
        <div class="modal fade" id="addTaskModal" tabindex="-1" role="dialog" aria-labelledby="addTaskModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addTaskModalLabel">Add Task</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="position">Position</label> <!-- Changed to 'Assigned' -->
                                    <input type="text" class="form-control" id="position" name="position" required>
                                </div>
                                <div class="form-group">
                                    <label for="shift">Shift</label>
                                    <input type="text" class="form-control" id="shift" name="shift" required>
                                </div>
                                <div class="form-group">
                                    <label for="salary">Salary</label>
                                    <input type="text" class="form-control" id="salary" name="salary" required>
                                </div>
                                <div class="form-group">
                                    <label for="status">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" required>
                                </div>
                                <div class="form-group">
                                    <label for="start_date">Phone Number</label>
                                    <input type="text" class="form-control" id="phone_number" name="phone_number" required>
                                </div>
                                <div class="form-group">
                                    <label for="due_date">Age</label>
                                    <input type="text" class="form-control" id="age" name="age" required>
                                </div>
                                <div class="form-group">
                                    <label for="completion">Email</label>
                                    <input type="text" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="photo">Photo</label>
                                    <input type="file" class="form-control-file" id="photo" name="photo" accept="image/*">
                                </div>

                                <input type="hidden" name="status" value="Active">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="submit" name="addTask" class="btn btn-primary">Save Task</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
<!-- Edit Task Modal -->
            <div class="modal fade" id="editTaskModal" tabindex="-1" role="dialog" aria-labelledby="editTaskModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editTaskModalLabel">Edit Employee</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="modal-body">
                                <input type="hidden" id="editTaskId" name="task_id">
                                <div class="form-group">
                                    <label for="editTaskName">Name</label>
                                    <input type="text" class="form-control" id="editTaskName" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="editTaskPosition">Position</label>
                                    <input type="text" class="form-control" id="editTaskPosition" name="position" required>
                                </div>
                                <div class="form-group">
                                    <label for="editTaskShift">Shift</label>
                                    <input type="text" class="form-control" id="editTaskShift" name="shift" required>
                                </div>
                                <div class="form-group">
                                    <label for="editTaskSalary">Salary</label>
                                    <input type="text" class="form-control" id="editTaskSalary" name="salary" required>
                                </div>
                                <div class="form-group">
                                    <label for="editTaskAddress">Address</label>
                                    <input type="text" class="form-control" id="editTaskAddress" name="address" required>
                                </div>
                                <div class="form-group">
                                    <label for="editTaskPhone">Phone Number</label>
                                    <input type="text" class="form-control" id="editTaskPhone" name="phone_number" required>
                                </div>
                                <div class="form-group">
                                    <label for="editTaskAge">Age</label>
                                    <input type="text" class="form-control" id="editTaskAge" name="age" required>
                                </div>
                                <div class="form-group">
                                    <label for="editTaskEmail">Email</label>
                                    <input type="text" class="form-control" id="editTaskEmail" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="start_date">Start Date</label>
                                    <input type="date" class="form-control" id="editTaskDate" name="start_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="photo">Photo</label>
                                    <input type="file" class="form-control-file" id="photo" name="photo" accept="image/*">
                                </div>

                                <input type="hidden" name="status" value="Active">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="photo" value="">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="submit" name="editTask" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
    </body>
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
        logoName.textContent = 'Personnel Records';

        // Function to update selected count display
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
    </script>
</html>