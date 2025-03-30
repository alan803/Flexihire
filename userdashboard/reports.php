<?php
    session_start();
    include '../database/connectdatabase.php';

    // Check if admin is logged in
    if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
        // Set cache-control headers
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    // Set cache-control headers for authenticated users too
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    // Fetch report statistics
    $stats_sql = "SELECT 
        (SELECT COUNT(*) FROM tbl_reports WHERE status = 'pending') as pending_reports,
        (SELECT COUNT(*) FROM tbl_reports WHERE status = 'resolved') as resolved_reports,
        (SELECT COUNT(*) FROM tbl_reports WHERE status = 'rejected') as rejected_reports,
        (SELECT COUNT(*) FROM tbl_reports) as total_reports,
        (SELECT COUNT(*) FROM tbl_reports WHERE reported_job_id IS NOT NULL) as job_reports,
        (SELECT COUNT(*) FROM tbl_reports WHERE reported_employer_id IS NOT NULL) as employer_reports,
        (SELECT COUNT(*) FROM tbl_reports WHERE reported_user_id IS NOT NULL) as user_reports";

    $stats_result = mysqli_query($conn, $stats_sql);
    $stats = mysqli_fetch_assoc($stats_result);

    // Fetch all reports with details
    $reports_sql = "SELECT r.*, 
                    u.first_name as reporter_name,
                    u.profile_image as reporter_picture,
                    l.email as reporter_email,
                    CASE 
                        WHEN r.reported_job_id IS NOT NULL THEN ej.company_name
                        WHEN r.reported_employer_id IS NOT NULL THEN e.company_name
                        WHEN r.reported_user_id IS NOT NULL THEN CONCAT(u2.first_name, ' ', u2.last_name)
                    END as reported_entity,
                    CASE 
                        WHEN r.reported_job_id IS NOT NULL THEN j.employer_id
                        ELSE r.reported_employer_id
                    END as employer_id,
                    CASE 
                        WHEN r.reported_job_id IS NOT NULL THEN ej.company_name
                        ELSE e.company_name
                    END as company_name
                    FROM tbl_reports r 
                    LEFT JOIN tbl_user u ON r.reporter_id = u.user_id
                    LEFT JOIN tbl_login l ON u.user_id = l.user_id
                    LEFT JOIN tbl_jobs j ON r.reported_job_id = j.job_id
                    LEFT JOIN tbl_employer ej ON j.employer_id = ej.employer_id
                    LEFT JOIN tbl_employer e ON r.reported_employer_id = e.employer_id
                    LEFT JOIN tbl_user u2 ON r.reported_user_id = u2.user_id
                    ORDER BY r.created_at DESC";
    $reports_result = mysqli_query($conn, $reports_sql);

    // Handle report status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id']) && isset($_POST['action'])) {
        $report_id = $_POST['report_id'];
        $action = $_POST['action'];
        $resolution_notes = $_POST['resolution_notes'] ?? '';
        
        $update_sql = "UPDATE tbl_reports SET 
                       status = ?, 
                       resolution_notes = ?,
                       resolved_at = NOW()
                       WHERE report_id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "ssi", $action, $resolution_notes, $report_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Report status updated successfully";
        } else {
            $_SESSION['error'] = "Error updating report status";
        }
        
        header("Location: reports.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports - FlexiHire Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="reports.css">
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
                        <h1>Manage Reports</h1>
                    </div>
                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search reports..." id="searchInput">
                        </div>
                    </div>
                </div>
            </header>

            <?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
                <div class="alert alert-<?= isset($_SESSION['success']) ? 'success' : 'error' ?>" id="statusMessage">
                    <i class="fas <?= isset($_SESSION['success']) ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <span>
                        <?php 
                        if (isset($_SESSION['success'])) {
                            echo htmlspecialchars($_SESSION['success']);
                            unset($_SESSION['success']);
                        } elseif (isset($_SESSION['error'])) {
                            echo htmlspecialchars($_SESSION['error']);
                            unset($_SESSION['error']);
                        }
                        ?>
                    </span>
                    <button type="button" class="close-alert" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Report Statistics -->
            <section class="stats-overview">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= $stats['pending_reports'] ?></span>
                            <span class="stat-label">Pending Reports</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon resolved">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= $stats['resolved_reports'] ?></span>
                            <span class="stat-label">Resolved Reports</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon rejected">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= $stats['rejected_reports'] ?></span>
                            <span class="stat-label">Rejected Reports</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon reports">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div class="stat-content">
                            <span class="stat-value"><?= $stats['total_reports'] ?></span>
                            <span class="stat-label">Total Reports</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Reports List -->
            <section class="reports-section">
                <div class="section-header">
                    <h3>All Reports</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Reporter</th>
                                <th>Reported Entity</th>
                                <th>Reason</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $row_number = 1;
                            while ($report = mysqli_fetch_assoc($reports_result)): 
                            ?>
                                <tr>
                                    <td><?= $row_number++ ?></td>
                                    <td>
                                        <div class="reporter-info">
                                            <div class="reporter-avatar">
                                                <?php if ($report['reporter_picture']): ?>
                                                    <img src="../database/profile_picture/<?= htmlspecialchars($report['reporter_picture']) ?>" alt="Profile Picture">
                                                <?php else: ?>
                                                    <div class="avatar-placeholder">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="reporter-details">
                                                <span class="reporter-name"><?= htmlspecialchars($report['reporter_name']) ?></span>
                                                <span class="reporter-email"><?= htmlspecialchars($report['reporter_email']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($report['reported_entity']) ?></td>
                                    <td><?= htmlspecialchars(substr($report['reason'], 0, 50)) . '...' ?></td>
                                    <td><?= date('M d, Y', strtotime($report['created_at'])) ?></td>
                                    <td>
                                        <span class="status-badge <?= strtolower($report['status']) ?>">
                                            <?= ucfirst($report['status']) ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <?php if ($report['status'] === 'pending'): ?>
                                            <a href="view_report.php?report_id=<?= $report['report_id'] ?>" class="view-btn" title="View Report Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="#" onclick="showResolveModal(<?= $report['report_id'] ?>)" class="action-icon resolve" title="Resolve Report">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                            <a href="#" onclick="showRejectModal(<?= $report['report_id'] ?>)" class="action-icon reject" title="Reject Report">
                                                <i class="fas fa-times-circle"></i>
                                            </a>
                                        <?php elseif ($report['status'] === 'resolved'): ?>
                                            <a href="send_report_message.php?report_id=<?= $report['report_id'] ?>&job_id=<?= $report['reported_job_id'] ?>&employer_id=<?= $report['employer_id'] ?>" 
                                               class="action-icon email" 
                                               title="Send Email">
                                                <i class="fas fa-envelope"></i>
                                            </a>
                                            <a href="#" 
                                               onclick="showDeactivateModal(<?= $report['reported_job_id'] ? $report['employer_id'] : $report['reported_employer_id'] ?>, '<?= htmlspecialchars($report['company_name']) ?>')" 
                                               class="action-icon deactivate" 
                                               title="Deactivate Account">
                                                <i class="fas fa-user-slash"></i>
                                            </a>
                                        <?php elseif ($report['status'] === 'rejected'): ?>
                                            <a href="view_report.php?report_id=<?= $report['report_id'] ?>" class="view-btn" title="View Report Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <!-- Report Details Modal -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Report Details</h3>
                <button class="close-btn" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="reportDetails"></div>
            </div>
        </div>
    </div>

    <!-- Resolve Modal -->
    <div id="resolveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Resolve Report</h3>
                <button type="button" class="close-btn" onclick="window.location.href='reports.php'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="resolveForm" method="POST" action="resolve_report.php">
                <div class="modal-body">
                    <input type="hidden" name="report_id" id="resolveReportId">
                    <input type="hidden" name="action" value="resolved">
                    <div class="form-group">
                        <label for="resolveNotes">Resolution Notes</label>
                        <textarea name="resolution_notes" id="resolveNotes" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="cancel-btn" onclick="window.location.href='reports.php'">Cancel</button>
                    <button type="submit" class="confirm-btn success">Resolve Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reject Report</h3>
                <button type="button" class="close-btn" onclick="window.location.href='reports.php'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="rejectForm" method="POST" action="reject_report.php">
                <div class="modal-body">
                    <input type="hidden" name="report_id" id="rejectReportId">
                    <input type="hidden" name="action" value="rejected">
                    <div class="form-group">
                        <label for="rejectNotes">Resolution Notes</label>
                        <textarea name="resolution_notes" id="rejectNotes" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="cancel-btn" onclick="window.location.href='reports.php'">Cancel</button>
                    <button type="submit" class="confirm-btn danger">Reject Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Deactivate Modal -->
    <div id="deactivateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Deactivate Employer Account</h3>
                <button type="button" class="close-btn" onclick="closeDeactivateModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="deactivateForm" method="POST" action="deactivate_employer.php">
                <div class="modal-body">
                    <input type="hidden" name="employer_id" id="deactivateEmployerId">
                    <p>Are you sure you want to deactivate this employer account?</p>
                    <p class="employer-name" id="deactivateEmployerName"></p>
                    <div class="form-group">
                        <label for="deactivateReason">Reason for Deactivation:</label>
                        <textarea name="reason" id="deactivateReason" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="cancel-btn" onclick="closeDeactivateModal()">Cancel</button>
                    <button type="submit" class="confirm-btn danger">Deactivate Account</button>
                </div>
            </form>
        </div>
    </div>

    <script src="reports.js"></script>
</body>
</html>

<?php
mysqli_close($conn);
?>