<?php session_start();?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<style>
    /* Reset margins and paddings */
    html, body {
        margin: 0;
        padding: 0;
        height: 100%;
        width: 100%;
    }
    .login {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 90vh;
    }
    .login-btn {
        padding: 20px 40px;
        cursor: pointer;
        background-color: #FCA61D;
        border: 5px solid #131313;
        border-radius: 10px;
        margin-right: 20px;
        height: 250px;
        width: 250px;
    }
    .login-btn:hover {
        background-color: #FFCC66;
    }
    .register-btn {
        padding: 20px 40px;
        cursor: pointer;
        background-color: #2589BD;
        border: 5px solid #131313;
        border-radius: 10px;
        margin-left: 20px;
        height: 250px;
        width: 250px;
    
    }
    .register-btn:hover {
        background-color: #66CCFF;
    }
    .or-divider {
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 20px;
        font-weight: bold;
        font-size: 64px;
        font-family: 'Roboto', sans-serif;
    }
    .login-text, .register-text {
        margin-top: 40px;
        font-size: 40px;
        font-weight: bold;
        font-family: 'Roboto', sans-serif;
    }

    i {
        font-size: 70px;
    }
    .container-all {
        height: 100vh;
        width: 100vw;
        background: #6f9947 ;
        display: flex; /* Add flexbox to center content */
        justify-content: center; /* Center horizontally */
        align-items: center; /* Center vertically */
    }
</style>
<body>
    <?php include 'Capstone2/header.php'?>
    <div class="container-all">
    <div class="login">
        <button class="login-btn">
            <i class="fas fa-sign-in-alt"></i>
            <div class="login-text">Login</div>
        </button>
        
        <div class="or-divider">
            <span>OR</span>
        </div>

        <button class="register-btn">
            <i class="fas fa-user-plus"></i>
            <div class="register-text">Register</div>
        </button>
    </div>
    </div>
    <script>
        document.querySelector('.login-btn').addEventListener('click', function() {
            window.location.href = 'Face_API/Python/login.php';
        });
        document.querySelector('.register-btn').addEventListener('click', function() {
            window.location.href = 'Face_API/Python/register.php';
        });
    </script>
</body>
</html>