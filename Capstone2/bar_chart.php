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

// Define the threshold times
$on_time_threshold = "07:30:00";
$late_threshold = "07:31:00";

// Query to fetch time_in per employee
$query = "SELECT name, time_in, QUARTER(time_in) as quarter FROM attendance where name = '$username'";
$result = $conn->query($query);

$employees = [];
$on_time_data = [];
$late_data = [];
$absent_data = [];

if ($result->num_rows > 0) {
    // Process each record
    while($row = $result->fetch_assoc()) {
        $name = $row['name'];
        $time_in = $row['time_in'];
        $quarter = $row['quarter'];
        
        // Initialize the employee's data if not already set
        if (!isset($employees[$name][$quarter])) {
            $employees[$name][$quarter] = [
                'on_time' => 0,
                'late' => 0,
                'absent' => 0
            ];
        }

        // Check time_in and categorize
        if (is_null($time_in)) {
            // Absent if no time_in value
            $employees[$name][$quarter]['absent']++;
        } else {
            $time_only = date('H:i:s', strtotime($time_in));
            if ($time_only <= $on_time_threshold) {
                // On Time
                $employees[$name][$quarter]['on_time']++;
            } elseif ($time_only > $on_time_threshold && $time_only <= $late_threshold) {
                // Late
                $employees[$name][$quarter]['late']++;
            } else {
                // Absent if past the late threshold
                $employees[$name][$quarter]['absent']++;
            }
        }
    }

    // Format data for ApexCharts
    foreach ($employees as $name => $quarters) {
        foreach ($quarters as $quarter => $data) {
            $categories[] = "$name (Q$quarter)";
            $on_time_data[] = $data['on_time'];
            $late_data[] = $data['late'];
            $absent_data[] = $data['absent'];
        }
    }
} else {
    echo "No data found.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bar Chart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts">
    <style>
        #bar-chart {
            width: 100%;
            height: 100%; /* Ensures the chart fills the height of its container */
        }
        .bar-chart .bar {
            animation: grow 1s ease-out;
        }

        /* Custom animation for hover effect */
        .bar-chart .bar:hover {
            animation: none;
            background-color: #28a745;
        }
        .bar-container {
            margin: 0 auto;
            text-align: left;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="bar-container">
        <h2>My Attendance Record</h2>
        <div id="bar-chart"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        var options = {
            chart: {
                type: 'bar',
                width: '60%',
                height: 185,
                stacked: true,
            },
            plotOptions: {
                bar: {
                horizontal: true
                }
            },
            series: [{
                name: 'On Time',
                data: <?php echo json_encode($on_time_data); ?>
            }, {
                name: 'Late',
                data: <?php echo json_encode($late_data); ?>
            }, {
                name: 'Absent',
                data: <?php echo json_encode($absent_data); ?>
            }],
            xaxis: {
                categories: <?php echo json_encode($categories); ?>
            },
            yaxis: {
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val + " days";
                    }
                }
            }

        }

        var chart = new ApexCharts(document.querySelector("#bar-chart"), options);

        chart.render();
    </script>
</body>
</html>