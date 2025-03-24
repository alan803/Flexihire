<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // Check if admin is logged in
    if (!isset($_SESSION['admin_id'])) {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    //fetch stats div values

    //fetching no of users are registered
    $sql_total_users="SELECT COUNT(*) AS total_users FROM tbl_user";
    $result_total_users=mysqli_query($conn,$sql_total_users);
    $row_total_users=mysqli_fetch_assoc($result_total_users);
    $total_users=$row_total_users['total_users'];

    //fetching no of employers are registered
    $sql_total_employers="SELECT COUNT(*) AS total_employers FROM tbl_employer";
    $result_total_employers=mysqli_query($conn,$sql_total_employers);
    $row_total_employers=mysqli_fetch_assoc($result_total_employers);
    $total_employers=$row_total_employers['total_employers'];

    //fetching no of jobs are posted
    $sql_total_jobs="SELECT COUNT(*) AS total_jobs FROM tbl_jobs";
    $result_total_jobs=mysqli_query($conn,$sql_total_jobs);
    $row_total_jobs=mysqli_fetch_assoc($result_total_jobs);
    $total_jobs=$row_total_jobs['total_jobs'];

    //fetching no of users are applied for a job
    $sql_total_applicants="SELECT COUNT(*) AS total_applicants FROM tbl_applications";
    $result_total_applicants=mysqli_query($conn,$sql_total_applicants);
    $row_total_applicants=mysqli_fetch_assoc($result_total_applicants);
    $total_applicants=$row_total_applicants['total_applicants'];

// // Fetch dashboard statistics
// $stats = array();

// // Total Users
// $sql_users = "SELECT COUNT(*) as count FROM tbl_user";
// $result_users = mysqli_query($conn, $sql_users);
// $stats['users'] = mysqli_fetch_assoc($result_users)['count'];

// // Total Employers
// $sql_employers = "SELECT COUNT(*) as count FROM tbl_employer";
// $result_employers = mysqli_query($conn, $sql_employers);
// $stats['employers'] = mysqli_fetch_assoc($result_employers)['count'];

// // Total Jobs
// $sql_jobs = "SELECT COUNT(*) as count FROM tbl_jobs";
// $result_jobs = mysqli_query($conn, $sql_jobs);
// $stats['jobs'] = mysqli_fetch_assoc($result_jobs)['count'];

// // Total Applications
// $sql_applications = "SELECT COUNT(*) as count FROM tbl_applications";
// $result_applications = mysqli_query($conn, $sql_applications);
// $stats['applications'] = mysqli_fetch_assoc($result_applications)['count'];

// // Recent Activities (last 10)
// $sql_activities = "SELECT * FROM (
//     SELECT 'New User' as type, first_name, last_name, created_at 
//     FROM tbl_user 
//     UNION ALL
//     SELECT 'New Employer' as type, company_name as first_name, '' as last_name, created_at 
//     FROM tbl_employer
//     UNION ALL
//     SELECT 'New Job' as type, job_title as first_name, '' as last_name, posted_date as created_at 
//     FROM tbl_jobs
// ) as activities 
// ORDER BY created_at DESC 
// LIMIT 10";

// $result_activities = mysqli_query($conn, $sql_activities);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admindashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-section">
                <h1>FlexiHire</h1>
            </div>
            <nav class="nav-menu">
                <a href="admindashboard.php" class="nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="manage_users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <a href="manage_employers.php" class="nav-item">
                    <i class="fas fa-building"></i>
                    <span>Manage Employers</span>
                </a>
                <a href="manage_jobs.php" class="nav-item">
                    <i class="fas fa-briefcase"></i>
                    <span>Manage Jobs</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="../login/logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="stats-grid">
                <!-- Users Stats -->
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Users</span>
                        <div class="stat-icon bg-blue">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value" data-target="<?php echo $stats['users']; ?>"><?php echo $total_users; ?></div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i>
                        <a href="manage_users.php">Active Job Seekers</a>
                    </div>
                </div>

                <!-- Employers Stats -->
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Employers</span>
                        <div class="stat-icon bg-green">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                    <div class="stat-value" data-target="<?php echo $stats['employers']; ?>"><?php echo $total_employers; ?></div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i>
                        <a href="manage_employers.php">Registered Companies</a>
                    </div>
                </div>

                <!-- Jobs Stats -->
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Jobs</span>
                        <div class="stat-icon bg-yellow">
                            <i class="fas fa-briefcase"></i>
                        </div>
                    </div>
                    <div class="stat-value" data-target="<?php echo $stats['jobs']; ?>"><?php echo $total_jobs; ?></div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i>
                        <a href="manage_jobs.php">Active Listings</a>
                    </div>
                </div>

                <!-- Applications Stats -->
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Applications</span>
                        <div class="stat-icon bg-red">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                    <div class="stat-value" data-target="<?php echo $stats['applications']; ?>"><?php echo $total_applicants; ?></div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i>
                        <a href="manage_applications.php">Job Applications</a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="recent-activity">
                <div class="section-header">
                    <h2 class="section-title">Recent Activity</h2>
                </div>
                <div class="activity-list">
                    <?php while($activity = mysqli_fetch_assoc($result_activities)): ?>
                        <div class="activity-item">
                            <div class="activity-icon bg-<?php echo strtolower(str_replace(' ', '-', $activity['type'])); ?>">
                                <i class="fas fa-<?php 
                                    echo $activity['type'] === 'New User' ? 'user' : 
                                        ($activity['type'] === 'New Employer' ? 'building' : 'briefcase'); 
                                ?>"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">
                                    <?php 
                                        echo $activity['type'] . ': ' . 
                                        htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); 
                                    ?>
                                </div>
                                <div class="activity-time">
                                    <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="admindashboard.js"></script>
</body>
</html>