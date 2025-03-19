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
});
