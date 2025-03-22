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

// Combined salary filter function
function applySalaryFilter() {
    const minSalary = parseInt(document.getElementById('minsalary').value) || 0;
    const maxSalary = parseInt(document.getElementById('maxsalary').value) || Infinity;
    const jobCards = document.querySelectorAll('.job-card');
    let matchFound = false;

    // Remove existing message
    document.querySelectorAll('.no-jobs').forEach(el => el.remove());

    // Debug logging
    console.log('Filtering with min salary:', minSalary, 'max salary:', maxSalary);

    jobCards.forEach(card => {
        // Get salary text and remove ₹ symbol and commas
        const salaryText = card.querySelector('.salary').textContent;
        const salary = parseInt(salaryText.replace(/[₹,]/g, '')) || 0;
        
        // Debug logging
        console.log('Job salary:', salary, 'from text:', salaryText);

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

// Salary filter (max)
function filtermaxsalary() {
    const maxSalary = parseInt(document.getElementById('maxsalary').value) || Infinity;
    const minSalary = parseInt(document.getElementById('minsalary').value) || 0;
    
    // Validate input
    if (maxSalary < 0) {
        document.getElementById('maxsalary').value = '';
        return;
    }
    
    // Ensure min salary doesn't exceed max salary
    if (minSalary > maxSalary) {
        document.getElementById('minsalary').value = maxSalary;
    }
    
    applySalaryFilter();
}

// Salary filter (min)
function filterminsalary() {
    const minSalary = parseInt(document.getElementById('minsalary').value) || 0;
    const maxSalary = parseInt(document.getElementById('maxsalary').value) || Infinity;
    
    // Validate input
    if (minSalary < 0) {
        document.getElementById('minsalary').value = '';
        return;
    }
    
    // Ensure max salary isn't less than min salary
    if (maxSalary < minSalary) {
        document.getElementById('maxsalary').value = minSalary;
    }
    
    applySalaryFilter();
}

// Date filter
function filterdate() {
    const date = document.getElementById('date').value;
    
    // If date is empty, show all jobs
    if (!date) {
        fetch('reportedjobs.php', {
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

    fetch('reportedjobs.php', {
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
    document.querySelectorAll('.no-jobs').forEach(el => el.remove());

    const jobListings = document.querySelector('.job-listings');
    const noJobsMessage = document.createElement('div');
    noJobsMessage.className = 'no-jobs';
    
    noJobsMessage.innerHTML = `
        <div class="no-jobs-content">
            <div class="search-icon">
                <i class="fas fa-search"></i>
            </div>
            <h2>No Jobs Found</h2>
            <p>${message}</p>
            <button class="reset-filters-btn" onclick="resetFilters()">
                <i class="fas fa-undo"></i> Reset Filters
            </button>
        </div>
    `;
    
    jobListings.appendChild(noJobsMessage);

    // Add styles dynamically
    const style = document.createElement('style');
    style.textContent = `
        .no-jobs {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            text-align: center;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
        }

        .no-jobs-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            max-width: 400px;
        }

        .search-icon {
            width: 64px;
            height: 64px;
            background: #f0e6ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }

        .search-icon i {
            font-size: 32px;
            color: #9747FF;
        }

        .no-jobs h2 {
            font-size: 24px;
            color: #333;
            margin: 0;
            font-weight: 600;
        }

        .no-jobs p {
            color: #666;
            margin: 0;
            font-size: 16px;
        }

        .no-jobs .reset-filters-btn {
            background: #9747FF;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-top: 8px;
        }

        .no-jobs .reset-filters-btn:hover {
            background: #8034ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(151, 71, 255, 0.2);
        }

        .no-jobs .reset-filters-btn i {
            font-size: 16px;
        }
    `;

    // Only add the style tag if it hasn't been added before
    if (!document.querySelector('style[data-no-jobs-style]')) {
        style.setAttribute('data-no-jobs-style', '');
        document.head.appendChild(style);
    }
}

// Reset Filters - show all reported jobs when filters are cleared
function resetFilters() {
    // Reset all input fields
    document.getElementById('search').value = '';
    document.getElementById('location').value = '';
    document.getElementById('minsalary').value = '';
    document.getElementById('maxsalary').value = '';
    document.getElementById('date').value = '';

    // Fetch all reported jobs
    fetch('reportedjobs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'reset=1'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        const jobListings = document.querySelector('.job-listings');
        if (data.success) {
            let html = '';
            data.jobs.forEach(job => {
                const statusClass = job.report_status.toLowerCase();
                html += `
                    <div class="job-card" data-job-id="${job.job_id}">
                        <div class="job-header">
                            <h3 class="job_title">${job.job_title}</h3>
                            <span class="salary">₹${job.salary}</span>
                        </div>
                        <div class="job-body">
                            <p class="description">${job.job_description}</p>
                            <p class="location"><i class="fas fa-map-marker-alt"></i> ${job.location}</p>
                            <p class="date"><i class="fas fa-calendar-alt"></i> Posted: ${job.created_at}</p>
                        </div>
                        <div class="job-footer">
                            <button class="status-btn ${statusClass}">
                                <i class="fas fa-clock"></i> Status: ${job.report_status}
                            </button>
                        </div>
                    </div>
                `;
            });
            jobListings.innerHTML = html;
        } else {
            jobListings.innerHTML = '<div class="no-jobs">No reported jobs found.</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const jobListings = document.querySelector('.job-listings');
        jobListings.innerHTML = '<div class="no-jobs">Error loading jobs. Please try again.</div>';
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
});
