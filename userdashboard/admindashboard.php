<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // Add the time_elapsed_string function here
    function time_elapsed_string($datetime) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        if ($diff->y > 0) {
            return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        }
        if ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        }
        if ($diff->d > 0) {
            if ($diff->d == 1) {
                return 'Yesterday';
            }
            return $diff->d . ' days ago';
        }
        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        }
        if ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        }
        return 'Just now';
    }

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

    //fetching no of reports
    $sql_total_reports="SELECT COUNT(*) AS total_reports FROM tbl_reports";
    $result_total_reports=mysqli_query($conn,$sql_total_reports);
    $row_total_reports=mysqli_fetch_assoc($result_total_reports);
    $total_reports=$row_total_reports['total_reports'];

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

// Add this before the HTML section, with your other queries
// Recent Activities Query
$sql_activities = "SELECT * FROM (
    SELECT 
        'New User' as type,
        u.first_name,
        u.last_name,
        l.email,
        u.created_at,
        l.status,
        'user' as category
    FROM tbl_user u
    LEFT JOIN tbl_login l ON u.user_id = l.user_id
    
    UNION ALL
    
    SELECT 
        'New Employer' as type,
        e.company_name as first_name,
        '' as last_name,
        l.email,
        e.created_at,
        l.status,
        'employer' as category
    FROM tbl_employer e
    LEFT JOIN tbl_login l ON e.employer_id = l.employer_id
    
    UNION ALL
    
    SELECT 
        'New Job' as type,
        j.job_title as first_name,
        e.company_name as last_name,
        l.email,
        j.created_at,
        j.status,
        'job' as category
    FROM tbl_jobs j
    JOIN tbl_employer e ON j.employer_id = e.employer_id
    LEFT JOIN tbl_login l ON e.employer_id = l.employer_id
) as activities 
ORDER BY created_at DESC 
LIMIT 10";

$result_activities = mysqli_query($conn, $sql_activities);

if (!$result_activities) {
    die("Query failed: " . mysqli_error($conn));
}
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
                <div class="admin-badge">
                    <i class="fas fa-user-shield"></i>
                    <span>Admin Dashboard</span>
                </div>
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
                    <div class="stat-value" data-target="<?php echo $total_users; ?>">0</div>
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
                    <div class="stat-value" data-target="<?php echo $total_employers; ?>">0</div>
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
                    <div class="stat-value" data-target="<?php echo $total_jobs; ?>">0</div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i>
                        <a href="manage_jobs.php">Active Listings</a>
                    </div>
                </div>

                <!-- Reports Stats -->
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Reports</span>
                        <div class="stat-icon bg-red">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                    <div class="stat-value" data-target="<?php echo $total_reports; ?>">0</div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i>
                        <a href="reports.php">View Reports</a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section with essential information -->
            <div class="recent-activity">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Recent Activities</h2>
                </div>
                
                <div class="activity-list">
                    <?php if(mysqli_num_rows($result_activities) > 0): ?>
                        <?php while($activity = mysqli_fetch_assoc($result_activities)): ?>
                            <div class="activity-item">
                                <div class="activity-icon bg-<?php echo $activity['category']; ?>">
                                    <i class="fas fa-<?php 
                                        echo $activity['category'] === 'user' ? 'user' : 
                                            ($activity['category'] === 'employer' ? 'building' : 
                                                ($activity['category'] === 'job' ? 'briefcase' : 'file-alt')); 
                                    ?>"></i>
                                </div>
                                
                                <div class="activity-content">
                                    <div class="activity-header">
                                        <div class="activity-info">
                                            <span class="activity-name">
                                                <?php 
                                                switch($activity['category']) {
                                                    case 'user':
                                                        echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']);
                                                        break;
                                                    case 'employer':
                                                        echo htmlspecialchars($activity['first_name']); // company name
                                                        break;
                                                    case 'job':
                                                        echo htmlspecialchars($activity['first_name']); // job title
                                                        echo ' at ' . htmlspecialchars($activity['last_name']); // company name
                                                        break;
                                                    default:
                                                        echo htmlspecialchars($activity['first_name']);
                                                }
                                                ?>
                                            </span>
                                            <span class="activity-type"><?php echo $activity['type']; ?></span>
                                        </div>
                                        <span class="activity-time">
                                            <?php echo time_elapsed_string($activity['created_at']); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if($activity['email']): ?>
                                        <div class="activity-subtitle">
                                            <i class="fas fa-envelope"></i>
                                            <?php echo htmlspecialchars($activity['email']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="activity-status">
                                        <span class="status-badge <?php echo strtolower($activity['status']); ?>">
                                            <?php echo $activity['status']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-activity">
                            <i class="fas fa-info-circle"></i>
                            <p>No recent activities found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="admindashboard.js"></script>
</body>
</html>