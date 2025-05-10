<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../welcome.php');
    exit();
}

// Include user functions
require_once('user_functions.php');

$username = getCurrentUsername();
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

$host = "localhost";
$user = "root";
$password = "password";
$database = "superpack_database";
$port = 3306;

// mysqli connection
$conn = new mysqli($host, $user, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create uploads directory if it doesn't exist
$uploadDir = 'uploads/profile_pictures/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Get user profile picture
$userImage = getUserProfilePicture($username, $conn);

// Get user info
$stmt = $conn->prepare("SELECT * FROM employee_records WHERE name = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userData = $result->fetch_assoc();
} else {
    // If user not found, create a default user data array
    $userData = ['username' => $username, 'photo' => null];
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if profile picture is being updated
    if (isset($_FILES['profile_picture'])) {
        $result = updateUserProfilePicture($username, $_FILES['profile_picture'], $conn);
        
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
        if ($result['success']) {
            // Update user data
            $userData['photo'] = $result['filename'];
            
            // Force page refresh to clear browser cache
            echo '<script>
                window.location.href = window.location.href + "?updated=" + new Date().getTime();
            </script>';
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
    <link rel="stylesheet" href="style_index.css">
    <link rel="icon" type="image/x-icon" href="Superpack-Enterprise-Logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboardnew.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 30px;
            border: 5px solid #f0f0f0;
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-actions {
            margin-top: 20px;
        }
        
        .alert {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_small.php'?>
    <div class="container-everything" style="height:100%;">
        <div class="container-all">
            <div class="container-top">
                <?php include 'header_2.php';?>
            </div>
            
            <div class="container-bottom" style="padding: 20px;">
                <div class="profile-container">
                    <h2 class="mb-4">My Profile</h2>
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-header">
                        <img src="<?php echo !empty($userData['photo']) ? $uploadDir . $userData['photo'] . '?v=' . time() : 'uploads/profile_pictures/default.png'; ?>" 
                             alt="Profile Picture" class="profile-picture" id="preview-image">
                        
                        <div class="profile-info">
                            <h3><?php echo htmlspecialchars($username); ?></h3>
                            <p>Role: <?php echo htmlspecialchars($role); ?></p>
                            
                            <div class="profile-actions">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#updatePictureModal">
                                    Update Profile Picture
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-details">
                        <h4>Account Information</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                                <p><strong>Role:</strong> <?php echo htmlspecialchars($role); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Profile Picture Modal -->
    <div class="modal fade" id="updatePictureModal" tabindex="-1" role="dialog" aria-labelledby="updatePictureModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updatePictureModalLabel">Update Profile Picture</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="profile_picture">Select new profile picture:</label>
                            <input type="file" class="form-control-file" id="profile_picture" name="profile_picture" required onchange="previewImage(this)">
                            <small class="form-text text-muted">Allowed formats: JPG, JPEG, PNG, GIF</small>
                        </div>
                        
                        <div class="mt-3">
                            <h6>Preview:</h6>
                            <img id="image-preview" src="#" alt="Preview" style="max-width: 100%; max-height: 200px; display: none;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Function to preview the selected image
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html> 