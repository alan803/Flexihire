document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables for filters
    let searchTimeout;
    const jobCards = document.querySelectorAll('.job-card');
    const noResultsMessage = document.createElement('div');
    noResultsMessage.className = 'no-results';
    noResultsMessage.innerHTML = `
        <i class="fas fa-search"></i>
        <h3>No jobs found</h3>
        <p>Try adjusting your search criteria</p>
    `;
    document.querySelector('.job-card-container').appendChild(noResultsMessage);
    noResultsMessage.style.display = 'none';
    
    // Applicants Sidebar Toggle
    const applicantsToggle = document.getElementById('applicantsToggle');
    const closeSidebar = document.getElementById('closeSidebar');
    const applicantsSidebar = document.getElementById('applicantsSidebar');
    const body = document.body;
    
    // Create overlay element
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    body.appendChild(overlay);
    
    // Open sidebar
    if (applicantsToggle) {
        applicantsToggle.addEventListener('click', function() {
            applicantsSidebar.classList.add('open');
            overlay.classList.add('active');
            // Don't prevent scrolling on the main content
            applicantsSidebar.style.overflowY = 'auto';
        });
    }
    
    // Close sidebar
    if (closeSidebar) {
        closeSidebar.addEventListener('click', function() {
            applicantsSidebar.classList.remove('open');
            overlay.classList.remove('active');
            applicantsSidebar.style.overflowY = 'hidden';
        });
    }
    
    // Close sidebar when clicking on overlay
    overlay.addEventListener('click', function() {
        applicantsSidebar.classList.remove('open');
        overlay.classList.remove('active');
        applicantsSidebar.style.overflowY = 'hidden';
    });
    
    // Close sidebar with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && applicantsSidebar.classList.contains('open')) {
            applicantsSidebar.classList.remove('open');
            overlay.classList.remove('active');
            applicantsSidebar.style.overflowY = 'hidden';
        }
    });
    
    // Function to check if any jobs are visible
    function checkVisibleJobs() {
        const visibleJobs = Array.from(jobCards).some(card => card.style.display !== 'none');
        noResultsMessage.style.display = visibleJobs ? 'none' : 'flex';
    }
    
    // Search function
    window.filterjobs = function() {
        const searchInput = document.getElementById('search');
        const searchTerm = searchInput.value.toLowerCase();
        
        jobCards.forEach(card => {
            const jobTitle = card.querySelector('.job-details h3').textContent.toLowerCase();
            const jobDescription = card.querySelector('.job-description').textContent.toLowerCase();
            
            if (jobTitle.includes(searchTerm) || jobDescription.includes(searchTerm)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
        checkVisibleJobs();
    };

    // Reset search
    window.resetFilters = function() {
        // Reset search input
        document.getElementById('search').value = '';
        
        // Show all job cards
        jobCards.forEach(card => {
            card.style.display = 'flex';
        });
        noResultsMessage.style.display = 'none';
    };
});