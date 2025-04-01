<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // Message handling
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $message_type = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }

    if (!isset($_SESSION['user_id'])) 
    {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Get user data
    $sql = "SELECT l.email, u.first_name, u.last_name, u.username, u.profile_image, u.user_id
            FROM tbl_login l
            INNER JOIN tbl_user u ON l.user_id = u.user_id
            WHERE l.user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);
        $email = $user_data['email'];
        
        if (!empty($user_data['username'])) {
            $display_name = $user_data['username'];
        } else {
            $display_name = $user_data['first_name'] . " " . $user_data['last_name'];
        }
        
        $profile_image = $user_data['profile_image'];
    }

    // Fetch accepted jobs with their details and check if user has already rated
    $sql = "SELECT j.job_id, j.job_title, j.employer_id, e.company_name, e.profile_image,
            (SELECT COUNT(*) FROM tbl_job_ratings r 
             WHERE r.job_id = j.job_id AND r.user_id = ?) as has_rated
            FROM tbl_applications a 
            JOIN tbl_jobs j ON a.job_id = j.job_id 
            JOIN tbl_employer e ON j.employer_id = e.employer_id 
            WHERE a.user_id = ? AND a.status = 'accepted'";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $accepted_jobs = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $accepted_jobs[] = $row;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - FlexiHire</title>
    <link rel="stylesheet" href="userdashboard.css">
    <link rel="stylesheet" href="reviews.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="reviews.js"></script>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
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

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-content">
            <div class="toast-icon">
                <i class="fas"></i>
            </div>
            <div class="toast-message-container">
                <div class="toast-title"></div>
                <span id="toast-message"></span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-menu">
                <a href="userdashboard.php"><i class="fas fa-home"></i> Home</a>
                <a href="applied.php"><i class="fas fa-paper-plane"></i> Applied Job</a>
                <a href="bookmark.php"><i class="fas fa-bookmark"></i> Bookmarks</a>
                <a href="appointment.php"><i class="fas fa-calendar"></i> Appointments</a>
                <a href="reportedjobs.php"><i class="fas fa-flag"></i> Reported Jobs</a>
                <a href="reviews.php" class="active"><i class="fas fa-star"></i> Reviews</a>
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
            <!-- <div class="content-header">
                <h2>Review Your Accepted Jobs</h2>
            </div> -->
            
            <?php if (empty($accepted_jobs)): ?>
                <div class="no-employers">
                    <i class="fas fa-briefcase"></i>
                    <p>No accepted jobs found to review.</p>
                </div>
            <?php else: ?>
                <div class="employers-list">
                    <?php foreach ($accepted_jobs as $job): ?>
                        <div class="employer-card">
                            <div class="employer-info">
                                <h3><?php echo htmlspecialchars($job['job_title']); ?></h3>
                                <p class="employer-id">Company: <?php echo htmlspecialchars($job['company_name']); ?></p>
                            </div>
                            <div class="employer-actions">
                                <a href="jobdetails.php?job_id=<?php echo $job['job_id']; ?>" class="view-details-btn">
                                    <i class="fas fa-info-circle"></i> View Job Details
                                </a>
                                <a href="fetch_employer_rating.php?employer_id=<?php echo $job['employer_id']; ?>" class="view-details-btn">
                                    <i class="fas fa-user-tie"></i> Employer Details
                                </a>
                                <?php if ($job['has_rated'] > 0): ?>
                                    <div class="already-rated">
                                        <i class="fas fa-check-circle"></i> Already Rated
                                    </div>
                                <?php else: ?>
                                    <button class="add-review-btn" data-job-id="<?php echo $job['job_id']; ?>">
                                        <i class="fas fa-star"></i> Rate Job
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Hidden Review Form -->
                        <?php if ($job['has_rated'] == 0): ?>
                            <div class="review-form-container" id="review-form-<?php echo $job['job_id']; ?>" style="display: none;">
                                <form action="submit_review.php" method="POST" class="review-form">
                                    <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                    <input type="hidden" name="employer_id" value="<?php echo $job['employer_id']; ?>">
                                    
                                    <div class="form-group">
                                        <label>Rating</label>
                                        <div class="rating-input">
                                            <input type="radio" name="rating" id="star5-<?php echo $job['job_id']; ?>" value="5">
                                            <label for="star5-<?php echo $job['job_id']; ?>">★</label>
                                            
                                            <input type="radio" name="rating" id="star4-<?php echo $job['job_id']; ?>" value="4">
                                            <label for="star4-<?php echo $job['job_id']; ?>">★</label>
                                            
                                            <input type="radio" name="rating" id="star3-<?php echo $job['job_id']; ?>" value="3">
                                            <label for="star3-<?php echo $job['job_id']; ?>">★</label>
                                            
                                            <input type="radio" name="rating" id="star2-<?php echo $job['job_id']; ?>" value="2">
                                            <label for="star2-<?php echo $job['job_id']; ?>">★</label>
                                            
                                            <input type="radio" name="rating" id="star1-<?php echo $job['job_id']; ?>" value="1">
                                            <label for="star1-<?php echo $job['job_id']; ?>">★</label>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="review">Review:</label>
                                        <textarea name="review" id="review-<?php echo $job['job_id']; ?>" required></textarea>
                                    </div>

                                    <div class="form-actions">
                                        <button type="submit" class="submit-btn">Submit Review</button>
                                        <button type="button" class="cancel-btn" data-job-id="<?php echo $job['job_id']; ?>">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add this div at the end of body, before closing body tag -->
    <div class="modal-overlay">
        <?php if ($job['has_rated'] == 0): ?>
            <div class="review-form-container" id="review-form-<?php echo $job['job_id']; ?>" style="display: none;">
                <!-- Your existing form content -->
            </div>
        <?php endif; ?>
    </div>

    <script>
        $(document).ready(function() {
            // Show modal
            $('.add-review-btn').click(function() {
                const jobId = $(this).data('job-id');
                $(`#review-form-${jobId}`).show();
                $('.modal-overlay').addClass('modal-show');
                $('body').css('overflow', 'hidden');
            });

            // Hide modal
            $('.cancel-btn, .modal-overlay').click(function(e) {
                if (e.target === this) {
                    $('.review-form-container').hide();
                    $('.modal-overlay').removeClass('modal-show');
                    $('body').css('overflow', 'auto');
                }
            });

            // Show toast message if exists
            <?php if (isset($message)): ?>
                showToast('<?php echo addslashes($message); ?>', '<?php echo $message_type; ?>');
            <?php endif; ?>
        });

        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            const toastTitle = toast.querySelector('.toast-title');
            const icon = toast.querySelector('i');
            
            // Reset classes
            toast.className = 'toast';
            icon.className = 'fas';
            
            // Set type-specific properties
            switch(type) {
                case 'success':
                    toastTitle.textContent = 'Success';
                    icon.classList.add('fa-check-circle');
                    toast.classList.add('success');
                    break;
                case 'error':
                    toastTitle.textContent = 'Error';
                    icon.classList.add('fa-exclamation-circle');
                    toast.classList.add('error');
                    break;
                default:
                    toastTitle.textContent = 'Information';
                    icon.classList.add('fa-info-circle');
                    toast.classList.add('info');
            }
            
            toastMessage.textContent = message;
            toast.classList.add('show');
            
            // Hide toast after 4 seconds with fade out
            setTimeout(() => {
                toast.style.transition = 'opacity 0.5s ease';
                toast.style.opacity = '0';
                setTimeout(() => {
                    toast.classList.remove('show');
                    toast.style.opacity = '1'; // Reset opacity for next use
                }, 500); // Wait for fade out to complete
            }, 4000); // Show for 4 seconds
        }
    </script>
</body>
</html>