<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hamburger Menu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>

        .hamburger {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }

        .hamburger-button {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #toggleSidebar {
            background-color: #64A651;
            color: #ffffff;
            border: none;
            cursor: pointer;
            font-weight: bold;
            padding-left: 10px;
            padding-right: 10px;
            font-size: 30px;
        }

        #toggleSidebar:hover {
            background-color: #34495e;
        }
        

    </style>
</head>
<body>
    <div class="hamburger">
        <div class="hamburger-button">
            <button id="toggleSidebar" onclick="toggleSidebarvis()">☰</button>
        </div>
    </div>
    <script>
        function toggleSidebarvis() {
            const sidebar = document.querySelector('.big-sidebar');
            sidebar.style.left = sidebar.style.left === '0px' ? '-350px' : '0px';

            // change hamburger icon to close icon
            const toggleSidebar = document.querySelector('#toggleSidebar');
            toggleSidebar.innerHTML = toggleSidebar.innerHTML === '☰' ? '✖' : '☰';

        }
    </script>
</body>
</html>



