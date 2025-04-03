<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "password";
$database = "superpack_database";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Check if ID parameter is set
if(isset($_POST['id'])) {
    $id = $_POST['id'];
    
    // Prepare and execute SQL DELETE query
    $sql = "DELETE FROM workers WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo "Employee deleted successfully";
    } else {
        echo "Error deleting employee: " . $conn->error;
    }

    $stmt->close();
} else {
    echo "ID parameter not set";
}

$conn->close();
?>
