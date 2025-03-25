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

    // Modal Functions
    const modals = {
        report: $('#reportModal'),
        resolve: $('#resolveModal'),
        reject: $('#rejectModal')
    };

    function showModal(modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        // Close all modals and redirect
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
        
        // Clear form inputs
        const forms = document.querySelectorAll('form');
        forms.forEach(form => form.reset());

        // Redirect back to reports.php
        window.location.href = 'reports.php';
    }

    // Close modal when clicking outside
    Object.values(modals).forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    });

    // Close modal with escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    // View Report Details
    window.viewReport = async (reportId) => {
        try {
            const response = await fetch(`get_report_details.php?report_id=${reportId}`);
            const data = await response.json();
            
            if (data.success) {
                const report = data.report;
                const detailsHtml = `
                    <div class="report-details">
                        <div class="detail-group reporter-header">
                            <div class="reporter-avatar">
                                ${report.reporter_picture ? 
                                    `<img src="${report.reporter_picture}" alt="Profile Picture">` :
                                    `<div class="avatar-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>`
                                }
                            </div>
                            <div class="reporter-info">
                                <span class="reporter-name">${report.reporter_name}</span>
                                <span class="reporter-email">${report.reporter_email}</span>
                            </div>
                        </div>
                        <div class="detail-group">
                            <label>Report Type</label>
                            <span class="type-badge ${report.report_type.toLowerCase()}">${report.report_type}</span>
                        </div>
                        <div class="detail-group">
                            <label>Reported Entity</label>
                            <span>${report.reported_entity}</span>
                        </div>
                        <div class="detail-group">
                            <label>Reason</label>
                            <div class="message-content">${report.reason}</div>
                        </div>
                        <div class="detail-group">
                            <label>Status</label>
                            <span class="status-badge ${report.status.toLowerCase()}">${report.status}</span>
                        </div>
                        <div class="detail-group">
                            <label>Date Reported</label>
                            <span>${new Date(report.created_at).toLocaleString()}</span>
                        </div>
                        ${report.resolution_notes ? `
                            <div class="detail-group">
                                <label>Resolution Notes</label>
                                <div class="resolution-notes">${report.resolution_notes}</div>
                            </div>
                        ` : ''}
                    </div>
                `;
                
                $('#reportDetails').innerHTML = detailsHtml;
                showModal(modals.report);
            } else {
                showAlert('error', 'Failed to load report details');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('error', 'An error occurred while loading report details');
        }
    };

    // Show Resolve Modal
    window.showResolveModal = (reportId) => {
        const modal = document.getElementById('resolveModal');
        modal.style.display = 'block';
        document.getElementById('resolveReportId').value = reportId;
        
        // Add event listeners for this specific modal
        const closeBtn = modal.querySelector('.close-btn');
        const cancelBtn = modal.querySelector('.cancel-btn');
        const textarea = modal.querySelector('textarea');
        
        // Add validation for textarea
        if (textarea) {
            textarea.addEventListener('input', validateInput);
            textarea.addEventListener('paste', handlePaste);
        }
        
        if (closeBtn) {
            closeBtn.onclick = function() {
                window.location.href = 'reports.php';
            }
        }
        
        if (cancelBtn) {
            cancelBtn.onclick = function() {
                window.location.href = 'reports.php';
            }
        }
        
        // Close on outside click
        window.onclick = function(event) {
            if (event.target == modal) {
                window.location.href = 'reports.php';
            }
        }
    };

    // Show Reject Modal
    window.showRejectModal = (reportId) => {
        const modal = document.getElementById('rejectModal');
        modal.style.display = 'block';
        document.getElementById('rejectReportId').value = reportId;
        
        // Add event listeners for reject modal
        const closeBtn = modal.querySelector('.close-btn');
        const cancelBtn = modal.querySelector('.cancel-btn');
        const textarea = modal.querySelector('textarea');
        
        // Add validation for textarea
        if (textarea) {
            textarea.addEventListener('input', validateInput);
            textarea.addEventListener('paste', handlePaste);
        }
        
        if (closeBtn) {
            closeBtn.onclick = function() {
                window.location.href = 'reports.php';
            }
        }
        
        if (cancelBtn) {
            cancelBtn.onclick = function() {
                window.location.href = 'reports.php';
            }
        }
        
        // Close on outside click for reject modal
        window.onclick = function(event) {
            if (event.target == modal) {
                window.location.href = 'reports.php';
            }
        }
    };

    // Alert Function
    function showAlert(type, message) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        const mainContent = $('.main-content');
        mainContent.insertBefore(alert, mainContent.firstChild);
        
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }

    // Add animation styles
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

    // Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Handle form submissions
        const resolveForm = document.getElementById('resolveForm');
        const rejectForm = document.getElementById('rejectForm');
        
        if (resolveForm) {
            resolveForm.addEventListener('submit', function(event) {
                // Form will submit normally
            });
        }
        
        if (rejectForm) {
            rejectForm.addEventListener('submit', function(event) {
                // Form will submit normally
            });
        }
        
        // Handle Escape key for both modals
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const activeModal = document.querySelector('.modal[style*="display: block"]');
                if (activeModal) {
                    window.location.href = 'reports.php';
                }
            }
        });
    });

    // Input validation function
    function validateInput(event) {
        const textarea = event.target;
        const originalValue = textarea.value;
        
        // Remove special characters except basic punctuation
        const sanitizedValue = originalValue.replace(/[^\w\s.,!?-]/g, '');
        
        if (originalValue !== sanitizedValue) {
            textarea.value = sanitizedValue;
            
            // Show validation message
            showValidationMessage(textarea, 'Special characters are not allowed');
        } else {
            hideValidationMessage(textarea);
        }
    }

    // Handle paste events
    function handlePaste(event) {
        event.preventDefault();
        const text = (event.originalEvent || event).clipboardData.getData('text/plain');
        const sanitizedText = text.replace(/[^\w\s.,!?-]/g, '');
        document.execCommand('insertText', false, sanitizedText);
    }

    // Show validation message
    function showValidationMessage(element, message) {
        let validationMessage = element.parentElement.querySelector('.validation-message');
        
        if (!validationMessage) {
            validationMessage = document.createElement('div');
            validationMessage.className = 'validation-message';
            element.parentElement.appendChild(validationMessage);
        }
        
        validationMessage.textContent = message;
        validationMessage.style.display = 'block';
        
        // Add error state to textarea
        element.classList.add('error');
    }

    // Hide validation message
    function hideValidationMessage(element) {
        const validationMessage = element.parentElement.querySelector('.validation-message');
        if (validationMessage) {
            validationMessage.style.display = 'none';
        }
        
        // Remove error state from textarea
        element.classList.remove('error');
    }

    // Add these functions to your existing JavaScript
    function sendEmail(userId) {
        // Redirect to email page with user ID
        window.location.href = `send_email.php?user_id=${userId}`;
    }

    function confirmDeactivate(userId) {
        if (confirm('Are you sure you want to deactivate this account?')) {
            window.location.href = `deactivate_account.php?user_id=${userId}`;
        }
    }
});

// Debounce Utility
function debounce(func, wait) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}
