<?php
// Show notifications for all logged-in users
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true):
?>
<div class="notifications-container">
    <div class="notifications-icon" id="notifications-icon">
        <i class="fas fa-bell"></i>
        <span class="badge" id="notification-badge">0</span>
    </div>
    <div class="notifications-dropdown" id="notifications-dropdown">
        <div class="notifications-header">
            <h3>Notifications</h3>
            <a href="#" id="mark-all-read">Mark all as read</a>
        </div>
        <div class="notifications-list" id="notifications-list">
            <div class="notification-item loading">
                <p>Loading notifications...</p>
            </div>
        </div>
        <div class="notifications-footer">
            <a href="#" id="clear-all-notifications">Clear all notifications</a>
        </div>
    </div>
</div>

<style>
.notifications-container {
    position: relative;
    margin-left: 15px;
}

.notifications-icon {
    position: relative;
    cursor: pointer;
    font-size: 1.2rem;
}

.notifications-icon .badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: #ff3b30;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.notifications-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 320px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    display: none;
    z-index: 1000;
    max-height: 400px;
    overflow: hidden;
    animation: slideDown 0.3s forwards;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notifications-header, .notifications-footer {
    padding: 10px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f0f0f0;
}

.notifications-header h3 {
    margin: 0;
    font-size: 1rem;
}

.notifications-footer {
    border-top: 1px solid #f0f0f0;
    border-bottom: none;
}

.notifications-header a, .notifications-footer a {
    color: #007bff;
    text-decoration: none;
    font-size: 0.85rem;
}

.notifications-list {
    overflow-y: auto;
    max-height: 320px;
}

.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background-color 0.3s;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #e8f4ff;
}

.notification-item.unread:hover {
    background-color: #d8ecff;
}

.notification-item .time {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 5px;
}

.notification-item.loading {
    text-align: center;
    color: #6c757d;
}

.notifications-dropdown.show {
    display: block;
}

/* pulse animation for the badge */
@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

.pulse {
    animation: pulse 0.5s 3;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationsIcon = document.getElementById('notifications-icon');
    const notificationsDropdown = document.getElementById('notifications-dropdown');
    const notificationsList = document.getElementById('notifications-list');
    const notificationBadge = document.getElementById('notification-badge');
    const markAllReadBtn = document.getElementById('mark-all-read');
    const clearAllNotificationsBtn = document.getElementById('clear-all-notifications');
    
    let notifications = [];
    let unreadCount = 0;
    let isInitialLoad = true;
    
    // Toggle dropdown
    notificationsIcon.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationsDropdown.classList.toggle('show');
        
        if (notificationsDropdown.classList.contains('show')) {
            fetchNotifications();
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (notificationsDropdown.classList.contains('show') && 
            !notificationsDropdown.contains(e.target) && 
            !notificationsIcon.contains(e.target)) {
            notificationsDropdown.classList.remove('show');
        }
    });
    
    // Fetch notifications
    function fetchNotifications() {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notifications = data.notifications;
                    unreadCount = data.unread_count;
                    updateNotificationBadge();
                    renderNotifications();
                }
            })
            .catch(error => {
                console.error('Error fetching notifications:', error);
                notificationsList.innerHTML = '<div class="notification-item loading"><p>Failed to load notifications</p></div>';
            });
    }
    
    // Update notification badge
    function updateNotificationBadge() {
        notificationBadge.textContent = unreadCount;
        
        if (unreadCount > 0) {
            notificationBadge.style.display = 'flex';
            
            // Add pulse animation if this isn't the initial load and there are new notifications
            if (!isInitialLoad && unreadCount > 0) {
                notificationBadge.classList.add('pulse');
                setTimeout(() => {
                    notificationBadge.classList.remove('pulse');
                }, 1500);
            }
        } else {
            notificationBadge.style.display = 'none';
        }
        
        isInitialLoad = false;
    }
    
    // Render notifications
    function renderNotifications() {
        if (notifications.length === 0) {
            notificationsList.innerHTML = '<div class="notification-item loading"><p>No notifications</p></div>';
            return;
        }
        
        notificationsList.innerHTML = '';
        
        notifications.forEach(notification => {
            const item = document.createElement('div');
            item.className = `notification-item ${notification.is_read == 0 ? 'unread' : ''}`;
            item.setAttribute('data-id', notification.id);
            
            // Format date
            const date = new Date(notification.created_at);
            const timeAgo = getTimeAgo(date);
            
            item.innerHTML = `
                <p>${notification.message}</p>
                <div class="time">${timeAgo}</div>
            `;
            
            item.addEventListener('click', function() {
                markAsRead(notification.id);
                // Redirect based on notification type
                if (notification.type === 'leave_request' || notification.type === 'leave_status') {
                    window.location.href = 'attendance_check.php';
                } else {
                    window.location.href = 'selection.php';
                }
            });
            
            notificationsList.appendChild(item);
        });
    }
    
    // Mark notification as read
    function markAsRead(id) {
        const formData = new FormData();
        formData.append('mark_read', '1');
        formData.append('notification_id', id);
        
        fetch('get_notifications.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update local notification status
                const index = notifications.findIndex(n => n.id == id);
                if (index !== -1) {
                    notifications[index].is_read = 1;
                    unreadCount = Math.max(0, unreadCount - 1);
                    updateNotificationBadge();
                    renderNotifications();
                }
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }
    
    // Mark all as read
    markAllReadBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        notifications.forEach(notification => {
            if (notification.is_read == 0) {
                markAsRead(notification.id);
            }
        });
    });
    
    // Clear all notifications
    clearAllNotificationsBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        fetch('clear_notifications.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                notifications = [];
                unreadCount = 0;
                updateNotificationBadge();
                renderNotifications();
                notificationsDropdown.classList.remove('show');
            }
        })
        .catch(error => {
            console.error('Error clearing notifications:', error);
        });
    });
    
    // Helper function to format time ago
    function getTimeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        
        let interval = Math.floor(seconds / 31536000);
        if (interval >= 1) {
            return interval + " year" + (interval === 1 ? "" : "s") + " ago";
        }
        
        interval = Math.floor(seconds / 2592000);
        if (interval >= 1) {
            return interval + " month" + (interval === 1 ? "" : "s") + " ago";
        }
        
        interval = Math.floor(seconds / 86400);
        if (interval >= 1) {
            return interval + " day" + (interval === 1 ? "" : "s") + " ago";
        }
        
        interval = Math.floor(seconds / 3600);
        if (interval >= 1) {
            return interval + " hour" + (interval === 1 ? "" : "s") + " ago";
        }
        
        interval = Math.floor(seconds / 60);
        if (interval >= 1) {
            return interval + " minute" + (interval === 1 ? "" : "s") + " ago";
        }
        
        return "just now";
    }
    
    // Initial fetch and then poll for updates
    fetchNotifications();
    
    // Check for new notifications every 30 seconds
    setInterval(fetchNotifications, 30000);
});
</script>
<?php endif; ?> 