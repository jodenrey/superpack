<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superpack Enterprise - Employee Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Reset CSS */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #c3e6cb; /* Green background */
            color: #333;
            margin: 0;
        }

        .dashboard {
            display: flex;
            align-items: flex-start;
        }

        .sidebar {
            width: 250px;
            max-width: 250px;
            background-color: #fff;
            padding: 30px;
            padding-bottom: 100px; /* Extend the menu downwards */
            border-right: 1px solid #ddd;
        }
        .content {
            flex: 1;
            padding: 0 20px;
        }

        .company-logo-container {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .company-logo {
            max-width: 80px;
            height: auto;
            margin-right: 10px;
        }

        .company-info {
            color: #000;
        }

        .company-info h3 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
        }

        .company-info p {
            margin: 5px 0;
            font-size: 14px;
        }

        .menu {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .menu-item {
            margin-bottom: 15px;
        }

        .menu-item a {
            text-decoration: none;
            color: #333;
            font-size: 18px;
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .menu-item a i {
            margin-right: 10px;
        }
        .menu-item a img {
            width: 24px; /* Adjust the width as needed */
            height: 24px; /* Adjust the height as needed */
            margin-right: 10px;
        }

        .menu-item a:hover {
            background-color: #d4edda;
            color: #28a745;
        }

        .content {
            flex: 1;
            padding: 40px;
        }

        .title {
            margin-bottom: 40px;
            text-align: center;
        }

        .title h1 {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }

        .options {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
        }

        .option {
            flex: 1 1 300px;
            margin: 10px;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .option:hover {
            transform: translateY(-5px);
        }

        .option h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }

        .option p {
            font-size: 16px;
            line-height: 1.6;
            color: #666;
        }

        .option a {
            display: block;
            margin-top: 20px;
            color: #333;
            font-weight: bold;
            text-decoration: none;
            transition: color 0.3s;
        }

        .option a:hover {
            color: #28a745;
        }

        .logout-btn {
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 20px;
            padding: 15px 30px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 20px;
        }
        .logout-btn i {
            font-size: 20px; /* Adjust the font size of the icon */
            margin-right: 10px; /* Adjust the spacing between icon and text */
        }

        .logout-btn:hover {
            background-color: #218838;
        }   
    </style>
</head>
<body>

<div class="dashboard">
    <div class="sidebar">
        <div class="company-logo-container">
            <img src="logo.png" alt="Company Logo" class="company-logo">
            <div class="company-info">
                <h3>Superpack Enterprise</h3>
                <p>Because your box matters</p>
            </div>
        </div>
        <ul class="menu">
            <li class="menu-item dashboard-link"><a href="dashboard.php"><img src="dashboard icon.png" alt="Dashboard"> Dashboard</a></li>
            <li class="menu-item employee-link"><a href="employee_dashboard.php"><img src="employee icon.png" alt="Employee"> Employee</a></li>
            <li class="menu-item payroll-link"><a href="Payroll_system.php"><img src="payroll icon.png" alt="Payroll"> Payroll</a></li>
            <li class="menu-item task-management-link"><a href="task.php"><img src="task icon.png" alt="Task Management"> Task Management</a></li>
            <li class="menu-item staff-notice-link"><a href="staff_notice.php"><img src="staff notice icon.png" alt="Staff Notice"> Staff Notice</a></li>
            <li class="menu-item employment-link"><a href="recruitment.php"><img src="recruitment icon.png" alt="Recruitment"> Recruitment</a></li>
            <li class="menu-item personnel-records-link"><a href="employee_records.php"><img src="personnel records icon.png" alt="Personnel Records"> Personnel Records</a></li>
            <button class="logout-btn" onclick="logout()">
            <img src="logout icon.png" alt="Logout" style="max-width: 20px; margin-right: 10px;"> Logout
            </button>
            </li>
        </ul>
    </div>

    <div class="content">
        <div class="title">
            <h1>Welcome, Admin! Dashboard</h1>
            <p class="time" id="current-time"></p>
        </div>

        <div class="options">
      <div class="option">
        <h2>Checking Attendance</h2>
        <p>Track employee attendance and view attendance reports.</p>
        <a href="attendance.php">View Details</a>
      </div>
      <div class="option">
        <h2>Evaluation</h2>
        <p>Conduct employee evaluations and track performance.</p>
        <a href="evaluation.php">View Details</a>
      </div>
      <div class="option">
        <h2>Employee Training</h2>
        <p>Manage employee training programs.</p>
        <a href="training.php">View Details</a>
      </div>
      <div class="option">
        <h2>Leave Request</h2>
        <p>Managing the request of leaves of employees or workers.</p>
        <a href="leave_request.php">View Details</a>
      </div>
        </div>
    </div>
</div>
<script>// Function to logout and redirect to login.php
    function logout() {
        // Redirect to login page
        window.location.href = "login.php";
    }</script>
</body>
</html>