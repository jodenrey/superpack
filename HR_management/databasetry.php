<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "password";
$dbname = "superpack_database";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$name = $_POST['newName'];
$position = $_POST['newPosition'];
$shift = $_POST['newShift'];
$salary = $_POST['newSalary'];
$status = $_POST['newStatus'];

// Insert data into database
$sql = "INSERT INTO employees (name, position, shift, salary, status) VALUES ('$name', '$position', '$shift', '$salary', '$status')";

if ($conn->query($sql) === TRUE) {
    echo "New record created successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
