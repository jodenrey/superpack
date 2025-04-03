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

// Fetch table counts
$sql = "SELECT table_name, table_rows FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$database'";
$result = $conn->query($sql);

$tables = [];
$counts = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tables[] = $row['table_name'];
        $counts[] = $row['table_rows'];
    }
} else {
    echo "No tables found in the database.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pie Chart Example</title>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        #pie-chart {
            width: 100%;
            height: 100%; /* Ensures the chart fills the height of its container */
            background-color: #f0f0f0;
        }
        .pie-container {
            width: 100%;
            height: auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="pie-container">
        <h2>Pie Chart</h2>
        <div id="pie-chart"></div>
    </div>
    <script>
        var options = {
            series: <?php echo json_encode($counts); ?>,
            chart: {
                type: 'donut',
                width: '100%',
                height: 300,
            },
            labels: <?php 
                $formatted_tables = array_map(function($table) {
                    $table = str_replace('tasks', '', $table);
                    return ucwords(str_replace('_', ' ', $table));
                }, $tables);
                echo json_encode($formatted_tables); 
            ?>,
            legend: {
                position: 'right'
            }
        };

        var chart = new ApexCharts(document.querySelector("#pie-chart"), options);
        chart.render();
    </script>
</body>
</html>