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
        reject: $('#rejectModal'),
        deactivate: $('#deactivateModal')
    };

    function showModal(modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        Object.values(modals).forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = 'auto';
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
        $('#resolveReportId').value = reportId;
        showModal(modals.resolve);
    };

    // Show Reject Modal
    window.showRejectModal = (reportId) => {
        $('#rejectReportId').value = reportId;
        showModal(modals.reject);
    };

    // Show Deactivate Modal
    window.showDeactivateModal = (employerId, companyName) => {
        $('#deactivateEmployerId').value = employerId;
        $('#deactivateEmployerName').textContent = companyName;
        showModal(modals.deactivate);
    };

    // Close Deactivate Modal
    window.closeDeactivateModal = () => {
        modals.deactivate.style.display = 'none';
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

    // Add near the top after your existing variable declarations
    const searchInput = document.getElementById('searchInput');
    const tableContainer = document.querySelector('.table-container');
    const tableRows = document.querySelectorAll('table tbody tr');

    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            let resultsFound = false;

            // Remove existing no results message if it exists
            const existingMessage = document.querySelector('.no-results-container');
            if (existingMessage) {
                existingMessage.remove();
            }

            tableRows.forEach(row => {
                const reporterName = row.querySelector('.reporter-name').textContent.toLowerCase();
                const reporterEmail = row.querySelector('.reporter-email').textContent.toLowerCase();
                const reportedEntity = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const reason = row.querySelector('td:nth-child(4)').textContent.toLowerCase();

                if (reporterName.includes(searchText) || 
                    reporterEmail.includes(searchText) || 
                    reportedEntity.includes(searchText) || 
                    reason.includes(searchText)) {
                    row.style.display = '';
                    resultsFound = true;
                } else {
                    row.style.display = 'none';
                }
            });

            if (!resultsFound && searchText.length > 0) {
                const noResultsHtml = `
                    <div class="no-results-container">
                        <div class="no-results-content">
                            <h2>No Results Found</h2>
                            <p>We couldn't find any reports matching your search criteria.</p>
                            <button type="button" class="clear-search-btn" onclick="clearSearch()">
                                <i class="fas fa-times"></i>
                                Clear Search
                            </button>
                        </div>
                    </div>
                `;
                tableContainer.insertAdjacentHTML('beforeend', noResultsHtml);
            }
        });
    }

    // Clear search function
    window.clearSearch = function() {
        if (searchInput) {
            searchInput.value = '';
            
            // Show all rows
            tableRows.forEach(row => {
                row.style.display = '';
            });

            // Remove no results message
            const noResultsContainer = document.querySelector('.no-results-container');
            if (noResultsContainer) {
                noResultsContainer.remove();
            }

            // Focus back on search input
            searchInput.focus();
        }
    };

    // Form validation
    function validateInput(event) {
        const textarea = event.target;
        const originalValue = textarea.value;
        
        // Remove special characters except basic punctuation
        const sanitizedValue = originalValue.replace(/[^\w\s.,!?-]/g, '');
        
        if (originalValue !== sanitizedValue) {
            textarea.value = sanitizedValue;
            showValidationMessage(textarea, 'Special characters are not allowed');
        } else {
            hideValidationMessage(textarea);
        }
    }

    function handlePaste(event) {
        event.preventDefault();
        const text = (event.originalEvent || event).clipboardData.getData('text/plain');
        const sanitizedText = text.replace(/[^\w\s.,!?-]/g, '');
        document.execCommand('insertText', false, sanitizedText);
    }

    function showValidationMessage(element, message) {
        let validationMessage = element.parentElement.querySelector('.validation-message');
        
        if (!validationMessage) {
            validationMessage = document.createElement('div');
            validationMessage.className = 'validation-message';
            element.parentElement.appendChild(validationMessage);
        }
        
        validationMessage.textContent = message;
        validationMessage.style.display = 'block';
        element.classList.add('error');
    }

    function hideValidationMessage(element) {
        const validationMessage = element.parentElement.querySelector('.validation-message');
        if (validationMessage) {
            validationMessage.style.display = 'none';
        }
        element.classList.remove('error');
    }

    // Add validation to all textareas in modals
    $$('.modal textarea').forEach(textarea => {
        textarea.addEventListener('input', validateInput);
        textarea.addEventListener('paste', handlePaste);
    });

    // Debounce Utility
    function debounce(func, wait) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Message auto-removal functionality
    const statusMessage = document.getElementById('statusMessage');
    if (statusMessage) {
        setTimeout(() => {
            statusMessage.style.opacity = '0';
            setTimeout(() => {
                statusMessage.remove();
            }, 300);
        }, 3000);
    }
});