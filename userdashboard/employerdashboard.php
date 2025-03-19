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

    // count fo active_jobs
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
    <title>Employer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #dbeafe;
            --secondary-color: #64748b;
            --accent-color: #f59e0b;
            --text-color: #1e293b;
            --light-text: #64748b;
            --border-color: #e2e8f0;
            --background-color: #f8fafc;
            --card-bg: #ffffff;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--card-bg);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100vh;
            z-index: 100;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .logo-container {
            padding: 20px;
            display: flex;
            justify-content: center;
            border-bottom: 1px solid var(--border-color);
        }
        
        .logo-container img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }
        
        .company-info {
            padding: 15px 20px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }
        
        .company-info span:first-child {
            font-weight: 600;
            font-size: 16px;
            display: block;
            margin-bottom: 5px;
        }
        
        .nav-menu {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }
        
        .nav-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            color: var(--secondary-color);
            transition: all 0.3s ease;
            margin-bottom: 5px;
        }
        
        .nav-item:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        .nav-item.active {
            background-color: var(--primary-light);
            color: var(--primary-color);
            border-left: 3px solid var(--primary-color);
        }
        
        .nav-item i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .nav-item a {
            text-decoration: none;
            color: inherit;
            font-size: 14px;
            font-weight: 500;
        }
        
        .settings-section {
            padding: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        /* Main Content Styles */
        .main-container {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .post-job-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background-color 0.3s ease;
        }
        
        .post-job-btn:hover {
            background-color: var(--primary-dark);
        }
        
        .post-job-btn a {
            color: white;
            text-decoration: none;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background-color: var(--primary-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--light-text);
        }
        
        /* Search Bar */
        .search-container {
            margin-bottom: 20px;
        }
        
        .search-bar {
            display: flex;
            background-color: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .search-bar select {
            padding: 12px 15px;
            border: none;
            background-color: var(--primary-light);
            color: var(--primary-color);
            font-weight: 500;
            outline: none;
            cursor: pointer;
        }
        
        .search-bar input {
            flex: 1;
            padding: 12px 15px;
            border: none;
            outline: none;
            font-size: 14px;
        }
        
        /* Job Cards - Updated for vertical layout */
        .job-card-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .job-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .job-info {
            display: flex;
            width: 100%;
        }
        
        .company-logo {
            width: 50px;
            height: 50px;
            background-color: var(--primary-light);
            color: var(--primary-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
        }
        
        .job-details h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-color);
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 8px;
        }
        
        .job-meta-item {
            color: var(--light-text);
            font-size: 13px;
            display: flex;
            align-items: center;
        }
        
        .job-meta-item i {
            margin-right: 5px;
        }
        
        .job-description {
            margin-top: 15px;
            font-size: 14px;
            color: var(--light-text);
        }
        
        .job-stats {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .job-stat-item {
            display: flex;
            align-items: center;
            font-size: 13px;
            color: var(--primary-color);
        }
        
        .job-stat-item i {
            margin-right: 5px;
        }
        
        .apply-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 15px;
            transition: background-color 0.3s ease;
            display: inline-block;
        }
        
        .apply-btn:hover {
            background-color: var(--primary-dark);
        }
        
        .apply-btn a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        /* Applicants Sidebar */
        .applicants-sidebar {
            width: 300px;
            background-color: var(--card-bg);
            position: fixed;
            right: 0;
            top: 0;
            height: 100vh;
            box-shadow: -1px 0 3px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow-y: auto;
            z-index: 90;
        }
        
        .applicants-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 20px;
        }
        
        .applicants-header h2 {
            font-size: 18px;
            font-weight: 600;
        }
        
        .applicant-count {
            background-color: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .applicant-card {
            background-color: var(--background-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: transform 0.3s ease;
        }
        
        .applicant-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .applicant-info {
            display: flex;
            align-items: center;
        }
        
        .applicant-avatar {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: 500;
            font-size: 14px;
        }
        
        .applicant-name {
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 3px;
        }
        
        .applicant-position {
            font-size: 12px;
            color: var(--light-text);
        }
        
        /* Responsive Styles */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .job-card-container {
                grid-template-columns: 1fr;
            }
            
            .applicants-sidebar {
                width: 250px;
            }
        }
        
        @media (max-width: 992px) {
            .main-container {
                margin-right: 250px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
                padding: 20px 0;
            }
            
            .logo-container {
                padding: 0 15px;
            }
            
            .logo-container img {
                width: 50px;
                height: 50px;
            }
            
            .company-info, .nav-item span {
                display: none;
            }
            
            .nav-item {
                justify-content: center;
                padding: 15px;
            }
            
            .nav-item i {
                margin-right: 0;
            }
            
            .main-container {
                margin-left: 80px;
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .job-card-container {
                grid-template-columns: 1fr;
            }
            
            .job-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .job-stats {
                flex-direction: column;
                gap: 8px;
            }
        }
        
        /* Job card action buttons hover effects */
        .action-btn {
            transition: all 0.3s ease;
        }
        
        .view-btn:hover {
            background-color: #bfdbfe !important;
        }
        
        .edit-btn:hover {
            background-color: #f97316 !important;
        }
        
        .delete-btn:hover {
            background-color: #dc2626 !important;
        }
        
        /* Toggle button for applicants sidebar */
        .toggle-sidebar-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .toggle-sidebar-btn:hover {
            background-color: var(--primary-dark);
        }
        
        /* Applicants sidebar styles */
        .applicants-sidebar {
            position: fixed;
            top: 0;
            right: -350px; /* Start off-screen */
            width: 350px;
            height: 100vh;
            background-color: var(--card-bg);
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: right 0.3s ease;
            overflow-y: auto;
            padding: 20px;
        }
        
        .applicants-sidebar.open {
            right: 0; /* Slide in when open */
        }
        
        .close-sidebar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        
        .close-sidebar button {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--light-text);
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .close-sidebar button:hover {
            color: var(--danger-color);
        }
        
        /* Overlay when sidebar is open */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .applicants-sidebar {
                width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Main Sidebar -->
        <div class="sidebar">
            <div class="logo-container">
                <?php 
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
                <div class="nav-item active">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
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
                            <div class="stat-number">42</div>
                            <div class="stat-label">New Applicants</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div>
                            <div class="stat-number">24</div>
                            <div class="stat-label">Reviewed</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <div class="stat-number">12</div>
                            <div class="stat-label">Shortlisted</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Jobs Section -->
                <div>
                    <!-- searchbar -->
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
                    
                    <!-- job listing -->
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
                                                    <i class="fas fa-check-circle" style="margin-right: 5px;"></i>
                                                    <span>Active</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Action buttons -->
                                    <div class="job-actions" style="display: flex; gap: 10px;">
                                        <!-- <a href="view_job.php?job_id=<?php echo $job_data['job_id']; ?>" class="action-btn view-btn" style="background-color: var(--primary-light); color: var(--primary-color); padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center;">
                                            <i class="fas fa-eye" style="margin-right: 5px;"></i>
                                            View
                                        </a> -->
                                        <a href="editjob.php?job_id=<?php echo $job_data['job_id']; ?>" class="action-btn edit-btn" style="background-color: var(--warning-color); color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center;">
                                            <i class="fas fa-edit" style="margin-right: 5px;"></i>
                                            Edit
                                        </a>
                                        <a href="deletejob.php?id=<?php echo $job_data['job_id']; ?>&action=deactivate" onclick="return confirm('Are you sure you want to delete this job?')" class="action-btn delete-btn" style="background-color: var(--danger-color); color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center;">
                                            <i class="fas fa-trash-alt" style="margin-right: 5px;"></i>
                                            Deactivate
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
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
        });
        
        function showSearchBar() {
            // Add search functionality if needed
        }
    </script>
</body>
</html>