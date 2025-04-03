<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Your head content -->
</head>
<body>

<!-- Your existing HTML content -->

<script>
function showPopup() {
  // Your existing showPopup function
}

function hidePopup() {
  // Your existing hidePopup function
}

function editEmployee(id) {
  var row = document.querySelectorAll("tr")[id + 1];
  var cells = row.querySelectorAll("td");

  var name = cells[0].textContent;
  var position = cells[1].textContent;
  var shift = cells[2].textContent;
  var salary = cells[3].textContent;
  var status = cells[4].textContent;

  document.getElementById("editId").value = id;
  document.getElementById("editName").value = name;
  document.getElementById("editPosition").value = position;
  document.getElementById("editShift").value = shift;
  document.getElementById("editSalary").value = salary;
  document.getElementById("editStatus").value = status;

  showPopup();
}

function saveEditedEmployee(event) {
  event.preventDefault();

  var id = document.getElementById("editId").value;
  var name = document.getElementById("editName").value;
  var position = document.getElementById("editPosition").value;
  var shift = document.getElementById("editShift").value;
  var salary = document.getElementById("editSalary").value;
  var status = document.getElementById("editStatus").value;

  var xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function() {
    if (xhr.readyState === XMLHttpRequest.DONE) {
      if (xhr.status === 200) {
        // Employee edited successfully, reload the page to display updated data
        location.reload();
      } else {
        // Display error message
        alert("Error: " + xhr.responseText);
      }
    }
  };
  xhr.open("POST", "update_employee.php", true);
  xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xhr.send("id=" + id + "&name=" + encodeURIComponent(name) + "&position=" + encodeURIComponent(position) + "&shift=" + encodeURIComponent(shift) + "&salary=" + encodeURIComponent(salary) + "&status=" + encodeURIComponent(status));
}

function deleteEmployee(id) {
  // Your existing deleteEmployee function
}
</script>

</body>
</html>
