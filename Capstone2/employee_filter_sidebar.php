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
        <label for="filter-name">Name:</label>
        <input type="text" id="filter-name" placeholder="Search by Name">
        </div>

        <!-- Textbox for Position -->
        <div>
        <label for="filter-position">Position:</label>
        <input type="text" id="filter-position" placeholder="Search by Position">
        </div>

        <!-- Calendar Range for Due Date -->
        <div>
        <label for="filter-due-date">Starting Date:</label>
        <input type="date" id="filter-due-date" placeholder="Start Date">

        </div>
        <!-- Dropdown for Priority -->
        <div>
        <label for="filter-status">Status:</label>
        <select id="filter-status">
            <option value="">Select Status</option>
            <option value="Low">Active</option>
            <option value="Medium">Inactive</option>
            <option value="High">Hired</option>
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
    const filterName = document.getElementById('filter-name').value;
    const filterPosition = document.getElementById('filter-position').value;
    const filterStartDate = document.getElementById('filter-due-date').value;
    const filterStatus = document.getElementById('filter-status').value;
    let url = `employee_list.php`;

    // Append query parameters
    const params = [];

    if (filterName) {
        params.push(`name=${encodeURIComponent(filterName)}`);
    }
    if (filterPosition) {
        params.push(`position=${encodeURIComponent(filterPosition)}`);
    }
    if (filterStartDate) {
        params.push(`start_date=${encodeURIComponent(filterStartDate)}`);
    }
    if (filterStatus) {
        params.push(`status=${encodeURIComponent(filterStatus)}`);
    }

    // If there are parameters, add them to the URL
    if (params.length > 0) {
        url += `?${params.join('&')}`;
    }

    // Redirect to the same page with the filter parameters
    window.location.href = url;
}


    </script>
</body>