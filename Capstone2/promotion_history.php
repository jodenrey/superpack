<?php
session_start([
    'cookie_lifetime' => 86400,
    'read_and_close'  => false,
]);

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../welcome.php');
    exit();
}

// Get the correct username from session
$username = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';

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

// Get promotions data
$promotionsQuery = "SELECT p.*, we.performance, we.comments as evaluation_comments 
                   FROM promotions p 
                   LEFT JOIN worker_evaluations we ON p.evaluation_id = we.id 
                   ORDER BY p.promotion_date DESC";
$promotionsResult = $conn->query($promotionsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Promotion History</title>
    <link rel="stylesheet" href="style_index.css">
    <link rel="icon" type="image/x-icon" href="Superpack-Enterprise-Logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="dashboardnew.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'sidebar_small.php'; ?>
    <?php include 'eval_sidebar.php'; ?>
    <div class="container-everything" style="height:100%;">
        <div class="container-all">
            <div class="container-top">
                <?php include 'header_2.php'; ?>
            </div>
            <div class="container-bottom">
                <div class="container-table">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Promotion History</h3>
                        <a href="worker_eval.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Evaluations
                        </a>
                    </div>
                    
                    <div class="table-container">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Employee Name</th>
                                    <th>From Position</th>
                                    <th>To Position</th>
                                    <th>Promotion Date</th>
                                    <th>Performance Score</th>
                                    <th>Promoted By</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($promotionsResult->num_rows > 0) {
                                    while ($row = $promotionsResult->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row['employee_id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['employee_name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['current_position']) . "</td>";
                                        echo "<td><span class='badge badge-success'>" . htmlspecialchars($row['promoted_to_position']) . "</span></td>";
                                        echo "<td>" . htmlspecialchars($row['promotion_date']) . "</td>";
                                        echo "<td>" . (!empty($row['performance']) ? $row['performance'] . "/50" : "N/A") . "</td>";
                                        echo "<td>" . htmlspecialchars($row['promoted_by']) . "</td>";
                                        echo "<td>";
                                        if (strlen($row['reason']) > 50) {
                                            echo "<span title='" . htmlspecialchars($row['reason']) . "'>" . 
                                                 htmlspecialchars(substr($row['reason'], 0, 50)) . "...</span>";
                                        } else {
                                            echo htmlspecialchars($row['reason']);
                                        }
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='text-center'>No promotions recorded yet</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Change logo name 
        const logoName = document.querySelector('.logo_name');
        logoName.textContent = 'Promotion History';

        const clock = document.querySelector('.current-time');
        const options = {hour: '2-digit', minute: '2-digit'};
        const locale = 'en-PH';
        setInterval(() => {
            const now = new Date();
            clock.textContent = now.toLocaleTimeString(locale, options);
        }, 1000);
    </script>
</body>
</html> 