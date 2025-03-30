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

// Check if job_id is provided
if (!isset($_GET['job_id'])) {
    $_SESSION['error'] = "Job ID not provided";
    header("Location: manage_jobs.php");
    exit();
}

$job_id = $_GET['job_id'];

// Fetch job details with employer information
$sql = "SELECT j.*, e.company_name, e.profile_image as employer_image 
        FROM tbl_jobs j 
        LEFT JOIN tbl_employer e ON j.employer_id = e.employer_id 
        WHERE j.job_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $job_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$job = mysqli_fetch_assoc($result);

if (!$job) {
    $_SESSION['error'] = "Job not found";
    header("Location: manage_jobs.php");
    exit();
}

// Fetch application statistics
$stats_sql = "SELECT 
    COUNT(*) as total_applications,
    COUNT(CASE WHEN status = 'applied' THEN 1 END) as pending_applications,
    COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_applications,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_applications
    FROM tbl_applications 
    WHERE job_id = ?";
$stats_stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stats_stmt, "i", $job_id);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);

// Fetch recent applications
$applications_sql = "SELECT a.*, u.first_name, u.last_name, u.profile_image, l.email, j.created_at 
                    FROM tbl_applications a 
                    LEFT JOIN tbl_user u ON a.user_id = u.user_id 
                    LEFT JOIN tbl_login l ON u.user_id = l.user_id 
                    LEFT JOIN tbl_jobs j ON a.job_id = j.job_id 
                    WHERE a.job_id = ? 
                    ORDER BY j.created_at DESC 
                    LIMIT 5";
$applications_stmt = mysqli_prepare($conn, $applications_sql);
mysqli_stmt_bind_param($applications_stmt, "i", $job_id);
mysqli_stmt_execute($applications_stmt);
$applications_result = mysqli_stmt_get_result($applications_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>View Job - FlexiHire Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="view_job.css">
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
                <a href="admindashboard.php" class="nav-item">
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
                <a href="manage_jobs.php" class="nav-item active">
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
            <header class="page-header">
                <div class="header-content">
                    <div class="header-left">
                        <h1>Job Details</h1>
                    </div>
                    <div class="header-actions">
                        <a href="manage_jobs.php" class="back-link">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back to Jobs</span>
                        </a>
                        <!-- <?php if ($job['is_deleted'] == 1): ?>
                            <button type="button" class="restore-btn" onclick="showRestoreConfirmation(<?= $job['job_id'] ?>)">
                                <i class="fas fa-undo"></i> Restore Job
                            </button>
                        <?php endif; ?> -->
                    </div>
                </div>
            </header>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Job Details Section -->
            <section class="job-details">
                <div class="job-header">
                    <div class="job-title-section">
                        <h2><?= htmlspecialchars($job['job_title']) ?></h2>
                        <div class="job-meta">
                            <span class="company">
                                <i class="fas fa-building"></i>
                                <?= htmlspecialchars($job['company_name']) ?>
                            </span>
                            <span class="location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($job['location']) ?>
                            </span>
                            <span class="salary">
                                <i class="fas fa-money-bill-wave"></i>
                                ₹<?= number_format($job['salary']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="job-status">
                        <span class="status-badge <?= strtolower($job['status']) ?>">
                            <?= ucfirst($job['status']) ?>
                        </span>
                        <span class="visibility-badge <?= $job['is_deleted'] ? 'deleted' : 'active' ?>">
                            <?= $job['is_deleted'] ? 'Deactivated' : 'Active' ?>
                        </span>
                    </div>
                </div>

                <div class="job-content">
                    <div class="job-description">
                        <h3>Job Description</h3>
                        <p><?= nl2br(htmlspecialchars($job['job_description'])) ?></p>
                    </div>

                    <div class="job-details-grid">
                        <div class="detail-item">
                            <i class="fas fa-calendar"></i>
                            <div class="detail-content">
                                <span class="label">Posted Date</span>
                                <span class="value"><?= date('M d, Y', strtotime($job['created_at'])) ?></span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-clock"></i>
                            <div class="detail-content">
                                <span class="label">Vacancy Date</span>
                                <span class="value"><?= date('M d, Y', strtotime($job['vacancy_date'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Application Statistics -->
            <section class="application-stats">
                <h3>Application Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon total">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= $stats['total_applications'] ?></span>
                            <span class="stat-label">Total Applications</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= $stats['pending_applications'] ?></span>
                            <span class="stat-label">Pending</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon accepted">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= $stats['accepted_applications'] ?></span>
                            <span class="stat-label">Accepted</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon rejected">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= $stats['rejected_applications'] ?></span>
                            <span class="stat-label">Rejected</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Recent Applications -->
            <section class="recent-applications">
                <div class="section-header">
                    <h3>Recent Applications</h3>
                    <div class="header-actions">
                        <a href="manage_jobs.php" class="back-link">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back to Jobs</span>
                        </a>
                    </div>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Applicant Name</th>
                                <th>Email</th>
                                <th>Created Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($application = mysqli_fetch_assoc($applications_result)): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <?php if (!empty($application['profile_image'])): ?>
                                                <img src="../database/profile_picture/<?php echo htmlspecialchars($application['profile_image']); ?>" 
                                                     alt="Profile" 
                                                     class="user-avatar">
                                            <?php else: ?>
                                                <img src="../assets/images/default-avatar.png" 
                                                     alt="Default Profile" 
                                                     class="user-avatar">
                                            <?php endif; ?>
                                            <span><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($application['email']) ?></td>
                                    <td><?= date('M d, Y', strtotime($application['created_at'])) ?></td>
                                    <td>
                                        <span class="status-badge <?= strtolower($application['status']) ?>">
                                            <?= ucfirst($application['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <!-- Confirmation Panels -->
    <div id="confirmationOverlay" class="overlay"></div>

    <!-- Restore Confirmation Panel -->
    <div class="confirmation-panel" id="restorePanel">
        <div class="confirmation-content">
            <div class="confirmation-icon success">
                <i class="fas fa-undo"></i>
            </div>
            <div class="confirmation-text">
                <h3>Restore Job</h3>
                <p>Are you sure you want to restore this job? It will become visible to users again.</p>
            </div>
            <div class="confirmation-actions">
                <button class="cancel-btn" onclick="hideRestoreConfirmation()">Cancel</button>
                <a href="#" id="confirmRestore" class="confirm-btn success">Restore</a>
            </div>
        </div>
    </div>

    <script src="view_job.js"></script>
</body>
</html>

<?php
mysqli_stmt_close($stmt);
mysqli_stmt_close($stats_stmt);
mysqli_stmt_close($applications_stmt);
mysqli_close($conn);
?>
