<?php
session_start();
include '../database/connectdatabase.php';

if(!isset($_SESSION['admin_id'])) {
    header("Location: ../loginvalidation.php");
    exit();
}

if(!isset($_GET['report_id'])) {
    header("Location: reports.php");
    exit();
}

$report_id = $_GET['report_id'];

// Fetch report details
$sql = "SELECT r.*, 
        u.first_name as reporter_name,
        u.profile_image as reporter_picture,
        l.email as reporter_email,
        CASE 
            WHEN r.reported_job_id IS NOT NULL THEN 'Job'
            WHEN r.reported_employer_id IS NOT NULL THEN 'Employer'
            WHEN r.reported_user_id IS NOT NULL THEN 'User'
        END as report_type,
        CASE 
            WHEN r.reported_job_id IS NOT NULL THEN j.job_title
            WHEN r.reported_employer_id IS NOT NULL THEN e.company_name
            WHEN r.reported_user_id IS NOT NULL THEN CONCAT(u2.first_name, ' ', u2.last_name)
        END as reported_entity,
        j.employer_id as job_employer_id,
        e.company_name as employer_name,
        le.email as employer_email
        FROM tbl_reports r 
        LEFT JOIN tbl_user u ON r.reporter_id = u.user_id
        LEFT JOIN tbl_login l ON u.user_id = l.user_id
        LEFT JOIN tbl_jobs j ON r.reported_job_id = j.job_id
        LEFT JOIN tbl_employer e ON (r.reported_employer_id = e.employer_id OR j.employer_id = e.employer_id)
        LEFT JOIN tbl_login le ON e.employer_id = le.employer_id
        LEFT JOIN tbl_user u2 ON r.reported_user_id = u2.user_id
        WHERE r.report_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $report_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(!$report = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "Report not found";
    header("Location: reports.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Details - FlexiHire Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="view_report.css">
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
                <a href="manage_jobs.php" class="nav-item">
                    <i class="fas fa-briefcase"></i>
                    <span>Manage Jobs</span>
                </a>
                <a href="reports.php" class="nav-item active">
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
                        <a href="reports.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i>
                            Back to Reports
                        </a>
                        <h1>Report Details</h1>
                    </div>
                </div>
            </header>

            <div class="report-details-container">
                <div class="report-card">
                    <div class="reporter-header">
                        <div class="reporter-avatar">
                            <?php if ($report['reporter_picture']): ?>
                                <img src="../database/profile_picture/<?= htmlspecialchars($report['reporter_picture']) ?>" alt="Profile Picture">
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="reporter-info">
                            <span class="reporter-name"><?= htmlspecialchars($report['reporter_name']) ?></span>
                            <span class="reporter-email"><?= htmlspecialchars($report['reporter_email']) ?></span>
                        </div>
                    </div>

                    <div class="detail-group">
                        <label>Report Type</label>
                        <span class="type-badge <?= strtolower($report['report_type']) ?>">
                            <?= $report['report_type'] ?>
                        </span>
                    </div>

                    <div class="detail-group">
                        <label>Reported Entity</label>
                        <div class="reported-entity">
                            <div class="entity-info">
                                <span class="entity-name"><?= htmlspecialchars($report['reported_entity']) ?></span>
                            </div>
                            <!-- <?php if ($report['report_type'] === 'Job' && $report['job_employer_id']): ?>
                                <a href="view_emplloyer_report.php?employer_id=<?= $report['job_employer_id'] ?>" class="view-employer-btn">
                                    <i class="fas fa-building"></i> View Employer
                                </a>
                            <?php endif; ?> -->
                        </div>
                    </div>

                    <div class="detail-group">
                        <label>Reason</label>
                        <div class="message-content"><?= nl2br(htmlspecialchars($report['reason'])) ?></div>
                    </div>

                    <div class="detail-group">
                        <label>Status</label>
                        <span class="status-badge <?= strtolower($report['status']) ?>">
                            <?= ucfirst($report['status']) ?>
                        </span>
                    </div>

                    <div class="detail-group">
                        <label>Date Reported</label>
                        <span><?= date('M d, Y H:i', strtotime($report['created_at'])) ?></span>
                    </div>

                    <?php if ($report['resolution_notes']): ?>
                        <div class="detail-group">
                            <label>Resolution Notes</label>
                            <div class="resolution-notes"><?= nl2br(htmlspecialchars($report['resolution_notes'])) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($report['status'] === 'pending'): ?>
                        <div class="action-buttons">
                            <form action="reports.php" method="POST" class="inline-form">
                                <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                                <input type="hidden" name="action" value="resolved">
                                <button type="submit" class="resolve-btn">
                                    <i class="fas fa-check"></i> Resolve Report
                                </button>
                            </form>
                            <form action="reports.php" method="POST" class="inline-form">
                                <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                                <input type="hidden" name="action" value="rejected">
                                <button type="submit" class="reject-btn">
                                    <i class="fas fa-times"></i> Reject Report
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
mysqli_close($conn);
?> 