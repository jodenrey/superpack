<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password</title>
  <style>
    body {
      background-color: #f5f5f5;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
    }

    .container {
      display: flex;
      min-height: 100vh;
      justify-content: center;
      align-items: center;
    }

    .forgot-panel {
      background-color: #ffffff;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      text-align: center; /* Center-align content */
      max-width: 400px;
      width: 90%;
    }

    .forgot-panel h1 {
      font-size: 32px;
      margin-bottom: 30px;
      color: #28a745; /* Green color */
    }

    .forgot-panel input[type="email"],
    .forgot-panel input[type="password"],
    .forgot-panel input[type="text"] {
      width: 100%;
      padding: 15px;
      margin-bottom: 20px;
      box-sizing: border-box;
      border: 2px solid #28a745; /* Green color */
      background-color: #f5f5f5;
      outline: none;
      transition: border-color 0.3s ease;
    }

    .forgot-panel input[type="email"]:focus,
    .forgot-panel input[type="password"]:focus,
    .forgot-panel input[type="text"]:focus {
      border-color: #32CD32; /* Light green color */
    }

    .forgot-panel input[type="submit"] {
      width: 100%;
      background-color: #28a745; /* Green color */
      color: white;
      padding: 14px;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      font-size: 18px;
      transition: background-color 0.3s ease;
    }

    .forgot-panel input[type="submit"]:hover {
      background-color: #1e7e34; /* Dark green color */
    }

    .forgot-footer {
      margin-top: 20px;
      color: #777;
      font-weight: bold;
    }

    .forgot-footer a {
      color: #28a745; /* Green color */
    }

    .otp-section {
      display: flex;
      justify-content: center;
      margin-top: 20px;
    }

    .otp-button {
      background-color: #007bff; /* Blue color */
      color: white;
      border: none;
      border-radius: 20px; /* Smaller border radius */
      padding: 10px 20px; /* Smaller padding */
      cursor: pointer;
      font-size: 16px; /* Font size */
      margin-left: 10px; /* Added margin */
    }
  </style>
</head>
<body>

<div class="container">
  <div class="forgot-panel">
    <h1>Forgot Password</h1>
    <form action="forgot_password.php" method="post">
      <input name="email" type="email" placeholder="Email" required>
      <input name="verification_code" type="text" placeholder="Verification Code" required>
      <input name="new_password" type="password" placeholder="New Password" required>
      <input name="confirm_new_password" type="password" placeholder="Confirm New Password" required>
      <input type="submit" value="Reset Password">
    </form>
    <div class="forgot-footer">
      <p>Remember your password? <a href="login.php">Login</a></p>
    </div>
    <div class="otp-section">
      <button class="otp-button">Send OTP</button>
    </div>
  </div>
</div>

</body>
</html>