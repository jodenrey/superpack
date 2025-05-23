<?php
/**
 * Helper functions for notification system
 */

/**
 * Create a notification in the database
 */
function createNotification($conn, $user_id, $user_role, $type, $message, $link = '') {
    // Create notifications table if it doesn't exist
    $createNotificationsTable = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50),
        user_role VARCHAR(50),
        type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255) NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($createNotificationsTable);
    
    // Insert notification
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_role, type, message, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $user_id, $user_role, $type, $message, $link);
    return $stmt->execute();
}

/**
 * Create notification for admin when employee submits leave request
 */
function notifyAdminLeaveRequest($conn, $employee_name, $leave_type, $start_date, $end_date) {
    $message = "New leave request from {$employee_name} for {$leave_type} from {$start_date} to {$end_date}";
    $link = "attendance_check.php";
    return createNotification($conn, null, 'Admin', 'leave_request', $message, $link);
}

/**
 * Create notification for employee when admin approves/denies leave request
 */
function notifyEmployeeLeaveStatus($conn, $employee_id, $status, $leave_type, $start_date, $end_date) {
    $status_text = ($status === 'Approved') ? 'approved' : 'denied';
    $message = "Your {$leave_type} request from {$start_date} to {$end_date} has been {$status_text}";
    $link = "attendance_check.php";
    
    // Don't set user_role to null - leave it empty so it's employee-specific only
    return createNotification($conn, $employee_id, '', 'leave_status', $message, $link);
}

/**
 * Get user ID from username for notifications
 */
function getUserIdFromUsername($conn, $username) {
    // First, try to find by exact username match in users table
    $stmt = $conn->prepare("SELECT employee_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['employee_id'];
    }
    
    // Try to find by exact match in register table using CONCAT of names
    $stmt = $conn->prepare("SELECT employee_id FROM register WHERE CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) = ? OR CONCAT(first_name, ' ', last_name) = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['employee_id'];
    }
    
    // Try partial matches in register table
    $stmt = $conn->prepare("SELECT employee_id FROM register WHERE first_name = ? OR last_name = ? OR CONCAT(first_name, ' ', last_name) LIKE ?");
    $likeName = '%' . $username . '%';
    $stmt->bind_param("sss", $username, $username, $likeName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['employee_id'];
    }
    
    // If still not found, return the username itself
    return $username;
}

/**
 * Get user role from username
 */
function getUserRoleFromUsername($conn, $username) {
    // Try users table first
    $stmt = $conn->prepare("SELECT role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['role'];
    }
    
    // Try register table
    $stmt = $conn->prepare("SELECT role FROM register WHERE CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) = ? OR CONCAT(first_name, ' ', last_name) = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['role'];
    }
    
    // Default role for employees
    return 'Employee';
}
?> 