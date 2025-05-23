<?php
// Database connection parameters
$host = "localhost";
$user = "root";
$password = "password";
$database = "superpack_database";
$port = 3306;

// Create connection
$conn = new mysqli($host, $user, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to create the job_positions table
$createPositionsTable = "CREATE TABLE IF NOT EXISTS job_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT NOT NULL,
    status ENUM('Open', 'Closed') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// SQL to create the job_applications table
$createApplicationsTable = "CREATE TABLE IF NOT EXISTS job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    address TEXT NOT NULL,
    education TEXT NOT NULL,
    experience TEXT NOT NULL,
    resume_path VARCHAR(255),
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('New', 'Screening', 'Interview', 'Offer', 'Hired', 'Rejected') DEFAULT 'New',
    FOREIGN KEY (position_id) REFERENCES job_positions(id)
)";

// SQL to create the candidates_training table
$createTrainingTable = "CREATE TABLE IF NOT EXISTS candidates_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    candidate_name VARCHAR(255) NOT NULL,
    position VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    offer_status ENUM('Pending', 'Accepted', 'Rejected') NOT NULL DEFAULT 'Pending',
    training_status ENUM('Not Started', 'In Progress', 'Completed') NOT NULL DEFAULT 'Not Started',
    scheduled_date DATE,
    completion_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE
)";

// Execute the SQL statements
$positionsResult = $conn->query($createPositionsTable);
if (!$positionsResult) {
    die("Error creating job_positions table: " . $conn->error);
}

$applicationsResult = $conn->query($createApplicationsTable);
if (!$applicationsResult) {
    die("Error creating job_applications table: " . $conn->error);
}

$trainingResult = $conn->query($createTrainingTable);
if (!$trainingResult) {
    die("Error creating candidates_training table: " . $conn->error);
}

// Insert sample job position
$insertSamplePosition = "INSERT INTO job_positions (title, department, description, requirements, status) 
                        VALUES ('Software Developer', 'IT Department', 'Responsible for developing and maintaining software applications.', 
                        'Bachelor\'s degree in Computer Science or related field. Experience with PHP, JavaScript, and MySQL.', 'Open')";

$samplePositionResult = $conn->query($insertSamplePosition);
if (!$samplePositionResult) {
    echo "Error inserting sample position: " . $conn->error . "<br>";
}

echo "Job tables created successfully!";

// Close the connection
$conn->close();
?> 