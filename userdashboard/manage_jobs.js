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

    // Export Functionality
    const exportBtn = $('.export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', async () => {
            try {
                toggleLoading(exportBtn, true);
                const response = await fetch('export_jobs.php');
                if (!response.ok) throw new Error('Export failed');
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `jobs_export_${new Date().toISOString().slice(0,10)}.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                showNotification('Jobs exported successfully', 'success');
            } catch (error) {
                showNotification('Export failed: ' + error.message, 'error');
            } finally {
                toggleLoading(exportBtn, false);
            }
        });
    }

    // Hide confirmation panel initially
    const deactivatePanel = document.getElementById('deactivatePanel');
    const activatePanel = document.getElementById('activatePanel');
    const overlay = document.getElementById('confirmationOverlay');
    const rejectPanel = document.getElementById('rejectPanel');

    deactivatePanel.style.display = 'none';
    activatePanel.style.display = 'none';
    overlay.style.display = 'none';
    rejectPanel.style.display = 'none';

    window.showConfirmation = (jobId) => {
        const panel = document.getElementById('deactivatePanel');
        const overlay = document.getElementById('confirmationOverlay');
        const confirmLink = document.getElementById('confirmDeactivate');
        confirmLink.href = `deactivate_job.php?job_id=${jobId}`;
        overlay.style.display = 'block';
        panel.style.display = 'block';
    };

    window.hideConfirmation = () => {
        const panel = document.getElementById('deactivatePanel');
        const overlay = document.getElementById('confirmationOverlay');
        overlay.style.display = 'none';
        panel.style.display = 'none';
    };

    window.showActivateConfirmation = (jobId) => {
        const panel = document.getElementById('activatePanel');
        const overlay = document.getElementById('confirmationOverlay');
        const confirmLink = document.getElementById('confirmActivate');
        confirmLink.href = `activate_job.php?job_id=${jobId}`;
        overlay.style.display = 'block';
        panel.style.display = 'block';
    };

    window.hideActivateConfirmation = () => {
        const panel = document.getElementById('activatePanel');
        const overlay = document.getElementById('confirmationOverlay');
        overlay.style.display = 'none';
        panel.style.display = 'none';
    };

    // Show reject confirmation
    window.showRejectConfirmation = (jobId) => {
        overlay.style.display = 'block';
        rejectPanel.style.display = 'block';
        document.getElementById('confirmReject').href = `reject_job.php?job_id=${jobId}`;
    };

    // Hide reject confirmation
    window.hideRejectConfirmation = () => {
        overlay.style.display = 'none';
        rejectPanel.style.display = 'none';
    };

    // Update overlay click handler
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            if (deactivatePanel.style.display === 'block') {
                hideConfirmation();
            } else if (activatePanel.style.display === 'block') {
                hideActivateConfirmation();
            } else if (rejectPanel.style.display === 'block') {
                hideRejectConfirmation();
            }
        }
    });

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
            element.innerHTML = '<i class="fas fa-download"></i> Export Jobs';
        }
    }

    // Animation Keyframes
    const styleSheet = document.createElement('style');
    styleSheet.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
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