<?php
// Start session
session_start();

// Error handling to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

try {
    // Connect to the database
    $host = "localhost";
    $db_username = "root";
    $db_password = "password"; // Empty password for XAMPP default
    $db_name = "superpack_database";

    $conn = new mysqli($host, $db_username, $db_password, $db_name);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Determine login method
    $login_method = $_POST['login_method'] ?? '';

    if ($login_method === 'qr') {
        // QR Code Login
        $qr_code_data = $_POST['qr_code_data'] ?? '';
        
        if (empty($qr_code_data)) {
            throw new Exception('Invalid QR code data');
        }
        
        // Decrypt and validate QR code data using compatibility function
        $employee_data = checkAndConvertOldFormat($qr_code_data);
        
        if ($employee_data === false) {
            throw new Exception('Invalid or expired QR code');
        }
        
        // Extract employee ID from the decoded data
        $employee_id = $employee_data['employee_id'] ?? '';
        
        // Verify employee exists in database
        $stmt = $conn->prepare("SELECT id, name, role, department, qr_code_data FROM register WHERE employee_id = ?");
        $stmt->bind_param("s", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify the user's identity
            $is_valid = false;
            
            // Check if the stored QR code matches directly
            if ($qr_code_data === $user['qr_code_data']) {
                $is_valid = true;
            } else {
                // If not, try to decode the stored QR code and compare employee IDs
                $stored_data = checkAndConvertOldFormat($user['qr_code_data']);
                if ($stored_data !== false && $stored_data['employee_id'] === $employee_id) {
                    $is_valid = true;
                }
            }
            
            if ($is_valid) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['employee_id'] = $employee_id;
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department'] = $user['department'];
                $_SESSION['loggedin'] = true;
                
                $response = [
                    'success' => true,
                    'message' => 'Login successful',
                    'redirect' => '../../Capstone2/dashboardnew.php'
                ];
            } else {
                throw new Exception('Invalid QR code');
            }
        } else {
            throw new Exception('Employee not found');
        }
        
        $stmt->close();
        
    } elseif ($login_method === 'manual') {
        // Manual Login
        $employee_id = $_POST['employee_id'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($employee_id) || empty($password)) {
            throw new Exception('Employee ID and password are required');
        }
        
        // Check credentials
        $stmt = $conn->prepare("SELECT id, name, role, department, password FROM register WHERE employee_id = ?");
        $stmt->bind_param("s", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['employee_id'] = $employee_id;
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department'] = $user['department'];
                $_SESSION['loggedin'] = true;
                
                $response = [
                    'success' => true,
                    'message' => 'Login successful',
                    'redirect' => '../../Capstone2/dashboardnew.php'
                ];
            } else {
                throw new Exception('Invalid password');
            }
        } else {
            throw new Exception('Employee ID not found');
        }
        
        $stmt->close();
        
    } else {
        throw new Exception('Invalid login method');
    }

    $conn->close();
    
    // Output JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    // Handle any exceptions and return a proper JSON error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;

// Function to decrypt QR code data
function decryptQRData($qr_data) {
    // Split the data and signature
    $parts = explode('.', $qr_data);
    
    if (count($parts) !== 2) {
        return false; // Invalid format
    }
    
    $encoded_data = $parts[0];
    $received_signature = $parts[1];
    
    // Decode the base64 data
    $json_data = base64_decode($encoded_data);
    if ($json_data === false) {
        return false; // Invalid base64 data
    }
    
    // Verify the signature
    $encryption_key = 'SuperPackKey2023';
    $expected_signature = substr(hash_hmac('sha256', $json_data, $encryption_key), 0, 16);
    
    if ($received_signature !== $expected_signature) {
        return false; // Signature verification failed
    }
    
    // Parse the JSON data
    $data = json_decode($json_data, true);
    if (!$data) {
        return false; // Invalid JSON
    }
    
    // Convert back to the expected format
    $full_data = [
        'employee_id' => $data['id'] ?? '',
        'name' => $data['n'] ?? '',
        'role' => $data['r'] ?? '',
        'department' => $data['d'] ?? '',
        'timestamp' => $data['t'] ?? 0
    ];
    
    return $full_data;
}

// Check compatibility with old format
function checkAndConvertOldFormat($qr_code_data) {
    // Try the new format first
    $data = decryptQRData($qr_code_data);
    
    // If it fails, try the old format
    if ($data === false) {
        // Try to decrypt using the old method
        $encryption_key = 'SuperPackEnterpriseSecretKey2023';
        $decrypted_data = openssl_decrypt(
            $qr_code_data,
            'AES-256-CBC',
            $encryption_key,
            0,
            substr(md5($encryption_key), 0, 16)
        );
        
        if ($decrypted_data !== false) {
            // Parse the JSON data
            $old_data = json_decode($decrypted_data, true);
            if ($old_data) {
                return $old_data;
            }
        }
        return false;
    }
    
    return $data;
}
?> 