<?php
    session_start();
    // Check if user is logged in
    if (!isset($_SESSION['employer_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
    }

    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // Initializing variables
    $username = '';
    $email = '';

    $employer_id = $_SESSION['employer_id'];

    $sql = "SELECT u.company_name, l.email 
        FROM tbl_login AS l
        JOIN tbl_employer AS u ON l.employer_id = u.employer_id
        WHERE u.employer_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
    $employer_data = mysqli_fetch_array($result);
    $email = $employer_data['email'];
    $username = $employer_data['company_name'];
    } else {
    error_log("Database error or user not found for ID: $employer_id");
    session_destroy();
    header("Location: ../login/logout.php");
    exit();
    }

    // Fetching jobs with application status counts
    $sql_fetch = "SELECT j.*, 
                COUNT(CASE WHEN a.status = 'applied' THEN 1 END) AS applied_count,
                COUNT(CASE WHEN a.status = 'accepted' THEN 1 END) AS accepted_count,
                COUNT(CASE WHEN a.status = 'rejected' THEN 1 END) AS rejected_count,
                COUNT(a.id) AS total_applicants
                FROM tbl_jobs j
                LEFT JOIN tbl_applications a ON j.job_id = a.job_id
                WHERE j.employer_id = ? AND j.is_deleted = 0
                GROUP BY j.job_id";
    $stmt_fetch = mysqli_prepare($conn, $sql_fetch);
    mysqli_stmt_bind_param($stmt_fetch, "i", $employer_id);
    mysqli_stmt_execute($stmt_fetch);
    $result_fetch = mysqli_stmt_get_result($stmt_fetch);
    mysqli_stmt_close($stmt_fetch);

    // Count of active jobs
    $sql_count = "SELECT COUNT(*) AS active_jobs FROM tbl_jobs WHERE employer_id = ? AND is_deleted = 0";
    $stmt_count = mysqli_prepare($conn, $sql_count);
    mysqli_stmt_bind_param($stmt_count, "i", $employer_id);
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $active_jobs = 0;

    if ($row = mysqli_fetch_assoc($result_count)) {
    $active_jobs = $row['active_jobs'];
    }
    mysqli_stmt_close($stmt_count);

    // Fetch profile image and check if it exists
    $sql_profile = "SELECT profile_image FROM tbl_employer WHERE employer_id = ?";
    $stmt_profile = mysqli_prepare($conn, $sql_profile);
    mysqli_stmt_bind_param($stmt_profile, "i", $employer_id);
    mysqli_stmt_execute($stmt_profile);
    $result_profile = mysqli_stmt_get_result($stmt_profile);
    $profile_data = mysqli_fetch_assoc($result_profile);
    $profile_image_path = !empty($profile_data['profile_image']) && file_exists("employer_pf/" . basename($profile_data['profile_image']))
    ? "employer_pf/" . htmlspecialchars(basename($profile_data['profile_image']))
    : "../assets/images/company-logo.png";

    // Add this query before the stats-grid section to count total applicants
    $sql_applicants = "SELECT COUNT(*) as total_applicants 
                   FROM tbl_applications a 
                   JOIN tbl_jobs j ON a.job_id = j.job_id 
                   WHERE j.employer_id = ? AND j.is_deleted = 0";
    $stmt_applicants = mysqli_prepare($conn, $sql_applicants);
    mysqli_stmt_bind_param($stmt_applicants, "i", $employer_id);
    mysqli_stmt_execute($stmt_applicants);
    $result_applicants = mysqli_stmt_get_result($stmt_applicants);
    $total_applicants = mysqli_fetch_assoc($result_applicants)['total_applicants'];
    mysqli_stmt_close($stmt_applicants);

    // Add this query to count hired applicants
    $sql_hired = "SELECT COUNT(*) as hired_count 
              FROM tbl_applications a 
              JOIN tbl_jobs j ON a.job_id = j.job_id 
              WHERE j.employer_id = ? 
              AND j.is_deleted = 0 
              AND a.status = 'hired'";
    $stmt_hired = mysqli_prepare($conn, $sql_hired);
    mysqli_stmt_bind_param($stmt_hired, "i", $employer_id);
    mysqli_stmt_execute($stmt_hired);
    $result_hired = mysqli_stmt_get_result($stmt_hired);
    $hired_count = mysqli_fetch_assoc($result_hired)['hired_count'];
    mysqli_stmt_close($stmt_hired);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="employerdashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Main Sidebar -->
        <div class="sidebar">
            <div class="logo-container">
                <img src="<?php echo $profile_image_path; ?>" alt="<?php echo htmlspecialchars($username); ?>">
            </div>
            <div class="company-info">
                <span><?php echo htmlspecialchars($username); ?></span>
                <span style="font-size: 13px; color: var(--light-text);"><?php echo htmlspecialchars($email); ?></span>
            </div>
            <nav class="nav-menu">
                <div class="nav-item active">
                    <i class="fas fa-th-large"></i>
                    <a href="employerdashboard.php">Dashboard</a>
                </div>
                <div class="nav-item">
                    <i class="fas fa-plus-circle"></i>
                    <a href="postjob.php">Post a Job</a>
                </div>
                <div class="nav-item">
                    <i class="fas fa-briefcase"></i>
                    <a href="myjoblist.php">My Jobs</a>
                </div>
                <div class="nav-item">
                    <i class="fas fa-users"></i>
                    <a href="applicants.php">Applicants</a>
                </div>
                <div class="nav-item">
                    <i class="fas fa-calendar-check"></i>
                    <a href="interviews.php">Interviews</a>
                </div>
            </nav>
            <div class="settings-section">
                <div class="nav-item">
                    <i class="fas fa-user-cog"></i>
                    <a href="employer_profile.php">My Profile</a>
                </div>
                <div class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <a href="../login/logout.php">Logout</a>
                </div>
            </div>
        </div>

        <!-- Main Container -->
        <div class="main-container">
            <div class="main-content">
                <div class="header">
                    <h1>Welcome, <?php echo htmlspecialchars($username); ?></h1>
                    <div style="display: flex; gap: 15px;">
                        <button id="applicantsToggle" class="toggle-sidebar-btn">
                            <i class="fas fa-users"></i>
                            <span>Applicants</span>
                        </button>
                        <button class="post-job-btn">
                            <i class="fas fa-plus-circle" style="margin-right: 8px;"></i>
                            <a href="postjob.php">Post a Job</a>
                        </button>
                    </div>
                </div>

                <!-- Overview Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo $active_jobs; ?></div>
                            <div class="stat-label">Active Jobs</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo $total_applicants; ?></div>
                            <div class="stat-label">Total Applicants</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo $hired_count; ?></div>
                            <div class="stat-label">Hired</div>
                        </div>
                    </div>
                    <!-- <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <div class="stat-number">12</div>
                            <div class="stat-label">Shortlisted</div>
                        </div>
                    </div> -->
                </div>

                <!-- Recent Jobs Section -->
                <div>
                    <div class="search-container">
                        <div class="search-bar">
                            <select>
                                <option value="all">All Categories</option>
                                <option value="salary">Salary</option>
                                <option value="location">Location</option>
                                <option value="title">Job Title</option>
                            </select>
                            <input type="text" placeholder="Search for jobs..." onfocus="showSearchBar()">
                        </div>
                    </div>
                    
                    <h2 style="margin-bottom: 20px; font-size: 18px; color: var(--text-color); display: flex; align-items: center;">
                        <i class="fas fa-list" style="margin-right: 10px; color: var(--primary-color);"></i>
                        Recent Job Postings
                    </h2>
                    
                    <div class="job-card-container">
                        <?php if(mysqli_num_rows($result_fetch) > 0): ?>
                            <?php while ($job_data = mysqli_fetch_array($result_fetch)): ?>
                                <div class="job-card">
                                    <div class="job-info">
                                        <div class="company-logo">
                                            <i class="fas fa-briefcase"></i>
                                        </div>
                                        <div class="job-details">
                                            <h3><?php echo htmlspecialchars($job_data['job_title']); ?></h3>
                                            <div class="job-meta">
                                                <div class="job-meta-item">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?php echo htmlspecialchars($job_data['location']); ?>
                                                </div>
                                                <div class="job-meta-item">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    <?php echo htmlspecialchars($job_data['vacancy_date']); ?>
                                                </div>
                                                <div class="job-meta-item">
                                                    <i class="fas fa-rupee-sign"></i>
                                                    <?php echo htmlspecialchars($job_data['salary']); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="job-description">
                                                <?php 
                                                    $description = htmlspecialchars($job_data['job_description']);
                                                    echo (strlen($description) > 150) ? substr($description, 0, 150) . '...' : $description; 
                                                ?>
                                            </div>
                                            
                                            <div class="job-stats">
                                                <div class="job-stat-item">
                                                    <i class="fas fa-users"></i>
                                                    <span><?php echo $job_data['total_applicants']; ?> Applicants</span>
                                                </div>
                                                <div class="job-stat-item status-applied">
                                                    <i class="fas fa-paper-plane"></i>
                                                    <span><?php echo $job_data['applied_count']; ?> Applied</span>
                                                </div>
                                                <div class="job-stat-item status-accepted">
                                                    <i class="fas fa-check-circle"></i>
                                                    <span><?php echo $job_data['accepted_count']; ?> Accepted</span>
                                                </div>
                                                <div class="job-stat-item status-rejected">
                                                    <i class="fas fa-times-circle"></i>
                                                    <span><?php echo $job_data['rejected_count']; ?> Rejected</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="job-actions">
                                        <a href="editjob.php?job_id=<?php echo $job_data['job_id']; ?>" class="action-btn edit-btn" style="background-color: var(--warning-color); color: white; padding: 8px 10px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center; width: 120px;">
                                            <i class="fas fa-edit" style="margin-right: 5px;"></i> Edit
                                        </a>
                                        <a href="deletejob.php?id=<?php echo $job_data['job_id']; ?>&action=deactivate" onclick="return confirm('Are you sure you want to delete this job?')" class="action-btn delete-btn" style="background-color: var(--danger-color); color: white; padding: 8px 10px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center; width: 120px;">
                                            <i class="fas fa-trash-alt" style="margin-right: 5px;"></i> Deactivate
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 30px; background-color: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                                <i class="fas fa-briefcase" style="font-size: 48px; color: #e2e8f0; margin-bottom: 20px;"></i>
                                <h3>No jobs posted yet</h3>
                                <p style="color: var(--light-text); margin-top: 10px; margin-bottom: 20px;">
                                    Start by posting your first job opening
                                </p>
                                <button class="post-job-btn" style="margin: 0 auto; display: inline-flex;">
                                    <i class="fas fa-plus-circle" style="margin-right: 8px;"></i>
                                    <a href="postjob.php">Post a Job</a>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applicants Sidebar -->
        <div class="applicants-sidebar" id="applicantsSidebar">
            <div class="close-sidebar">
                <button id="closeSidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="applicants-header">
                <h2>Recent Applicants</h2>
                <span class="applicant-count">9</span>
            </div>
            <div class="applicant-list">
                <div style="margin-bottom: 20px;">
                    <h3 style="margin-bottom: 12px; font-size: 15px; color: var(--light-text);">Web Developer</h3>
                    <div class="applicant-card">
                        <div class="applicant-info">
                            <div class="applicant-avatar">MZ</div>
                            <div>
                                <div class="applicant-name">Mohd Zaid</div>
                                <div class="applicant-position">React Developer</div>
                            </div>
                        </div>
                    </div>
                    <!-- Add more applicant cards as needed -->
                </div>
                <a href="applicants.php" style="display: block; text-align: center; margin-top: 20px; color: var(--primary-color); text-decoration: none; font-weight: 500; font-size: 14px;">
                    View All Applicants <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const applicantsToggle = document.getElementById('applicantsToggle');
            const closeSidebar = document.getElementById('closeSidebar');
            const applicantsSidebar = document.getElementById('applicantsSidebar');
            const body = document.body;
            
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            body.appendChild(overlay);
            
            applicantsToggle.addEventListener('click', function() {
                applicantsSidebar.classList.add('open');
                overlay.classList.add('active');
                body.style.overflow = 'hidden';
            });
            
            closeSidebar.addEventListener('click', function() {
                applicantsSidebar.classList.remove('open');
                overlay.classList.remove('active');
                body.style.overflow = '';
            });
            
            overlay.addEventListener('click', function() {
                applicantsSidebar.classList.remove('open');
                overlay.classList.remove('active');
                body.style.overflow = '';
            });
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && applicantsSidebar.classList.contains('open')) {
                    applicantsSidebar.classList.remove('open');
                    overlay.classList.remove('active');
                    body.style.overflow = '';
                }
            });
        });
        
        function showSearchBar() {
            // Add search functionality if needed
        }

        document.addEventListener("DOMContentLoaded", function() {
            const logoImg = document.querySelector('.logo-container img');
            logoImg.classList.add('loading');
            logoImg.onload = function() {
                logoImg.classList.remove('loading');
            };

            // Existing sidebar toggle code...
            const applicantsToggle = document.getElementById('applicantsToggle');
            const closeSidebar = document.getElementById('closeSidebar');
            const applicantsSidebar = document.getElementById('applicantsSidebar');
            const body = document.body;
            
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            body.appendChild(overlay);
            
            applicantsToggle.addEventListener('click', function() {
                applicantsSidebar.classList.add('open');
                overlay.classList.add('active');
                body.style.overflow = 'hidden';
            });
            
            closeSidebar.addEventListener('click', function() {
                applicantsSidebar.classList.remove('open');
                overlay.classList.remove('active');
                body.style.overflow = '';
            });
            
            overlay.addEventListener('click', function() {
                applicantsSidebar.classList.remove('open');
                overlay.classList.remove('active');
                body.style.overflow = '';
            });
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && applicantsSidebar.classList.contains('open')) {
                    applicantsSidebar.classList.remove('open');
                    overlay.classList.remove('active');
                    body.style.overflow = '';
                }
            });
        });
    </script>
</body>
</html>