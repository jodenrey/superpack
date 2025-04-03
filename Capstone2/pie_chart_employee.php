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

// Get username
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
} else {
    $username = "User";
}

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
$department = isset($_SESSION['user_department']) ? $_SESSION['user_department'] : 'Superpack Enterprise';

// Initialize counts for each status
$inProgressCount = 0;
$startedCount = 0;
$completedCount = 0;

// Query to get tasks by the specified username
$sql = "SELECT status FROM " . $department . "_tasks WHERE owner = '$username'";
$result = $conn->query($sql);

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
} else {
    echo "No tasks found.";
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
