<?php
session_start([
]);

// Check if form data is received
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data from the POST request
    $username = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
    $role = isset($_POST['role']) ? htmlspecialchars($_POST['role']) : '';
    $user_department = isset($_POST['user_department']) ? htmlspecialchars($_POST['user_department']) : '';
    $loggedin = isset($_POST['loggedin']) ? htmlspecialchars($_POST['loggedin']) : false;

    // Store data in session if required for later use
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['user_department'] = $user_department;
    $_SESSION['loggedin'] = $loggedin;

    // Example of displaying form data in the header
    //echo "<header>";
    //echo "<p>Welcome, $username!</p>";
    //echo "<p>Your Role: $role</p>";
    //echo "<p>Your Department: $user_department</p>";
    //echo "</header>";
}
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../welcome.php');
    exit;
}


$host = "localhost";
$user = "root";
$password = "password";
$database_workers = "superpack_database";
$database_hr = "superpack_database";
$database_default = "superpack_database";
$port = 3306;

// mysqli connection
$conn = new mysqli($host, $user, $password, $database_default, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Make a query that fetches the total number of employees present today
$query = "SELECT DISTINCT COUNT(*) AS total FROM attendance";

// prepare the query
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->execute();
    $is_present = $stmt->get_result();
    
    // Ensure there's a result
    if ($is_present && $is_present->num_rows > 0) {
        // Fetch the result row as an associative array
        $row = $is_present->fetch_assoc();
        $total = $row['total'] ?? 0; // Check if 'total' exists
    } else {
        $total = 0; // Default value if no rows found
    }
}

// Check if user is on time or late
$query = "SELECT DISTINCT name, time_in FROM attendance WHERE name = ?";
$on_time_threshold = "07:30:00";

$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    // Ensure there's a result
    if ($result && $result->num_rows > 0) {
        // Fetch the result row as an associative array
        $row = $result->fetch_assoc();
        $time_in = $row['time_in'] ?? null; // Check if 'time_in' exists
    } else {
        $time_in = null; // Default value if no rows found
    }
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style_index.css">
    <link rel="icon" type="image/x-icon" href="Superpack-Enterprise-Logo.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="dashboardnew.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    
    <div class="container-sidebar">
        <?php include 'sidebar_small.php'?>
    </div>
    <div class="container-everything">
        <div class="container-all">
            <div class="container-top">
                
                <?php include 'header_2.php';?>
                <div class="top-widgets-container">
                    <div class="options">
                        <div class="option">
                            <div class="overview-card blue">
                                <div class="count-employee-total">
                                    <?php echo $total;?>
                                </div>
                                <div class="info">Total Employees</div>
                            </div>
                        </div>

                        <div class="option">
                            <div class="overview-card orange">
                                <div class="count-ontime-total">
                                    <?php
                                    if ($time_in) {
                                        if ($time_in > $on_time_threshold) {
                                            echo "LATE";
                                        } else {
                                            echo "ON TIME";
                                        }
                                    } else {
                                        echo "No time recorded";
                                    }
                                    ?>
                                </div>
                                <div class="info">On Time Today</div>
                            </div>
                        </div>
                        <div class="option" onclick="window.location.href='task_management.php?department=<?php echo $department;?>'" style="cursor: pointer;">
                            <div class="overview-card green">
                                <div class="button-widget-text">Click Here</div>
                                <div class="info">Check Task</div>
                            </div>
                        </div>
                        <div class="option">
                            <div class="overview-card red">
                                <div class="current-time-widget">&#8203;</div>
                                <div class="info">Current Time</div>
                            </div>
                        </div>
                        <div class="option" onclick="window.location.href='attendance_check.php?username=<?php echo $username?>'" style="cursor: pointer;">
                            <div class="overview-card green">
                                <div class="button-widget-text">Click Here</div>
                                <div class="info">Check Attendance</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>  
            <div class="container-bottom">
                <div class="container-left">
                    <div class="graph-container">
                        <?php include 'bar_chart.php'; ?>
                    </div>

                    <div class="graph-container">
                        <?php include 'leave_table_widget.php'; ?>
                    </div>

                    <div class="graph-container">
                        <?php include 'employee_table_widget.php'; ?>
                    </div>

                    <div class="graph-container">
                        <?php include 'calendar.php'; ?>
                    </div>
                </div>
                <div class="container-right">
                    <div class="graph-container">
                        <?php include 'pie_chart.php'; ?>
                    </div>

                    <div class="graph-container">
                        <?php include 'treemap.php'; ?>
                    </div>

                    <div class="graph-container">
                        <?php include 'pie_chart_employee.php'; ?>
                    </div>
            
                </div>
            </div>
        </div>
    </div>
    <script>
        

        document.addEventListener('DOMContentLoaded', () => {
            const clock_widget = document.querySelector('.current-time-widget');
            const options = { hour: '2-digit', minute: '2-digit' };
            const locale = 'en-PH';

            if (clock_widget) {
                setInterval(() => {
                    const now = new Date();
                    clock_widget.textContent = now.toLocaleTimeString(locale, options);
                }, 1000);
            } else {
                console.error("Element with class 'current-time-widget' not found.");
            }
        });

        // Function to show or hide the scroll-to-top button
        window.onscroll = function() {
            var button = document.querySelector(".scroll-to-top");
            if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
                button.style.display = "block";
            } else {
                button.style.display = "none";
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            // If role is Admin, hide other widgets
            var role = "<?php echo $role; ?>";

            // Select count-employee-total and its parent
            var countEmployeeTotal = document.querySelector('.count-employee-total');
            var countEmployeeTotalParent = countEmployeeTotal.parentElement;


            // Select fourth graph-container
            var pieContainer = document.querySelectorAll('.graph-container')[4];
            
            // Select fifth graph-container
            var treemapContainer = document.querySelectorAll('.graph-container')[5];

            var employeeContainer = document.querySelectorAll('.graph-container')[2];

            var employeepieContainer = document.querySelectorAll('.graph-container')[6];

            if (role !== 'Admin') {
                countEmployeeTotalParent.style.display = 'none';
                pieContainer.style.display = 'none';
                treemapContainer.style.display = 'none';
                employeeContainer.style.display = 'none';
                
            } else {
                countEmployeeTotalParent.style.display = 'block';
                pieContainer.style.display = 'block';
                treemapContainer.style.display = 'block';
                employeepieContainer.style.display = 'none';
            }
        });
    </script>
</body>
</html>
