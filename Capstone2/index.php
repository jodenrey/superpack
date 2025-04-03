<?php
$tasksFile = 'tasks.json';
$tasks = json_decode(file_get_contents($tasksFile), true);

// Handling form submissions for adding, editing, and deleting tasks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addTask'])) {
        $newTask = [
            "id" => "DC-T" . rand(1000, 9999),
            "task" => $_POST['task'],
            "owner" => $_POST['owner'],
            "status" => $_POST['status'],
            "start_date" => $_POST['start_date'],
            "due_date" => $_POST['due_date'],
            "completion" => $_POST['completion'],
            "priority" => $_POST['priority'],
            "duration" => $_POST['duration']
        ];
        $tasks[] = $newTask;
    }

    if (isset($_POST['editTask'])) {
        foreach ($tasks as &$task) {
            if ($task['id'] === $_POST['id']) {
                $task['task'] = $_POST['task'];
                $task['owner'] = $_POST['owner'];
                $task['status'] = $_POST['status'];
                $task['start_date'] = $_POST['start_date'];
                $task['due_date'] = $_POST['due_date'];
                $task['completion'] = $_POST['completion'];
                $task['priority'] = $_POST['priority'];
                $task['duration'] = $_POST['duration'];
                break;
            }
        }
    }

    if (isset($_POST['deleteTask'])) {
        $tasks = array_filter($tasks, function ($task) {
            return $task['id'] !== $_POST['id'];
        });
    }

    file_put_contents($tasksFile, json_encode(array_values($tasks)));
    header('Location: index.php');
    exit();
}

// Handle search query
$searchId = isset($_GET['search_id']) ? $_GET['search_id'] : '';

// Filter tasks if search ID is provided
if ($searchId) {
    $tasks = array_filter($tasks, function ($task) use ($searchId) {
        return stripos($task['id'], $searchId) !== false;
    });
}

// Export to Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="tasks.xls"');
    header('Cache-Control: max-age=0');
    
    echo "ID\tTask\tAssigned\tStatus\tStart Date\tDue Date\tCompletion\tPriority\tDuration\n";
    
    foreach ($tasks as $task) {
        echo "{$task['id']}\t{$task['task']}\t{$task['owner']}\t{$task['status']}\t{$task['start_date']}\t{$task['due_date']}\t{$task['completion']}\t{$task['priority']}\t{$task['duration']}\n";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Sales Department</title>
    <link rel="stylesheet" href="style_index.css">
    <link rel="icon" type="image/x-icon" href="Superpack-Enterprise-Logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Existing styles */
        .search-bar {
            margin-bottom: 20px;
        }

        .search-bar input {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 5px;
            width: 300px;
        }
    </style>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <h1 class="my-4">Sales Department</h1>

        <!-- Search Bar and Export/Print Options -->
        <div class="search-bar">
            <form method="GET" action="">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" name="search_id" placeholder="Search by ID" value="<?php echo htmlspecialchars($searchId); ?>">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">Search</button>
                        <a href="?export=excel" class="btn btn-success ml-2">Export to Excel</a>
                    </div>
                </div>
            </form>
        </div>

        <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#addTaskModal">Add Task</button>
        <button class="btn btn-secondary mb-3" onclick="window.print()">Print</button>
        
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Task</th>
                    <th>Assigned</th>
                    <th>Status</th>
                    <th>Start Date</th>
                    <th>Due Date</th>
                    <th>Completion</th>
                    <th>Priority</th>
                    <th>Duration</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
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
                    <td>
                        <!-- Actions -->
                        <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editTaskModal" data-id="<?php echo $task['id']; ?>">Edit</button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $task['id']; ?>">
                            <button type="submit" name="deleteTask" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
                            <label for="task">Task</label>
                            <input type="text" class="form-control" id="task" name="task" required>
                        </div>
                        <div class="form-group">
                            <label for="owner">Assigned</label> <!-- Changed to 'Assigned' -->
                            <input type="text" class="form-control" id="owner" name="owner" required>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="due_date">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                        </div>
                        <div class="form-group">
                            <label for="completion">Completion (%)</label>
                            <input type="number" class="form-control" id="completion" name="completion" required>
                        </div>
                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <input type="text" class="form-control" id="priority" name="priority" required>
                        </div>
                        <div class="form-group">
                            <label for="duration">Duration (days)</label>
                            <input type="number" class="form-control" id="duration" name="duration" required>
                        </div>
                        <input type="hidden" name="addTask" value="1">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Task</button>
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
                    <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="task_edit">Task</label>
                            <input type="text" class="form-control" id="task_edit" name="task" required>
                        </div>
                        <div class="form-group">
                            <label for="owner_edit">Assigned</label> <!-- Changed to 'Assigned' -->
                            <input type="text" class="form-control" id="owner_edit" name="owner" required>
                        </div>
                        <div class="form-group">
                            <label for="status_edit">Status</label>
                            <select class="form-control" id="status_edit" name="status" required>
                                <option value="Not Started">Not Started</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="start_date_edit">Start Date</label>
                            <input type="date" class="form-control" id="start_date_edit" name="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="due_date_edit">Due Date</label>
                            <input type="date" class="form-control" id="due_date_edit" name="due_date" required>
                        </div>
                        <div class="form-group">
                            <label for="completion_edit">Completion (%)</label>
                            <input type="number" class="form-control" id="completion_edit" name="completion" required>
                        </div>
                        <div class="form-group">
                            <label for="priority_edit">Priority</label>
                            <input type="text" class="form-control" id="priority_edit" name="priority" required>
                        </div>
                        <div class="form-group">
                            <label for="duration_edit">Duration (days)</label>
                            <input type="number" class="form-control" id="duration_edit" name="duration" required>
                        </div>
                        <input type="hidden" name="id" id="task_id">
                        <input type="hidden" name="editTask" value="1">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $('#editTaskModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var modal = $(this);
            
            $.ajax({
                url: 'get_task.php',
                method: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function (data) {
                    modal.find('#task_id').val(data.id);
                    modal.find('#task_edit').val(data.task);
                    modal.find('#owner_edit').val(data.owner);
                    modal.find('#status_edit').val(data.status);
                    modal.find('#start_date_edit').val(data.start_date);
                    modal.find('#due_date_edit').val(data.due_date);
                    modal.find('#completion_edit').val(data.completion);
                    modal.find('#priority_edit').val(data.priority);
                    modal.find('#duration_edit').val(data.duration);
                }
            });
        });
    </script>
</body>
</html>
