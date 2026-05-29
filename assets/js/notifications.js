// notifications.js - Handle tournament platform notifications dynamically
document.addEventListener('DOMContentLoaded', () => {
    const notifBell = document.getElementById('notifBell');
    const notifMenu = document.getElementById('notifMenu');
    const notifCount = document.getElementById('notifCount');
    const notifList = document.getElementById('notifList');
    const notifMarkAll = document.getElementById('notifMarkAll');

    if (!notifBell || !notifMenu) return;

    const apiPath = '../../api/api/api_notifications.php';

    // Fetch notifications summary
    async function fetchNotifications() {
        try {
            const response = await fetch(`${apiPath}?action=summary`);
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            
            if (data.error) {
                console.error('API Error:', data.error);
                return;
            }

            // Update badge count
            const count = parseInt(data.unread || 0);
            if (count > 0) {
                notifCount.textContent = count;
                notifCount.style.display = 'flex';
            } else {
                notifCount.style.display = 'none';
            }

            // Populate list
            notifList.innerHTML = '';
            const items = data.items || [];
            
            if (items.length === 0) {
                notifList.innerHTML = '<div class="notif-empty">No notifications yet.</div>';
            } else {
                items.forEach(item => {
                    const unreadClass = item.unread ? 'unread' : '';
                    const notifLink = item.tournament_id ? `view_tournament.php?id=${item.tournament_id}` : '#';
                    
                    const div = document.createElement('a');
                    div.href = notifLink;
                    div.className = `notif-item ${unreadClass}`;
                    
                    // Format date nicely
                    const date = new Date(item.created_at);
                    const formattedDate = date.toLocaleDateString(undefined, { 
                        month: 'short', 
                        day: 'numeric', 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });

                    div.innerHTML = `
                        <div class="notif-title">${item.title}</div>
                        <div class="notif-message">${item.message || ''}</div>
                        <div class="notif-meta">${formattedDate}</div>
                    `;
                    notifList.appendChild(div);
                });
            }
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }

    // Toggle notification menu
    notifBell.addEventListener('click', (e) => {
        e.stopPropagation();
        const isShown = notifMenu.classList.contains('show');
        if (isShown) {
            notifMenu.classList.remove('show');
        } else {
            notifMenu.classList.add('show');
            
            // Hide profile menu if open
            const profileMenu = document.getElementById('profileMenu');
            if (profileMenu) {
                profileMenu.classList.remove('show');
            }
            
            // Fetch latest on open
            fetchNotifications();
        }
    });

    // Close on click outside
    document.addEventListener('click', (e) => {
        if (!notifMenu.contains(e.target) && e.target !== notifBell) {
            notifMenu.classList.remove('show');
        }
    });

    // Mark all as read
    if (notifMarkAll) {
        notifMarkAll.addEventListener('click', async (e) => {
            e.stopPropagation();
            try {
                const response = await fetch(`${apiPath}?action=mark_read`, { method: 'POST' });
                if (!response.ok) throw new Error('Network response was not ok');
                const data = await response.json();
                
                if (data.success) {
                    notifCount.style.display = 'none';
                    notifCount.textContent = '0';
                    
                    // Refresh display
                    fetchNotifications();
                }
            } catch (error) {
                console.error('Error marking notifications as read:', error);
            }
        });
    }

    // Initial fetch
    fetchNotifications();

    // Auto refresh notifications every 30 seconds
    setInterval(fetchNotifications, 30000);
});
