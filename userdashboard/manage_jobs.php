<?php
session_start();
include '../database/connectdatabase.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

// Pagination setup
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$records_per_page = 10;
$offset = max(0, ($page - 1) * $records_per_page);

// Fetch jobs statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
    COUNT(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE()) THEN 1 END) as new
    FROM tbl_jobs";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Fetch all jobs with employer details
$jobs_query = "SELECT j.job_id, j.job_title, j.status, j.created_at, j.is_deleted, e.company_name 
               FROM tbl_jobs j 
               LEFT JOIN tbl_employer e ON j.employer_id = e.employer_id 
               ORDER BY j.created_at DESC";
$jobs_result = mysqli_query($conn, $jobs_query);

// Total records for pagination
$total_query = "SELECT COUNT(*) as count FROM tbl_jobs";
$total_result = mysqli_query($conn, $total_query);
$total_records = mysqli_fetch_assoc($total_result)['count'];
$total_pages = ceil($total_records / $records_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Manage Jobs - FlexiHire Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="manage_jobs.css">
    <style>
        /* ... existing styles ... */

        .accept-btn {
            background-color: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .accept-btn:hover {
            background-color: #bbf7d0;
            color: #15803d;
            border-color: #86efac;
        }

        .accept-btn i {
            color: #16a34a;
        }

        .confirmation-icon.success {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .confirm-btn.success {
            background-color: #16a34a;
            color: white;
        }

        .confirm-btn.success:hover {
            background-color: #15803d;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-section">
                <h1>FlexiHire</h1>
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
                <h1>Manage Jobs</h1>
                <div class="header-actions">
                    <button class="export-btn" aria-label="Export jobs data">
                        <i class="fas fa-download"></i> Export Jobs
                    </button>
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

            <!-- Stats Section -->
            <section class="users-stats" aria-label="Job statistics">
                <div class="stat-item">
                    <span class="stat-value"><?= number_format($stats['total']) ?></span>
                    <span class="stat-label">Total Jobs</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= number_format($stats['active']) ?></span>
                    <span class="stat-label">Active Jobs</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= number_format($stats['new']) ?></span>
                    <span class="stat-label">New This Month</span>
                </div>
            </section>

            <!-- Jobs Table -->
            <section class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Posted Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($jobs_result) > 0): ?>
                            <?php while ($job = mysqli_fetch_assoc($jobs_result)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($job['job_title']) ?></td>
                                    <td><?= htmlspecialchars($job['company_name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($job['created_at'])) ?></td>
                                    <td>
                                        <span class="status-badge <?= strtolower($job['status']) ?>">
                                            <?= ucfirst($job['status']) ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="view_job.php?job_id=<?= $job['job_id'] ?>" class="view-btn">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($job['is_deleted'] == 1): ?>
                                            <button type="button" class="restore-btn" onclick="showRestoreConfirmation(<?= $job['job_id'] ?>)">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        <?php else: ?>
                                            <?php if (strtolower($job['status']) === 'pending'): ?>
                                                <button type="button" class="accept-btn" onclick="showAcceptConfirmation(<?= $job['job_id'] ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="reject-btn" onclick="showRejectConfirmation(<?= $job['job_id'] ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if (strtolower($job['status']) === 'active'): ?>
                                                <button type="button" class="delete-btn" onclick="showConfirmation(<?= $job['job_id'] ?>)">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($job['is_deleted'] != 1): ?>
                                            <button type="button" class="trash-btn" onclick="showDeleteConfirmation(<?= $job['job_id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="no-records">No jobs found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav class="pagination" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1" class="page-link" aria-label="First page">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?page=<?= $page-1 ?>" class="page-link" aria-label="Previous page">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                        <a href="?page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>" aria-label="Page <?= $i ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page+1 ?>" class="page-link" aria-label="Next page">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?page=<?= $total_pages ?>" class="page-link" aria-label="Last page">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Confirmation Panels -->
    <div id="confirmationOverlay" class="overlay"></div>

    <!-- Delete Confirmation Panel -->
    <div class="confirmation-panel" id="deletePanel">
        <div class="confirmation-content">
            <div class="confirmation-icon danger">
                <i class="fas fa-trash-alt"></i>
            </div>
            <div class="confirmation-text">
                <h3>Delete Job</h3>
                <p>Are you sure you want to permanently delete this job? This action cannot be undone.</p>
            </div>
            <div class="confirmation-actions">
                <button class="cancel-btn" onclick="hideDeleteConfirmation()">Cancel</button>
                <a href="#" id="confirmDelete" class="confirm-btn danger">Delete</a>
            </div>
        </div>
    </div>

    <!-- Deactivate Confirmation Panel -->
    <div class="confirmation-panel" id="deactivatePanel">
        <div class="confirmation-content">
            <div class="confirmation-icon warning">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="confirmation-text">
                <h3>Deactivate Job</h3>
                <p>Are you sure you want to deactivate this job posting? It will no longer be visible to users.</p>
            </div>
            <div class="confirmation-actions">
                <button class="cancel-btn" onclick="hideConfirmation()">Cancel</button>
                <a href="#" id="confirmDeactivate" class="confirm-btn warning">Deactivate</a>
            </div>
        </div>
    </div>

    <!-- Activate Confirmation Panel -->
    <div class="confirmation-panel" id="activatePanel">
        <div class="confirmation-content">
            <div class="confirmation-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="confirmation-text">
                <h3>Activate Job</h3>
                <p>Are you sure you want to activate this job posting? It will become visible to users.</p>
            </div>
            <div class="confirmation-actions">
                <button class="cancel-btn" onclick="hideActivateConfirmation()">Cancel</button>
                <a href="#" id="confirmActivate" class="confirm-btn success">Activate</a>
            </div>
        </div>
    </div>

    <!-- Reject Confirmation Panel -->
    <div class="confirmation-panel" id="rejectPanel">
        <div class="confirmation-content">
            <div class="confirmation-icon danger">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="confirmation-text">
                <h3>Reject Job</h3>
                <p>Are you sure you want to reject this job posting? This action cannot be undone.</p>
            </div>
            <div class="confirmation-actions">
                <button class="cancel-btn" onclick="hideRejectConfirmation()">Cancel</button>
                <a href="#" id="confirmReject" class="confirm-btn danger">Reject</a>
            </div>
        </div>
    </div>

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

    <!-- Accept Confirmation Panel -->
    <div class="confirmation-panel" id="acceptPanel">
        <div class="confirmation-content">
            <div class="confirmation-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="confirmation-text">
                <h3>Accept Job</h3>
                <p>Are you sure you want to accept this job? It will become visible to users.</p>
            </div>
            <div class="confirmation-actions">
                <button class="cancel-btn" onclick="hideAcceptConfirmation()">Cancel</button>
                <a href="#" id="confirmAccept" class="confirm-btn success">Accept</a>
            </div>
        </div>
    </div>

    <script src="manage_jobs.js" defer></script>
</body>
</html>

<?php mysqli_close($conn); ?>