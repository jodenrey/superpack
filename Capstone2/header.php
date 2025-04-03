<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<style>
    .header {
        position: fixed;
        width: 100%;
        height: 50px;
    }

    .header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: 100%;
        padding:50px;
    }

    .logo_details {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        z-index: -1;
    }

    .logo_image {
        height: 70px;
        width: 70px;
        border-radius: 50%;
        margin-right: 8px;
    }
    
    .logo_name {
        font-size: 40px;
        font-weight: bold;
        color: #FFFAFA;
        font-family: 'Roboto', sans-serif;
    }
</style>
<body>
    <div class="header">
        <div class="header-bar">
            <div class="logo_details">
                <img src="Superpack-Enterprise-Logo.png" alt="Superpack Enterprise Logo" class="logo_image">
                <span class="logo_name">Superpack</span>
            </div>
            
        </div>
    </div>
</body>
</html>