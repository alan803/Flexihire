<?php
session_start();
include '../database/connectdatabase.php';

if (!isset($_SESSION['employer_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

$employer_id = $_SESSION['employer_id'];
$dbname = "project";
mysqli_select_db($conn, $dbname);

// Fetch employer details
$sql = "SELECT u.company_name, l.email 
        FROM tbl_login AS l
        JOIN tbl_employer AS u ON l.employer_id = u.employer_id
        WHERE u.employer_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$username = "Unknown"; // Default value
$email = "Not Available";

if ($result && mysqli_num_rows($result) > 0) {
    $employer_data = mysqli_fetch_assoc($result);
    $email = $employer_data['email'];
    $username = $employer_data['company_name'];
}

mysqli_stmt_close($stmt);

// fetching data from tbl_jobs
$sql_fetch="SELECT * FROM tbl_jobs WHERE employer_id=? AND is_deleted=0";
$stmt_fetch=mysqli_prepare($conn,$sql_fetch);
mysqli_stmt_bind_param($stmt_fetch,"i",$employer_id);
mysqli_stmt_execute($stmt_fetch);
$result_fetch=mysqli_stmt_get_result($stmt_fetch);
mysqli_stmt_close($stmt_fetch);

// count for active_jobs
$sql_count = "SELECT COUNT(*) AS active_jobs FROM tbl_jobs WHERE employer_id=? AND is_deleted=0";
$stmt_count = mysqli_prepare($conn, $sql_count);
mysqli_stmt_bind_param($stmt_count, "i", $employer_id);
mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$active_jobs = 0;

if ($row = mysqli_fetch_assoc($result_count)) {
    $active_jobs = $row['active_jobs'];
}

mysqli_stmt_close($stmt_count);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs | AutoRecruits</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="employerdashboard.css">
    <style>
        /* Additional styles specific to myjoblist.php */
        .job-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 8px 15px;
            font-size: 14px;
            color: var(--text-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background-color: var(--primary-light);
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .job-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }
        
        .status-closed {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }
        
        .status-draft {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }
        
        .job-actions {
            display: flex;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .job-actions {
                flex-direction: column;
            }
        }

        /* Remove the active element effect in the sidebar */
        .nav-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: var(--light-text);
            transition: all 0.3s ease;
            border-radius: 8px;
            margin-bottom: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .nav-item:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .nav-item i {
            margin-right: 15px;
            font-size: 18px;
        }

        .nav-item a {
            color: inherit;
            text-decoration: none;
            display: flex;
            align-items: center;
            width: 100%;
        }

        /* Remove the active class styling */
        .nav-item.active {
            background-color: transparent;
            color: var(--light-text);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Main Sidebar -->
        <div class="sidebar">
            <div class="logo-container">
                <?php 
                // Fetch profile image
                $sql_profile = "SELECT profile_image FROM tbl_employer WHERE employer_id = ?";
                $stmt_profile = mysqli_prepare($conn, $sql_profile);
                mysqli_stmt_bind_param($stmt_profile, "i", $employer_id);
                mysqli_stmt_execute($stmt_profile);
                $result_profile = mysqli_stmt_get_result($stmt_profile);
                $profile_data = mysqli_fetch_assoc($result_profile);
                ?>
                <?php if(!empty($profile_data['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($profile_data['profile_image']); ?>" 
                         alt="<?php echo htmlspecialchars($username); ?>"
                         onerror="this.src='../assets/images/company-logo.png';">
                <?php else: ?>
                    <img src="../assets/images/company-logo.png" alt="AutoRecruits.in">
                <?php endif; ?>
            </div>
            <div class="company-info">
                <span><?php echo htmlspecialchars($username); ?></span>
                <span style="font-size: 13px; color: var(--light-text);"><?php echo htmlspecialchars($email); ?></span>
            </div>
            <nav class="nav-menu">
                <div class="nav-item">
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
            <!-- Main Content -->
            <div class="main-content">
                <div class="header">
                    <h1>My Jobs</h1>
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

                <!-- Job Filters -->
                <div class="job-filters">
                    <button class="filter-btn active">All Jobs (<?php echo $active_jobs; ?>)</button>
                    <button class="filter-btn">Active</button>
                    <button class="filter-btn">Closed</button>
                    <button class="filter-btn">Draft</button>
                </div>

                <!-- Search Bar -->
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
                
                <!-- Job Listings -->
                <div class="job-card-container">
                    <?php if(mysqli_num_rows($result_fetch) > 0): ?>
                        <?php while ($job_data = mysqli_fetch_array($result_fetch)): ?>
                            <div class="job-card">
                                <div class="job-info">
                                    <div class="company-logo">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                    <div class="job-details">
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                            <h3><?php echo htmlspecialchars($job_data['job_title']); ?></h3>
                                            <span class="job-status status-active">Active</span>
                                        </div>
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
                                                ₹
                                                <?php echo htmlspecialchars($job_data['salary']); ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Additional job details -->
                                        <div class="job-description" style="margin-top: 15px; font-size: 14px; color: var(--light-text);">
                                            <?php 
                                                // Truncate description to 150 characters
                                                $description = htmlspecialchars($job_data['job_description']);
                                                echo (strlen($description) > 150) ? substr($description, 0, 150) . '...' : $description; 
                                            ?>
                                        </div>
                                        
                                        <!-- Job stats -->
                                        <div class="job-stats" style="display: flex; gap: 15px; margin-top: 15px;">
                                            <div class="job-stat-item" style="display: flex; align-items: center; font-size: 13px; color: var(--primary-color);">
                                                <i class="fas fa-users" style="margin-right: 5px;"></i>
                                                <span>12 Applicants</span>
                                            </div>
                                            <div class="job-stat-item" style="display: flex; align-items: center; font-size: 13px; color: var(--primary-color);">
                                                <i class="fas fa-eye" style="margin-right: 5px;"></i>
                                                <span>48 Views</span>
                                            </div>
                                            <div class="job-stat-item" style="display: flex; align-items: center; font-size: 13px; color: var(--success-color);">
                                                <i class="fas fa-clock" style="margin-right: 5px;"></i>
                                                <span>Posted 3 days ago</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action buttons -->
                                <div class="job-actions">
                                    <a href="applicants.php?job_id=<?php echo $job_data['job_id']; ?>" class="action-btn view-btn" style="background-color: var(--primary-light); color: var(--primary-color); padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center;">
                                        <i class="fas fa-users" style="margin-right: 5px;"></i>
                                        Applicants
                                    </a>
                                    <a href="editjob.php?job_id=<?php echo $job_data['job_id']; ?>" class="action-btn edit-btn" style="background-color: var(--warning-color); color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center;">
                                        <i class="fas fa-edit" style="margin-right: 5px;"></i>
                                        Edit
                                    </a>
                                    <a href="delete_job.php?job_id=<?php echo $job_data['job_id']; ?>" onclick="return confirm('Are you sure you want to delete this job?')" class="action-btn delete-btn" style="background-color: var(--danger-color); color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center;">
                                        <i class="fas fa-trash-alt" style="margin-right: 5px;"></i>
                                        Delete
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
                <!-- Web Developer Group -->
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
                    <div class="applicant-card">
                        <div class="applicant-info">
                            <div class="applicant-avatar">SK</div>
                            <div>
                                <div class="applicant-name">Samra Khawar</div>
                                <div class="applicant-position">Node.JS Developer</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Designer Group -->
                <div style="margin-bottom: 20px;">
                    <h3 style="margin-bottom: 12px; font-size: 15px; color: var(--light-text);">Designer</h3>
                    <div class="applicant-card">
                        <div class="applicant-info">
                            <div class="applicant-avatar">BA</div>
                            <div>
                                <div class="applicant-name">Bilal Ahmed</div>
                                <div class="applicant-position">UI/UX Designer</div>
                            </div>
                        </div>
                    </div>
                    <div class="applicant-card">
                        <div class="applicant-info">
                            <div class="applicant-avatar">ZA</div>
                            <div>
                                <div class="applicant-name">Zohail Ali</div>
                                <div class="applicant-position">Product Designer</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <a href="applicants.php" style="display: block; text-align: center; margin-top: 20px; color: var(--primary-color); text-decoration: none; font-weight: 500; font-size: 14px;">
                    View All Applicants <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const applicantsToggle = document.getElementById('applicantsToggle');
            const closeSidebar = document.getElementById('closeSidebar');
            const applicantsSidebar = document.getElementById('applicantsSidebar');
            const body = document.body;
            
            // Create overlay element
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            body.appendChild(overlay);
            
            // Open sidebar
            applicantsToggle.addEventListener('click', function() {
                applicantsSidebar.classList.add('open');
                overlay.classList.add('active');
                body.style.overflow = 'hidden'; // Prevent scrolling when sidebar is open
            });
            
            // Close sidebar
            closeSidebar.addEventListener('click', function() {
                applicantsSidebar.classList.remove('open');
                overlay.classList.remove('active');
                body.style.overflow = ''; // Restore scrolling
            });
            
            // Close sidebar when clicking on overlay
            overlay.addEventListener('click', function() {
                applicantsSidebar.classList.remove('open');
                overlay.classList.remove('active');
                body.style.overflow = ''; // Restore scrolling
            });
            
            // Close sidebar with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && applicantsSidebar.classList.contains('open')) {
                    applicantsSidebar.classList.remove('open');
                    overlay.classList.remove('active');
                    body.style.overflow = ''; // Restore scrolling
                }
            });
            
            // Filter buttons functionality
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    // Add filter functionality here
                });
            });
        });
        
        function showSearchBar() {
            // Add search functionality if needed
        }
    </script>
</body>
</html>
