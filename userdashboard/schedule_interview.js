document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const dateInput = document.querySelector('input[name="appointment_date"]');
    const timeInput = document.querySelector('input[name="appointment_time"]');
    const interviewTypeSelect = document.getElementById('interview_type');
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    dateInput.setAttribute('min', today);

    // Get dates from data attributes
    const vacancyDate = new Date(dateInput.dataset.vacancyDate);
    const deadlineDate = new Date(dateInput.dataset.deadlineDate);

    // Validate User ID
    document.querySelector('input[name="user_id"]').addEventListener('input', function(e) {
        const value = e.target.value;
        const errorDiv = getErrorDiv(this);
        
        if (!value) {
            showError(this, 'User ID is required');
        } else if (value <= 0) {
            showError(this, 'User ID must be a positive number');
        } else {
            hideError(this);
        }
    });

    // Validate Job ID
    document.querySelector('input[name="job_id"]').addEventListener('input', function(e) {
        const value = e.target.value;
        const errorDiv = getErrorDiv(this);
        
        if (!value) {
            showError(this, 'Job ID is required');
        } else if (value <= 0) {
            showError(this, 'Job ID must be a positive number');
        } else {
            hideError(this);
        }
    });

    // Function to show error message
    function showError(element, message) {
        let errorDiv = getErrorDiv(element);
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            element.parentNode.appendChild(errorDiv);
        }
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        element.classList.add('error');
    }

    // Validate date on input
    dateInput.addEventListener('input', function(e) {
        const selectedDate = new Date(this.value);
        selectedDate.setHours(0, 0, 0, 0);

        // Remove any existing error message
        const existingError = dateInput.parentElement.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }

        // Validation checks
        if (selectedDate < today) {
            showError(this, 'Please select a future date');
            return false;
        }
        
        if (selectedDate <= deadlineDate) {
            showError(this, 'Please select a date after the application deadline');
            return false;
        }
        
        if (selectedDate >= vacancyDate) {
            showError(this, 'Please select a date before the vacancy date');
            return false;
        }
    });

    // Validate Time
    timeInput.addEventListener('input', function(e) {
        const selectedTime = e.target.value;
        const [hours, minutes] = selectedTime.split(':');
        
        if (hours < 9 || hours > 17) {
            showError(this, 'Please select a time between 9 AM and 5 PM');
        } else {
            hideError(this);
        }
    });

    // Validate Interview Type specific fields
    interviewTypeSelect.addEventListener('change', function(e) {
        const type = e.target.value;
        hideError(this);
        
        if (type === 'Physical') {
            validatePhysicalFields();
        } else if (type === 'Online') {
            validateOnlineFields();
        } else if (type === 'Phone') {
            validatePhoneFields();
        }
    });

    // Physical Interview Validation
    function validatePhysicalFields() {
        const locationInput = document.querySelector('input[name="location"]');
        locationInput.addEventListener('input', function(e) {
            if (!e.target.value.trim()) {
                showError(this, 'Location is required');
            } else if (e.target.value.length < 10) {
                showError(this, 'Please enter a detailed location');
            } else {
                hideError(this);
            }
        });
    }

    // Online Interview Validation
    function validateOnlineFields() {
        const linkInput = document.querySelector('input[name="meeting_link"]');
        linkInput.addEventListener('input', function(e) {
            const urlPattern = /^https?:\/\/.+/i;
            if (!e.target.value) {
                showError(this, 'Meeting link is required');
            } else if (!urlPattern.test(e.target.value)) {
                showError(this, 'Please enter a valid URL starting with http:// or https://');
            } else {
                hideError(this);
            }
        });
    }

    // Phone Interview Validation
    function validatePhoneFields() {
        const phoneInput = document.querySelector('input[name="phone_number"]');
        phoneInput.addEventListener('input', function(e) {
            const phonePattern = /^\d{10}$/;
            if (!e.target.value) {
                showError(this, 'Phone number is required');
            } else if (!phonePattern.test(e.target.value)) {
                showError(this, 'Please enter a valid 10-digit phone number');
            } else {
                hideError(this);
            }
        });
    }

    // Add Notes Validation
    const notesTextarea = document.querySelector('textarea[name="notes"]');
    notesTextarea.addEventListener('input', function(e) {
        const value = e.target.value;
        // Regular expression to allow only letters, numbers, spaces, and basic punctuation
        const validPattern = /^[a-zA-Z0-9\s.,!?()-]*$/;
        
        if (!validPattern.test(value)) {
            showError(this, 'Special characters are not allowed in notes');
            // Remove the invalid characters
            this.value = value.replace(/[^a-zA-Z0-9\s.,!?()-]/g, '');
        } else {
            hideError(this);
        }
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        const selectedDate = new Date(dateInput.value);
        selectedDate.setHours(0, 0, 0, 0);

        if (selectedDate <= deadlineDate || selectedDate >= vacancyDate || selectedDate < today) {
            e.preventDefault();
            showError(dateInput, 'Please select a valid date between application deadline and vacancy date');
        }
    });

    // Utility Functions
    function hideError(element) {
        const errorDiv = getErrorDiv(element);
        if (errorDiv) {
            errorDiv.remove();
        }
        element.classList.remove('error');
    }

    function getErrorDiv(element) {
        return element.parentNode.querySelector('.error-message');
    }

    // Update the showFields function
    function showFields() {
        const interviewType = document.getElementById('interview_type').value;
        const physicalFields = document.getElementById('physical_fields');
        const onlineFields = document.getElementById('online_fields');
        const phoneFields = document.getElementById('phone_fields');

        // First hide all fields
        physicalFields.style.display = 'none';
        onlineFields.style.display = 'none';
        phoneFields.style.display = 'none';

        // Remove show class from all
        physicalFields.classList.remove('show');
        onlineFields.classList.remove('show');
        phoneFields.classList.remove('show');

        // Show the selected field with animation
        switch(interviewType) {
            case 'Physical':
                physicalFields.style.display = 'block';
                setTimeout(() => physicalFields.classList.add('show'), 10);
                break;
            case 'Online':
                onlineFields.style.display = 'block';
                setTimeout(() => onlineFields.classList.add('show'), 10);
                break;
            case 'Phone':
                phoneFields.style.display = 'block';
                setTimeout(() => phoneFields.classList.add('show'), 10);
                break;
        }
    }

    // Add event listener to the dropdown
    if(interviewTypeSelect) {
        interviewTypeSelect.addEventListener('change', showFields);
        // Show initial fields
        showFields();
    }
});