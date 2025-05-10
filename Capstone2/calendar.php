<?php
// Start session to access user info if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang='en'>
  <head>
    <meta charset='utf-8' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
      var calendar; // Declare the calendar variable globally
      var currentNoteId; // Store current note ID for edit/delete operations
      
      // Check if the current user is an admin
      const isAdmin = <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') ? 'true' : 'false'; ?>;

      // This function renders the calendar
      document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listMonth'
          },
          buttonText: {
            today: 'Today',
            month: 'Month',
            list: 'List'
          },
          dateClick: function(info) {
            // Only allow admins to add notes
            if (isAdmin) {
              // Clear the form
              document.getElementById('note-text').value = '';
              document.getElementById('note-date').value = info.dateStr;
              document.getElementById('modal-title').innerText = 'Add Note';
              document.getElementById('save-note-btn').innerText = 'Save Note';
              document.getElementById('save-note-btn').onclick = saveNote;
              
              // Show the modal
              const noteModal = new bootstrap.Modal(document.getElementById('noteModal'));
              noteModal.show();
            } else {
              showToast('Only administrators can add calendar notes.', 'warning');
            }
          },
          eventClick: function(info) {
            // Set current note ID
            currentNoteId = info.event.id;
            
            if (isAdmin) {
              // Show action buttons
              const actionBtns = document.createElement('div');
              actionBtns.className = 'note-actions';
              
              // Edit button
              const editBtn = document.createElement('button');
              editBtn.className = 'btn btn-sm btn-primary me-2';
              editBtn.innerHTML = '<i class="fas fa-edit"></i>';
              editBtn.onclick = function() {
                // Populate edit form
                document.getElementById('edit-note-text').value = info.event.title;
                document.getElementById('edit-note-date').value = info.event.startStr.split('T')[0];
                document.getElementById('edit-note-id').value = info.event.id;
                
                // Show edit modal
                const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
              };
              
              // Delete button
              const deleteBtn = document.createElement('button');
              deleteBtn.className = 'btn btn-sm btn-danger';
              deleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
              deleteBtn.onclick = function() {
                // Set note info in delete modal
                document.getElementById('delete-note-text').innerText = info.event.title;
                
                // Show delete confirmation modal
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
              };
              
              actionBtns.appendChild(editBtn);
              actionBtns.appendChild(deleteBtn);
              
              // Create tooltip content
              const tooltipContent = document.createElement('div');
              tooltipContent.className = 'note-tooltip';
              
              const noteTitle = document.createElement('div');
              noteTitle.className = 'note-title mb-2';
              noteTitle.innerText = info.event.title;
              
              tooltipContent.appendChild(noteTitle);
              tooltipContent.appendChild(actionBtns);
              
              // Show tooltip
              const tooltip = new bootstrap.Tooltip(info.el, {
                title: tooltipContent,
                html: true,
                placement: 'top',
                trigger: 'click',
                container: 'body'
              });
              
              tooltip.show();
              
              // Close tooltip when clicking elsewhere
              document.addEventListener('click', function closeTooltip(e) {
                if (!info.el.contains(e.target) && !document.querySelector('.tooltip')?.contains(e.target)) {
                  tooltip.hide();
                  document.removeEventListener('click', closeTooltip);
                }
              });
            }
          },
          eventContent: function(arg) {
            let eventDiv = document.createElement('div');
            eventDiv.className = 'fc-event-title-container';
            
            const noteText = document.createElement('div');
            noteText.className = 'fc-event-title fc-sticky';
            
            // Truncate long titles
            const title = arg.event.title;
            const maxLength = 25;
            noteText.innerText = title.length > maxLength ? title.substring(0, maxLength) + '...' : title;
            
            // Add icons for admin
            if (isAdmin) {
              const actionsIcon = document.createElement('i');
              actionsIcon.className = 'fas fa-ellipsis-v float-end me-1';
              noteText.appendChild(actionsIcon);
            }
            
            eventDiv.appendChild(noteText);
            return { domNodes: [eventDiv] };
          },
          eventDidMount: function(info) {
            // Add hover effect
            info.el.addEventListener('mouseenter', function() {
              info.el.style.cursor = 'pointer';
            });
          }
        });
        
        // Load saved notes from the database
        loadNotes();
        
        calendar.render();
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        });
      });

      // Function to save the note to the database
      function saveNote() {
        var note = document.getElementById('note-text').value.trim();
        if (!note) {
          showToast('Please enter a note text.', 'warning');
          return;
        }
        
        var date = document.getElementById('note-date').value;
        var noteId = Date.now().toString(); // Unique ID for the event
        
        // Send the note to the server
        fetch('calendar_notes.php?action=add', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            id: noteId,
            title: note,
            date: date
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Add the event to the calendar
            calendar.addEvent({
              id: noteId,
              title: note,
              start: date,
              allDay: true
            });
            
            // Hide modal
            const noteModal = bootstrap.Modal.getInstance(document.getElementById('noteModal'));
            noteModal.hide();
            
            // Show success message
            showToast('Note added successfully!', 'success');
          } else {
            showToast('Error: ' + data.message, 'danger');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showToast('An error occurred while saving the note.', 'danger');
        });
      }
      
      // Function to edit a note
      function editNote() {
        var noteId = document.getElementById('edit-note-id').value;
        var note = document.getElementById('edit-note-text').value.trim();
        var date = document.getElementById('edit-note-date').value;
        
        if (!note) {
          showToast('Please enter a note text.', 'warning');
          return;
        }
        
        // Send the updated note to the server
        fetch('calendar_notes.php?action=edit', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            id: noteId,
            title: note,
            date: date
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Update the event in the calendar
            let event = calendar.getEventById(noteId);
            if (event) {
              event.setProp('title', note);
              event.setStart(date);
            }
            
            // Hide modal
            const editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
            editModal.hide();
            
            // Show success message
            showToast('Note updated successfully!', 'success');
          } else {
            showToast('Error: ' + data.message, 'danger');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showToast('An error occurred while updating the note.', 'danger');
        });
      }
      
      // Function to delete a note
      function deleteNote() {
        fetch('calendar_notes.php?action=remove', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            id: currentNoteId
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Remove the event from the calendar
            let event = calendar.getEventById(currentNoteId);
            if (event) {
              event.remove();
            }
            
            // Hide modal
            const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            deleteModal.hide();
            
            // Show success message
            showToast('Note deleted successfully!', 'success');
          } else {
            showToast('Error: ' + data.message, 'danger');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showToast('An error occurred while deleting the note.', 'danger');
        });
      }
      
      // Function to load all notes from the database
      function loadNotes() {
        fetch('calendar_notes.php?action=get')
        .then(response => response.json())
        .then(data => {
          if (data.success && data.notes) {
            data.notes.forEach(note => {
              calendar.addEvent(note);
            });
          } else {
            console.error('Failed to load notes:', data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
        });
      }
      
      // Function to show toast notifications
      function showToast(message, type) {
        const toastContainer = document.getElementById('toast-container');
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        const toastBody = document.createElement('div');
        toastBody.className = 'd-flex';
        
        const messageDiv = document.createElement('div');
        messageDiv.className = 'toast-body';
        messageDiv.innerText = message;
        
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn-close btn-close-white me-2 m-auto';
        closeButton.setAttribute('data-bs-dismiss', 'toast');
        closeButton.setAttribute('aria-label', 'Close');
        
        toastBody.appendChild(messageDiv);
        toastBody.appendChild(closeButton);
        toast.appendChild(toastBody);
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
        
        // Remove toast from DOM after it's hidden
        toast.addEventListener('hidden.bs.toast', function() {
          toastContainer.removeChild(toast);
        });
      }
    </script>
    <style>
      body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }
      #calendar {
        height: 350px;
        width: 100%;
        padding: 0.5rem;
      }
      #calendar .fc-event {
        cursor: pointer;
        transition: all 0.2s ease;
        border-radius: 4px;
        border: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      }
      #calendar .fc-event:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
      }
      #calendar .fc-daygrid-day-top {
        padding: 5px;
      }
      #calendar .fc-daygrid-day-number {
        font-weight: 500;
      }
      #calendar .fc-toolbar-title {
        font-size: 1.5rem;
      }
      #calendar .fc-button {
        background-color: #4CAF50;
        border-color: #4CAF50;
      }
      #calendar .fc-button:hover {
        background-color: #45a049;
        border-color: #45a049;
      }
      #calendar .fc-button:active,
      #calendar .fc-button:focus {
        box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
      }
      .tooltip-inner {
        max-width: 300px;
        padding: 15px;
        color: #fff;
        text-align: left;
        background-color: rgba(33, 37, 41, 0.9);
        border-radius: 6px;
      }
      .note-title {
        font-weight: bold;
        font-size: 1rem;
        word-break: break-word;
      }
      .note-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 10px;
      }
      .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1060;
      }
    </style>
  </head>
  <body>
    <div id='calendar'></div>
    
    <!-- Toast Container -->
    <div id="toast-container" class="position-fixed top-0 end-0 p-3"></div>

    <!-- Add/Edit Note Modal -->
    <div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="modal-title" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modal-title">Add Note</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="note-date" class="form-label">Date</label>
              <input type="date" class="form-control" id="note-date">
            </div>
            <div class="mb-3">
              <label for="note-text" class="form-label">Note</label>
              <textarea class="form-control" id="note-text" rows="4" placeholder="Enter your note here..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="save-note-btn" onclick="saveNote()">Save Note</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Note Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editModalLabel">Edit Note</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="edit-note-id">
            <div class="mb-3">
              <label for="edit-note-date" class="form-label">Date</label>
              <input type="date" class="form-control" id="edit-note-date">
            </div>
            <div class="mb-3">
              <label for="edit-note-text" class="form-label">Note</label>
              <textarea class="form-control" id="edit-note-text" rows="4"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="editNote()">Update Note</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete this note?</p>
            <div class="alert alert-secondary">
              <p id="delete-note-text" class="mb-0"></p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="deleteNote()">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
