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
        const shouldCollapse = window.innerWidth <= 1024 && !sidebar.classList.contains('collapsed');
        const shouldExpand = window.innerWidth > 1024 && savedSidebarState !== 'collapsed' && sidebar.classList.contains('collapsed');
        
        if (shouldCollapse) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('sidebar-collapsed');
        } else if (shouldExpand) {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('sidebar-collapsed');
        }
    };

    window.addEventListener('resize', debounce(updateSidebar, 200));
    updateSidebar();

    // Search Functionality
    const searchInput = $('#searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchQuery = searchInput.value.trim();
                window.location.href = `manage_employers.php?search=${encodeURIComponent(searchQuery)}`;
            }, 500);
        });
    }

    // Export Functionality
    const exportBtn = $('.export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', async () => {
            try {
                toggleLoading(exportBtn, true);
                const response = await fetch('export_employers.php');
                if (!response.ok) throw new Error('Export failed');
                
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `employers_export_${new Date().toISOString().slice(0,10)}.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                showNotification('Employers exported successfully', 'success');
            } catch (error) {
                console.error('Error:', error);
                showNotification('Export failed: ' + error.message, 'error');
            } finally {
                toggleLoading(exportBtn, false);
            }
        });
    }

    // Employer Action Handlers
    window.viewEmployer = (employerId) => {
        window.location.href = `view_employer.php?employer_id=${employerId}`;
    };

    window.editEmployer = (employerId) => {
        window.location.href = `edit_employer.php?employer_id=${employerId}`;
    };

    // Initialize elements
    const overlay = document.getElementById('confirmationOverlay');
    const deactivatePanel = document.getElementById('deactivatePanel');
    const activatePanel = document.getElementById('activatePanel');

    // Show deactivate confirmation
    window.showConfirmation = (employerId) => {
        overlay.style.display = 'block';
        deactivatePanel.style.display = 'block';
        document.getElementById('confirmDeactivate').href = `deactivate_employer.php?employer_id=${employerId}`;
    };

    // Hide deactivate confirmation
    window.hideConfirmation = () => {
        overlay.style.display = 'none';
        deactivatePanel.style.display = 'none';
    };

    // Show activate confirmation
    window.showActivateConfirmation = (employerId) => {
        overlay.style.display = 'block';
        activatePanel.style.display = 'block';
        document.getElementById('confirmActivate').href = `activate_employer.php?employer_id=${employerId}`;
    };

    // Hide activate confirmation
    window.hideActivateConfirmation = () => {
        overlay.style.display = 'none';
        activatePanel.style.display = 'none';
    };

    // Close on overlay click
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            if (deactivatePanel.style.display === 'block') {
                hideConfirmation();
            } else if (activatePanel.style.display === 'block') {
                hideActivateConfirmation();
            }
        }
    });

    // Notification System
    window.showNotification = (message, type) => {
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
            background: ${type === 'success' ? 'var(--success)' : 'var(--danger)'};
            animation: slideIn 0.3s ease forwards;
        `;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease forwards';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    };

    // Loading State Toggle
    function toggleLoading(element, isLoading) {
        if (isLoading) {
            element.disabled = true;
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
        } else {
            element.disabled = false;
            element.innerHTML = '<i class="fas fa-download"></i> Export Employers';
        }
    }

    // Image Loading
    const images = $$('.employer-avatar');
    images.forEach(img => {
        if (img.complete) {
            img.classList.add('loaded');
        } else {
            img.addEventListener('load', () => img.classList.add('loaded'));
            img.addEventListener('error', () => img.parentElement.style.display = 'none');
        }
    });

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
    `;
    document.head.appendChild(styleSheet);

    function showRestoreConfirmation(jobId) {
        document.getElementById('confirmationOverlay').style.display = 'block';
        document.getElementById('restorePanel').style.display = 'block';
        document.getElementById('confirmRestore').href = `restore_jobbyadmin.php?job_id=${jobId}`;
    }

    function hideRestoreConfirmation() {
        document.getElementById('confirmationOverlay').style.display = 'none';
        document.getElementById('restorePanel').style.display = 'none';
    }

    document.getElementById('confirmationOverlay').addEventListener('click', function() {
        hideRestoreConfirmation();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideRestoreConfirmation();
        }
    });

    window.showRestoreConfirmation = showRestoreConfirmation;
    window.hideRestoreConfirmation = hideRestoreConfirmation;
});

// Debounce Utility
function debounce(func, wait) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}