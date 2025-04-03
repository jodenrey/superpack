<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Evaluation Assessment Form</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <style>
    /* CSS styles for the evaluation assessment form */
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
      max-width: 800px; /* Limit container width for better readability */
      margin: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
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
      background-color: #4CAF50; /* Dark green header */
      color: #fff; /* White text */
      font-weight: bold;
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

    .stars {
      unicode-bidi: bidi-override;
      color: #888;
      font-size: 20px;
      display: inline-block;
      position: relative;
    }

    .stars:hover:before,
    .stars:hover ~ .stars:before {
      content: "\2605";
      position: absolute;
      color: #FFD700;
    }

    h1 {
      margin-bottom: 20px;
      font-size: 36px; /* Increased font size */
      color: #388e3c; /* Dark green heading */
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
      font-size: 28px; /* Increased font size */
      font-weight: bold;
      color: #388e3c; /* Dark green color for company name */
    }

    .slogan {
      font-size: 16px; /* Increased font size */
      color: #388e3c; /* Dark green color for slogan */
    }

    .back-btn, .evaluate-btn {
      background-color: #4CAF50; /* Green button */
      color: #fff; /* White text */
      border: none;
      border-radius: 5px;
      padding: 12px 24px; /* Increased padding */
      font-size: 18px; /* Increased font size */
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .back-btn:hover, .evaluate-btn:hover {
      background-color: #45a049; /* Darker green on hover */
    }

    .stars > input {
      display: none;
    }

    .stars > label {
      font-size: 36px; /* Increased font size */
      cursor: pointer;
      color: #888;
    }

    .stars > label:hover,
    .stars > input:checked ~ label,
    .stars > input:checked ~ label ~ label {
      color: #FFD700;
    }

    .evaluation-result {
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
      background-color: #f9f9f9; /* Light gray background */
    }

    .evaluation-result h3 {
      font-size: 24px; /* Increased font size */
      color: #4CAF50; /* Green heading */
    }

    .evaluation-result p {
      margin: 10px 0;
      font-size: 18px; /* Increased font size */
    }

    .evaluation-result .status-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background-color: #fff; /* White background */
      border: 1px solid #ddd;
      border-radius: 5px;
      padding: 10px;
    }

    .evaluation-result .status-bar .employee-name {
      font-weight: bold;
    }

    .evaluation-result .status-bar .work-quality {
      flex-grow: 1;
      margin: 0 10px;
    }

    .evaluation-result .status-bar .stars {
      color: #FFD700; /* Yellow star color */
    }

    .evaluation-result .status-bar .remark-note {
      flex-grow: 2;
    }

    /* Hover effect for buttons */
    .back-btn:hover, .evaluate-btn:hover {
      filter: brightness(90%);
    }

    /* Hover effect for table rows */
    tr:hover {
      background-color: #f0f0f0;
    }

  </style>
</head>
<body>

<h1>Evaluation Assessment Form</h1>

<div class="container">
  <div class="company-info-container">
    <img class="company-logo" src="logo.png" alt="Company Logo">
    <div>
      <h1 class="company-name">Superpack Enterprise</h1>
      <p class="slogan">"Because your box matters"</p>
    </div>
  </div>
  <table>
    <thead>
      <tr>
        <th>Employee No</th>
        <th>Name</th>
        <th>Position</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody id="employeeTableBody">
      <tr>
        <td>001</td>
        <td onclick="showEvaluationPopup('Abay, Ruben S')">Abay, Ruben S</td>
        <td>Diecut operator</td>
        <td>
          <button class="evaluate-btn" onclick="showEvaluationPopup('Abay, Ruben S')">Evaluate</button>
        </td>
      </tr>
      <tr>
        <td>038</td>
        <td onclick="showEvaluationPopup('Antiola, Marvin')">Antiola, Marvin</td>
        <td>Driver</td>
        <td>
          <button class="evaluate-btn" onclick="showEvaluationPopup('Antiola, Marvin')">Evaluate</button>
        </td>
      </tr>
      <tr>
        <td>032</td>
        <td onclick="showEvaluationPopup('Ayroso, Diazy')">Ayroso, Diazy</td>
        <td>Quality Control</td>
        <td>
          <button class="evaluate-btn" onclick="showEvaluationPopup('Ayroso, Diazy')">Evaluate</button>
        </td>
      </tr>
      <!-- Add more rows as needed -->
    </tbody>
  </table>
</div>

<div id="evaluationPopup" class="popup-form">
  <h2>Evaluation for <span id="employeeName"></span></h2>
  <form onsubmit="saveEvaluation(event)">
    <label for="workQuality">Employee Work Quality:</label>
    <div class="stars">
      <input type="radio" id="workQuality5" name="workQuality" value="5" />
      <label for="workQuality5">&#9733;</label>
      <input type="radio" id="workQuality4" name="workQuality" value="4" />
      <label for="workQuality4">&#9733;</label>
      <input type="radio" id="workQuality3" name="workQuality" value="3" />
      <label for="workQuality3">&#9733;</label>
      <input type="radio" id="workQuality2" name="workQuality" value="2" />
      <label for="workQuality2">&#9733;</label>
      <input type="radio" id="workQuality1" name="workQuality" value="1" />
      <label for="workQuality1">&#9733;</label>
    </div><br>
    <!-- Add other evaluation criteria here -->
    <label for="remarkNote">Remark Note:</label>
    <textarea id="remarkNote" rows="3"></textarea><br>
    <button class="evaluate-btn" type="submit">Save</button>
    <button class="back-btn" type="button" onclick="hideEvaluationPopup()">Cancel</button>
  </form>
</div>

<div id="evaluationResult" class="container">
  <h2>Evaluation Result</h2>
  <div id="resultContent" class="evaluation-result"></div>
  <button class="back-btn" onclick="goBack()">Back</button>
</div>

<script>
function showEvaluationPopup(employeeName) {
  document.getElementById("employeeName").innerText = employeeName;
  document.getElementById("evaluationPopup").style.display = "block";
}

function hideEvaluationPopup() {
  document.getElementById("evaluationPopup").style.display = "none";
}

function saveEvaluation(event) {
  event.preventDefault();
  const employeeName = document.getElementById("employeeName").innerText;
  const workQualityStars = document.querySelector('input[name="workQuality"]:checked').value;
  // Retrieve other evaluation criteria here
  const remarkNote = document.getElementById("remarkNote").value;

  // Create result content
  const resultContent = document.createElement("div");
  resultContent.classList.add("evaluation-result");

  const heading = document.createElement("h3");
  heading.textContent = "Evaluation saved";
  resultContent.appendChild(heading);

  const employeeStatus = document.createElement("div");
  employeeStatus.classList.add("status-bar");

  const name = document.createElement("p");
  name.classList.add("employee-name");
  name.textContent = employeeName;
  employeeStatus.appendChild(name);

  const workQuality = document.createElement("p");
  workQuality.classList.add("work-quality");
  workQuality.innerHTML = `Work Quality: ${workQualityStars} &#9733;`;
  employeeStatus.appendChild(workQuality);

  const remark = document.createElement("p");
  remark.classList.add("remark-note");
  remark.textContent = `Remark Note: ${remarkNote}`;
  employeeStatus.appendChild(remark);

  resultContent.appendChild(employeeStatus);

  document.getElementById("resultContent").appendChild(resultContent);
  document.getElementById("evaluationResult").style.display = "block";

  hideEvaluationPopup();
}

function goBack() {
  // Navigate back to the dashboard page
  window.location.href = "employee_dashboard.php";
}
</script>

</body>
</html>