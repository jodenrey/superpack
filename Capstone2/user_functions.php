<?php
/**
 * User-related helper functions
 */

/**
 * Gets the current username from session with fallbacks
 *
 * @return string The current username or 'Guest' if not found
 */
function getCurrentUsername() {
    return isset($_SESSION['name']) ? $_SESSION['name'] : 
           (isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest');
}

/**
 * Gets a user's profile picture path
 *
 * @param string $username The username to get a profile picture for
 * @param mysqli $conn Database connection
 * @return string Path to the profile picture
 */
function getUserProfilePicture($username, $conn) {
    $uploadDir = 'uploads/profile_pictures/';
    $defaultImage = $uploadDir . 'default.png';
    
    // If no connection or no username, return default
    if (!$conn || empty($username) || $username === 'Guest') {
        return $defaultImage;
    }
    
    try {
        // Try to get from employee_records
        $stmt = $conn->prepare("SELECT photo FROM employee_records WHERE name = ?");
        if (!$stmt) {
            return $defaultImage;
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row['photo'])) {
                $imagePath = $uploadDir . $row['photo'];
                
                // Check if file exists
                if (file_exists($imagePath)) {
                    return $imagePath;
                }
            }
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Log error and continue
        error_log("Error getting profile picture: " . $e->getMessage());
    }
    
    return $defaultImage;
}

/**
 * Update a user's profile picture
 *
 * @param string $username The username to update
 * @param array $fileData The $_FILES array for the uploaded file
 * @param mysqli $conn Database connection
 * @return array ['success' => bool, 'message' => string, 'filename' => string]
 */
function updateUserProfilePicture($username, $fileData, $conn) {
    $result = [
        'success' => false,
        'message' => '',
        'filename' => ''
    ];
    
    if (empty($username) || empty($fileData) || !$conn) {
        $result['message'] = 'Missing required data';
        return $result;
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = 'uploads/profile_pictures/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    if ($fileData['error'] !== UPLOAD_ERR_OK) {
        $result['message'] = 'File upload error: ' . $fileData['error'];
        return $result;
    }
    
    $fileInfo = pathinfo($fileData['name']);
    $extension = strtolower($fileInfo['extension']);
    
    // Only allow image files
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($extension, $allowedExtensions)) {
        $result['message'] = 'Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.';
        return $result;
    }
    
    // Generate unique filename
    $newFileName = $username . '_' . time() . '.' . $extension;
    $targetFile = $uploadDir . $newFileName;
    
    // Try to upload the file
    if (!move_uploaded_file($fileData['tmp_name'], $targetFile)) {
        $result['message'] = 'Failed to move uploaded file';
        return $result;
    }
    
    // Update the database
    $stmt = $conn->prepare("UPDATE employee_records SET photo = ? WHERE name = ?");
    if (!$stmt) {
        $result['message'] = 'Error preparing database statement: ' . $conn->error;
        return $result;
    }
    
    $stmt->bind_param("ss", $newFileName, $username);
    
    if (!$stmt->execute()) {
        $result['message'] = 'Error updating profile picture in database: ' . $stmt->error;
        return $result;
    }
    
    $result['success'] = true;
    $result['message'] = 'Profile picture updated successfully!';
    $result['filename'] = $newFileName;
    
    return $result;
} 