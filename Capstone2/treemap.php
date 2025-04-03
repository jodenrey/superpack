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

// Query to get department data and count from worker_evaluations
$query = "SELECT department, COUNT(*) as count FROM worker_evaluations GROUP BY department";
$result = $conn->query($query);

$data = [];
if ($result->num_rows > 0) {
    // Fetching each department and its count
    while($row = $result->fetch_assoc()) {
        $data[] = [
            'x' => $row['department'],
            'y' => (int) $row['count']
        ];
    }
} else {
    echo "No data found.";
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treemap Example</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts">
    <style>
        #treemap {
            width: 100%;
            height: 100%;
            padding: 10px;
        }
        .treemap-container {
            margin: 0 auto;
            text-align: left;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="treemap-container">
        <h2>Department Eval Distribution</h2>
        <div id="treemap"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        var options = {
            chart: {
                type: 'treemap',
                width: '100%',
                height: 300,
            },
            series: [{
                data: <?php echo json_encode($data); ?>
            }]
        };

        var chart = new ApexCharts(document.querySelector("#treemap"), options);
        chart.render();
    </script>
</body>
</html>
