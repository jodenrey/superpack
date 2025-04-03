<!DOCTYPE html>
<html lang='en'>
  <head>
    <meta charset='utf-8' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
    <script>
      var calendar; // Declare the calendar variable globally

      // This function renders the calendar
      document.addEventListener('DOMContentLoaded', function() {
      var calendarEl = document.getElementById('calendar');
      calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        dateClick: function(info) {
        document.getElementById('modal').style.display = 'block';
        document.getElementById('note-date').innerText = info.dateStr;
        },
        eventContent: function(arg) {
        let deleteButton = document.createElement('button');
        deleteButton.innerText = 'Del';
        deleteButton.classList.add('delete-button');
        deleteButton.onclick = function() {
          calendar.getEventById(arg.event.id).remove();
        };

        let arrayOfDomNodes = [document.createTextNode(arg.event.title), deleteButton];
        return { domNodes: arrayOfDomNodes };
        }
      });
      calendar.render();
      });

      // Function to close the modal
      function closeModal() {
      document.getElementById('modal').style.display = 'none';
      }

      // Function to save the note
      function saveNote() {
      var note = document.getElementById('note-text').value;
      var date = document.getElementById('note-date').innerText;
      console.log('Note for ' + date + ': ' + note);

      // Add the event to the calendar
      calendar.addEvent({
        id: Date.now().toString(), // Unique ID for the event
        title: note,
        start: date,
        allDay: true,
        display: 'block' // Ensure the note takes up the full grid size
      });

      closeModal();
      }
    </script>
    <style>
      #calendar .fc-daygrid-day:hover {
        cursor: pointer;
      }
      #calendar {
        height: 350px;
        width: 100%;
        padding: 20px;
      }
      /* Modal styles */
      .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgb(0,0,0);
        background-color: rgba(0,0,0,0.4);
        padding-top: 60px;
      }
      .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 30%;
      }
      .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
      }
      .close:hover,
      .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
      }
      .delete-button {
        margin-left: 10px;
        background-color: red;
        color: white;
        border: none;
        cursor: pointer;
      }
      .delete-button:hover {
        background-color: darkred;
      }
    </style>
  </head>
  <body>
    <div id='calendar'></div>

    <!-- The Modal -->
    <div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Set a Note for <span id="note-date"></span></h2>
        <textarea id="note-text" rows="4" cols="50"></textarea><br>
        <button onclick="saveNote()">Save Note</button>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  </body>
</html>
