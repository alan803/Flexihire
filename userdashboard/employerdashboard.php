<?php
    session_start();
    // Check if user is logged in
    if (!isset($_SESSION['employer_id'])) 
    {
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

    if ($result && mysqli_num_rows($result) > 0) 
    {
        $employer_data = mysqli_fetch_array($result);
        $email = $employer_data['email'];
        $username = $employer_data['company_name'];
    } 
    else 
    {
        // Handle database error or missing user
        error_log("Database error or user not found for ID: $employer_id");
        session_destroy();
        header("Location: ../login/loginvalidation.php");
        exit();
    }
    // fetching data from tbl_jobs
    $sql_fetch="SELECT * FROM tbl_jobs WHERE employer_id=? AND is_deleted=0";
    $stmt_fetch=mysqli_prepare($conn,$sql_fetch);
    mysqli_stmt_bind_param($stmt_fetch,"i",$employer_id);
    mysqli_stmt_execute($stmt_fetch);
    $result_fetch=mysqli_stmt_get_result($stmt_fetch);
    mysqli_stmt_close($stmt_fetch);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoRecruits.in - Dashboard</title>
    <link rel="stylesheet" href="employerdashboard.css">
</head>
<body>
    <!-- Applicants Sidebar -->
    <div class="applicants-sidebar">
        <div class="applicants-header">
            <h2>New Applicants</h2>
            <span class="applicant-count">09</span>
        </div>
        <div class="applicant-list">
            <!-- Web Developer Group -->
            <div style="margin-bottom: 20px;">
                <h3 style="margin-bottom: 12px;">Web Developer</h3>
                <div class="applicant-card">
                    <div class="applicant-info">
                        <div class="applicant-avatar">MZ</div>
                        <div>
                            <div class="applicant-name"><?php echo $employer_id;?></div>
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
                <h3 style="margin-bottom: 12px;">Designer</h3>
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
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Main Sidebar -->
        <div class="sidebar">
            <div class="logo-container">
                <img src="logo.png" alt="AutoRecruits.in">
            </div>
            <div class="company-info">
                <span><?php echo $username;?></span>
            </div>
            <nav class="nav-menu">
                <div class="nav-item active">
                    <i class="fas fa-th-large"></i>
                    Home
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
                    <i class="fas fa-calendar-check"></i>
                    Interviews
                </div>
                <!-- /<div class="nav-item"> 
                    <i class="fas fa-recycle"></i>
                    Job Bin
                </div>-->
            </nav>
            <div class="settings-section">
                <div class="nav-item">
                    <i class="fas fa-user"></i>
                    My Profile
                </div>
                <div class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <a href="../login/logout.php">Logout</a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
                <button class="post-job-btn"><a href="postjob.php">Post a Job</a></button>
            </div>

            <!-- Overview Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🔵</div>
                    <div>
                        <div class="stat-number">50</div>
                        <div class="stat-label">Active Jobs</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div>
                        <div class="stat-number">42</div>
                        <div class="stat-label">New Applicants</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div>
                        <div class="stat-number">24</div>
                        <div class="stat-label">Shortlisted Reviewed</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div>
                        <div class="stat-number">12</div>
                        <div class="stat-label">Candidates Shortlisted</div>
                    </div>
                </div>
            </div>

            <!-- Recent Jobs Section -->
            <div class="main-contentt">
                <!-- searchbar -->
                <div class="search-container">
                    <div class="search-bar">
                        <select>
                            <option value="salary">Salary</option>
                            <option value="location">Location</option>
                            <option value="title">Job Title</option>
                        </select>
                        <input type="text" placeholder="Enter your search query..." onfocus="showSearchBar()">
                    </div>
                </div>
                <!-- job listing -->
                <div class="job-card-container" style="gap:20px;">
                <?php while ($job_data = mysqli_fetch_array($result_fetch)): ?>
                <div class="job-card">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="display: flex;">
                            <div class="company-logo" style="background: #000;"></div>
                            <div>
                                <h3><?php echo $job_data['job_title']; ?></h3>
                                <div style="color: #666;">
                                    <?php echo $job_data['location']; ?> • <?php echo $job_data['vacancy_date']; ?> • <?php echo $job_data['salary'] . "₹"; ?>
                                </div>
                            </div>
                        </div>
                        <button class="apply-btn"><a href="myjoblist.php">Details</a></button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            </div>
        </div>
    </div>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js">
        document.addEventListener("DOMContentLoaded", function () 
        {
            const bookmarkIcon = document.querySelector(".bookmark-icon");
            
            bookmarkIcon.addEventListener("click", function () {
                this.classList.toggle("bookmarked");
            });
        });
    </script>
</body>