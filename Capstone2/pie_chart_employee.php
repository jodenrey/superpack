<?php 
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

// Get user information
$username = isset($_SESSION['name']) ? $_SESSION['name'] : 
           (isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest');

$department = isset($_SESSION['department']) ? $_SESSION['department'] : 
             (isset($_SESSION['user_department']) ? $_SESSION['user_department'] : 'sales');

// Convert department to lowercase and remove any spaces
$department_table = strtolower(str_replace(' ', '_', $department));

// Check if the department table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE '{$department_table}_tasks'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

// Initialize counts for each status
$inProgressCount = 0;
$startedCount = 0;
$completedCount = 0;

if ($table_exists) {
    // Query to get tasks by the specified username from the department-specific table
    $sql = "SELECT status FROM {$department_table}_tasks WHERE owner = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Count tasks by their status
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row['status'] == 'In Progress') {
                $inProgressCount++;
            } elseif ($row['status'] == 'Not Started') {
                $startedCount++;
            } elseif ($row['status'] == 'Completed') {
                $completedCount++;
            }
        }
    }
    $stmt->close();
} else {
    // Try to find any available task table
    $tables = array('sales_tasks', 'warehouse_tasks', 'logistics_tasks', 'accounting_tasks', 'purchasing_tasks', 'proddev_tasks');
    $found_table = '';
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            $found_table = $table;
            break;
        }
    }
    
    if (!empty($found_table)) {
        // Use the first available task table
        $sql = "SELECT status FROM $found_table WHERE owner = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Count tasks by their status
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($row['status'] == 'In Progress') {
                    $inProgressCount++;
                } elseif ($row['status'] == 'Not Started') {
                    $startedCount++;
                } elseif ($row['status'] == 'Completed') {
                    $completedCount++;
                }
            }
        }
        $stmt->close();
    } else {
        // No task tables found, use placeholder data
        $inProgressCount = 1;
        $startedCount = 2;
        $completedCount = 0;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Status Pie Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        #pie-employee-chart {
            width: 100%;
            height: 100%; /* Ensures the chart fills the height of its container */
            background-color: #f0f0f0;
        }
        .pie-employee-container {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="pie-employee-container">
        <h2>Task Status Overview</h2>
        <div id="pie-employee-chart"></div>
    </div>

    <script>
        // Pass the PHP counts into JavaScript
        var inProgressCount = <?php echo $inProgressCount; ?>;
        var startedCount = <?php echo $startedCount; ?>;
        var completedCount = <?php echo $completedCount; ?>;

        var options = {
            series: [inProgressCount, startedCount, completedCount],
            chart: {
                type: 'donut',
                height: 300
            },
            labels: ['In Progress', 'Not Started', 'Completed'],
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        var chart = new ApexCharts(document.querySelector("#pie-employee-chart"), options);
        chart.render();
    </script>
</body>
</html>
