<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Management</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <style>
    /* CSS styles for the dashboard */
    b/* Reset CSS */
    *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #c3e6cb; /* Green pastel background */
            color: #333;
            margin: 0;
        }
    .content {
       display: flex;
       justify-content: center;
       flex-wrap: wrap;
       gap: 10px;
       margin-top: 10px;
    }
    
    .title {
      margin-bottom: 50px;
      text-align: right;
      color: #333; /* Dark green heading */
      width: 100%;
      display: flex;
      margin-right: 50px;
      justify-content: center; /* Center items horizontally */
      white-space: nowrap; /* Prevent text from breaking into multiple lines */
      margin-right: 275px;

        

    }
    .title h1{
      text-align: center;
      width: 70%;
      display: flex;
      align-items: center;
      max-width: 100px;
      height: auto;
      margin-right: 5px;
    }

    .container {
      width: 80%;
      max-width: 1200px; /* Limit container width for better readability */
    }
    .form button {
      width: 100%;
      padding: 8px;
      border: none;
      border-radius: 5px;
      background-color: #4CAF50; /* Green button */
      color: #fff; /* White text */
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .form button:hover {
      background-color: #45a049; /* Darker green on hover */
    }

    /* Add styles for the action bar */
    .action-bar {
      display: flex;
      justify-content: center;
      align-items: center; /* Center items vertically */
      width: 100%;
      margin-bottom: 10px;
    }

    .action-bar button {
      margin: 0 5px; /* Add some space between buttons */
      padding: 12px 20px;
      border: none;
      border-radius: 5px;
      background-color: #4CAF50; /* Green button */
      color: #fff; /* White text */
      cursor: pointer;
      transition: background-color 0.3s;
      font-size: 16px;
    }

    .action-bar button:hover {
      background-color: #45a049; /* Darker green on hover */
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 100px;
      background-color: #fff; /* White background for table */
      border-radius: 10px; /* Rounded corners */
      box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    }

    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #7fbf7f; /* Dark green header */
      color: #fff; /* White text */
    }

    /* Style for the pop-up form */
    .popup-form {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background-color: #ffffff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.2);
      z-index: 1000;
    }

    .popup-form label {
      display: block;
      margin-bottom: 10px;
      font-weight: bold;
    }

    .popup-form input, .popup-form select {
      width: calc(100% - 24px); /* Adjusted width to account for padding */
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 16px;
    }

    .popup-form button {
      width: 40%; /* Adjusted width to fit two buttons side by side */
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      background-color: #4CAF50; /* Green button */
      color: #fff; /* White text */
      cursor: pointer;
      transition: background-color 0.3s;
      font-size: 16px;
    }

    .popup-form button:hover {
      background-color: #45a049; /* Darker green on hover */
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
    }

    .action-bar button:hover {
      background-color: #45a049; /* Darker green on hover */
    }

    h1 {
      color: #333; /* Dark green heading */
      width: 50%;
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    /* Company logo and name */
    .company-info-container {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .company-logo {
      max-width: 80px;
      height: auto;
      margin-right: 10px;
    }

    .company-name {
      font-size: 24px;
      font-weight: bold;
      color: #388e3c; /* Dark green color for company name */
    }

    .slogan {
      font-size: 14px;
      color: #388e3c; /* Dark green color for slogan */
    }

    .back-btn {
      background-color: #4CAF50; /* Green button */
      color: #fff; /* White text */
      border: none;
      border-radius: 5px;
      padding: 10px 20px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .back-btn:hover {
      background-color: #45a049; /* Darker green on hover */
    }
  </style>
</head>
<body>

<div class="content">
<div class="title"><h1>Attendance Management</h1>
</div>
<div class="container">
  <div class="company-info-container">
    <img class="company-logo" src="logo.png" alt="Company Logo">
    <div>
      <h1 class="company-name">Factory Workers</h1>
      <p class="slogan">"Because your box matters"</p>
    </div>
  </div>
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Position</th>
        <th>Time In (Breaks)</th>
        <th>Time Out (Breaks)</th>
        <th>Time In (Lunch)</th>
        <th>Time Out (Lunch)</th>
        <th>End of Day</th>
        <th>Date</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody id="employeeTableBody">
      <!-- Table rows will be dynamically populated -->
    </tbody>
  </table>
</div>

<div class="action-bar">
  <button onclick="showPopup()">Add New Leave Request</button>
  <button class="back-btn" onclick="goBack()">Back</button>

<div id="popup" class="popup-form">
  <h2>Add New Entry</h2>
  <form id="employeeForm" onsubmit="saveNewEntry(event)">
    <label for="newName">Name:</label>
    <input type="text" id="newName" required><br>
    <label for="newPosition">Position:</label>
    <input type="text" id="newPosition" required><br>
    <button type="submit">Save</button>
    <button type="button" onclick="hidePopup()">Cancel</button>
  </form>
</div>

<div id="editPopup" class="popup-form">
  <h2>Edit Entry</h2>
  <form id="editEmployeeForm" onsubmit="updateEntry(event)">
    <input type="hidden" id="editIndex" value="">
    <label for="editName">Name:</label>
    <input type="text" id="editName" required><br>
    <label for="editPosition">Position:</label>
    <input type="text" id="editPosition" required><br>
    <label for="editDate">Date:</label>
    <input type="date" name="shootdate" id="editDate" required title ="Choose your desired date" min="<?php echo date('Y-m-d'); ?>"/>><br>
    <label for="editTimeInBreaks">Time In (Breaks):</label>
    <input type="time" id="editTimeInBreaks" required><br>
    <label for="editTimeOutBreaks">Time Out (Breaks):</label>
    <input type="time" id="editTimeOutBreaks" required><br>
    <label for="editTimeInLunch">Time In (Lunch):</label>
    <input type="time" id="editTimeInLunch" required><br>
    <label for="editTimeOutLunch">Time Out (Lunch):</label>
    <input type="time" id="editTimeOutLunch" required><br>
    <label for="editEndOfDay">End of Day:</label>
    <input type="time" id="editEndOfDay" required><br>
    <button type="submit">Update</button>
    <button type="button" onclick="hideEditPopup()">Cancel</button>
  </form>
</div>

<script>
function saveNewEntry(event) {
  event.preventDefault();
  
  var name = document.getElementById("newName").value;
  var position = document.getElementById("newPosition").value;
  
  // Add the new entry data to the table
  var newRow = document.createElement("tr");
  newRow.innerHTML = `
    <td>${name}</td>
    <td>${position}</td>
    <td><input type="time" class="editField" value="09:00"></td>
    <td><input type="time" class="editField" value="17:00"></td>
    <td><input type="time" class="editField" value="12:00"></td>
    <td><input type="time" class="editField" value="13:00"></td>
    <td><input type="time" class="editField" value="18:00"></td>
    <td>${getCurrentDate()}</td>
    <td>
      <button onclick="editEntry(this)" style="background-color: #4CAF50;">Edit</button>
      <button onclick="deleteEntry(this)" style="background-color: #f44336;">Delete</button>
    </td>
  `;
  
  document.getElementById("employeeTableBody").appendChild(newRow);
  
  // Clear the form fields
  document.getElementById("newName").value = "";
  document.getElementById("newPosition").value = "";
  
  // Hide the popup
  hidePopup();
}

function editEntry(button) {
  var row = button.closest('tr');
  var cells = row.cells;
  
  // Populate the edit form fields
  document.getElementById("editName").value = cells[0].innerText;
  document.getElementById("editPosition").value = cells[1].innerText;
  document.getElementById("editTimeInBreaks").value = cells[2].querySelector('input').value;
  document.getElementById("editTimeOutBreaks").value = cells[3].querySelector('input').value;
  document.getElementById("editTimeInLunch").value = cells[4].querySelector('input').value;
  document.getElementById("editTimeOutLunch").value = cells[5].querySelector('input').value;
  document.getElementById("editEndOfDay").value = cells[6].querySelector('input').value;
  document.getElementById("editDate").value = cells[7].innerText;
  
  // Show the edit popup
  document.getElementById("editPopup").style.display = "block";
}

function updateEntry(event) {
  event.preventDefault();
  
  var index = document.getElementById("editIndex").value;
  var name = document.getElementById("editName").value;
  var position = document.getElementById("editPosition").value;
  var timeInBreaks = document.getElementById("editTimeInBreaks").value;
  var timeOutBreaks = document.getElementById("editTimeOutBreaks").value;
  var timeInLunch = document.getElementById("editTimeInLunch").value;
  var timeOutLunch = document.getElementById("editTimeOutLunch").value;
  var endOfDay = document.getElementById("editEndOfDay").value;
  var date = document.getElementById("editDate").value;
  
  var row = document.getElementById("employeeTableBody").children[index];
  row.cells[0].innerText = name;
  row.cells[1].innerText = position;
  row.cells[2].querySelector('input').value = timeInBreaks;
  row.cells[3].querySelector('input').value = timeOutBreaks;
  row.cells[4].querySelector('input').value = timeInLunch;
  row.cells[5].querySelector('input').value = timeOutLunch;
  row.cells[6].querySelector('input').value = endOfDay;
  
  // Hide the edit popup
  hideEditPopup();
}

function deleteEntry(button) {
  var row = button.closest('tr');
  row.remove();
}

function getCurrentDate() {
  var currentDate = new Date();
  var day = String(currentDate.getDate()).padStart(2, '0');
  var month = String(currentDate.getMonth() + 1).padStart(2, '0'); // January is 0!
  var year = currentDate.getFullYear();
  return `${month}/${day}/${year}`;
}

function goBack() {
  window.history.back();
}

function showPopup() {
  document.getElementById("popup").style.display = "block";
}

function hidePopup() {
  document.getElementById("popup").style.display = "none";
}

function hideEditPopup() {
  document.getElementById("editPopup").style.display = "none";
}
</script>

</body>
</html>