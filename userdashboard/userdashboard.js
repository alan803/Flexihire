// Search filter
function filterjobs() {
    const search = document.getElementById('search').value.trim().toLowerCase();
    const jobCards = document.querySelectorAll('.job-card');
    let matchFound = false;

    // Remove existing message
    document.querySelectorAll('.no-jobs').forEach(el => el.remove());

    if (search === '') {
        // Show all jobs if search is empty
        jobCards.forEach(card => card.style.display = "block");
        return;
    }

    jobCards.forEach(card => {
        const title = card.querySelector('h3').textContent.toLowerCase();
        if (title.includes(search)) {
            card.style.display = "block";
            matchFound = true;
        } else {
            card.style.display = "none";
        }
    });

    if (!matchFound) {
        showNoJobsMessage(`No jobs match your search criteria.`);
    }
}

// Location filter
function filterlocation() {
    const location = document.getElementById('location').value.trim().toLowerCase();
    const jobCards = document.querySelectorAll('.job-card');
    let matchFound = false;

    document.querySelectorAll('.no-jobs').forEach(el => el.remove());

    if (location === '') {
        jobCards.forEach(card => card.style.display = "block");
        return;
    }

    jobCards.forEach(card => {
        const jobLocation = card.querySelector('.location').textContent.toLowerCase();
        if (jobLocation.includes(location)) {
            card.style.display = "block";
            matchFound = true;
        } else {
            card.style.display = "none";
        }
    });

    if (!matchFound) {
        showNoJobsMessage(`No jobs found in location "${location}"`);
    }
}

// Salary filter (max)
function filtermaxsalary() {
    const maxSalary = parseInt(document.getElementById('maxsalary').value) || 0;
    const jobCards = document.querySelectorAll('.job-card');
    let matchFound = false;

    document.querySelectorAll('.no-jobs').forEach(el => el.remove());

    if (!maxSalary) {
        jobCards.forEach(card => card.style.display = "block");
        return;
    }

    jobCards.forEach(card => {
        const salary = parseInt(card.querySelector('.salary').textContent.replace(/[^0-9]/g, '')) || 0;
        if (salary <= maxSalary) {
            card.style.display = "block";
            matchFound = true;
        } else {
            card.style.display = "none";
        }
    });

    if (!matchFound) {
        showNoJobsMessage(`No jobs found with salary below ₹${maxSalary}`);
    }
}

// Salary filter (min)
function filterminsalary() {
    const minSalary = parseInt(document.getElementById('minsalary').value) || 0;
    const jobCards = document.querySelectorAll('.job-card');
    let matchFound = false;

    document.querySelectorAll('.no-jobs').forEach(el => el.remove());

    if (!minSalary) {
        jobCards.forEach(card => card.style.display = "block");
        return;
    }

    jobCards.forEach(card => {
        const salary = parseInt(card.querySelector('.salary').textContent.replace(/[^0-9]/g, '')) || 0;
        if (salary >= minSalary) {
            card.style.display = "block";
            matchFound = true;
        } else {
            card.style.display = "none";
        }
    });

    if (!matchFound) {
        showNoJobsMessage(`No jobs found with salary above ₹${minSalary}`);
    }
}

// Date filter
function filterdate() {
    const date = document.getElementById('date').value;

    fetch('userdashboard.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `date=${encodeURIComponent(date)}`
    })
    .then(response => response.text())
    .then(data => {
        document.querySelector('.job-listings').innerHTML = data;
    })
    .catch(error => {
        console.error('Error:', error);
        showNoJobsMessage('Error loading jobs. Please try again.');
    });
}

// Function to show "No jobs found" message
function showNoJobsMessage(message) {
    const jobListings = document.querySelector('.job-listings');
    const noJobsMessage = document.createElement('div');
    noJobsMessage.className = 'no-jobs';
    noJobsMessage.textContent = message;
    jobListings.appendChild(noJobsMessage);
}

// Reset Filters - show all jobs when filters are cleared
function resetFilters() {
    const jobCards = document.querySelectorAll('.job-card');
    jobCards.forEach(card => card.style.display = "block");
    document.querySelectorAll('.no-jobs').forEach(el => el.remove());
}

// Add event listeners
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('search').addEventListener('input', filterjobs);
    document.getElementById('location').addEventListener('input', filterlocation);
    document.getElementById('maxsalary').addEventListener('input', filtermaxsalary);
    document.getElementById('minsalary').addEventListener('input', filterminsalary);
    document.getElementById('date').addEventListener('input', filterdate);

    // **Reset when user clears input**
    document.getElementById('search').addEventListener('blur', resetFilters);
    document.getElementById('location').addEventListener('blur', resetFilters);
    document.getElementById('maxsalary').addEventListener('blur', resetFilters);
    document.getElementById('minsalary').addEventListener('blur', resetFilters);

    // Set up file input listeners
    const fileInputs = document.querySelectorAll('.file-input');
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            handleFileSelect(this);
        });
    });

    // Set up drag and drop
    const uploadBoxes = document.querySelectorAll('.upload-box');
    uploadBoxes.forEach(box => {
        box.addEventListener('dragover', (e) => {
            e.preventDefault();
            box.classList.add('dragover');
        });

        box.addEventListener('dragleave', () => {
            box.classList.remove('dragover');
        });

        box.addEventListener('drop', (e) => {
            e.preventDefault();
            box.classList.remove('dragover');
            const input = box.querySelector('.file-input');
            const files = e.dataTransfer.files;
            if (files.length) {
                input.files = files;
                handleFileSelect(input);
            }
        });
    });
});

function checkJobRequirements(jobId) {
    fetch('check_requirements.php?job_id=' + jobId)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showToast(data.error, 'error');
                return;
            }
            
            if (data.needsDocuments) {
                // Show upload modal
                showUploadModal(jobId, data.needsLicense, data.needsBadge);
            } else {
                // Direct application if no documents needed
                window.location.href = 'applyjob.php?job_id=' + jobId;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred. Please try again.', 'error');
        });
}

function showUploadModal(jobId, needsLicense, needsBadge) {
    const modal = document.getElementById('uploadModal');
    document.getElementById('upload_job_id').value = jobId;
    
    // Show/hide document upload sections based on requirements
    const licenseGroup = document.querySelector('.license-group');
    const badgeGroup = document.querySelector('.badge-group');
    
    if (licenseGroup) licenseGroup.style.display = needsLicense ? 'block' : 'none';
    if (badgeGroup) badgeGroup.style.display = needsBadge ? 'block' : 'none';
    
    modal.style.display = 'block';
}

function closeUploadModal() {
    const modal = document.getElementById('uploadModal');
    modal.style.display = 'none';
    // Reset form
    document.getElementById('uploadForm').reset();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('uploadModal');
    if (event.target == modal) {
        closeUploadModal();
    }
}

function handleFileSelect(input) {
    const file = input.files[0];
    const uploadBox = input.closest('.upload-box');
    const validationDiv = uploadBox.closest('.upload-container').querySelector('.validation-message');
    
    // Reset previous validation state
    resetValidationState(uploadBox);
    
    if (!file) {
        showErrorState(uploadBox, null, 'No file selected');
        return;
    }

    // Validate file
    const validationResult = validateFile(file);
    
    if (validationResult.isValid) {
        showSuccessState(uploadBox, file);
    } else {
        showErrorState(uploadBox, null, validationResult.error);
        input.value = ''; // Clear invalid file
    }
}

function showErrorState(uploadBox, fileInfo, errorMessage) {
    // Update upload box style
    uploadBox.classList.add('invalid');
    uploadBox.classList.remove('valid');
    
    // Show validation message
    const validationDiv = uploadBox.closest('.upload-container').querySelector('.validation-message');
    if (validationDiv) {
        validationDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${errorMessage}`;
        validationDiv.classList.add('error');
        validationDiv.classList.remove('success');
        validationDiv.style.display = 'block'; // Ensure it's visible
    }
}

function showSuccessState(uploadBox, file) {
    // Update upload box style
    uploadBox.classList.add('valid');
    uploadBox.classList.remove('invalid');
    
    // Show success message
    const validationDiv = uploadBox.closest('.upload-container').querySelector('.validation-message');
    if (validationDiv) {
        validationDiv.innerHTML = `<i class="fas fa-check-circle"></i> File selected successfully`;
        validationDiv.classList.add('success');
        validationDiv.classList.remove('error');
        validationDiv.style.display = 'block'; // Ensure it's visible
    }
}

function resetValidationState(uploadBox) {
    uploadBox.classList.remove('valid', 'invalid');
    const validationDiv = uploadBox.closest('.upload-container').querySelector('.validation-message');
    if (validationDiv) {
        validationDiv.innerHTML = '';
        validationDiv.classList.remove('error', 'success');
        validationDiv.style.display = 'none';
    }
}

function validateFile(file) {
    // Check file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        return {
            isValid: false,
            error: 'File size must be less than 5MB'
        };
    }

    // Check file type
    const validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!validTypes.includes(file.type)) {
        return {
            isValid: false,
            error: 'Please upload PDF, JPG or PNG files only'
        };
    }

    return {
        isValid: true
    };
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Update the form submission handler
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const jobId = document.getElementById('upload_job_id').value;
    
    fetch('process_documents.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Documents uploaded successfully', 'success');
            // Add a small delay before redirect to show the success message
            setTimeout(() => {
                window.location.href = 'userdashboard.php';
            }, 1500);
        } else {
            showToast(data.message || 'Error uploading documents', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error uploading documents. Please try again.', 'error');
    });
});

// Update showToast function if not already defined
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
