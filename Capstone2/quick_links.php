<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Links Widget</title>
    <style>
        .quick-links-container {
            background-color: #f0f0f0;
            padding: 20px;
            border-radius: 8px;
        }
        .widget {
            display: flex;
            flex-direction: column;
            justify-content: center;
            width: 100%;
            
        }
        .widget button {
            margin: 5px 0;
            padding: 10px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 8px;
        }

        .widget button:hover {
            background-color: #d0d0d0;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="quick-links-container">
    <h2>Quick Links</h2>
    <div class="widget">
        <button onclick="location.href='attendance_check.php'">Attendance Check</button>
        <button onclick="location.href='leave_request.php?autoClick=true'">Make a Leave Request</button>
        <button onclick="location.href='task_management.php'">Task Management</button>
        <button onclick="location.href='leave_request.php'">Make a Leave Request</button>
    </div>
    </div>
</body>
</html>