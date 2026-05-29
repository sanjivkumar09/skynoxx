// header.js - Handle interactive header elements like the profile dropdown menu
document.addEventListener('DOMContentLoaded', () => {
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');

    if (profileBtn && profileMenu) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isShown = profileMenu.classList.contains('show');
            if (isShown) {
                profileMenu.classList.remove('show');
                profileBtn.setAttribute('aria-expanded', 'false');
            } else {
                profileMenu.classList.add('show');
                profileBtn.setAttribute('aria-expanded', 'true');
                
                // Hide notification menu if open
                const notifMenu = document.getElementById('notifMenu');
                if (notifMenu) {
                    notifMenu.classList.remove('show');
                }
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!profileMenu.contains(e.target) && e.target !== profileBtn) {
                profileMenu.classList.remove('show');
                profileBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
