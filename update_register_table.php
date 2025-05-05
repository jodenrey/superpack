<?php
// Database connection settings
$host = "localhost";
$db_username = "root";
$db_password = "password"; // Use the correct password here
$db_name = "superpack_database";

// Connect to the database
$conn = new mysqli($host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Update Script</h2>";
echo "<p>Checking and updating database structure...</p>";

// Check if employee_id column exists in register table
$checkColumn = $conn->query("SHOW COLUMNS FROM `register` LIKE 'employee_id'");
if ($checkColumn->num_rows == 0) {
    // Add employee_id column
    $alterQuery = "ALTER TABLE `register` ADD COLUMN `employee_id` VARCHAR(50) NOT NULL AFTER `department`";
    if ($conn->query($alterQuery) === TRUE) {
        echo "<p>Successfully added employee_id column to register table.</p>";
    } else {
        echo "<p>Error adding employee_id column: " . $conn->error . "</p>";
    }
    
    // Add UNIQUE constraint to employee_id
    $alterQuery = "ALTER TABLE `register` ADD UNIQUE INDEX `employee_id` (`employee_id`)";
    if ($conn->query($alterQuery) === TRUE) {
        echo "<p>Successfully added UNIQUE constraint to employee_id column.</p>";
    } else {
        echo "<p>Error adding UNIQUE constraint: " . $conn->error . "</p>";
    }
} else {
    echo "<p>employee_id column already exists in register table.</p>";
}

// Check if password column exists in register table
$checkColumn = $conn->query("SHOW COLUMNS FROM `register` LIKE 'password'");
if ($checkColumn->num_rows == 0) {
    // Add password column
    $alterQuery = "ALTER TABLE `register` ADD COLUMN `password` VARCHAR(255) NOT NULL AFTER `employee_id`";
    if ($conn->query($alterQuery) === TRUE) {
        echo "<p>Successfully added password column to register table.</p>";
    } else {
        echo "<p>Error adding password column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>password column already exists in register table.</p>";
}

// Check if qr_code_data column exists in register table
$checkColumn = $conn->query("SHOW COLUMNS FROM `register` LIKE 'qr_code_data'");
if ($checkColumn->num_rows == 0) {
    // Add qr_code_data column
    $alterQuery = "ALTER TABLE `register` ADD COLUMN `qr_code_data` TEXT NOT NULL AFTER `password`";
    if ($conn->query($alterQuery) === TRUE) {
        echo "<p>Successfully added qr_code_data column to register table.</p>";
    } else {
        echo "<p>Error adding qr_code_data column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>qr_code_data column already exists in register table.</p>";
}

// Check if landmarks_hash column exists and remove it if not needed
$checkColumn = $conn->query("SHOW COLUMNS FROM `register` LIKE 'landmarks_hash'");
if ($checkColumn->num_rows > 0) {
    echo "<p>landmarks_hash column exists. You can remove it if it's no longer needed.</p>";
    echo "<p>To remove it, uncomment the code below and run this script again.</p>";
    
    /*
    $alterQuery = "ALTER TABLE `register` DROP COLUMN `landmarks_hash`";
    if ($conn->query($alterQuery) === TRUE) {
        echo "<p>Successfully removed landmarks_hash column from register table.</p>";
    } else {
        echo "<p>Error removing landmarks_hash column: " . $conn->error . "</p>";
    }
    */
}

// Check if employee_id column exists in users table
$checkColumn = $conn->query("SHOW COLUMNS FROM `users` LIKE 'employee_id'");
if ($checkColumn->num_rows == 0) {
    // Add employee_id column
    $alterQuery = "ALTER TABLE `users` ADD COLUMN `employee_id` VARCHAR(50) NOT NULL AFTER `id`";
    if ($conn->query($alterQuery) === TRUE) {
        echo "<p>Successfully added employee_id column to users table.</p>";
    } else {
        echo "<p>Error adding employee_id column to users table: " . $conn->error . "</p>";
    }
    
    // Add UNIQUE constraint to employee_id
    $alterQuery = "ALTER TABLE `users` ADD UNIQUE INDEX `employee_id` (`employee_id`)";
    if ($conn->query($alterQuery) === TRUE) {
        echo "<p>Successfully added UNIQUE constraint to employee_id column in users table.</p>";
    } else {
        echo "<p>Error adding UNIQUE constraint to users table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>employee_id column already exists in users table.</p>";
}

// Close the connection
$conn->close();

echo "<h3>Database update completed.</h3>";
echo "<p>You can now <a href='Face_API/Python/register.php'>register</a> with the new QR code system.</p>";
?> 