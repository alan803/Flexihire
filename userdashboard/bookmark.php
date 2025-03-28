<?php
session_start();
include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

// Get user data with proper error handling
$user_id = $_SESSION['user_id'];

// Validate user_id
if (!$user_id || !is_numeric($user_id)) {
    // Log the error
    error_log("Invalid or missing user_id in session. Session data: " . print_r($_SESSION, true));
    // Redirect to login
    header("Location: ../login/loginvalidation.php");
    exit();
}

$sql = "SELECT l.email, u.first_name, u.last_name, u.username, u.profile_image 
        FROM tbl_login l
        INNER JOIN tbl_user u ON l.user_id = u.user_id
        WHERE l.user_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $user_data = mysqli_fetch_assoc($result);
    $display_name = !empty($user_data['username']) ? $user_data['username'] : 
                   $user_data['first_name'] . " " . $user_data['last_name'];
    $profile_image = $user_data['profile_image'];
} else {
    // Log the error
    error_log("User not found in database for ID: $user_id");
    // Redirect to login
    header("Location: ../login/loginvalidation.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookmarked Jobs</title>
    <link rel="stylesheet" href="bookmark.css">
    <link rel="stylesheet" href="userdashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar" style="margin-bottom: 0px;">
        <div class="nav-brand">
            <img src="logowithoutbcakground.png" alt="Logo" class="logo">
            <h1>FlexiHire</h1>
        </div>
        
        <div class="nav-right">
            <div class="profile-info">
                <span class="nav-username"><?php echo htmlspecialchars($display_name); ?></span>
                <div class="profile-container">
                    <?php if (!empty($profile_image)): ?>
                        <img src="/mini project/database/profile_picture/<?php echo htmlspecialchars($profile_image); ?>" class="profile-pic" alt="Profile">
                    <?php else: ?>
                        <img src="profile.png" class="profile-pic" alt="Profile">
                    <?php endif; ?>
                    <div class="dropdown-menu">
                        <a href="profiles/user/userprofile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Add this right after the navbar -->
    <div id="toast" class="toast">
        <div class="toast-content">
            <div class="toast-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="toast-message-container">
                <div class="toast-title">Success:</div>
                <div id="toast-message">Operation completed successfully</div>
            </div>
        </div>
    </div>

    <style>
    /* Ultra-Compact Toast Message Styling */
    .toast {
        visibility: hidden;
        position: fixed;
        top: 20px;
        right: 20px;
        width: 280px; /* Fixed width */
        max-width: 90%; /* For responsiveness */
        background-color: lightgreen;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        border-radius: 4px;
        z-index: 1000;
        overflow: hidden;
        border-left: 3px solid #4CAF50;
        font-family: 'Poppins', sans-serif;
        height: 60px; /* Increased height */
    }

    .toast.show {
        visibility: visible;
        animation: slideInRight 0.3s, fadeOut 0.5s 2.5s;
    }

    .toast-content {
        display: flex;
        align-items: center;
        padding: 0 10px; /* Horizontal padding only */
        gap: 8px;
        height: 100%; /* Fill the parent height */
    }

    .toast-icon {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        height: 100%;
    }

    .toast-icon i {
        font-size: 24px; /* Slightly larger icon */
    }

    .toast.success {
        border-left-color: #4CAF50;
    }

    .toast.error {
        border-left-color: #f44336;
    }

    .toast.warning {
        border-left-color: #ff9800;
    }

    .toast.info {
        border-left-color: #2196F3;
    }

    .toast.success .toast-icon i {
        color: #4CAF50;
    }

    .toast.error .toast-icon i {
        color: #f44336;
    }

    .toast.warning .toast-icon i {
        color: #ff9800;
    }

    .toast.info .toast-icon i {
        color: #2196F3;
    }

    .toast-message-container {
        flex-grow: 1;
        min-width: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        height: 100%;
    }

    .toast-title {
        font-weight: 600;
        color: #333;
        font-size: 12px; /* Larger font */
        margin-right: 4px;
    }

    #toast-message {
        color: #666;
        font-size: 12px; /* Larger font */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin: 0;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
        }
        to {
            transform: translateX(0);
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
        }
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .toast {
            width: 250px;
            left: 50%;
            right: auto;
            transform: translateX(-50%);
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(-50%) translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(-50%) translateY(0);
                opacity: 1;
            }
        }
    }

    .nav-right {
        display: flex;
        align-items: center;
    }

    .profile-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .nav-username {
        color: #333;
        font-weight: 500;
        font-size: 0.95rem;
    }

    .profile-container {
        display: flex;
        align-items: center;
        position: relative;
        cursor: pointer;
    }

    .profile-pic {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: none;
        outline: none;
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-radius: 8px;
        padding: 8px 0;
        min-width: 180px;
        z-index: 1000;
    }

    .profile-container:hover .dropdown-menu {
        display: block;
    }

    .dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        color: #333;
        text-decoration: none;
        transition: background-color 0.2s;
    }

    .dropdown-menu a:hover {
        background-color: #f5f5f5;
    }
    </style>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-menu">
                <a href="userdashboard.php"><i class="fas fa-home"></i> Home</a>
                <a href="applied.php"><i class="fas fa-paper-plane"></i> Applied Job</a>
                <a href="bookmark.php" class="active"><i class="fas fa-bookmark"></i> Bookmarks</a>
                <a href="appointment.php"><i class="fas fa-calendar"></i> Appointments</a>
                <a href="reportedjobs.php"><i class="fas fa-flag"></i> Reported Jobs</a>
                <a href="reviews.php"><i class="fas fa-star"></i> Reviews</a>
                <a href="profiles/user/userprofile.php"><i class="fas fa-user"></i> Profile</a>
            </div>
            <div class="logout-container">
                <div class="sidebar-divider"></div>
                <a href="../login/logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Search Bar -->
            <div class="search-container" style="margin-top: 0px;">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search bookmarked jobs..." id="search" name="search" oninput="filterjobs()">
                </div>
                <div class="filter-box">
                    <input type="text" placeholder="Location" id="location" name="location" oninput="filterlocation()">
                    <div class="salary-range">
                        <input type="number" placeholder="Min Salary" id="minsalary" name="minsalary" oninput="filterminsalary()">
                        <input type="number" placeholder="Max Salary" id="maxsalary" name="maxsalary" oninput="filtermaxsalary()">
                    </div>
                    <input type="date" id="date" name="date" oninput="filterdate()">
                    <button class="reset-button" onclick="resetFilters()">
                        <i class="fas fa-undo"></i> Reset Filters
                    </button>
                </div>
            </div>

            <!-- Job Listings -->
            <div class="job-listings">
                <?php
                // Use the already validated user_id from session
                if (!isset($user_id) || !$user_id) {
                    echo '<div class="no-jobs">Session expired. Please login again.</div>';
                    exit();
                }
                
                // Fetch bookmarked jobs with error checking
                $bookmark_sql = "SELECT j.*, b.id 
                                FROM tbl_jobs j 
                                INNER JOIN tbl_bookmarks b ON j.job_id = b.job_id 
                                WHERE b.user_id = ?";
                
                $stmt = mysqli_prepare($conn, $bookmark_sql);
                if (!$stmt) {
                    error_log("Prepare failed: " . mysqli_error($conn));
                    echo '<div class="no-jobs">Error preparing query</div>';
                    exit;
                }

                mysqli_stmt_bind_param($stmt, "i", $user_id);
                if (!mysqli_stmt_execute($stmt)) {
                    error_log("Execute failed: " . mysqli_stmt_error($stmt));
                    echo '<div class="no-jobs">Error executing query</div>';
                    exit;
                }

                $result = mysqli_stmt_get_result($stmt);
                error_log("Number of rows found: " . mysqli_num_rows($result));

                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Debug row data
                        error_log("Job data: " . print_r($row, true));
                        
                        echo '<div class="job-card" data-job-id="' . $row['job_id'] . '">';
                        echo '<div class="job-header">';
                        echo '<h3 class="job_title">' . htmlspecialchars($row['job_title']) . '</h3>';
                        echo '<span class="salary">₹' . number_format($row['salary']) . '</span>';
                        echo '</div>';
                        echo '<div class="job-body">';
                        echo '<p class="description">' . htmlspecialchars($row['job_description']) . '</p>';
                        echo '<p class="location"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($row['location']) . '</p>';
                        echo '<p class="date"><i class="fas fa-calendar-alt"></i> Posted: ' . date('Y-m-d', strtotime($row['created_at'])) . '</p>';
                        echo '</div>';
                        echo '<div class="job-footer">';
                        echo '<a href="jobdetails.php?job_id=' . $row['job_id'] . '" class="details-btn"><i class="fas fa-info-circle"></i> Details</a>';
                        echo '<button onclick="toggleBookmark(' . $row['job_id'] . ')" class="save-btn saved">';
                        echo '<i class="fas fa-bookmark"></i> Saved';
                        echo '</button>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="no-jobs">No bookmarked jobs found.</div>';
                }
                ?>
            </div>
        </main>
    </div>

    <!-- <script src="shared-search.js"></script> -->
    <script src="bookmark.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const gridIcon = document.getElementById('grid');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const profileContainer = document.querySelector('.profile-container');
            const dropdownMenu = document.querySelector('.dropdown-menu');

            // Sidebar toggle
            gridIcon.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            });

            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });

            // Profile dropdown toggle
            profileContainer.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                if (dropdownMenu.classList.contains('show')) {
                    dropdownMenu.classList.remove('show');
                }
            });

            // Remove bookmark functionality
            document.querySelectorAll('.remove-bookmark').forEach(button => {
                button.addEventListener('click', function() {
                    const jobId = this.dataset.jobId;
                    if (confirm('Are you sure you want to remove this bookmark?')) {
                        // Send AJAX request to remove bookmark
                        fetch('remove_bookmark.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `job_id=${jobId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.closest('.job-card').remove();
                                if (document.querySelectorAll('.job-card').length === 0) {
                                    document.querySelector('.bookmarks-container').innerHTML = 
                                        '<div class="no-bookmarks">No bookmarked jobs found.</div>';
                                }
                            }
                        });
                    }
                });
            });
        });

        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            const toastTitle = toast.querySelector('.toast-title');
            const icon = toast.querySelector('.toast-icon i');
            
            // Reset classes and clear any existing animations
            toast.className = 'toast';
            toast.style.animation = 'none';
            
            // Force reflow to ensure animation reset
            void toast.offsetWidth;
            
            // Set type-specific properties
            switch(type) {
                case 'success':
                    toastTitle.textContent = 'Success:';
                    icon.className = 'fas fa-check-circle';
                    toast.classList.add('success');
                    break;
                case 'error':
                    toastTitle.textContent = 'Error:';
                    icon.className = 'fas fa-exclamation-circle';
                    toast.classList.add('error');
                    break;
                case 'warning':
                    toastTitle.textContent = 'Warning:';
                    icon.className = 'fas fa-exclamation-triangle';
                    toast.classList.add('warning');
                    break;
                default:
                    toastTitle.textContent = 'Info:';
                    icon.className = 'fas fa-info-circle';
                    toast.classList.add('info');
            }
            
            toastMessage.textContent = message;
            toast.style.animation = '';
            toast.classList.add('show');
            
            // Manually hide after 3 seconds in case animation doesn't work
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        function toggleBookmark(jobId) {
            const formData = new FormData();
            formData.append('job_id', jobId);

            const jobCard = document.querySelector(`.job-card[data-job-id="${jobId}"]`);
            const btn = jobCard.querySelector('.save-btn');
            btn.style.pointerEvents = 'none';

            fetch('bookmarkprocess.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'unbookmarked') {
                        // Remove the job card with animation
                        jobCard.style.opacity = '0';
                        jobCard.style.transform = 'scale(0.9)';
                        setTimeout(() => {
                            jobCard.remove();
                            // Check if there are no more jobs
                            if (document.querySelectorAll('.job-card').length === 0) {
                                document.querySelector('.job-listings').innerHTML = 
                                    '<div class="no-jobs">No bookmarked jobs found.</div>';
                            }
                        }, 300);
                        showToast('Bookmark removed');
                    }
                } else {
                    showToast(data.message || 'Error processing bookmark', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error processing bookmark', 'error');
            })
            .finally(() => {
                btn.style.pointerEvents = 'auto';
            });
        }
    </script>
</body>
</html>