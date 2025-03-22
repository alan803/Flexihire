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
    const minSalary = parseInt(document.getElementById('minsalary').value) || 0;
    const jobCards = document.querySelectorAll('.job-card');
    let matchFound = false;

    document.querySelectorAll('.no-jobs').forEach(el => el.remove());

    jobCards.forEach(card => {
        const salaryText = card.querySelector('.salary').textContent;
        // Remove the ₹ symbol and any commas, then convert to number
        const salary = parseInt(salaryText.replace(/[₹,]/g, '')) || 0;
        
        // Debug log
        console.log('Job salary:', salary, 'Min:', minSalary);
        
        if (salary >= minSalary) {
            card.style.display = "block";
            matchFound = true;
        } else {
            card.style.display = "none";
        }
    });

    if (!matchFound) {
        let message = 'No jobs found';
        if (minSalary > 0) {
            message = `No jobs found with salary above ₹${minSalary.toLocaleString()}`;
        }
        showNoJobsMessage(message);
    }
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

        .reset-filters-btn {
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

        .reset-filters-btn:hover {
            background: #8034ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(151, 71, 255, 0.2);
        }

        .reset-filters-btn i {
            font-size: 16px;
        }
    `;

    // Only add the style tag if it hasn't been added before
    if (!document.querySelector('style[data-no-jobs-style]')) {
        style.setAttribute('data-no-jobs-style', '');
        document.head.appendChild(style);
    }
}

// Reset Filters - show all bookmarked jobs when filters are cleared
function resetFilters() {
    // Clear all input values
    document.getElementById('search').value = '';
    document.getElementById('location').value = '';
    document.getElementById('minsalary').value = '';
    document.getElementById('maxsalary').value = '';
    document.getElementById('date').value = '';

    // Remove any "No jobs found" messages
    const noJobsMessages = document.querySelectorAll('.no-jobs');
    noJobsMessages.forEach(msg => msg.remove());

    // Get the current page URL to determine which page we're on
    const currentPage = window.location.pathname.split('/').pop();
    
    // Make a fetch request to reload the bookmarked jobs
    fetch(currentPage, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'reset=1'
    })
    .then(response => response.text())
    .then(html => {
        // Create a temporary div to parse the HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // Find the job listings container in the response
        const newJobListings = tempDiv.querySelector('.job-listings');
        
        if (newJobListings) {
            // Update the job listings container
            document.querySelector('.job-listings').innerHTML = newJobListings.innerHTML;
            
            // Show all job cards
            const jobCards = document.querySelectorAll('.job-card');
            jobCards.forEach(card => {
                card.style.display = 'block';
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNoJobsMessage('Error loading bookmarked jobs. Please try again.');
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