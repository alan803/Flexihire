// Define required statuses based on database values
const ALL_STATUSES = [
    'Pending',
    'Accepted',
    'Interview Scheduled'
];

// Filter appointments by status
function filterAppointments() {
    const statusFilter = document.getElementById('status-filter')?.value || 'all';
    const appointmentCards = document.querySelectorAll('.appointment-card');
    let matchFound = false;

    console.log('Filtering by:', statusFilter);

    // Remove existing no-appointments message
    document.querySelectorAll('.no-appointments').forEach(el => el.remove());

    appointmentCards.forEach(card => {
        const status = card.getAttribute('data-status').trim();

        console.log('Card status:', status);

        if (statusFilter === 'all' || status === statusFilter) {
            card.style.display = 'block';
            matchFound = true;
        } else {
            card.style.display = 'none';
        }
    });

    if (!matchFound) {
        const message = statusFilter === 'all' 
            ? 'No appointments found' 
            : `No ${statusFilter} appointments found`;
        showNoAppointmentsMessage(message);
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
            <h2>No Appointments Found</h2>
            <p>${message}</p>
        </div>
    `;
    
    appointmentListings.appendChild(noAppointmentsMessage);
}

// Enhance the existing status filter dropdown with styling
function enhanceStatusFilter() {
    const statusFilter = document.getElementById('status-filter');
    if (!statusFilter) return;

    // Apply styles to the select element
    statusFilter.style.cssText = `
        appearance: none;
        background-color: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 36px 12px 16px;
        font-size: 0.95rem;
        color: #4b5563;
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px;
        min-width: 200px;
    `;

    // Add hover effect
    statusFilter.addEventListener('mouseover', () => {
        statusFilter.style.borderColor = '#4f46e5';
    });

    statusFilter.addEventListener('mouseout', () => {
        statusFilter.style.borderColor = '#e2e8f0';
    });

    // Add event listener for filtering
    statusFilter.addEventListener('change', (e) => {
        console.log('Selected status:', e.target.value);
        filterAppointments();
    });
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    enhanceStatusFilter();
    filterAppointments();

    document.querySelectorAll('.appointment-card').forEach(card => {
        card.classList.add('fade-in');
    });
});

// Add CSS animation
const style = document.createElement('style');
style.textContent = `
    .appointment-card {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    .appointment-card.fade-in {
        opacity: 1;
        transform: translateY(0);
    }
    .no-appointments {
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    #status-filter {
        transition: all 0.3s ease;
    }
    #status-filter:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }
`;
document.head.appendChild(style);