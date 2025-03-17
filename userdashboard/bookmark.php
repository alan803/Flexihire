<?php
session_start();
include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

// if (!isset($_SESSION['user_id'])) {
//     header("Location: ../login/loginvalidation.php");
//     exit();
// }

// Get user data
$user_id = $_SESSION['user_id'];
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
            <div class="profile-container">
                <?php if (!empty($profile_image)): ?>
                    <img src="/mini project/database/profile_picture/<?php echo htmlspecialchars($profile_image); ?>" class="profile-pic" alt="Profile">
                <?php else: ?>
                    <img src="profile.png" class="profile-pic" alt="Profile">
                <?php endif; ?>
                <div class="dropdown-menu">
                    <div class="user-info">
                        <span class="username"><?php echo htmlspecialchars($display_name); ?></span>
                        <!-- <span class="email"><?php echo $email; ?></span> -->
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="profiles/user/userprofile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-menu">
                <a href="userdashboard.php" class="sidebar-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <!-- <a href="userdashboard.php"><i class="fas fa-list"></i> Job List</a> -->
                <!-- <a href="sidebar/jobgrid/jobgrid.html"><i class="fas fa-th"></i>Job Grid</a> -->
                <a href="applied.php"><i class="fas fa-paper-plane"></i> Applied Job</a>
                <a href="bookmark.php" class="active"><i class="fas fa-bookmark"></i> Bookmarks</a>
                <a href="sidebar/appointment/appointment.html"><i class="fas fa-calendar"></i> Appointments</a>
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
                    <input type="text" placeholder="Search jobs..." id="search" name="search" oninput="filterjobs()">
                </div>
                <div class="filter-box">
                    <input type="text" placeholder="Location" id="location" name="location" oninput="filterlocation()">
                    <div class="salary-range">
                        <input type="number" placeholder="Min Salary" id="minsalary" name="minsalary" oninput="filterminsalary()">
                        <input type="number" placeholder="Max Salary" id="maxsalary" name="maxsalary" oninput="filtermaxsalary()">
                    </div>
                    <input type="date" id="date" name="date" oninput="filterdate()">
                </div>
            </div>

            <!-- Job Listings -->
            <div class="job-listings">
                <?php
                // Debug statements
                error_log("Session ID: " . print_r($_SESSION, true));
                
                // Use either id or user_id from session
                $user_id = isset($_SESSION['id']) ? $_SESSION['id'] : $_SESSION['user_id'];
                
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
                        echo '<span class="salary">₹' . htmlspecialchars($row['salary']) . '</span>';
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

    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toast-message"></span>
    </div>

    <script src="userdashboard.js"></script>
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

        function showToast(message, success = true) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            
            toast.style.background = success ? '#4CAF50' : '#F44336';
            toastMessage.textContent = message;
            toast.classList.add('show');
            
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
                    showToast(data.message || 'Error processing bookmark', false);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error processing bookmark', false);
            })
            .finally(() => {
                btn.style.pointerEvents = 'auto';
            });
        }
    </script>
</body>
</html>
