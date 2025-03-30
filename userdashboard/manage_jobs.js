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

    // Hide all confirmation panels initially
    const panels = document.querySelectorAll('.confirmation-panel');
    panels.forEach(panel => {
        panel.style.display = 'none';
    });

    // Overlay click handler
    document.getElementById('confirmationOverlay').addEventListener('click', function() {
        hideDeleteConfirmation();
        hideRestoreConfirmation();
        hideRejectConfirmation();
        hideAcceptConfirmation();
    });

    // Close confirmation with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideDeleteConfirmation();
            hideRestoreConfirmation();
            hideRejectConfirmation();
            hideAcceptConfirmation();
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

    // Delete Confirmation Functions
    function showDeleteConfirmation(jobId) {
        document.getElementById('confirmationOverlay').style.display = 'block';
        document.getElementById('deletePanel').style.display = 'block';
        document.getElementById('confirmDelete').href = `delete_job.php?job_id=${jobId}`;
    }

    function hideDeleteConfirmation() {
        document.getElementById('confirmationOverlay').style.display = 'none';
        document.getElementById('deletePanel').style.display = 'none';
    }

    // Restore Confirmation Functions
    function showRestoreConfirmation(jobId) {
        document.getElementById('confirmationOverlay').style.display = 'block';
        document.getElementById('restorePanel').style.display = 'block';
        document.getElementById('confirmRestore').href = `restore_jobbyadmin.php?job_id=${jobId}`;
    }

    function hideRestoreConfirmation() {
        document.getElementById('confirmationOverlay').style.display = 'none';
        document.getElementById('restorePanel').style.display = 'none';
    }

    // Reject Confirmation Functions
    function showRejectConfirmation(jobId) {
        document.getElementById('confirmationOverlay').style.display = 'block';
        document.getElementById('rejectPanel').style.display = 'block';
        document.getElementById('confirmReject').href = `reject_job.php?job_id=${jobId}`;
    }

    function hideRejectConfirmation() {
        document.getElementById('confirmationOverlay').style.display = 'none';
        document.getElementById('rejectPanel').style.display = 'none';
    }

    // Accept Confirmation Functions
    function showAcceptConfirmation(jobId) {
        document.getElementById('confirmationOverlay').style.display = 'block';
        document.getElementById('acceptPanel').style.display = 'block';
        document.getElementById('confirmAccept').href = `accept_job.php?job_id=${jobId}`;
    }

    function hideAcceptConfirmation() {
        document.getElementById('confirmationOverlay').style.display = 'none';
        document.getElementById('acceptPanel').style.display = 'none';
    }

    // Make functions globally available
    window.showDeleteConfirmation = showDeleteConfirmation;
    window.hideDeleteConfirmation = hideDeleteConfirmation;
    window.showRestoreConfirmation = showRestoreConfirmation;
    window.hideRestoreConfirmation = hideRestoreConfirmation;
    window.showRejectConfirmation = showRejectConfirmation;
    window.hideRejectConfirmation = hideRejectConfirmation;
    window.showAcceptConfirmation = showAcceptConfirmation;
    window.hideAcceptConfirmation = hideAcceptConfirmation;

    // Message auto-removal functionality
    const statusMessage = document.getElementById('statusMessage');
    if (statusMessage) {
        setTimeout(() => {
            statusMessage.style.opacity = '0';
            setTimeout(() => {
                statusMessage.remove();
            }, 300); // Wait for fade out animation to complete
        }, 3000); // Wait 3 seconds before starting fade out
    }

    // Job filtering functionality
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', () => {
            // Update active button
            document.querySelector('.filter-btn.active').classList.remove('active');
            button.classList.add('active');

            // Filter jobs
            const filter = button.dataset.filter;
            document.querySelectorAll('.job-card').forEach(card => {
                if (filter === 'all' || 
                   (filter === 'active' && card.dataset.status === 'active') ||
                   (filter === 'deactivated' && card.dataset.status === 'deactivated')) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const jobCards = document.querySelectorAll('.job-card');
            let resultsFound = false;

            jobCards.forEach(card => {
                const jobTitle = card.querySelector('h3').textContent.toLowerCase();
                const companyName = card.querySelector('.job-meta span').textContent.toLowerCase();
                const location = card.querySelectorAll('.job-meta span')[1].textContent.toLowerCase();
                
                if (jobTitle.includes(searchText) || 
                    companyName.includes(searchText) || 
                    location.includes(searchText)) {
                    card.style.display = 'flex';
                    resultsFound = true;
                } else {
                    card.style.display = 'none';
                }
            });

            // Show/hide no results message
            const noResultsElement = document.getElementById('noSearchResults');
            if (!resultsFound && searchText.length > 0) {
                noResultsElement.style.display = 'flex';
            } else {
                noResultsElement.style.display = 'none';
            }
        });
    }

    // Clear search function
    window.clearSearch = function() {
        const searchInput = document.getElementById('searchInput');
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('keyup'));
        searchInput.focus();
    };
});

// Debounce Utility
function debounce(func, wait) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}