// appointment.js

// Filter appointments by status
function filterAppointments() {
    const statusFilter = document.getElementById('status-filter')?.value || 'all';
    const appointmentCards = document.querySelectorAll('.appointment-card');
    let matchFound = false;

    // Remove existing no-appointments message
    document.querySelectorAll('.no-appointments').forEach(el => el.remove());

    appointmentCards.forEach(card => {
        const status = card.querySelector('.status-badge').textContent.toLowerCase();
        if (statusFilter === 'all' || status === statusFilter) {
            card.style.display = 'block';
            matchFound = true;
        } else {
            card.style.display = 'none';
        }
    });

    if (!matchFound) {
        showNoAppointmentsMessage(`No ${statusFilter === 'all' ? '' : statusFilter} appointments found.`);
    }
}

// Function to show "No appointments found" message
function showNoAppointmentsMessage(message) {
    const appointmentListings = document.querySelector('.appointment-listings');
    const noAppointmentsMessage = document.createElement('div');
    noAppointmentsMessage.className = 'no-appointments';
    
    noAppointmentsMessage.innerHTML = `
        <div class="no-appointments-content">
            <div class="calendar-icon">
                <i class="fas fa-calendar-times"></i>
            </div>
            <h2>No Appointments</h2>
            <p>${message}</p>
        </div>
    `;
    
    appointmentListings.appendChild(noAppointmentsMessage);

    // Fade out after 4 seconds
    setTimeout(() => {
        noAppointmentsMessage.style.transition = 'opacity 0.5s ease';
        noAppointmentsMessage.style.opacity = '0';
        setTimeout(() => {
            noAppointmentsMessage.remove();
        }, 500); // Remove after fade out
    }, 4000); // Show for 4 seconds
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    const statusFilter = document.getElementById('status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', filterAppointments);
    }
});