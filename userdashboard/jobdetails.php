<?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login/login.php");
        exit();
    }

    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // Get job_id from URL
    $job_id = isset($_GET['job_id']) ? $_GET['job_id'] : null;

    if (!$job_id) {
        header("Location: userdashboard.php");
        exit();
    }

    // Fetch job details
    $sql = "SELECT * FROM tbl_jobs WHERE job_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $job_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $job = mysqli_fetch_assoc($result);

    //fecth user profile
    $user_id = $_SESSION['user_id'];
    echo $user_id;
    $sql_user = "SELECT * FROM tbl_user WHERE user_id = '$user_id'";
    $result_user = mysqli_query($conn, $sql_user);
    $user = mysqli_fetch_assoc($result_user);
    $profile_image = $user['profile_image'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details</title>
    <link rel="stylesheet" href="jobdetails.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <img src="logowithoutbcakground.png" alt="Logo" class="logo">
            <h1>FlexiHire</h1>
        </div>
        
        <div class="nav-right">
            <div class="profile-container">
                <?php if (!empty($profile_image)): ?>
                    <img src="../database/profile_picture/<?php echo $profile_image; ?>" class="profile-pic" alt="Profile">
                <?php else: ?>
                    <img src="profile.png" class="profile-pic" alt="Profile">
                <?php endif; ?>
                <div class="dropdown-menu">
                    <div class="user-info">
                        <span class="username"><?php echo $_SESSION['username']; ?></span>
                        <span class="email"><?php echo $_SESSION['email']; ?></span>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="profiles/user/userprofile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-menu">
                <a href="userdashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="userdashboard.php"><i class="fas fa-list"></i> Job List</a>
                <a href="sidebar/jobgrid/jobgrid.html"><i class="fas fa-th"></i> Job Grid</a>
                <a href="sidebar/applyjob/applyjob.html"><i class="fas fa-paper-plane"></i> Apply Job</a>
                <a href="jobdetails.php"><i class="fas fa-info-circle"></i> Job Details</a>
                <a href="sidebar/jobcategory/jobcategory.html"><i class="fas fa-tags"></i> Job Category</a>
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

        <!-- Main Content -->
        <main class="main-content">
            <div class="job-details-container">
                <!-- Move back button inside -->
                <div class="back-section">
                    <a href="userdashboard.php" class="back-button">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Jobs</span>
                    </a>
                </div>

                <div class="job-header">
                    <h2><?php echo htmlspecialchars($job['job_title']); ?></h2>
                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?>, <?php echo htmlspecialchars($job['town']); ?></span>
                        <span><i class="fas fa-money-bill-wave"></i> ₹<?php echo htmlspecialchars($job['salary']); ?></span>
                        <span><i class="fas fa-calendar-plus"></i> Posted: <?php echo date('Y-m-d', strtotime($job['created_at'])); ?></span>
                        <span><i class="fas fa-calendar-alt"></i> Valid Until: <?php echo date('Y-m-d', strtotime($job['vacancy_date'])); ?></span>
                    </div>
                </div>

                <div class="job-content">
                    <div class="content-section">
                        <h3>Job Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($job['job_description'])); ?></p>
                    </div>

                    <div class="content-section">
                        <h3>Additional Details</h3>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="label">Vacancy:</span>
                                <span class="value"><?php echo htmlspecialchars($job['vacancy']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Working Days:</span>
                                <span class="value"><?php echo htmlspecialchars($job['working_days']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Working Hours:</span>
                                <span class="value"><?php echo htmlspecialchars($job['start_time']); ?> - <?php echo htmlspecialchars($job['end_time']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Category:</span>
                                <span class="value"><?php echo htmlspecialchars($job['category']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="content-section">
                        <h3>Requirements</h3>
                        <div class="requirements-list">
                            <div class="requirement-item">
                                <i class="fas fa-id-card"></i>
                                <span>License Required: <?php echo $job['license_required'] ? 'Yes' : 'No'; ?></span>
                            </div>
                            <div class="requirement-item">
                                <i class="fas fa-certificate"></i>
                                <span>Badge Required: <?php echo $job['badge_required'] ? 'Yes' : 'No'; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="content-section">
                        <h3>Contact Information</h3>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($job['contact_no']); ?></p>
                    </div>

                    <div class="job-actions">
                        <button class="apply-btn">
                            <i class="fas fa-paper-plane"></i>
                            Apply Now
                        </button>
                        <button class="save-btn">
                            <i class="far fa-bookmark"></i>
                            Save Job
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Dropdown menu functionality
        document.addEventListener('DOMContentLoaded', function() {
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
        });
    </script>
</body>
</html>