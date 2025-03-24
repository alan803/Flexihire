document.addEventListener('DOMContentLoaded', () => {
    // Utility Functions
    const $ = (selector) => document.querySelector(selector);
    const $$ = (selector) => document.querySelectorAll(selector);

    // Sidebar Toggle for Mobile
    const sidebar = $('.sidebar');
    const mainContent = $('.main-content');
    const menuToggle = $('.menu-toggle'); // Add this button in HTML if needed

    // Restore sidebar state
    const savedSidebarState = localStorage.getItem('sidebarState');
    if (savedSidebarState === 'collapsed' && window.innerWidth <= 1024) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('sidebar-collapsed');
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarState', 
                sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded'
            );
        });
    }

    // Responsive Sidebar Adjustments
    const updateSidebar = () => {
        if (window.innerWidth <= 1024 && !sidebar.classList.contains('collapsed')) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('sidebar-collapsed');
        } else if (window.innerWidth > 1024 && savedSidebarState !== 'collapsed') {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('sidebar-collapsed');
        }
    };

    window.addEventListener('resize', debounce(updateSidebar, 200));
    updateSidebar();

    // Search Functionality
    const searchInput = $('#searchInput');
    let searchTimeout;

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchQuery = searchInput.value.trim();
                window.location.href = `manage_users.php?search=${encodeURIComponent(searchQuery)}`;
            }, 500);
        });
    }

    // Export Functionality
    const exportBtn = $('.export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', async () => {
            try {
                toggleLoading(exportBtn, true);
                const response = await fetch('export_users.php');
                if (!response.ok) throw new Error('Export failed');
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `users_export_${new Date().toISOString().slice(0,10)}.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                showNotification('Users exported successfully', 'success');
            } catch (error) {
                showNotification('Export failed: ' + error.message, 'error');
            } finally {
                toggleLoading(exportBtn, false);
            }
        });
    }

    // User Action Handlers
    window.viewUser = (userId) => {
        window.location.href = `view_user.php?id=${userId}`;
    };

    window.editUser = (userId) => {
        window.location.href = `edit_user.php?id=${userId}`;
    };

    // Hide confirmation panel initially
    const panel = document.getElementById('deactivatePanel');
    const overlay = document.getElementById('confirmationOverlay');
    const dashboardContainer = document.querySelector('.dashboard-container');
    
    // Initially hide panel and overlay
    panel.style.display = 'none';
    overlay.style.display = 'none';

    // Function to show confirmation
    window.showConfirmation = (userId) => {
        const confirmBtn = document.getElementById('confirmDeactivate');
        confirmBtn.href = `deactivate_user.php?user_id=${userId}`;
        
        // Show panel and overlay
        panel.style.display = 'block';
        overlay.style.display = 'block';
        
        // Add blur effect
        dashboardContainer.style.filter = 'blur(4px)';
        dashboardContainer.style.transition = 'filter 0.3s ease';
        
        // Add show class after a brief delay for animation
        requestAnimationFrame(() => {
            panel.classList.add('show');
            overlay.classList.add('show');
        });
    };

    // Function to hide confirmation
    window.hideConfirmation = () => {
        panel.classList.remove('show');
        overlay.classList.remove('show');
        dashboardContainer.style.filter = 'none';
        
        // Hide elements after animation completes
        setTimeout(() => {
            panel.style.display = 'none';
            overlay.style.display = 'none';
        }, 300);
    };

    // Close on overlay click
    overlay.addEventListener('click', hideConfirmation);

    // Close on cancel button click
    const cancelBtn = document.querySelector('.cancel-action');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', hideConfirmation);
    }

    // Notification System
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            z-index: 1000;
            animation: slideIn 0.3s ease forwards;
            background: ${type === 'success' ? 'var(--success-color)' : 'var(--danger-color)'};
        `;

        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease forwards';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Loading State
    function toggleLoading(element, isLoading) {
        if (isLoading) {
            element.disabled = true;
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
        } else {
            element.disabled = false;
            element.innerHTML = '<i class="fas fa-download"></i> Export Users';
        }
    }

    // Animation Keyframes
    const styleSheet = document.createElement('style');
    styleSheet.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .sidebar.collapsed {
            width: 80px;
        }
        .sidebar.collapsed .logo-section h1,
        .sidebar.collapsed .nav-item span {
            display: none;
        }
        .sidebar.collapsed .nav-item {
            justify-content: center;
            padding: 1rem;
            margin: 0.3rem;
        }
        .sidebar.collapsed .nav-item i {
            margin-right: 0;
            font-size: 1.5rem;
        }
        .sidebar-collapsed {
            margin-left: 80px !important;
        }
    `;
    document.head.appendChild(styleSheet);
});

// Debounce Utility
function debounce(func, wait) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}