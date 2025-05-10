<?php
session_start();

// Database connection
$host = "localhost";
$user = "root";
$password = "password";
$database = "superpack_database";
$port = 3306;

// Connect to database
$conn = new mysqli($host, $user, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]));
}

// Check if table exists, if not create it
$sql = "CREATE TABLE IF NOT EXISTS calendar_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    note_id VARCHAR(50) UNIQUE,
    title TEXT NOT NULL,
    date DATE NOT NULL,
    created_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die(json_encode(['success' => false, 'message' => "Table creation failed: " . $conn->error]));
}

// Handle different operations based on request method and action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Return all notes
if ($action === 'get') {
    $sql = "SELECT * FROM calendar_notes";
    $result = $conn->query($sql);
    
    $notes = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $notes[] = [
                'id' => $row['note_id'],
                'title' => $row['title'],
                'start' => $row['date'],
                'allDay' => true,
                'display' => 'block',
                'created_by' => $row['created_by']
            ];
        }
    }
    
    echo json_encode(['success' => true, 'notes' => $notes]);
}
// Add a new note
elseif ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
        echo json_encode(['success' => false, 'message' => "Only administrators can add notes"]);
        exit;
    }

    // Get the data from POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['title']) || !isset($data['date'])) {
        echo json_encode(['success' => false, 'message' => "Missing required fields"]);
        exit;
    }
    
    $noteId = $data['id'];
    $title = $data['title'];
    $date = $data['date'];
    $createdBy = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown Admin';
    
    // Insert the note
    $stmt = $conn->prepare("INSERT INTO calendar_notes (note_id, title, date, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $noteId, $title, $date, $createdBy);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Note added successfully"]);
    } else {
        echo json_encode(['success' => false, 'message' => "Error adding note: " . $stmt->error]);
    }
}
// Edit an existing note
elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
        echo json_encode(['success' => false, 'message' => "Only administrators can edit notes"]);
        exit;
    }

    // Get the data from POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || !isset($data['title']) || !isset($data['date'])) {
        echo json_encode(['success' => false, 'message' => "Missing required fields"]);
        exit;
    }
    
    $noteId = $data['id'];
    $title = $data['title'];
    $date = $data['date'];
    $createdBy = isset($_SESSION['username']) ? $_SESSION['username'] . ' (edited)' : 'Unknown Admin (edited)';
    
    // Update the note
    $stmt = $conn->prepare("UPDATE calendar_notes SET title = ?, date = ?, created_by = ? WHERE note_id = ?");
    $stmt->bind_param("ssss", $title, $date, $createdBy, $noteId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Note updated successfully"]);
    } else {
        echo json_encode(['success' => false, 'message' => "Error updating note: " . $stmt->error]);
    }
}
// Remove a note
elseif ($action === 'remove' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
        echo json_encode(['success' => false, 'message' => "Only administrators can remove notes"]);
        exit;
    }
    
    // Get the data from POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => "Missing note ID"]);
        exit;
    }
    
    $noteId = $data['id'];
    
    // Delete the note
    $stmt = $conn->prepare("DELETE FROM calendar_notes WHERE note_id = ?");
    $stmt->bind_param("s", $noteId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Note removed successfully"]);
    } else {
        echo json_encode(['success' => false, 'message' => "Error removing note: " . $stmt->error]);
    }
}
else {
    echo json_encode(['success' => false, 'message' => "Invalid action"]);
}

$conn->close();
?> 