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

// Get calculation parameters from the form
$salary = $_POST['salary'];
$sss = $_POST['sss'];
$pagibig = $_POST['pagibig'];
$otherInsurance = $_POST['other_insurance'];

// Perform the calculation
$totalDeduction = $sss + $pagibig + $otherInsurance;
$netSalary = $salary - $totalDeduction;

// You can save the results to the database here if needed

// Send the calculation results back to the client
echo "Total Deduction: " . $totalDeduction . "\nNet Salary: " . $netSalary;

$conn->close();
?>
