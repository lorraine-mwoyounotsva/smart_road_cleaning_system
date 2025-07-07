// Fetch and display notifications
function loadNotifications() {
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            const container = document.querySelector('.notifications-container');
            if (container) {
                container.innerHTML = '';
                data.forEach(notification => {
                    const item = document.createElement('div');
                    item.className = `notification-item ${notification.is_read ? '' : 'unread'}`;
                    item.innerHTML = `
                        <div class="notification-title">${notification.title}</div>
                        <div>${notification.message}</div>
                        <div class="notification-time">
                            ${new Date(notification.created_at).toLocaleString()}
                        </div>
                    `;
                    container.appendChild(item);
                    
                    // Mark as read when clicked
                    item.addEventListener('click', () => {
                        markNotificationAsRead(notification.id);
                        item.classList.remove('unread');
                    });
                });
            }
        });
}

function markNotificationAsRead(id) {
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}`
    });
}

// Check for new notifications every 60 seconds
function setupNotificationPolling() {
    loadNotifications();
    setInterval(loadNotifications, 60000);
}

document.addEventListener('DOMContentLoaded', setupNotificationPolling);