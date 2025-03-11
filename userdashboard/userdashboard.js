document.addEventListener('DOMContentLoaded', function() {
    // Profile dropdown functionality
    const profilePic = document.querySelector('.profile-pic');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    profilePic.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdownMenu.classList.toggle('show');
    });

    document.addEventListener('click', function(e) {
        if (!dropdownMenu.contains(e.target)) {
            dropdownMenu.classList.remove('show');
        }
    });

    // Search and filter functionality
    const searchInput = document.getElementById('search');
    const locationInput = document.getElementById('location');
    const minSalaryInput = document.getElementById('minsalary');
    const maxSalaryInput = document.getElementById('maxsalary');
    const dateInput = document.getElementById('date');
    const jobCards = document.querySelectorAll('.job-card');

    function filterJobs() {
        const searchTerm = searchInput.value.toLowerCase();
        const location = locationInput.value.toLowerCase();
        const minSalary = parseInt(minSalaryInput.value) || 0;
        const maxSalary = parseInt(maxSalaryInput.value) || Infinity;
        const date = dateInput.value;

        jobCards.forEach(card => {
            const title = card.querySelector('h3').textContent.toLowerCase();
            const jobLocation = card.querySelector('.location').textContent.toLowerCase();
            const salary = parseInt(card.querySelector('.salary').textContent.replace(/[^0-9]/g, ''));
            const jobDate = card.querySelector('.date').textContent;

            const matchesSearch = title.includes(searchTerm);
            const matchesLocation = !location || jobLocation.includes(location);
            const matchesSalary = salary >= minSalary && salary <= maxSalary;
            const matchesDate = !date || jobDate.includes(date);

            if (matchesSearch && matchesLocation && matchesSalary && matchesDate) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Add event listeners for search and filters
    searchInput.addEventListener('input', filterJobs);
    locationInput.addEventListener('input', filterJobs);
    minSalaryInput.addEventListener('input', filterJobs);
    maxSalaryInput.addEventListener('input', filterJobs);
    dateInput.addEventListener('input', filterJobs);
}); 