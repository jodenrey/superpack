<?php

$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';
$department = isset($_SESSION['user_department']) ? $_SESSION['user_department'] : 'Superpack Enterprise';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar Big</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="sidebar_small.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="big-sidebar">
        <div class="big-sidebar-container-all" onclick="toggleBigSidebar()">
            <div class="container-big-sidebar-logo" >
                <img src="Superpack-Enterprise-Logo.png" alt="Superpack Enterprise Logo" class="logo_image">
                <span class="logo_name">Superpack<br>Enterprise</span>
                
            </div>
            <div class="big-sidebar-back-button">
                <span class="chevron-icon"><i class="fas fa-chevron-left"></i></span>
            </div>

        </div>

        <div class="container-big-sidebar-options">
            <ul class="big-nav_list">
                <li>
                    <a href="dashboardnew.php">
                        <span class="icon"><i class="fas fa-tachometer-alt"></i></span>
                        <span class="title">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-btn">
                        <span class="icon"><i class="fas fa-th-list"></i></span>
                        <span class="title">Employee</span>
                        <span class="dropdown-icon"><i class="fas fa-chevron-down"></i></span>
                    </a>
                    <ul class="dropdown-container">
                        <li><a href="attendance_check.php?username=<?php echo urlencode($_SESSION['username']); ?>">Checking Attendance</a></li>
                        <li><a href="worker_eval.php">Evaluation</a></li>
                        <li><a href="warning_notice.php">Warning Notice</a></li>
                        <li><a href="recruitment.php">Recruitment and Selection</a></li>
                        <li><a href="training.php">Training and Development</a></li>
                    </ul>
                </li>
                <li>
                    <a href="payroll.php">
                        <span class="icon"><i class="fas fa-pencil-alt"></i></span>
                        <span class="title">Payroll</span>
                    </a>
                </li>
                <li>
                    <a href="task_management.php?department=<?php echo $department?>">
                        <span class="icon"><i class="fas fa-chart-bar"></i></span>
                        <span class="title">Task Management</span>
                    </a>
                </li>
                <li>
                    <a href="employee_list.php">
                        <span class="icon"><i class="fas fa-cogs"></i></span>
                        <span class="title">Personnel Records</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <!-- JavaScript for dropdowns and scroll-to-top functionality -->
    <script>

document.addEventListener('DOMContentLoaded', function() {
    var role = "<?php echo $role; ?>";
    
    // Helper function to hide an item if it exists
    function hideElement(element) {
        if (element) {
            element.style.display = 'none';
        }
    }

    function showElement(element) {
        if (element) {
            element.style.display = 'block';
        }
    }

    // Select the Payroll menu item by its title text
    var payrollItem = Array.from(document.querySelectorAll('.big-sidebar ul li'))
        .find(li => li.textContent.trim().includes('Payroll'));

    // Select the Recruitment menu item by its title text
    var recruitmentItem = Array.from(document.querySelectorAll('.big-sidebar ul li'))
        .find(li => li.textContent.trim().includes('Recruitment'));

    // Select the Personnel Records menu item by its title text
    var personnelRecordsItem = Array.from(document.querySelectorAll('.big-sidebar ul li'))
        .find(li => li.textContent.trim().includes('Personnel Records'));

    // Select Evaluation inside Employee dropdown
    var evaluationItem = Array.from(document.querySelectorAll('.big-sidebar ul li a'))
        .find(a => a.textContent.trim().includes('Evaluation'));
    
    // Hide or show items based on role
    if (role !== 'Admin') {
        hideElement(payrollItem);
        hideElement(recruitmentItem);
        hideElement(personnelRecordsItem);
        hideElement(evaluationItem);
    } else {
        showElement(payrollItem);
        showElement(recruitmentItem);
        showElement(personnelRecordsItem);
        showElement(evaluationItem);
    }
});


        // Function to toggle dropdown
        document.addEventListener('DOMContentLoaded', function() {
            var dropdowns = document.querySelectorAll('.dropdown-btn');
            dropdowns.forEach(function(dropdown) {
                dropdown.addEventListener('click', function() {
                    this.classList.toggle('active');
                });
            });
        });

        


    </script>
</body>
</html>
