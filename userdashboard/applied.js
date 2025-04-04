// Search filter
function filterjobs() 
{
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

    jobCards.forEach(card => 
        {
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
    const maxSalary = parseInt(document.getElementById('maxsalary').value) || Infinity;
    const jobCards = document.querySelectorAll('.job-card');
    let matchFound = false;

    document.querySelectorAll('.no-jobs').forEach(el => el.remove());

    jobCards.forEach(card => {
        const salaryText = card.querySelector('.salary').textContent;
        // Remove the ₹ symbol and any commas, then convert to number
        const salary = parseInt(salaryText.replace(/[₹,]/g, '')) || 0;
        
        // Debug log
        console.log('Job salary:', salary, 'Max:', maxSalary);
        
        if (salary <= maxSalary) {
            card.style.display = "block";
            matchFound = true;
        } else {
            card.style.display = "none";
        }
    });

    if (!matchFound) {
        let message = 'No jobs found';
        if (maxSalary < Infinity) {
            message = `No jobs found with salary below ₹${maxSalary.toLocaleString()}`;
        }
        showNoJobsMessage(message);
    }
}

// Salary filter (min)
function filterminsalary() {
    applySalaryFilter();
}

// Combined salary filter function
function applySalaryFilter() {
    const minSalary = parseInt(document.getElementById('minsalary').value) || 0;
    const maxSalary = parseInt(document.getElementById('maxsalary').value) || Infinity;
    const jobCards = document.querySelectorAll('.job-card');
    let matchFound = false;

    document.querySelectorAll('.no-jobs').forEach(el => el.remove());

    jobCards.forEach(card => {
        const salaryText = card.querySelector('.salary').textContent;
        // Remove the ₹ symbol and any commas, then convert to number
        const salary = parseInt(salaryText.replace(/[₹,]/g, '')) || 0;
        
        // Debug log
        console.log('Job salary:', salary, 'Min:', minSalary, 'Max:', maxSalary);
        
        if (salary >= minSalary && salary <= maxSalary) {
            card.style.display = "block";
            matchFound = true;
        } else {
            card.style.display = "none";
        }
    });

    if (!matchFound) {
        let message = 'No jobs found';
        if (minSalary > 0 && maxSalary < Infinity) {
            message = `No jobs found with salary between ₹${minSalary.toLocaleString()} and ₹${maxSalary.toLocaleString()}`;
        } else if (minSalary > 0) {
            message = `No jobs found with salary above ₹${minSalary.toLocaleString()}`;
        } else if (maxSalary < Infinity) {
            message = `No jobs found with salary below ₹${maxSalary.toLocaleString()}`;
        }
        showNoJobsMessage(message);
    }
}

// Date filter
function filterdate() {
    const date = document.getElementById('date').value;
    
    // If date is empty, show all jobs
    if (!date) {
        fetch('userdashboard.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'date='
        })
        .then(response => response.text())
        .then(data => {
            document.querySelector('.job-listings').innerHTML = data;
        })
        .catch(error => {
            console.error('Error:', error);
            showNoJobsMessage('Error loading jobs. Please try again.');
        });
        return;
    }

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
    // Remove any existing "No jobs found" messages
    const existingMessages = document.querySelectorAll('.no-jobs');
    existingMessages.forEach(msg => msg.remove());

    // Get the jobs section container
    const jobsSection = document.querySelector('.jobs-section');
    
    // Create the message element
    const noJobsMessage = document.createElement('div');
    noJobsMessage.className = 'no-jobs';
    noJobsMessage.innerHTML = `
        <div class="no-jobs-content">
            <i class="fas fa-search"></i>
            <h3>No Jobs Found</h3>
            <p>${message}</p>
            <button class="reset-button" onclick="resetFilters()">
                <i class="fas fa-undo"></i> Reset Filters
            </button>
        </div>
    `;
    
    // Add the message to the jobs section
    jobsSection.appendChild(noJobsMessage);
}

// Add these styles to your CSS
const style = document.createElement('style');
style.textContent = `
    .no-jobs {
        width: 100%;
        padding: 40px 20px;
        text-align: center;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin: 20px 0;
    }

    .no-jobs-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }

    .no-jobs-content i {
        font-size: 48px;
        color: #9747FF;
        margin-bottom: 10px;
    }

    .no-jobs-content h3 {
        font-size: 24px;
        color: #333;
        margin: 0;
    }

    .no-jobs-content p {
        font-size: 16px;
        color: #666;
        margin: 0;
    }

    .no-jobs .reset-button {
        margin-top: 15px;
        padding: 10px 20px;
        background: #9747FF;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .no-jobs .reset-button:hover {
        background: #7c3aed;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(151, 71, 255, 0.2);
    }

    .no-jobs .reset-button i {
        font-size: 16px;
        color: white;
        margin: 0;
    }
`;
document.head.appendChild(style);

// Reset Filters - show all applied jobs when filters are cleared
function resetFilters() {
    // Reset all input fields
    document.getElementById('search').value = '';
    document.getElementById('location').value = '';
    document.getElementById('minsalary').value = '';
    document.getElementById('maxsalary').value = '';
    document.getElementById('date').value = '';

    // Fetch all jobs
    fetch('applied.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'reset=1'
    })
    .then(response => response.json())
    .then(data => {
        const jobsSection = document.querySelector('.jobs-section');
        if (data.success) {
            let html = '';
            data.jobs.forEach(job => {
                html += `
                    <div class="job-card" data-application-id="${job.application_id}">
                        <div class="job-header">
                            <h3 class="job_title">${job.job_title}</h3>
                            <span class="salary">₹${job.salary}</span>
                        </div>
                        <div class="job-body">
                            <p class="description">${job.job_description}</p>
                            <p class="location">
                                <i class="fas fa-map-marker-alt"></i> 
                                ${job.location}, ${job.town}
                            </p>
                            <p class="date">
                                <i class="fas fa-calendar-plus"></i> 
                                Posted: ${job.created_at}
                            </p>
                            <p class="date">
                                <i class="fas fa-calendar-alt"></i> 
                                Vacancy Date: ${job.vacancy_date}
                            </p>
                        </div>
                        <div class="job-footer">
                            <div class="button-group">
                                <button class="applied-btn" disabled>
                                    <i class="fas fa-check-circle"></i> Applied
                                    (${job.status})
                                </button>
                                <a href="cancel_application.php?application_id=${job.application_id}" 
                                   class="details-btn cancel-hover"
                                   onclick="openModal(${job.application_id}); return false;">
                                    <i class="fas fa-times-circle"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            });
            jobsSection.innerHTML = html;
        } else {
            jobsSection.innerHTML = '<div class="no-jobs">You haven\'t applied to any jobs yet.</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Add event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add input event listeners for all filters
    document.getElementById('search').addEventListener('input', filterjobs);
    document.getElementById('location').addEventListener('input', filterlocation);
    document.getElementById('maxsalary').addEventListener('input', filtermaxsalary);
    document.getElementById('minsalary').addEventListener('input', filterminsalary);
    document.getElementById('date').addEventListener('input', filterdate);

    // Add input validation for salary fields
    const minSalaryInput = document.getElementById('minsalary');
    const maxSalaryInput = document.getElementById('maxsalary');

    if (minSalaryInput && maxSalaryInput) {
        minSalaryInput.addEventListener('input', function() {
            const min = parseInt(this.value) || 0;
            const max = parseInt(maxSalaryInput.value) || Infinity;
            
            // Ensure min salary doesn't exceed max salary
            if (max !== Infinity && min > max) {
                this.value = max;
            }
            
            // Ensure min salary is not negative
            if (min < 0) {
                this.value = 0;
            }
        });

        maxSalaryInput.addEventListener('input', function() {
            const max = parseInt(this.value) || Infinity;
            const min = parseInt(minSalaryInput.value) || 0;
            
            // Ensure max salary is not less than min salary
            if (max < min) {
                this.value = min;
            }
            
            // Ensure max salary is not negative
            if (max < 0) {
                this.value = 0;
            }
        });
    }
});