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
    .filter-sidebar {
        width: 250px;
        height: 100vh;
        background-color: #64A651; /* Sidebar color */
        color: #fff;
        position: fixed;
        /* right: -300px; */
        right:0;
        top: 0;
        padding: 20px;
        transition: all 0.3s ease;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        display:flex;
        flex-direction: column;
        justify-content: space-evenly;
    }
    
    .filter-sidebar button {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        background-color: #fff;
        color: #64A651; /* Button color */
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.3s ease;
        
        border-radius: 10px;
    }

    .filter-sidebar button:hover {
        background-color: #64A651; /* Button hover color */
        color: #fff;
    }
    
    .filter-sidebar input,
    .filter-sidebar select {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border: none;
        background-color: #fff;
        color: #64A651; /* Text color */
        font-size: 16px;
        font-weight: 600;
        border-radius: 10px;
    }

    .filter-sidebar label {
        font-size: 16px;
        font-weight: 600;
    }

    .filter-sidebar select {
        cursor: pointer;
    }

    .filter-sidebar input[type="date"] {
        cursor: pointer;
    }
    
</style>
<body>
    <!-- Sidebar -->
    <div class="filter-sidebar">
        <!-- Textbox for Name -->
        <input type="text" id="filter-name" placeholder="Search by Name">
        
        <!-- Textbox for Position -->
        <input type="text" id="filter-position" placeholder="Search by Position">
        
        <!-- Textbox for Shift -->
        <input type="text" id="filter-shift" placeholder="Search by Shift">
        
        <!-- Textbox for Salary -->
        <input type="text" id="filter-salary" placeholder="Search by Salary">
        
        <!-- Calendar for Start Date -->
        <label for="filter-start-date">Start Date:</label>
        <input type="date" id="filter-start-date" placeholder="Start Date">
        
        <!-- Dropdown for Status -->
        <select id="filter-status">
            <option value="">Select Status</option>
            <option value="Hired">Hired</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>
        
        <button id="filter-apply-button" onclick="apply_filter()">Apply</button>
        <button id="filter-back-button" onclick="toggle_filter()">Back</button>
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
            const filterDueDateEnd = document.getElementById('filter-due-date-end').value;
            const filterStartDateStart = document.getElementById('filter-start-date-start').value;
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
            if (filterDueDateEnd) {
                url += `&due_date_end=${filterDueDateEnd}`;
            }
            if (filterStartDateStart) {
                url += `&start_date_start=${filterStartDateStart}`;
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