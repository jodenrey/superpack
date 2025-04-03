<?php

$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';
$department = isset($_SESSION['user_department']) ? $_SESSION['user_department'] : 'Superpack Enterprise';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar Example</title>
    <!-- Update to Font Awesome 6 for more modern icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="sidebar_small.css">

</head>
<body>
    <?php include 'sidebar.php'; ?>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="container-sidebar-all">
            <div class="container-sidebar-logo" onclick="toggleBigSidebar()">
                <img src="Superpack-Enterprise-Logo.png" alt="Superpack Enterprise Logo" class="logo_image">
            </div>

            <div class="container-sidebar-options">
                <ul class="nav_list" >
                    <li data-tooltip="Dashboard" onclick="window.location.href='task_management.php?department=<?php echo $department;?>'">
                        <a>
                            <i class="fa-solid fa-gauge-high"></i>
                        </a>
                    </li>
                    <li id="small-payroll" data-tooltip="Payroll" onclick="window.location.href='payroll.php'">
                        <a>
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </a>
                    </li>
                    <li id='small-employee' data-tooltip="Employee Management" onclick="window.location.href='employee_list.php';">
                        <a>
                            <i class="fa-solid fa-users-gear"></i>
                        </a>
                    </li>
                    <!-- Add other menu items here -->
                </ul>
            </div>
        </div>
    </div>

    <!-- JavaScript for dropdowns and sidebar functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const role = "<?php echo $role; ?>";
                
            // Select elements
            const employeeLi = document.getElementById('small-employee');
            const payrollLi = document.getElementById('small-payroll');
            
            // Select sidebar menu items
            const menuItems = {
                payroll: Array.from(document.querySelectorAll('.sidebar ul li'))
                    .find(li => li.textContent.includes('Payroll')),
                recruitment: Array.from(document.querySelectorAll('.sidebar ul li'))
                    .find(li => li.textContent.includes('Recruitment')),
                personnelRecords: Array.from(document.querySelectorAll('.sidebar ul li'))
                    .find(li => li.textContent.includes('Personnel Records')),
                evaluation: Array.from(document.querySelectorAll('.sidebar ul li a'))
                    .find(li => li.textContent.includes('Evaluation'))
            };
            
            // Hide items based on user role
            if (role !== 'Admin') {
                const elementsToHide = [
                    employeeLi, 
                    payrollLi, 
                    menuItems.payroll, 
                    menuItems.recruitment, 
                    menuItems.personnelRecords, 
                    menuItems.evaluation
                ];
                
                elementsToHide.forEach(el => {
                    if (el) el.style.display = 'none';
                });
            } else {
                // Show items for admin
                const elementsToShow = [
                    menuItems.payroll, 
                    menuItems.recruitment, 
                    menuItems.personnelRecords, 
                    menuItems.evaluation
                ];
                
                elementsToShow.forEach(el => {
                    if (el) el.style.display = 'block';
                });
            }
            
            // Setup dropdowns
            setupDropdowns();
        });

        // Dropdown functionality
        function setupDropdowns() {
            // Handle dropdown toggle
            document.querySelectorAll('.dropdown-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const dropdown = button.nextElementSibling;
                    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                    button.classList.toggle('active');
                });
            });
        }

        // Toggle big sidebar
        function toggleBigSidebar() {
            const sidebar = document.querySelector('.big-sidebar');
            const currentLeft = sidebar.style.left;
            
            // Use smooth animation
            sidebar.style.left = currentLeft === '0px' ? '-350px' : '0px';
        }
    </script>
</body>
</html>
