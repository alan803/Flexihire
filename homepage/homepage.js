if (window.performance.navigation.type === 2) {
    // Disable the animation
    document.querySelector('.animated-content').style.animation = 'none';
}
// Add this to your JavaScript file
document.addEventListener('DOMContentLoaded', function() {
    const signupBtn = document.getElementById('signupBtn');
    const dropdown = document.getElementById('signupDropdown');

    // Toggle dropdown when signup button is clicked
    signupBtn.addEventListener('click', function(event) {
        event.stopPropagation();
        dropdown.classList.toggle('show');
    });

    // Close dropdown when clicking anywhere else on the page
    document.addEventListener('click', function(event) {
        if (!dropdown.contains(event.target) && !signupBtn.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });
});