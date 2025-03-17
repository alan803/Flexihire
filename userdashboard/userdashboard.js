function filterjobs() 
{
    const search = document.getElementById('search').value.trim().toLowerCase();
    const jobCards = document.querySelectorAll('.job-card');
    let matchFound = false;

    // First remove any existing no-results message
    const existingMessage = document.querySelector('.no-jobs');
    if (existingMessage) 
    {
        existingMessage.remove();
    }

    jobCards.forEach(card => 
    {
        const title = card.querySelector('h3').textContent.toLowerCase();
        
        if (title.includes(search)) 
            {
            card.style.display = "block";
            matchFound = true;
        } 
        else 
        {
            card.style.display = "none";
        }
    });

    // If no matches found, show the message
    if (!matchFound && search !== '') 
        {
        const jobListings = document.querySelector('.job-listings');
        jobListings.innerHTML = '<div class="no-jobs">No jobs match your search criteria.</div>';
        }
}

// Add event listener when document loads
document.addEventListener('DOMContentLoaded', function() 
{
    // Add input event listener to search field
    const searchInput = document.getElementById('search');
    if (searchInput) 
    {
        searchInput.addEventListener('input', filterjobs);
    }
});
//location filter
function filterlocation() 
{
    const location = document.getElementById('location').value.trim().toLowerCase();
    const jobCards = document.querySelectorAll('.job-card');
    let matchFound = false;

    // First remove any existing no-results message
    const existingMessage = document.querySelector('.no-jobs');
    if (existingMessage) 
    {
        existingMessage.remove();
    }

    jobCards.forEach(card => 
    {
        const jobLocation = card.querySelector('.location').textContent.toLowerCase();
        
        if (jobLocation.includes(location)) 
        {
            card.style.display = "block";
            matchFound = true;
        } 
        else 
        {
            card.style.display = "none";
        }
    });

    // If no matches found, show the message
    if (!matchFound && location !== '') 
    {
        const jobListings = document.querySelector('.job-listings');
        jobListings.innerHTML = '<div class="no-jobs">No jobs found in location "' + location + '"</div>';
    }
}

//salary filter
function filtermaxsalary()
{
    const maxsalary = document.getElementById('maxsalary').value;
    const jobCards = document.querySelectorAll('.job-card');
    let matchFound = false;

    // First remove any existing no-results message
    const existingMessage = document.querySelector('.no-jobs');
    if (existingMessage) {
        existingMessage.remove();
    }

    jobCards.forEach(card => {
        const salary = card.querySelector('.salary').textContent.replace(/[^0-9]/g,'');
        
        if (salary <= maxsalary) {
            card.style.display = "block";
            matchFound = true;
        } else {
            card.style.display = "none";
        }
    });

    // If no matches found, show the message
    if (!matchFound && maxsalary !== '') {
        const jobListings = document.querySelector('.job-listings');
        jobListings.innerHTML = '<div class="no-jobs">No jobs found with salary below ₹' + maxsalary + '</div>';
    }
}

function filterminsalary() {
    const minsalary = document.getElementById('minsalary').value;
    const jobCards = document.querySelectorAll('.job-card');
    let matchFound = false;

    // First remove any existing no-results message
    const existingMessage = document.querySelector('.no-jobs');
    if (existingMessage) {
        existingMessage.remove();
    }

    jobCards.forEach(card => {
        const salary = card.querySelector('.salary').textContent.replace(/[^0-9]/g,'');
        
        if (salary >= minsalary) {
            card.style.display = "block";
            matchFound = true;
        } else {
            card.style.display = "none";
        }
    });

    // If no matches found, show the message
    if (!matchFound && minsalary !== '') {
        const jobListings = document.querySelector('.job-listings');
        jobListings.innerHTML = '<div class="no-jobs">No jobs found with salary above ₹' + minsalary + '</div>';
    }
}

function filterdate() {
    const date = document.getElementById('date').value;
    
    fetch('userdashboard.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `date=${encodeURIComponent(date)}`
    })
    .then(response => response.text())
    .then(data => {
        document.querySelector('.job-listings').innerHTML = data;
    })
    .catch(error => {
        console.error('Error:', error);
        document.querySelector('.job-listings').innerHTML = 
            '<div class="no-jobs">Error loading jobs. Please try again.</div>';
    });
}

// Add event listeners when document loads
document.addEventListener('DOMContentLoaded', function() {
    // Add input event listener to search field
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', filterjobs);
    }

    // Add input event listener to location field
    const locationInput = document.getElementById('location');
    if (locationInput) {
        locationInput.addEventListener('input', filterlocation);
    }

    // Add minimum salary listener
    const minSalaryInput = document.getElementById('minsalary');
    if (minSalaryInput) {
        minSalaryInput.addEventListener('input', filterminsalary);
    }

    // Add maximum salary listener
    const maxSalaryInput = document.getElementById('maxsalary');
    if (maxSalaryInput) {
        maxSalaryInput.addEventListener('input', filtermaxsalary);
    }

    // Add date listener
    const dateInput = document.getElementById('date');
    if (dateInput) {
        dateInput.addEventListener('input', filterdate);
    }
});