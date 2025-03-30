document.addEventListener('DOMContentLoaded', () => {
    // Utility Functions
    const $ = (selector) => document.querySelector(selector);
    const $$ = (selector) => document.querySelectorAll(selector);

    // Sidebar Toggle for Mobile
    const sidebar = $('.sidebar');
    const mainContent = $('.main-content');
    const menuToggle = $('.menu-toggle');

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
    const searchForm = document.querySelector('.search-box');
    const searchInput = document.querySelector('.search-box input');
    const employersTableContainer = document.querySelector('.users-table-container');
    let searchTimeout;
    
    // Handle search input
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const searchTerm = e.target.value.trim();
            
            // Update search parameter
            const searchParam = new URLSearchParams(window.location.search);
            if (searchTerm) {
                searchParam.set('search', searchTerm);
            } else {
                searchParam.delete('search');
            }
            searchParam.set('page', '1');
            
            // Debounce the search
            searchTimeout = setTimeout(() => {
                // Use fetch to get search results
                fetch(`${window.location.pathname}?${searchParam.toString()}`)
                    .then(response => response.text())
                    .then(html => {
                        // Create a temporary container
                        const tempContainer = document.createElement('div');
                        tempContainer.innerHTML = html;
                        
                        // Extract the table container content
                        const newTableContent = tempContainer.querySelector('.users-table-container').innerHTML;
                        
                        // Update the table container with new content
                        employersTableContainer.innerHTML = newTableContent;
                        
                        // Update URL without page reload
                        window.history.pushState({}, '', `${window.location.pathname}?${searchParam.toString()}`);
                    })
                    .catch(error => {
                        console.error('Search failed:', error);
                    });
            }, 500);
        });

        // Prevent form submission on enter
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
        });
    }
    
    // Handle clear search
    window.clearSearch = function() {
        if (searchInput) {
            // Clear the search input
            searchInput.value = '';
            
            // Update URL without page reload
            window.history.pushState({}, '', window.location.pathname);
            
            // Fetch fresh content
            fetch(window.location.pathname)
                .then(response => response.text())
                .then(html => {
                    const tempContainer = document.createElement('div');
                    tempContainer.innerHTML = html;
                    const newTableContent = tempContainer.querySelector('.users-table-container').innerHTML;
                    employersTableContainer.innerHTML = newTableContent;
                })
                .catch(error => {
                    console.error('Clear search failed:', error);
                });
        }
    };

    // Employer Action Handlers
    window.viewEmployer = (employerId) => {
        window.location.href = `view_employer.php?id=${employerId}`;
    };

    window.editEmployer = (employerId) => {
        window.location.href = `edit_employer.php?id=${employerId}`;
    };

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

    // Deactivation form handling
    const deactivateForm = document.getElementById('deactivateForm');
    const reasonTextarea = document.getElementById('deactivateReason');
    const charCount = document.getElementById('charCount');
    const reasonError = document.getElementById('reasonError');
    const submitBtn = document.getElementById('submitBtn');

    if (reasonTextarea) {
        reasonTextarea.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = `${length}/500`;
            
            if (length < 10) {
                reasonError.textContent = 'Please provide a detailed reason (minimum 10 characters)';
                submitBtn.disabled = true;
            } else {
                reasonError.textContent = '';
                submitBtn.disabled = false;
            }
        });
    }

    // Show deactivation confirmation
    window.showConfirmation = (employerId) => {
        const deactivatePanel = document.getElementById('deactivatePanel');
        const overlay = document.getElementById('confirmationOverlay');
        const employerIdInput = document.getElementById('deactivateEmployerId');

        employerIdInput.value = employerId;
        deactivatePanel.style.display = 'block';
        overlay.style.display = 'block';
        
        requestAnimationFrame(() => {
            deactivatePanel.classList.add('show');
            overlay.classList.add('show');
        });
    };

    // Hide deactivation confirmation
    window.hideConfirmation = () => {
        const deactivatePanel = document.getElementById('deactivatePanel');
        const overlay = document.getElementById('confirmationOverlay');
        
        deactivatePanel.classList.remove('show');
        overlay.classList.remove('show');
        
        setTimeout(() => {
            deactivatePanel.style.display = 'none';
            overlay.style.display = 'none';
            // Reset form
            if (deactivateForm) {
                deactivateForm.reset();
                reasonError.textContent = '';
                charCount.textContent = '0/500';
            }
        }, 300);
    };

    // Show activation confirmation
    window.showActivateConfirmation = (employerId) => {
        const activatePanel = document.getElementById('activatePanel');
        const overlay = document.getElementById('confirmationOverlay');
        const confirmBtn = document.getElementById('confirmActivate');
        
        confirmBtn.href = `activate_employer.php?employer_id=${employerId}`;
        activatePanel.style.display = 'block';
        overlay.style.display = 'block';
        
        requestAnimationFrame(() => {
            activatePanel.classList.add('show');
            overlay.classList.add('show');
        });
    };

    // Hide activation confirmation
    window.hideActivateConfirmation = () => {
        const activatePanel = document.getElementById('activatePanel');
        const overlay = document.getElementById('confirmationOverlay');
        
        activatePanel.classList.remove('show');
        overlay.classList.remove('show');
        
        setTimeout(() => {
            activatePanel.style.display = 'none';
            overlay.style.display = 'none';
        }, 300);
    };

    // Close panels on overlay click
    const overlay = document.getElementById('confirmationOverlay');
    if (overlay) {
        overlay.addEventListener('click', () => {
            hideConfirmation();
            hideActivateConfirmation();
        });
    }

    // Close panels on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            hideConfirmation();
            hideActivateConfirmation();
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