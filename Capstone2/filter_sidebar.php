<?php
// Check if the 'department' key exists in the URL query parameters
if (isset($_GET['department'])) {
    $department_name = $_GET['department'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<style>
    
</style>
<body>
    <!-- Sidebar -->
    <div class="filter-sidebar">
        <!-- Textbox for Assigned -->
        <div>
        <label for="filter-assigned">Assigned:</label>
        <input type="text" id="filter-assigned" placeholder="Search by Name">
        </div>

        <!-- Dropdown for Status -->
        <div>
        <label for="filter-status">Status:</label>
        <select id="filter-status">
            <option value="">Select Status</option>
            <option value="Not Started">Not Started</option>
            <option value="In Progress">In Progress</option>
            <option value="completed">Completed</option>
        </select>
        </div>

        <!-- Calendar Range for Due Date -->
        <div>
        <label for="filter-due-date">Duration:</label>
        <input type="date" id="filter-due-date-start" placeholder="Start Date">
        <input type="date" id="filter-start-date-end" placeholder="End Date">

        </div>
        <!-- Dropdown for Priority -->
        <div>
        <label for="filter-priority">Priority:</label>
        <select id="filter-priority">
            <option value="">Select Priority</option>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
        </select>
        </div>


        <div class="filter-sidebar-buttons">
        <button id="filter-apply-button" onclick="apply_filter()">Apply</button>
        <button id="filter-back-button" onclick="toggle_filter()">Back</button>
        </div>
    </div>
    <script>
        // Function to toggle the filter sidebar
        function toggle_filter() {
            const filterSidebar = document.querySelector('.filter-sidebar');
            filterSidebar.style.right = filterSidebar.style.right === '0px' ? '-300px' : '0px';
        }

        function apply_filter() {
            const filterAssigned = document.getElementById('filter-assigned').value;
            const filterStatus = document.getElementById('filter-status').value;
            const filterDueDateStart = document.getElementById('filter-due-date-start').value;
            const filterStartDateEnd = document.getElementById('filter-start-date-end').value;
            const filterPriority = document.getElementById('filter-priority').value;

            let url = `task_management.php?department=<?php echo $department_name; ?>`;

            if (filterAssigned) {
                url += `&assigned=${filterAssigned}`;
            }
            if (filterStatus) {
                url += `&status=${filterStatus}`;
            }
            if (filterDueDateStart) {
                url += `&due_date_start=${filterDueDateStart}`;
            }
            if (filterStartDateEnd) {
                url += `&start_date_end=${filterStartDateEnd}`;
            }
            if (filterPriority) {
                url += `&priority=${filterPriority}`;
            }

            // Redirect to the same page with the filter parameters
            window.location.href = url;
        }

    </script>
</body>