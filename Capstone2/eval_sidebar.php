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
    <title>Evaluation Sidebar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<style>
    
    
</style>
<body>
    <!-- Sidebar -->
    <div class="filter-sidebar">
        <!-- Textbox for Assigned -->
        <label for="filter-employee-id">Employee ID:</label>
        <input type="text" id="filter-employee-id" placeholder="Search by Employee ID">
        
        <!-- Textbox for Name -->
        <label for="filter-name">Name:</label>
        <input type="text" id="filter-name" placeholder="Search by Name">

        <!-- Textbox for Position -->
        <label for="filter-position">Position:</label>
        <input type="text" id="filter-position" placeholder="Search by Position">

        <!-- Dropdown for Department -->
        <label for="filter-department">Department:</label>
        <select id="filter-department">
            <option value="">Select Department</option>
            <option value="Sales">Sales</option>
            <option value="Purchasing">Purchasing</option>
            <option value="Purchase Development">Purchase Development</option>
            <option value="Warehouse">Warehouse</option>
            <option value="Logistics">Logistics</option>
            <option value="Accounting">Accounting</option>
        </select>

        <!-- Calendar Range for Start Date -->
        <div>
        <label for="filter-start-date">Start Date:</label>
        <input type="date" id="filter-start-date-start" placeholder="Start Date">
        <input type="date" id="filter-start-date-end" placeholder="End Date">
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
            // Get the values of the filter inputs
            const employeeId = document.getElementById('filter-employee-id').value;
            const name = document.getElementById('filter-name').value;
            const position = document.getElementById('filter-position').value;
            const department = document.getElementById('filter-department').value;
            const startDateStart = document.getElementById('filter-start-date-start').value;
            const startDateEnd = document.getElementById('filter-start-date-end').value;

            // Construct the URL with the filter values
            let url = 'worker_eval.php';
            let params = [];

            if (employeeId) {
                params.push(`employee_id=${encodeURIComponent(employeeId)}`);
            }
            if (name) {
                params.push(`name=${encodeURIComponent(name)}`);
            }
            if (position) {
                params.push(`position=${encodeURIComponent(position)}`);
            }
            if (department) {
                params.push(`department=${encodeURIComponent(department)}`);
            }
            if (startDateStart) {
                params.push(`start_date_start=${encodeURIComponent(startDateStart)}`);
            }
            if (startDateEnd) {
                params.push(`start_date_end=${encodeURIComponent(startDateEnd)}`);
            }

            // If there are any params, append them to the URL
            if (params.length > 0) {
                url += '?' + params.join('&');
            }

            // Redirect to the URL with the filter values
            window.location.href = url;
        }


    </script>
</body>