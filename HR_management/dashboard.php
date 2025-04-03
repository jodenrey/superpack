<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superpack Enterprise - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
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
            background-color: #c3e6cb; /* Green pastel background */
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
            color: #666;
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

        .menu-item a img {
            width: 24px; /* Adjust the width as needed */
            height: 24px; /* Adjust the height as needed */
            margin-right: 10px;
        }

        .menu-item a i {
            font-size: 18px; /* Adjust the font size of the icon */
        }

        .menu-item a:hover {
            background-color: #d4edda;
            color: #28a745;
        }

        .content {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 40px;
        }

        .title {
            width: 100%;
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
            justify-content: center;
            gap: 20px;
        }

        .option {
            flex: 1 1 300px;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
            background: linear-gradient(135deg, #ffffff 0%, #f5f5f5 100%);
        }

        .option:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.2);
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
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #28a745;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .option a:hover {
            background-color: #218838;
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

        .time {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }

        /* Bar Graph Styling */
        .bar-graph-container {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .bar-graph {
            position: relative;
            cursor: pointer;
            height: 200px;
        }

        .bar {
            position: absolute;
            bottom: 0;
            background-color: #007bff;
            width: 40px;
            transition: height 0.3s, background-color 0.3s;
        }

        .label {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 14px;
            color: #666;
        }

        /* Animation for bar graph */
        @keyframes grow {
            from {
                height: 0;
            }
        }

        .bar-graph .bar {
            animation: grow 1s ease-out;
        }

        /* Custom animation for hover effect */
        .bar-graph .bar:hover {
            animation: none;
            background-color: #28a745;
        }

        /* Adjustments to occupy white space */
        @media (max-width: 768px) {
            .dashboard {
                flex-direction: column;
            }
            .sidebar {
                max-width: 100%;
            }
            .content {
                padding: 20px;
            }
            .bar-graph-container {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>

<div class="dashboard">
    <div class="sidebar" id="sidebar">
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
                <h2>Attendance</h2>
                <p>Track employee attendance and view attendance reports.</p>
                <a href="attendance.php">View Details</a>
            </div>
            <div class="option">
                <h2>Assignments</h2>
                <p>Tracking work sufficient and scheduling.</p>
                <a href="task.php">View Details</a>
            </div>
            <div class="option">
                <h2>Evaluation</h2>
                <p>Conduct employee evaluations and track performance.</p>
                <a href="evaluation.php">View Details</a>
            </div>
        </div>

        <!-- Bar Graphs -->
        <div id="bar-chart" class="bar-graph-container"></div>
        <div id="area-chart" class="bar-graph-container"></div>
    </div>
</div>

<script>
    // Function to display welcome message and current time
    function displayWelcomeMessage() {
        var date = new Date();
        var hour = date.getHours();
        var greeting = "";

        if (hour < 12) {
            greeting = "Good morning";
        } else if (hour >= 12 && hour < 18) {
            greeting = "Good afternoon";
        } else {
            greeting = "Good evening";
        }

        document.getElementById("current-time").innerText = "Current time: " + date.toLocaleTimeString();
    }

    // Function to logout and redirect to login.php
    function logout() {
        // Redirect to login page
        window.location.href = "login.php";
    }

    window.onload = function() {
        displayWelcomeMessage();

        // Initialize ApexCharts
        var barChartOptions = {
            chart: {
                type: 'bar',
                height: 350
            },
            series: [{
                name: 'Sales',
                data: [30, 40, 35, 50, 49, 60, 70, 91, 125]
            }],
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep']
            }
        };

        var areaChartOptions = {
            chart: {
                type: 'area',
                height: 350
            },
            series: [{
                name: 'Sales',
                data: [30, 40, 35, 50, 49, 60, 70, 91, 125]
            }],
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep']
            }
        };

        var barChart = new ApexCharts(document.querySelector('#bar-chart'), barChartOptions);
        barChart.render();

        var areaChart = new ApexCharts(document.querySelector('#area-chart'), areaChartOptions);
        areaChart.render();
    };
</script>

</body>
</html>