<?php
session_start();
include '../database/connectdatabase.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') 
{
    header("Location: ../login/loginvalidation.php");
    exit();
}

// Get all jobs with employer details
$stmt = $conn->prepare("
    SELECT j.job_id,
           j.job_title,
           j.job_description as description,
           j.location,
           j.salary,
           j.category,
           j.status,
           j.is_deleted,
           j.created_at,
           j.vacancy,
           j.application_deadline,
           j.interview,
           j.working_days,
           j.contact_no,
           e.company_name,
           e.profile_image,
           COUNT(DISTINCT a.id) as total_applications,
           COUNT(DISTINCT CASE WHEN a.status = 'Pending' THEN a.id END) as pending_applications,
           COUNT(DISTINCT CASE WHEN a.status = 'Accepted' THEN a.id END) as shortlisted_applications,
           COUNT(DISTINCT CASE WHEN a.status = 'Rejected' THEN a.id END) as rejected_applications
               FROM tbl_jobs j 
               LEFT JOIN tbl_employer e ON j.employer_id = e.employer_id 
    LEFT JOIN tbl_applications a ON j.job_id = a.job_id
    GROUP BY j.job_id, j.job_title, j.job_description, j.location, j.salary, 
             j.category, j.status, j.is_deleted, j.created_at, j.vacancy,
             j.application_deadline, j.interview, j.working_days, j.contact_no,
             e.company_name, e.profile_image
    ORDER BY 
        CASE 
            WHEN j.status = 'pending' THEN 1
            WHEN j.status = 'approved' THEN 2
            WHEN j.status = 'rejected' THEN 3
            ELSE 4
        END,
        j.created_at DESC
");
$stmt->execute();
$jobs = $stmt->get_result();

// Handle job status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $job_id = $_POST['job_id'];
    
    if ($_POST['action'] === 'deactivate') {
        // Check if there are any applications for this job
        $check_applications = $conn->prepare("SELECT COUNT(*) as application_count FROM tbl_applications WHERE job_id = ?");
        $check_applications->bind_param("i", $job_id);
        $check_applications->execute();
        $result = $check_applications->get_result();
        $application_count = $result->fetch_assoc()['application_count'];

        if ($application_count > 0) {
            $_SESSION['error'] = "Cannot deactivate this job as there are active applications.";
        } else {
            $stmt = $conn->prepare("UPDATE tbl_jobs SET is_deleted = 1 WHERE job_id = ?");
            $stmt->bind_param("i", $job_id);
            $stmt->execute();
            $_SESSION['success'] = "Job has been deactivated successfully";
        }
    } elseif ($_POST['action'] === 'activate') {
        $stmt = $conn->prepare("UPDATE tbl_jobs SET is_deleted = 0 WHERE job_id = ?");
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $_SESSION['success'] = "Job has been activated successfully";
    }
    
    header("Location: manage_jobs.php");
    exit();
}

// Get job counts for filters
$total_jobs = $jobs->num_rows;
$active_jobs = 0;
$deactivated_jobs = 0;

// Reset pointer to beginning of result set
$jobs->data_seek(0);

while ($job = $jobs->fetch_assoc()) {
    if ($job['is_deleted'] == 0) {
        $active_jobs++;
    } else {
        $deactivated_jobs++;
    }
}

// Reset pointer again for the main loop
$jobs->data_seek(0);

// Add after authentication check
// Message handling
$message = '';
$messageType = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
} elseif (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    $messageType = 'success';
    unset($_SESSION['success']);
} elseif (isset($_SESSION['error'])) {
    $message = $_SESSION['error'];
    $messageType = 'error';
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Jobs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admindashboard.css">
    <link rel="stylesheet" href="manage_jobs.css">
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
                    <i class="fas fa-flag"></i>
                    <span>Reports</span>
                </a>
                <a href="../Login/logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1>Manage Jobs</h1>
                <div class="header-actions">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search jobs...">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>" id="statusMessage">
                    <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="close-alert" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <div class="content-card">
                <div class="job-filters">
                    <button class="filter-btn active" data-filter="all">
                        All Jobs
                        <span class="count"><?php echo $total_jobs; ?></span>
                    </button>
                    <button class="filter-btn" data-filter="active">
                        Active
                        <span class="count"><?php echo $active_jobs; ?></span>
                    </button>
                    <button class="filter-btn" data-filter="deactivated">
                        Deactivated
                        <span class="count"><?php echo $deactivated_jobs; ?></span>
                    </button>
                </div>

                <div class="job-list">
                    <!-- Add this div for no search results message -->
                    <div id="noSearchResults" class="no-search-results" style="display: none;">
                        <div class="no-results-icon">
                            <i class="fas fa-search"></i>
                </div>
                        <h3>No Results Found</h3>
                        <p>We couldn't find any jobs matching your search criteria.</p>
                        <button onclick="clearSearch()" class="clear-search-btn">
                            <i class="fas fa-times"></i> Clear Search
                        </button>
                </div>

                    <?php if ($jobs->num_rows > 0): ?>
                        <?php while ($job = $jobs->fetch_assoc()): ?>
                            <div class="job-card" data-status="<?php echo $job['is_deleted'] ? 'deactivated' : 'active'; ?>">
                                <div class="job-info">
                                    <div class="employer-avatar">
                                        <?php if (!empty($job['profile_image'])): ?>
                                            <img src="<?= htmlspecialchars($job['profile_image']) ?>" alt="Employer Profile">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <i class="fas fa-building"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="job-details">
                                        <h3><?php echo htmlspecialchars($job['job_title']); ?></h3>
                                        <div class="job-meta">
                                            <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($job['company_name']); ?></span>
                                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                            <span><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($job['category']); ?></span>
                                            <span><i class="fas fa-money-bill-wave"></i> $<?php echo number_format($job['salary'], 2); ?></span>
                                        </div>
                                        <p class="job-description"><?php echo htmlspecialchars(substr($job['description'], 0, 150)) . '...'; ?></p>
                                        <div class="job-stats">
                                            <span><i class="fas fa-users"></i> <?php echo $job['total_applications']; ?> Applications</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="job-actions">
                                    <span class="job-status status-<?php echo $job['status']; ?>">
                                        <?php echo ucfirst($job['status']); ?>
                                        </span>
                                    <a href="view_job.php?job_id=<?php echo $job['job_id']; ?>" class="action-btn view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if ($job['status'] === 'pending'): ?>
                                        <a href="accept_job.php?job_id=<?php echo $job['job_id']; ?>" class="action-btn approve-btn">
                                            <i class="fas fa-check"></i> Approve
                                        </a>
                                        <a href="reject_job.php?job_id=<?php echo $job['job_id']; ?>" class="action-btn reject-btn">
                                            <i class="fas fa-times"></i> Reject
                                        </a>
                                    <?php elseif ($job['is_deleted'] == 0): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                            <input type="hidden" name="action" value="deactivate">
                                            <button type="button" class="action-btn deactivate-btn" onclick="showDeactivateConfirmation(<?php echo $job['job_id']; ?>)">
                                                <i class="fas fa-ban"></i> Deactivate
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="action-btn activate-btn">
                                                <i class="fas fa-check"></i> Activate
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                        <div class="no-jobs">
                            <i class="fas fa-briefcase"></i>
                            <h3>No Jobs Found</h3>
                            <p>There are no jobs posted on the platform yet.</p>
                        </div>
                    <?php endif; ?>
            </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <div id="confirmationOverlay" class="confirmation-overlay" style="display: none;">
        <div id="deactivatePanel" class="confirmation-panel">
            <div class="confirmation-header">
                <div class="warning-icon">
                    <i class="fas fa-exclamation"></i>
                </div>
                <h3>Deactivate Job?</h3>
            </div>
            <div class="confirmation-content">
                <p>This job will no longer be visible or accessible to users.</p>
                <form method="POST" action="delete_job.php" id="deactivateForm" class="deactivate-form">
                    <input type="hidden" name="job_id" id="deactivateJobId">
                    <div class="form-group">
                        <label for="deactivateReason">Reason for Deactivation:</label>
                        <textarea id="deactivateReason" name="deactivate_reason" rows="3" 
                                  placeholder="Please provide a reason for deactivating this job..." 
                                  required></textarea>
                    </div>
                    <div class="confirmation-actions">
                        <button type="button" class="cancel-btn" onclick="hideDeactivateConfirmation()">Cancel</button>
                        <button type="submit" class="confirm-btn">Deactivate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="manage_jobs.js"></script>
</body>
</html>