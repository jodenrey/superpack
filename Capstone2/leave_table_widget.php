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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Widget</title>
    <style>
        .table-container {
            max-height: 280px; /* Adjust height as needed */
            overflow-y: auto;
            border: 1px solid #ccc;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        table.table-widget {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* Ensures consistent column widths */
            
            background-color: #f9f9f9;
        }
        
        .table-widget th, .table-widget td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
            word-wrap: break-word; /* Allows text to break in smaller cells */
        }
        
        .table-widget th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        
        .table-widget tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .table-widget tr:hover {
            background-color: #f1f1f1;
        }
        
        .table-container::-webkit-scrollbar {
            width: 10px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 5px;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background-color: #555;
        }
    </style>
</head>
<body>
    
    <div class="table-container">
    <h2>My Leaves</h2>
        <table class="table-widget">
            <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM leave_request WHERE name = '$username'";
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['leave_type'] . "</td>";
                        echo "<td>" . $row['start_date'] . "</td>";
                        echo "<td>" . $row['end_date'] . "</td>";
                        echo "<td>" . $row['status'] . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No leaves found</td></tr>";
                }
                
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
