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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        header {
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        h1 {
            font-size: 2.5rem;
            margin: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 20px;
        }
        
        .job-positions {
            margin-bottom: 3rem;
        }
        
        .positions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 2rem;
        }
        
        .position-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            transition: transform 0.3s ease;
        }
        
        .position-card:hover {
            transform: translateY(-5px);
        }
        
        .position-card h3 {
            margin-top: 0;
            color: #3a7bd5;
        }
        
        .position-card p.department {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .position-card p.description {
            margin-bottom: 1.5rem;
        }
        
        .application-form {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .form-row {
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 1rem;
        }
        
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .submit-button {
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-button:hover {
            background: linear-gradient(135deg, #3272c5, #00b8e0);
            transform: translateY(-2px);
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .home-link {
            display: inline-block;
            margin-top: 1rem;
            color: #3a7bd5;
            text-decoration: none;
            font-weight: 500;
        }
        
        .home-link:hover {
            text-decoration: underline;
        }
        
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        @media (max-width: 768px) {
            .two-columns {
                grid-template-columns: 1fr;
            }
            
            .positions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-box-open"></i> SuperPack Enterprise
            </div>
            <h1>Career Opportunities</h1>
            <p>Join our team and grow with us</p>
        </div>
    </header>

    <div class="container">
        <?php if ($applicationSubmitted): ?>
            <div class="success-message">
                <h2>Application Submitted Successfully!</h2>
                <p>Thank you for your interest in joining SuperPack Enterprise. We have received your application and will review it shortly.</p>
                <p>Our HR team will contact you if your qualifications match our requirements.</p>
                <a href="welcome.php" class="home-link"><i class="fas fa-arrow-left"></i> Return to Home Page</a>
            </div>
        <?php else: ?>
            <?php if (!empty($errorMessage)): ?>
                <div class="error-message">
                    <p><?php echo $errorMessage; ?></p>
                </div>
            <?php endif; ?>

            <section class="job-positions">
                <h2>Available Positions</h2>
                <div class="positions-grid">
                    <?php foreach ($positions as $position): ?>
                        <div class="position-card">
                            <h3><?php echo htmlspecialchars($position['title']); ?></h3>
                            <p class="department"><i class="fas fa-building"></i> <?php echo htmlspecialchars($position['department']); ?></p>
                            <p class="description">
                                <?php
                                    $positionDetails = $conn->query("SELECT description FROM job_positions WHERE id = " . $position['id'])->fetch_assoc();
                                    echo nl2br(htmlspecialchars($positionDetails['description']));
                                ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section id="apply">
                <h2>Apply Now</h2>
                <div class="application-form">
                    <form action="job_application.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="position_id">Position Applying For</label>
                            <select id="position_id" name="position_id" required>
                                <option value="">Select a position</option>
                                <?php foreach ($positions as $position): ?>
                                    <option value="<?php echo $position['id']; ?>">
                                        <?php echo htmlspecialchars($position['title'] . ' (' . $position['department'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row two-columns">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" pattern="^[A-Za-z\s]+$" title="Please enter only letters (no numbers or symbols)" oninput="this.value=this.value.replace(/[^A-Za-z\s]/g,'')" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" pattern="^[A-Za-z\s]+$" title="Please enter only letters (no numbers or symbols)" oninput="this.value=this.value.replace(/[^A-Za-z\s]/g,'')" required>
                            </div>
                        </div>

                        <div class="form-row two-columns">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" pattern="[0-9]{1,11}" maxlength="11" inputmode="numeric" title="Please enter numbers only (maximum 11 digits)" oninput="this.value=this.value.replace(/[^0-9]/g,'')" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" required>
                                <option value="">Select gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="education">Educational Background</label>
                            <textarea id="education" name="education" rows="4" required placeholder="Please list your educational qualifications, including degrees, institutions, and graduation years."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="experience">Work Experience</label>
                            <textarea id="experience" name="experience" rows="4" placeholder="Please describe your relevant work experience including company names, positions, and duration."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="resume">Upload Resume (PDF, DOC, or DOCX)</label>
                            <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx">
                        </div>

                        <div class="form-group" style="margin-top: 2rem;">
                            <button type="submit" name="submit_application" class="submit-button">
                                <i class="fas fa-paper-plane"></i> Submit Application
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2025 SuperPack Enterprise. All rights reserved.</p>
        <a href="welcome.php" class="home-link"><i class="fas fa-arrow-left"></i> Return to Home Page</a>
    </footer>
</body>
</html> 