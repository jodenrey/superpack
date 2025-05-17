<?php
// Database connection details
$host = "localhost";
$user = "root";
$password = "password";
$database = "superpack_database";
$port = 3306;

// Connect to database
$conn = new mysqli($host, $user, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Drop existing warning_notices table if it exists
$dropTableQuery = "DROP TABLE IF EXISTS warning_notices";
if ($conn->query($dropTableQuery)) {
    echo "Existing warning_notices table dropped successfully.<br>";
} else {
    echo "Error dropping table: " . $conn->error . "<br>";
}

// Create new warning_notices table with the expected schema
$createTableQuery = "CREATE TABLE warning_notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50),
    date_of_warning DATE,
    employee_name VARCHAR(100),
    department VARCHAR(100),
    location VARCHAR(100),
    violation_attendance TINYINT(1),
    violation_procedures TINYINT(1),
    violation_insubordination TINYINT(1),
    violation_carelessness TINYINT(1),
    violation_company_policies TINYINT(1),
    violation_damage TINYINT(1),
    violation_other TEXT,
    warning_level VARCHAR(50),
    employer_statement TEXT,
    employee_statement TEXT,
    consequence_verbal TINYINT(1),
    consequence_written TINYINT(1),
    consequence_probation TINYINT(1),
    consequence_suspension TINYINT(1),
    consequence_dismissal TINYINT(1),
    consequence_other TEXT,
    employee_initials VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($createTableQuery)) {
    echo "New warning_notices table created successfully.<br>";
    echo "Please go back to <a href='warning_notice.php'>Warning Notice</a> page.";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?> 