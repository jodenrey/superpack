<?php 
session_start(); // Starting session 

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST" ) {

    // Retrieve data from POST
    $name = $_POST["name"];
    $position = $_POST["position"];
    $address = $_POST["address"];
    $phone_number = $_POST["phone_number"];
    $age = $_POST["age"];
    $email = $_POST["email"];
    $username = isset($_POST["username"]) ? $_POST["username"] : $name; // Use name as default username
    $password = isset($_POST["password"]) ? $_POST["password"] : '';
    $createAccount = isset($_POST["create_account"]) ? true : false;

    // File upload handling
    $file_name = isset($_FILES['image']['name']) ? $_FILES['image']['name'] : ''; // Set default value if no image is uploaded
    $tempname = isset($_FILES['image']['tmp_name']) ? $_FILES['image']['tmp_name'] : '';
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../Capstone2/uploads/profile_pictures/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Process file upload if a file was selected
    if (!empty($file_name) && !empty($tempname)) {
        $fileInfo = pathinfo($file_name);
        $extension = strtolower($fileInfo['extension']);
        
        // Only allow image files
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($extension, $allowedExtensions)) {
            // Generate unique filename
            $newFileName = strtolower(str_replace(' ', '_', $name)) . '_' . time() . '.' . $extension;
            $targetFile = $uploadDir . $newFileName;
            
            // Move the uploaded file
            if (move_uploaded_file($tempname, $targetFile)) {
                $file_name = $newFileName; // Update filename to the new name
            } else {
                throw new Exception("Error uploading image file.");
            }
        } else {
            throw new Exception("Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.");
        }
    } else {
        $file_name = 'default.png'; // Set default image if none provided
    }

    // Include the database connection file 
    require_once("database.php");

    try {
        // Start a transaction to ensure data consistency
        $pdo->beginTransaction();
        
        // Define SQL query to insert data into 'employee_records' table
        $query = "INSERT INTO employee_records (name, position, address, phone_number, age, email, photo, shift, salary, status, start_date) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, '1', '0', 'Active', NOW())";
        
        // Prepare SQL statement
        $stmt = $pdo->prepare($query);

        // Bind parameters
        $stmt->bindParam(1, $name);
        $stmt->bindParam(2, $position);
        $stmt->bindParam(3, $address);
        $stmt->bindParam(4, $phone_number);
        $stmt->bindParam(5, $age);
        $stmt->bindParam(6, $email);
        $stmt->bindParam(7, $file_name);
       
        // Execute the statement
        if (!$stmt->execute()) {
            throw new Exception("Error adding employee record: " . implode(", ", $stmt->errorInfo()));
        }
        
        // Get the newly inserted employee ID
        $employee_id = $pdo->lastInsertId();
        
        // Create user account if requested
        if ($createAccount && !empty($username) && !empty($password)) {
            // Check if the username already exists in users table
            $checkUserQuery = "SELECT COUNT(*) FROM users WHERE username = ?";
            $checkUserStmt = $pdo->prepare($checkUserQuery);
            $checkUserStmt->execute([$username]);
            
            if ($checkUserStmt->fetchColumn() > 0) {
                throw new Exception("Username already exists. Please choose a different username.");
            }
            
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user into users table
            $insertUserQuery = "INSERT INTO users (username, password, role, department) 
                              VALUES (?, ?, ?, 'employee')";
            $insertUserStmt = $pdo->prepare($insertUserQuery);
            $insertUserStmt->execute([$username, $hashedPassword, $position]);
            
            // Insert into worker_evaluations
            $evalId = "EMP-" . str_pad($employee_id, 4, '0', STR_PAD_LEFT);
            $evalQuery = "INSERT INTO worker_evaluations (id, employee_id, name, position, department, start_date, comments, performance) 
                         VALUES (?, ?, ?, ?, 'General', NOW(), '', 0)";
            $evalStmt = $pdo->prepare($evalQuery);
            $evalStmt->execute([$evalId, $evalId, $name, $position]);
            
            // Insert initial payroll record
            $payrollQuery = "INSERT INTO payroll_records (name, position, salary, daily_rate, basic_pay, ot_pay, late_deduct, gross_pay, sss_deduct, pagibig_deduct, total_deduct, net_salary, date_created) 
                           VALUES (?, ?, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NOW())";
            $payrollStmt = $pdo->prepare($payrollQuery);
            $payrollStmt->execute([$name, $position]);
        }
        
        // If everything went well, commit the transaction
        $pdo->commit();
        
        // Successful submission
        $_SESSION['success_message'] = "New employee added successfully!";
        echo '<script>alert("New employee added successfully!"); window.location.href = "employee_list.php";</script>';
        exit; // Exit to prevent further execution
    } catch (Exception $e) {
        // Rollback the transaction if an error occurred
        $pdo->rollBack();
        
        // Handle exceptions
        $_SESSION['error_message'] = $e->getMessage();
        echo '<script>alert("Error: ' . $e->getMessage() . '"); window.location.href = "employee_records.php";</script>';
        exit;
    }
} 

// Include the database connection file
require_once("database.php");

try {
    // Prepare SQL statement to select all employees from 'employee_list' table
    $sql = "SELECT * FROM employee_records ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);

    // Execute the statement
    $stmt->execute();

    // Fetch all employees
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle PDO exceptions
    $_SESSION['error_message'] = "PDO Exception: " . $e->getMessage();
    echo '<script>alert("PDO Exception: ' . $e->getMessage() . '"); window.location.href = "employee_records.php";</script>';
    exit; // Exit to prevent further execution
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Records</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Roboto', sans-serif;
      background-color: #f7fff7; /* Light green background */
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
    }

    .container {
      width: 80%;
      max-width: 1200px; /* Limit container width for better readability */
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      align-items: flex-start;
    }

    .employee-box {
      position: relative;
      width: 200px;
      margin: 10px;
      padding: 10px;
      background-color: #fff;
      border-radius: 5px;
      box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
      text-align: center;
    }

    .employee-box img {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      margin-bottom: 10px;
    }

    .employee-box p {
      margin: 5px 0;
    }

    .action-bar {
      margin-top: 20px;
    }

    .action-bar button {
      padding: 12px 20px;
      border: none;
      border-radius: 5px;
      background-color: #4CAF50; /* Green button */
      color: #fff; /* White text */
      cursor: pointer;
      transition: background-color 0.3s;
      font-size: 16px;
      margin-right: 10px;
    }

    .action-bar button:hover {
      background-color: #45a049; /* Darker green on hover */
    }

    .add-form, .edit-form {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background-color: #fff;
      padding: 20px;
      border-radius: 5px;
      box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
      z-index: 1000;
    }

    .add-form input, .edit-form input {
      width: calc(100% - 20px);
      margin: 10px;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .add-form button, .edit-form button {
      width: calc(50% - 20px);
      margin: 10px;
      padding: 12px;
      border: none;
      border-radius: 5px;
      background-color: #4CAF50; /* Green button */
      color: #fff; /* White text */
      cursor: pointer;
      transition: background-color 0.3s;
      font-size: 16px;
    }

    .add-form button:hover, .edit-form button:hover {
      background-color: #45a049; /* Darker green on hover */
    }
  </style>
</head>
<body>

<h1>EMPLOYEE RECORDS</h1>

<div class="container" id="workersContainer">
  <!-- Employee boxes will be dynamically populated here -->
</div>

<div class="action-bar">
  <button onclick="showAddForm()">Add New Employee</button>
  <button class="back-btn" onclick="goBack()">Back</button>
</div>

<div class="add-form" id="addEmployeeForm">
  <form action="employee_records.php" method="post" enctype="multipart/form-data">

  <h2>Add New Employee</h2>
  <input name="name" type="text" oninput="lettersOnly(event)" id="newName" placeholder="Name" required>
  <input name="position" type="text" oninput="lettersOnly(event)" id="newPosition" placeholder="Position" required>
  <input name="address" type="text" id="newAddress" placeholder="Address" required>
  <input name="phone_number" type= "number" id="newPhoneNumber" placeholder="Phone Number" required>
  <input name="age" type="number" id="newAge" placeholder="Age" required>
  <input name="email" type="email" id="newEmail" placeholder="Email Address" required>
  <input name="image" type="file" id="newPhoto" accept="image/*" onchange="previewPhoto(event)">
  
  <div style="border-top: 1px solid #ccc; margin: 15px 0; padding-top: 15px;">
    <label style="display: block; margin-bottom: 10px;"><input type="checkbox" name="create_account" id="createAccount" onchange="toggleAccountFields()"> Create User Account</label>
    
    <div id="accountFields" style="display: none;">
      <input name="username" type="text" id="newUsername" placeholder="Username">
      <input name="password" type="password" id="newPassword" placeholder="Password">
    </div>
  </div>
  
  <button onclick="saveEmployee()">Save</button>
  <button onclick="hideAddForm()">Cancel</button>
  </form>
</div>

<div class="edit-form" id="editEmployeeForm">
  <h2>Edit Employee</h2>
  <input name="name" type="hidden" onkeyup="lettersOnly(this)" id="editIndex">
  <input name="name" type="text" id="editName" onkeyup="lettersOnly(this)" placeholder="Name">
  <input name="position" type="text" id="editPosition" placeholder="Position">
  <input name="address" type="text" id="editAddress" placeholder="Address">
  <input name="phone_number" type="text" id="editPhoneNumber" placeholder="Phone Number">
  <input name="age" type="number" id="editAge" placeholder="Age">
  <input name="email" type="email" id="editEmail" placeholder="Email Address">
  <input name="image" type="file" id="editPhoto" accept="image/*" onchange="previewEditedPhoto(event)">
  <br>
  <button onclick="updateEmployee()">Update</button>
  <button onclick="cancelEdit()">Cancel</button>
</div>

<script>
//Only letters will be inputted
function lettersOnly(event) {
     var inputValue = event.target.value;
  var newValue = inputValue.replace(/[0-9]/g, '');
  event.target.value = newValue;
}

let selectedEmployeeIndex = -1;

function showAddForm() {
  var addForm = document.getElementById("addEmployeeForm");
  addForm.style.display = "block";
}

function hideAddForm() {
  var addForm = document.getElementById("addEmployeeForm");
  addForm.style.display = "none";
}

function saveEmployee() {
  var newName = document.getElementById("newName").value;
  var newPosition = document.getElementById("newPosition").value;
  var newAddress = document.getElementById("newAddress").value;
  var newPhoneNumber = document.getElementById("newPhoneNumber").value;
  var newAge = parseInt(document.getElementById("newAge").value);
  var newEmail = document.getElementById("newEmail").value;
  var newPhoto = document.getElementById("newPhoto").files[0];
  var photoURL = '';

  // You may handle the photo upload to your server and store the URL in your database.
  // Here, we are just displaying the selected photo as a preview.
  if (newPhoto) {
    photoURL = URL.createObjectURL(newPhoto);
  }

  var workersContainer = document.getElementById("workersContainer");
  var employeeBox = document.createElement("div");
  employeeBox.classList.add("employee-box");
  employeeBox.innerHTML = `
    <img src="${photoURL}" alt="${newName}">
    <p>Name: ${newName}</p>
    <p>Position: ${newPosition}</p>
    <p>Address: ${newAddress}</p>
    <p>Phone Number: ${newPhoneNumber}</p>
    <p>Age: ${newAge}</p>
    <p>Email: ${newEmail}</p>
    <div class="action-buttons">
      <button onclick="editEmployee(this)">Edit</button>
      <button onclick="deleteEmployee(this)">Delete</button>
    </div>
  `;
  workersContainer.appendChild(employeeBox);

  hideAddForm();
}

function editEmployee(button) {
  var employeeBox = button.closest(".employee-box");
  selectedEmployeeIndex = Array.from(employeeBox.parentElement.children).indexOf(employeeBox) + 1;

  var name = employeeBox.querySelector("p:nth-child(2)").innerText.replace("Name: ", "");
  var position = employeeBox.querySelector("p:nth-child(3)").innerText.replace("Position: ", "");
  var address = employeeBox.querySelector("p:nth-child(4)").innerText.replace("Address: ", "");
  var phoneNumber = employeeBox.querySelector("p:nth-child(5)").innerText.replace("Phone Number: ", "");
  var age = parseInt(employeeBox.querySelector("p:nth-child(6)").innerText.replace("Age: ", ""));
  var email = employeeBox.querySelector("p:nth-child(7)").innerText.replace("Email: ", "");

  document.getElementById("editIndex").value = selectedEmployeeIndex;
  document.getElementById("editName").value = name;
  document.getElementById("editPosition").value = position;
  document.getElementById("editAddress").value = address;
  document.getElementById("editPhoneNumber").value = phoneNumber;
  document.getElementById("editAge").value = age;
  document.getElementById("editEmail").value = email;

  var editForm = document.getElementById("editEmployeeForm");
  editForm.style.display = "block";
}

function updateEmployee() {
  var editIndex = document.getElementById("editIndex").value;
  var editName = document.getElementById("editName").value;
  var editPosition = document.getElementById("editPosition").value;
  var editAddress = document.getElementById("editAddress").value;
  var editPhoneNumber = document.getElementById("editPhoneNumber").value;
  var editAge = parseInt(document.getElementById("editAge").value);
  var editEmail = document.getElementById("editEmail").value;
  var editPhoto = document.getElementById("editPhoto").files[0];
  var photoURL = '';

  // You may handle the photo upload to your server and store the URL in your database.
  // Here, we are just displaying the selected photo as a preview.
  if (editPhoto) {
    photoURL = URL.createObjectURL(editPhoto);
  }

  var workersContainer = document.getElementById("workersContainer");
  var employeeBox = workersContainer.children[editIndex - 1];
  employeeBox.innerHTML = `
    <img src="${photoURL}" alt="${editName}">
    <p>Name: ${editName}</p>
    <p>Position: ${editPosition}</p>
    <p>Address: ${editAddress}</p>
    <p>Phone Number: ${editPhoneNumber}</p>
    <p>Age: ${editAge}</p>
    <p>Email: ${editEmail}</p>
    <div class="action-buttons">
      <button onclick="editEmployee(this)">Edit</button>
      <button onclick="deleteEmployee(this)">Delete</button>
    </div>
  `;

  var editForm = document.getElementById("editEmployeeForm");
  editForm.style.display = "none";
}

function cancelEdit() {
  var editForm = document.getElementById("editEmployeeForm");
  editForm.style.display = "none";
}

function deleteEmployee(button) {
  var employeeBox = button.closest(".employee-box");
  employeeBox.parentNode.removeChild(employeeBox);
}

function goBack() {
  window.history.back();
}

function previewPhoto(event) {
  // Function to preview the selected photo
}

function previewEditedPhoto(event) {
  // Function to preview the edited photo
}

function toggleAccountFields() {
  var createAccount = document.getElementById("createAccount");
  var accountFields = document.getElementById("accountFields");
  
  if (createAccount.checked) {
    accountFields.style.display = "block";
  } else {
    accountFields.style.display = "none";
  }
}
</script>

</body>
</html>