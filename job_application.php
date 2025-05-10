<?php
session_start();

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

// Create job_positions table if it doesn't exist
$createPositionsTable = "CREATE TABLE IF NOT EXISTS job_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    description TEXT,
    requirements TEXT,
    status ENUM('Open', 'Closed') NOT NULL DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$conn->query($createPositionsTable);

// Insert sample job positions if none exist
$checkPositionsQuery = "SELECT COUNT(*) as count FROM job_positions";
$result = $conn->query($checkPositionsQuery);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Add sample job positions
    $samplePositions = [
        ['Production Operator', 'Production', 'Responsible for operating machinery and equipment in the production line.', 'High School Diploma, 1-2 years of experience in manufacturing', 'Open'],
        ['Quality Control Inspector', 'Quality Assurance', 'Inspect products for quality and compliance with standards.', 'Technical certification, attention to detail, 2+ years QA experience', 'Open'],
        ['Warehouse Associate', 'Logistics', 'Handle warehouse operations including receiving, storing and shipping products.', 'Physical stamina, ability to lift 50 lbs, forklift certification preferred', 'Open'],
        ['Administrative Assistant', 'Administration', 'Provide administrative support to office operations.', 'Associates degree, proficiency in MS Office, excellent communication skills', 'Open'],
        ['HR Specialist', 'Human Resources', 'Manage recruitment, employee relations, and HR administration.', 'Bachelors degree in HR or related field, 3+ years HR experience', 'Open']
    ];
    
    $stmt = $conn->prepare("INSERT INTO job_positions (title, department, description, requirements, status) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($samplePositions as $position) {
        $stmt->bind_param("sssss", $position[0], $position[1], $position[2], $position[3], $position[4]);
        $stmt->execute();
    }
}

// Create job_applications table if it doesn't exist
$createApplicationsTable = "CREATE TABLE IF NOT EXISTS job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position_id INT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    education TEXT NOT NULL,
    experience TEXT,
    resume_path VARCHAR(255),
    status ENUM('New', 'Screening', 'Interview', 'Offer', 'Hired', 'Rejected') NOT NULL DEFAULT 'New',
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (position_id) REFERENCES job_positions(id)
)";

$conn->query($createApplicationsTable);

// Fetch active job positions for the dropdown
$positionsQuery = "SELECT id, title, department FROM job_positions WHERE status = 'Open' ORDER BY title";
$positionsResult = $conn->query($positionsQuery);
$positions = [];

if ($positionsResult) {
    while ($row = $positionsResult->fetch_assoc()) {
        $positions[] = $row;
    }
}

// Process application submission
$applicationSubmitted = false;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    $resumePath = '';
    
    // Handle resume upload
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
        $uploadDir = 'uploads/resumes/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['resume']['name']);
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetFile)) {
            $resumePath = $targetFile;
        } else {
            $errorMessage = "Error uploading resume file.";
        }
    }
    
    if (empty($errorMessage)) {
        $stmt = $conn->prepare("INSERT INTO job_applications (position_id, first_name, last_name, email, phone, address, gender, education, experience, resume_path) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                              
        $stmt->bind_param("isssssssss", 
            $_POST['position_id'],
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'],
            $_POST['gender'],
            $_POST['education'],
            $_POST['experience'],
            $resumePath
        );
        
        if ($stmt->execute()) {
            $applicationSubmitted = true;
            
            // Create notification for admin
            $applicationId = $conn->insert_id;
            $positionName = '';
            
            // Get position name for the notification
            $posQuery = $conn->prepare("SELECT title FROM job_positions WHERE id = ?");
            $posQuery->bind_param("i", $_POST['position_id']);
            $posQuery->execute();
            $posResult = $posQuery->get_result();
            if ($posResult->num_rows > 0) {
                $posData = $posResult->fetch_assoc();
                $positionName = $posData['title'];
            }
            
            // Create notifications table if it doesn't exist
            $createNotificationsTable = "CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                link VARCHAR(255) NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->query($createNotificationsTable);
            
            // Add notification
            $notifyStmt = $conn->prepare("INSERT INTO notifications (type, message, link) VALUES (?, ?, ?)");
            $type = "job_application";
            $message = "New job application from " . $_POST['first_name'] . " " . $_POST['last_name'] . " for " . $positionName . " position";
            $link = "Capstone2/selection.php";
            $notifyStmt->bind_param("sss", $type, $message, $link);
            $notifyStmt->execute();
        } else {
            $errorMessage = "Error submitting application: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Opportunities - SuperPack Enterprise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --accent: #4cc9f0;
            --success: #2ec4b6;
            --warning: #ff9f1c;
            --danger: #e71d36;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --white: #ffffff;
            --transition: all 0.3s ease;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-md: 0 6px 12px rgba(67, 97, 238, 0.15);
            --shadow-lg: 0 15px 25px rgba(67, 97, 238, 0.2);
            --radius-sm: 4px;
            --radius: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --header-height: 70px;
        }
        
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--gray-100);
            color: var(--gray-800);
            line-height: 1.6;
        }
        
        /* Layout */
        .app-container {
            min-height: 100vh;
            display: grid;
            grid-template-rows: auto 1fr auto;
        }
        
        /* Header styles */
        .careers-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 3rem 0 5rem;
            position: relative;
            overflow: hidden;
        }
        
        .careers-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('data:image/svg+xml;utf8,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="25" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: 150px 150px;
            opacity: 0.5;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
            text-align: center;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .page-subtitle {
            font-size: 1.25rem;
            font-weight: 400;
            max-width: 800px;
            margin: 0 auto;
            opacity: 0.9;
        }
        
        /* Main content */
        .main-content {
            max-width: 1200px;
            margin: -3rem auto 3rem;
            padding: 0 2rem;
            position: relative;
            z-index: 2;
        }
        
        /* Job positions section */
        .job-positions {
            margin-bottom: 4rem;
        }
        
        .section-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--gray-800);
        }
        
        .positions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .position-card {
            background-color: var(--white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }
        
        .position-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .position-card h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: var(--primary);
            font-size: 1.25rem;
        }
        
        .position-card .department {
            display: inline-block;
            background-color: var(--gray-100);
            color: var(--gray-700);
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius);
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        
        .position-card .description {
            margin-bottom: 1.25rem;
            color: var(--gray-700);
        }
        
        .position-card .requirements {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-200);
            font-size: 0.9rem;
            color: var(--gray-600);
        }
        
        .position-card .requirements-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }
        
        /* Application form section */
        .application-form-container {
            background-color: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 2rem;
            margin-bottom: 3rem;
        }
        
        .form-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-800);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .form-full-width {
            grid-column: 1 / -1;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            font-family: inherit;
            font-size: 1rem;
            color: var(--gray-800);
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }
        
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            font-family: inherit;
            font-size: 1rem;
            color: var(--gray-800);
            transition: var(--transition);
            background-color: var(--white);
            cursor: pointer;
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .file-input-container {
            position: relative;
        }
        
        .file-input-label {
            display: block;
            padding: 0.75rem 1rem;
            background-color: var(--gray-100);
            border: 1px dashed var(--gray-400);
            border-radius: var(--radius);
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .file-input-label:hover {
            background-color: var(--gray-200);
        }
        
        .file-input-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        
        .file-input {
            position: absolute;
            width: 0;
            height: 0;
            opacity: 0;
        }
        
        .form-radio-group {
            display: flex;
            gap: 1.5rem;
        }
        
        .form-radio-item {
            display: flex;
            align-items: center;
        }
        
        .form-radio {
            margin-right: 0.5rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.125rem;
        }
        
        .btn-secondary {
            background-color: var(--gray-200);
            color: var(--gray-800);
        }
        
        .btn-secondary:hover {
            background-color: var(--gray-300);
        }
        
        .form-actions {
            margin-top: 2rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        
        /* Success message */
        .success-message {
            background-color: rgba(46, 196, 182, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
            padding: 1.5rem;
            border-radius: var(--radius);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
        }
        
        .success-icon {
            font-size: 2rem;
            margin-right: 1rem;
        }
        
        /* Error message */
        .error-message {
            background-color: rgba(231, 29, 54, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
        }
        
        /* Footer */
        .page-footer {
            background-color: var(--gray-800);
            color: var(--gray-300);
            padding: 3rem 0;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            text-align: center;
        }
        
        .footer-logo {
            margin-bottom: 1.5rem;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .footer-link {
            color: var(--gray-300);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .footer-link:hover {
            color: var(--white);
        }
        
        .footer-copyright {
            font-size: 0.875rem;
            color: var(--gray-500);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .positions-grid {
                grid-template-columns: 1fr;
            }
            
            .careers-header {
                padding: 2rem 0 4rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .main-content {
                margin-top: -2rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="careers-header">
            <div class="header-content">
                <h1 class="page-title">Career Opportunities</h1>
                <p class="page-subtitle">Join our team and be part of a company that values innovation, growth, and excellence. Explore our open positions and find the perfect fit for your career.</p>
            </div>
        </div>
        
        <div class="main-content">
            <?php if ($applicationSubmitted): ?>
                <div class="success-message">
                    <div class="success-icon"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <h3>Application Submitted Successfully!</h3>
                        <p>Thank you for your interest in joining SuperPack Enterprise. We have received your application and will review it shortly. You will be contacted if your qualifications match our requirements.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="job-positions">
                <h2 class="section-title">Open Positions</h2>
                
                <div class="positions-grid">
                    <?php foreach ($positions as $position): 
                        // Fetch full position details
                        $detailsQuery = $conn->prepare("SELECT * FROM job_positions WHERE id = ?");
                        $detailsQuery->bind_param("i", $position['id']);
                        $detailsQuery->execute();
                        $result = $detailsQuery->get_result();
                        $posDetails = $result->fetch_assoc();
                    ?>
                        <div class="position-card">
                            <h3><?php echo htmlspecialchars($position['title']); ?></h3>
                            <p class="department"><?php echo htmlspecialchars($position['department']); ?></p>
                            <p class="description"><?php echo htmlspecialchars($posDetails['description']); ?></p>
                            <div class="requirements">
                                <p class="requirements-title">Requirements:</p>
                                <p><?php echo htmlspecialchars($posDetails['requirements']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="application-form-container">
                <h2 class="form-title">Apply Now</h2>
                
                <?php if (!empty($errorMessage)): ?>
                    <div class="error-message">
                        <p><?php echo htmlspecialchars($errorMessage); ?></p>
                    </div>
                <?php endif; ?>
                
                <form action="job_application.php" method="post" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="position_id" class="form-label">Position</label>
                            <select name="position_id" id="position_id" class="form-select" required>
                                <option value="">Select a position</option>
                                <?php foreach ($positions as $position): ?>
                                    <option value="<?php echo $position['id']; ?>"><?php echo htmlspecialchars($position['title']) . ' (' . htmlspecialchars($position['department']) . ')'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="gender" class="form-label">Gender</label>
                            <div class="form-radio-group">
                                <div class="form-radio-item">
                                    <input type="radio" name="gender" id="gender_male" value="Male" class="form-radio" required>
                                    <label for="gender_male">Male</label>
                                </div>
                                <div class="form-radio-item">
                                    <input type="radio" name="gender" id="gender_female" value="Female" class="form-radio">
                                    <label for="gender_female">Female</label>
                                </div>
                                <div class="form-radio-item">
                                    <input type="radio" name="gender" id="gender_other" value="Other" class="form-radio">
                                    <label for="gender_other">Other</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" name="phone" id="phone" class="form-control" required>
                        </div>
                        
                        <div class="form-group form-full-width">
                            <label for="address" class="form-label">Address</label>
                            <textarea name="address" id="address" class="form-control" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="education" class="form-label">Education</label>
                            <textarea name="education" id="education" class="form-control form-textarea" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="experience" class="form-label">Work Experience</label>
                            <textarea name="experience" id="experience" class="form-control form-textarea" required></textarea>
                        </div>
                        
                        <div class="form-group form-full-width">
                            <label for="resume" class="form-label">Resume/CV</label>
                            <div class="file-input-container">
                                <label class="file-input-label">
                                    <span class="file-input-icon"><i class="fas fa-file-upload"></i></span>
                                    <span id="file-name">Click or drag to upload your resume (PDF, DOC, DOCX)</span>
                                    <input type="file" name="resume" id="resume" class="file-input" accept=".pdf,.doc,.docx">
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="welcome.php" class="btn btn-secondary">Back to Home</a>
                        <button type="submit" name="submit_application" class="btn btn-lg">Submit Application</button>
                    </div>
                </form>
            </div>
        </div>
        
        <footer class="page-footer">
            <div class="footer-content">
                <div class="footer-logo">
                    <i class="fas fa-box-open fa-2x"></i>
                    <h3>SuperPack Enterprise</h3>
                </div>
                
                <div class="footer-links">
                    <a href="#" class="footer-link">Home</a>
                    <a href="#" class="footer-link">About Us</a>
                    <a href="#" class="footer-link">Services</a>
                    <a href="#" class="footer-link">Contact</a>
                    <a href="#" class="footer-link">Privacy Policy</a>
                </div>
                
                <div class="footer-copyright">
                    <p>&copy; 2025 SuperPack Enterprise. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
    
    <script>
        // Display selected file name
        document.getElementById('resume').addEventListener('change', function() {
            var fileName = this.files[0] ? this.files[0].name : 'Click or drag to upload your resume (PDF, DOC, DOCX)';
            document.getElementById('file-name').textContent = fileName;
        });
    </script>
</body>
</html> 