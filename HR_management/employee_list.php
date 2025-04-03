<?php
// Include the database connection file
require_once("database.php");

try {
    // Prepare SQL statement to select all employees from 'employee_list' table
    $sql = "SELECT * FROM employee_records ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);

    // Execute the statement
    $stmt->execute();

    // Fetch all employees
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle PDO exceptions
    $_SESSION['error_message'] = "PDO Exception: " . $e->getMessage();
    echo '<script>alert("PDO Exception: ' . $e->getMessage() . '"); window.location.href = "employee_list.php";</script>';
    exit; // Exit to prevent further execution
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payroll System</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <style>
    /* CSS styles for the dashboard */
    body {
      font-family: 'Roboto', sans-serif;
      background-color: #f7fff7; /* Light green background */
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
    }

    .container {
      width: 80%;
      max-width: 1200px; /* Limit container width for better readability */
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }

    .title-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      margin-bottom: 20px;
    }

    .title-container img {
      max-width: 80px;
      height: auto;
      margin-right: 10px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
      background-color: #fff; /* White background for table */
      border-radius: 10px; /* Rounded corners */
      box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    }

    th, td {
      padding: 10px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #7fbf7f; /* Dark green header */
      color: #fff; /* White text */
    }

    /* Style for the form */
    .add-form, .edit-form {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background-color: #fff;
      padding: 20px;
      border-radius: 5px;
      box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
      z-index: 1000;
    }

    .form input {
      width: 100%;
      padding: 8px;
      margin-bottom: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .form button {
      width: 100%;
      padding: 8px;
      border: none;
      border-radius: 5px;
      background-color: #4CAF50; /* Green button */
      color: #fff; /* White text */
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .form button:hover {
      background-color: #45a049; /* Darker green on hover */
    }

    /* Add styles for the action bar */
    .action-bar {
      display: flex;
      justify-content: center;
      align-items: center; /* Center items vertically */
      width: 100%;
      margin-bottom: 10px;
    }

    .action-bar button {
      margin: 0 5px; /* Add some space between buttons */
      padding: 12px 20px;
      border: none;
      border-radius: 5px;
      background-color: #4CAF50; /* Green button */
      color: #fff; /* White text */
      cursor: pointer;
      transition: background-color 0.3s;
      font-size: 16px;
    }

    .action-bar button:hover {
      background-color: #45a049; /* Darker green on hover */
    }

    h1 {
      color: #388e3c; /* Dark green heading */
    }
    /* Company logo and name */
    .company-info-container {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .company-logo {
      max-width: 80px;
      height: auto;
      margin-right: 10px;
    }

    .company-name {
      font-size: 24px;
      font-weight: bold;
      color: #388e3c; /* Dark green color for company name */
    }

    .slogan {
      font-size: 14px;
      color: #388e3c; /* Dark green color for slogan */
    }

    .back-btn {
      background-color: #4CAF50; /* Green button */
      color: #fff; /* White text */
      border: none;
      border-radius: 5px;
      padding: 10px 20px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .back-btn:hover {
      background-color: #45a049; /* Darker green on hover */
    }
  </style>
</head>
<body>

<h1>EMPLOYEE LIST</h1>

<div class="container">
  <div class="company-info-container">
    <img class="company-logo" src="logo.png" alt="Company Logo">
    <div>
      <h1 class="company-name">Factory Workers</h1>
      <p class="slogan">"Because your box matters"</p>
    </div>
  </div>

  <table>
    <thead>
        <tr>
            <th>Employee No</th>
            <th>Name</th>
            <th>Position</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($employees as $row): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['position']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
  

</body>
</html>