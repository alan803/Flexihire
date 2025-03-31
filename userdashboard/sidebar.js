document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle functionality
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    // Function to toggle sidebar on mobile
    function toggleSidebar() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
        
        if (sidebar.classList.contains('open')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    // Add mobile menu toggle button if on mobile
    function setupMobileMenu() {
        if (window.innerWidth <= 768) {
            if (!mobileMenuToggle) {
                const toggleBtn = document.createElement('button');
                toggleBtn.id = 'mobileMenuToggle';
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                toggleBtn.className = 'mobile-menu-toggle';
                document.body.appendChild(toggleBtn);
                
                toggleBtn.addEventListener('click', toggleSidebar);
            }
        } else {
            const existingToggle = document.getElementById('mobileMenuToggle');
            if (existingToggle) {
                existingToggle.remove();
            }
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Close sidebar when clicking on overlay
    overlay.addEventListener('click', toggleSidebar);

    // Close sidebar with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            toggleSidebar();
        }
    });

    // Set active nav item based on current page
    function setActiveNavItem() {
        const currentPage = window.location.pathname.split('/').pop();
        const navItems = document.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            item.classList.remove('active');
            const link = item.querySelector('a');
            if (link && link.getAttribute('href') === currentPage) {
                item.classList.add('active');
            }
        });
    }

    // Initialize
    setupMobileMenu();
    setActiveNavItem();

    // Handle window resize
    window.addEventListener('resize', setupMobileMenu);
});

// Add mobile menu toggle styles
const mobileMenuStyles = document.createElement('style');
mobileMenuStyles.textContent = `
    .mobile-menu-toggle {
        position: fixed;
        top: 20px;
        left: 20px;
        background: var(--primary-color);
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1100;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        cursor: pointer;
    }
    
    .mobile-menu-toggle i {
        font-size: 20px;
    }
    
    @media (min-width: 769px) {
        .mobile-menu-toggle {
            display: none;
        }
    }
`;
document.head.appendChild(mobileMenuStyles);