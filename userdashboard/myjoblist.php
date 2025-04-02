<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    //session checking
    if(!isset($_SESSION['employer_id']))
    {
        header("location:../login/loginvalidation.php");
        exit();
    }
    $employer_id=$_SESSION['employer_id'];

    // Get employer details
    $employer_query = "SELECT company_name FROM tbl_employer WHERE employer_id = ?";
    $stmt_employer = mysqli_prepare($conn, $employer_query);
    mysqli_stmt_bind_param($stmt_employer, "i", $employer_id);
    mysqli_stmt_execute($stmt_employer);
    $employer_result = mysqli_stmt_get_result($stmt_employer);
    $employer_data = mysqli_fetch_assoc($employer_result);

    // Fetch jobs with application counts and employer details
    $sql = "SELECT j.*, 
               e.company_name,
               e.profile_image,
               COUNT(a.id) as application_count
        FROM tbl_jobs j
        LEFT JOIN tbl_employer e ON j.employer_id = e.employer_id
        LEFT JOIN tbl_applications a ON j.job_id = a.job_id
        WHERE j.employer_id = ?
        GROUP BY j.job_id
        ORDER BY j.created_at DESC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Count active and inactive jobs
    $active_count = 0;
    $inactive_count = 0;
    $jobs = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $jobs[] = $row;
        if ($row['is_deleted'] == 0) {
            $active_count++;
        } else {
            $inactive_count++;
        }
    }

    // Move message handling to use session instead of GET
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']); // Clear the message after retrieving
    } else {
        $message = '';
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs | AutoRecruits</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="myjoblist.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-container">
        <?php if (!empty($message)): ?>
            <div class="alert-message success" id="alertMessage">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1>My Posted Jobs</h1>
            <div class="header-actions">
                <div class="stats">
                    <div class="stat-item">
                        <i class="fas fa-briefcase"></i>
                        <span>Active Jobs: <?php echo $active_count; ?></span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-archive"></i>
                        <span>Inactive Jobs: <?php echo $inactive_count; ?></span>
                    </div>
                </div>
                <a href="postjob.php" class="post-job-btn">
                    <i class="fas fa-plus"></i> Post New Job
                </a>
            </div>
        </div>

        <div class="jobs-container">
            <?php if (!empty($jobs)): ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-header">
                            <div class="company-info">
                                <?php if (!empty($job['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($job['profile_image']); ?>" alt="Company Logo">
                                <?php else: ?>
                                    <div class="company-placeholder">
                                        <i class="fas fa-building"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h2><?php echo htmlspecialchars($job['job_title']); ?></h2>
                                    <p class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                </div>
                            </div>
                            <div class="job-status">
                                <span class="status-badge <?php echo $job['is_deleted'] ? 'inactive' : 'active'; ?>">
                                    <?php echo $job['is_deleted'] ? 'Inactive' : 'Active'; ?>
                                </span>
                                <?php if ($job['status'] === 'pending'): ?>
                                    <span class="status-badge pending">Pending Approval</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="job-details">
                            <div class="detail-row">
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($job['location']); ?>, <?php echo htmlspecialchars($job['town']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>₹<?php echo number_format($job['salary'], 2); ?>/month</span>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo date('h:i A', strtotime($job['start_time'])); ?> - <?php echo date('h:i A', strtotime($job['end_time'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-calendar-week"></i>
                                    <span><?php echo htmlspecialchars($job['working_days']); ?></span>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-item">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo htmlspecialchars($job['vacancy']); ?> Vacancies</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-hourglass-end"></i>
                                    <span>Deadline: <?php echo date('M d, Y', strtotime($job['application_deadline'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="job-footer">
                            <div class="application-info">
                                <i class="fas fa-file-alt"></i>
                                <span><?php echo $job['application_count']; ?> Applications</span>
                            </div>
                            <div class="action-buttons">
                                <!-- <a href="view_job.php?id=<?php echo $job['employer_id']; ?>" class="btn view-btn">
                                    <i class="fas fa-eye"></i> View
                                </a> -->
                                <a href="applicants.php?job_id=<?php echo $job['job_id']; ?>" class="btn applications-btn">
                                    <i class="fas fa-users"></i> Applications
                                </a>
                                <?php if (!$job['is_deleted']): ?>
                                    <?php if ($job['application_count'] > 0): ?>
                                        <button class="btn delete-btn disabled" disabled title="Cannot deactivate: Job has active applications">
                                            <i class="fas fa-trash-alt"></i> Deactivate
                                        </button>
                                    <?php else: ?>
                                        <button onclick="confirmDeactivation(<?php echo $job['job_id']; ?>)" class="btn delete-btn">
                                            <i class="fas fa-trash-alt"></i> Deactivate
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button onclick="confirmActivation(<?php echo $job['job_id']; ?>)" class="btn activate-btn">
                                        <i class="fas fa-redo"></i> Activate
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-jobs">
                    <i class="fas fa-briefcase"></i>
                    <h3>No Jobs Found</h3>
                    <p>You haven't posted any jobs yet. Start by posting your first job!</p>
                    <!-- <a href="postjob.php" class="post-job-btn">
                        <i class="fas fa-plus"></i> Post a New Job
                    </a> -->
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <h2>Confirm Deactivation</h2>
            <p>Are you sure you want to deactivate this job?</p>
            <div class="modal-buttons">
                <button id="confirmDeactivate" class="btn delete-btn">
                    <i class="fas fa-trash-alt"></i> Deactivate
                </button>
                <button id="cancelDeactivate" class="btn cancel-btn">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <div id="activationModal" class="modal">
        <div class="modal-content">
            <h2>Confirm Activation</h2>
            <p>Are you sure you want to activate this job?</p>
            <div class="modal-buttons">
                <button id="confirmActivate" class="btn activate-btn">
                    <i class="fas fa-redo"></i> Activate
                </button>
                <button id="cancelActivate" class="btn cancel-btn">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
    const deactivateModal = document.getElementById('confirmationModal');
    const activateModal = document.getElementById('activationModal');
    let jobIdToDeactivate = null;
    let jobIdToActivate = null;

    function confirmDeactivation(jobId) {
        jobIdToDeactivate = jobId;
        deactivateModal.style.display = 'flex';
    }

    function confirmActivation(jobId) {
        jobIdToActivate = jobId;
        activateModal.style.display = 'flex';
    }

    document.getElementById('confirmDeactivate').addEventListener('click', function() {
        if (jobIdToDeactivate) {
            window.location.href = 'deletejob.php?id=' + jobIdToDeactivate;
        }
    });

    document.getElementById('confirmActivate').addEventListener('click', function() {
        if (jobIdToActivate) {
            window.location.href = 'restore_job.php?id=' + jobIdToActivate;
        }
    });

    document.getElementById('cancelDeactivate').addEventListener('click', function() {
        deactivateModal.style.display = 'none';
        jobIdToDeactivate = null;
    });

    document.getElementById('cancelActivate').addEventListener('click', function() {
        activateModal.style.display = 'none';
        jobIdToActivate = null;
    });

    window.addEventListener('click', function(event) {
        if (event.target == deactivateModal) {
            deactivateModal.style.display = 'none';
            jobIdToDeactivate = null;
        }
        if (event.target == activateModal) {
            activateModal.style.display = 'none';
            jobIdToActivate = null;
        }
    });

    function showCannotDeactivateMessage() {
        alert('Cannot deactivate this job as it has active applications.');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const alertMessage = document.getElementById('alertMessage');
        if (alertMessage) {
            setTimeout(function() {
                alertMessage.style.opacity = '0';
                setTimeout(function() {
                    alertMessage.style.display = 'none';
                }, 300);
            }, 3000);
        }
    });
    </script>

    <style>
    /* Main container adjustments */
    .main-container {
        margin-left: 280px; /* Sidebar width */
        margin-top: 60px;  /* Navbar height */
        padding: 20px;
        min-height: calc(100vh - 60px);
        background: #f8f9fa;
    }

    /* Jobs container adjustments */
    .jobs-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px 0;
    }

    /* Page header adjustments */
    .page-header {
        margin-bottom: 25px;
        padding: 0 15px;
    }

    .page-header h1 {
        font-size: 24px;
        color: #333;
        margin: 0;
        margin-bottom: 15px;
    }

    .header-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
    }

    /* Stats section refinements */
    .stats {
        display: flex;
        gap: 20px;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .stat-item i {
        font-size: 16px;
        color: #4a90e2;
    }

    .stat-item span {
        font-size: 14px;
        color: #333;
        font-weight: 500;
    }

    /* Post job button refinements */
    .post-job-btn {
        padding: 8px 15px;
        font-size: 14px;
        background-color: #4a90e2;
        color: white;
        border: none;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        transition: background-color 0.3s;
        height: 35px;  /* Fixed height */
    }

    .post-job-btn i {
        font-size: 12px;
    }

    .post-job-btn:hover {
        background-color: #357abd;
    }

    /* Alert message positioning */
    .alert-message {
        position: fixed;
        top: 80px; /* Adjusted to appear below navbar */
        right: 20px;
        z-index: 1000;
    }

    /* Modal positioning */
    .modal {
        z-index: 1100; /* Higher than navbar */
    }

    /* Job card adjustments */
    .job-card {
        margin-bottom: 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px;
    }

    /* Alert message styling */
    .alert-message {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 1000;
        transition: opacity 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .alert-message.success {
        background-color: #4CAF50;
        color: white;
    }

    .alert-message i {
        font-size: 20px;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .modal-content {
        background-color: white;
        padding: 30px;
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }

    .modal-content h2 {
        margin: 0 0 15px 0;
        color: var(--text-color);
        font-size: 24px;
    }

    .modal-content p {
        margin: 0 0 20px 0;
        color: var(--light-text);
        font-size: 16px;
    }

    .modal-buttons {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    .modal-buttons .btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        border: none;
    }

    .delete-btn {
        background-color: var(--danger-color);
        color: white;
    }

    .cancel-btn {
        background-color: var(--secondary-color);
        color: var(--text-color);
    }

    .btn:hover {
        opacity: 0.9;
    }

    .activate-btn {
        background-color: var(--success-color);
        color: white;
    }

    .btn.disabled {
        opacity: 0.6;
        cursor: not-allowed;
        background-color: #ccc;
        pointer-events: none;
    }

    /* Add tooltip on hover */
    .btn.disabled:hover {
        position: relative;
    }

    .btn.disabled[title]:hover:after {
        content: attr(title);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        padding: 5px 10px;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        margin-bottom: 5px;
        z-index: 1000;
    }

    .company-info {
        width: 240px;
        height: 59.4px;
        padding: 0px 10px;
        margin: 20px 0px;
        text-align: center;
        color: #333333;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .company-info span:first-child {
        display: block;
        font-family: 'Poppins', sans-serif;
        font-size: 16px;
        font-weight: 500;
        line-height: 1.2;
        margin-bottom: 4px;
    }

    .company-info span:last-child {
        display: block;
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        color: #666;
        line-height: 1.2;
    }
    </style>
</body>
</html>