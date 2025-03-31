<?php
session_start();
include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

if (!isset($_SESSION['employer_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

$employer_id = $_SESSION['employer_id'];

// Get employer details
$sql = "SELECT e.*, l.email 
        FROM tbl_employer e 
        JOIN tbl_login l ON e.employer_id = l.employer_id 
        WHERE e.employer_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

// Get all interviews for this employer with correct field names
$sql = "SELECT 
            a.appointment_id,
            a.user_id,
            a.job_id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.location,
            a.notes,
            a.interview_type,
            j.job_title,
            u.first_name,
            u.last_name,
            u.profile_image,
            CONCAT('../database/profile_picture/', u.profile_image) as profile_image_path
        FROM tbl_appointments a
        JOIN tbl_jobs j ON a.job_id = j.job_id
        JOIN tbl_user u ON a.user_id = u.user_id
        WHERE a.employer_id = ?
        ORDER BY a.appointment_date ASC, a.appointment_time ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $employer_id);
mysqli_stmt_execute($stmt);
$interviews = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Interviews</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="employerdashboard.css">
    <link rel="stylesheet" href="interview.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-minimal@5/minimal.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>
    <!-- <div class="dashboard-container">
        
        <div class="sidebar">
            <div class="logo-container">
                <?php if(!empty($row['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($row['profile_image']); ?>" 
                         alt="<?php echo htmlspecialchars($row['company_name']); ?>"
                         onerror="this.src='../assets/images/company-logo.png';">
                <?php else: ?>
                    <img src="../assets/images/company-logo.png" alt="AutoRecruits.in">
                <?php endif; ?>
            </div>
            <div class="company-info">
                <span><?php echo htmlspecialchars($row['company_name'] ?? 'Company Name'); ?></span>
                <span style="font-size: 13px; color: var(--light-text);"><?php echo htmlspecialchars($row['email'] ?? 'Email'); ?></span>
            </div>
            <nav class="nav-menu">
                <div class="nav-item">
                    <i class="fas fa-th-large"></i>
                    <a href="employerdashboard.php">Dashboard</a>
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
                <div class="nav-item active">
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
        </div> -->

        <?php include 'sidebar.php'; ?>

        <!-- Main Content (Unchanged) -->
        <div class="main-container">
            <?php if(isset($_GET['success'])): ?>
                <div class="message success-message">
                    <div class="message-content">
                        <i class="fas fa-check-circle"></i>
                        <div class="message-text">
                            <?php 
                                switch($_GET['success']) {
                                    case 'interview_updated':
                                        echo "<h3>Success!</h3>";
                                        echo "<p>Interview details updated successfully!</p>";
                                        break;
                                    case 'interview_rejected':
                                        echo "<h3>Success!</h3>";
                                        echo "<p>Interview has been rejected successfully!</p>";
                                        break;
                                    case 'interview_completed':
                                        echo "<h3>Interview Completed!</h3>";
                                        echo "<p>The interview has been marked as completed and the applicant has been accepted for the position.</p>";
                                        break;
                                    default:
                                        echo "<h3>Success!</h3>";
                                        echo "<p>Operation completed successfully!</p>";
                                }
                            ?>
                        </div>
                    </div>
                    <button class="close-btn" onclick="this.parentElement.style.display='none';">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="message error-message">
                    <div class="message-content">
                        <i class="fas fa-exclamation-circle"></i>
                        <div class="message-text">
                            <?php 
                                switch($_GET['error']) {
                                    case 'reject_failed':
                                        echo "<h3>Error!</h3>";
                                        echo "<p>Failed to reject the interview. Please try again.</p>";
                                        break;
                                    case 'complete_failed':
                                        echo "<h3>Error!</h3>";
                                        echo "<p>Failed to complete the interview process. Please try again.</p>";
                                        break;
                                    default:
                                        echo "<h3>Error!</h3>";
                                        echo "<p>An error occurred. Please try again.</p>";
                                }
                            ?>
                        </div>
                    </div>
                    <button class="close-btn" onclick="this.parentElement.style.display='none';">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <div class="interview-container">
                <h2>Scheduled Interviews</h2>

                <?php if(mysqli_num_rows($interviews) > 0): ?>
                    <?php while($interview = mysqli_fetch_assoc($interviews)): ?>
                        <?php if($interview === null) continue; ?>
                        <div class="interview-card">
                            <div class="interview-header">
                                <div class="interview-title">
                                    Interview with <?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?>
                                </div>
                                <div class="interview-date">
                                    <?php 
                                        $date = new DateTime($interview['appointment_date'] ?? '');
                                        $time = new DateTime($interview['appointment_time'] ?? '');
                                        echo $date->format('F j, Y') . ' at ' . $time->format('g:i A');
                                    ?>
                                </div>
                            </div>

                            <div class="interview-details">
                                <div class="detail-group">
                                    <span class="detail-label">Position</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($interview['job_title']); ?></span>
                                </div>

                                <div class="detail-group">
                                    <span class="detail-label">Interview Type</span>
                                    <span class="detail-value interview-type type-<?php echo strtolower($interview['interview_type']); ?>">
                                        <i class="fas fa-<?php 
                                            echo $interview['interview_type'] === 'Physical' ? 'building' : 
                                                ($interview['interview_type'] === 'Online' ? 'video' : 'phone'); 
                                        ?>"></i>
                                        <?php echo htmlspecialchars($interview['interview_type']); ?> Interview
                                    </span>
                                </div>

                                <div class="detail-group">
                                    <span class="detail-label">
                                        <?php 
                                            echo $interview['interview_type'] === 'Physical' ? 'Location' : 
                                                ($interview['interview_type'] === 'Online' ? 'Meeting Link' : 'Phone Number'); 
                                        ?>
                                    </span>
                                    <span class="detail-value"><?php echo htmlspecialchars($interview['location']); ?></span>
                                </div>

                                <div class="detail-group">
                                    <span class="detail-label">Status</span>
                                    <?php $status = $interview['status'] ?? 'Pending'; ?>
                                    <span class="status-badge status-<?php echo strtolower($status); ?>">
                                        <?php echo ucfirst(htmlspecialchars($status)); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if(!empty($interview['notes'])): ?>
                                <div class="interview-notes">
                                    <div class="notes-label">Notes</div>
                                    <div class="notes-content"><?php echo nl2br(htmlspecialchars($interview['notes'])); ?></div>
                                </div>
                            <?php endif; ?>

                            <div class="completion-section">
                                <?php if(isset($interview['status']) && strtolower($interview['status']) === 'accepted'): ?>
                                    <button class="complete-interview-btn completed" disabled>
                                        <i class="fas fa-check-circle"></i> Interview Completed
                                    </button>
                                <?php else: ?>
                                    <button onclick="redirectToAcceptAfterInterview(<?php echo $interview['appointment_id'] ?? 0; ?>)" 
                                            class="complete-interview-btn">
                                        <i class="fas fa-check-circle"></i> Mark as Completed
                                    </button>
                                <?php endif; ?>
                            </div>

                            <div class="interview-actions">
                                <?php if(isset($interview['status']) && $interview['status'] === 'Pending'): ?>
                                    <!-- <a href="reschedule_interview.php?appointment_id=<?php echo $interview['appointment_id']; ?>" 
                                       class="action-btn btn-reschedule">
                                        <i class="fas fa-calendar-alt"></i> Reschedule
                                    </a> -->
                                    <a href="cancel_interview.php?appointment_id=<?php echo $interview['appointment_id']; ?>" 
                                       class="action-btn btn-cancel"
                                       onclick="return confirm('Are you sure you want to cancel this interview?')">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <?php if(($interview['interview_type'] ?? '') === 'Online'): ?>
                                        <a href="<?php echo htmlspecialchars($interview['location'] ?? '#'); ?>" 
                                           target="_blank" 
                                           class="action-btn btn-join">
                                            <i class="fas fa-video"></i> Join Meeting
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-interviews">
                        <i class="fas fa-calendar-times"></i>
                        <p>No interviews scheduled yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
    .completion-section {
        margin-top: 1.5rem;
        text-align: center;
        padding: 1rem 0;
        border-top: 1px solid rgba(186, 166, 227, 0.2);
    }

    .complete-interview-btn {
        background: var(--primary-color);
        color: white;
        padding: 0.8rem 1.5rem;
        border: none;
        border-radius: 6px;
        font-size: 0.95rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 2px 4px rgba(138, 108, 224, 0.15);
    }

    .complete-interview-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(138, 108, 224, 0.2);
        background: #7857d8;
    }

    .complete-interview-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(138, 108, 224, 0.15);
    }

    .complete-interview-btn i {
        font-size: 1.1rem;
    }

    .complete-interview-btn.completed {
        background: #E8F5E9;
        color: #2E7D32;
        cursor: not-allowed;
        border: 1px solid #A5D6A7;
        box-shadow: none;
        opacity: 0.9;
    }

    .complete-interview-btn.completed:hover {
        transform: none;
        box-shadow: none;
        background: #E8F5E9;
    }

    .complete-interview-btn.completed i {
        color: #2E7D32;
    }

    /* Add loading state styles */
    .complete-interview-btn.loading {
        background: #e0e0e0;
        cursor: wait;
        pointer-events: none;
    }

    .complete-interview-btn.loading i {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Add confirmation dialog styles */
    .swal2-popup {
        font-family: 'Poppins', sans-serif !important;
        border-radius: 12px !important;
        padding: 2rem !important;
    }

    .swal2-title {
        font-size: 1.5rem !important;
        font-weight: 600 !important;
        color: #333 !important;
    }

    .swal2-html-container {
        font-size: 1rem !important;
        color: #666 !important;
    }

    .swal2-confirm {
        background: var(--primary-color) !important;
        border-radius: 6px !important;
        font-weight: 500 !important;
        padding: 0.8rem 1.5rem !important;
        box-shadow: 0 2px 4px rgba(138, 108, 224, 0.15) !important;
    }

    .swal2-cancel {
        background: #f5f5f5 !important;
        color: #666 !important;
        border-radius: 6px !important;
        font-weight: 500 !important;
        padding: 0.8rem 1.5rem !important;
    }

    .swal-custom-container {
        z-index: 1000;
    }

    .swal-custom-backdrop {
        background: rgba(0, 0, 0, 0.4) !important;
    }

    .swal-custom-popup {
        position: relative;
        background: #ffffff;
        border-radius: 15px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        padding: 20px;
        z-index: 1001;
        filter: none !important;
    }

    .swal-custom-content {
        filter: none !important;
    }

    /* Transition for smooth blur effect */
    .dashboard-container {
        transition: filter 0.3s ease;
    }

    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
    }

    .toast {
        background: white;
        border-radius: 8px;
        padding: 16px 20px;
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
        min-width: 300px;
        transform: translateX(120%);
        transition: transform 0.3s ease;
    }

    .toast.show {
        transform: translateX(0);
    }

    .toast.success {
        border-left: 4px solid #4CAF50;
    }

    .toast.error {
        border-left: 4px solid #f44336;
    }

    .toast-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .toast-content i {
        font-size: 24px;
    }

    .toast.success i {
        color: #4CAF50;
    }

    .toast.error i {
        color: #f44336;
    }

    .toast-message {
        color: #333;
        font-size: 14px;
        font-weight: 500;
    }

    .toast-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: #eee;
        border-radius: 0 0 8px 8px;
        overflow: hidden;
    }

    .toast-progress::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        background: var(--primary-color);
        animation: progress 5s linear forwards;
    }

    @keyframes progress {
        from { width: 100%; }
        to { width: 0%; }
    }

    .message {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        animation: slideIn 0.5s ease-out;
    }

    .success-message {
        background-color: #e8f5e9;
        border-left: 4px solid #4caf50;
    }

    .error-message {
        background-color: #fdecea;
        border-left: 4px solid #f44336;
    }

    .message-content {
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }

    .message-content i {
        font-size: 24px;
        padding-top: 3px;
    }

    .success-message i {
        color: #4caf50;
    }

    .error-message i {
        color: #f44336;
    }

    .message-text {
        flex-grow: 1;
    }

    .message-text h3 {
        margin: 0 0 5px 0;
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }

    .message-text p {
        margin: 0;
        font-size: 14px;
        color: #666;
        line-height: 1.4;
    }

    .close-btn {
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        padding: 5px;
        font-size: 16px;
        transition: color 0.3s ease;
    }

    .close-btn:hover {
        color: #666;
    }

    @keyframes slideIn {
        from {
            transform: translateY(-20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    </style>

    <script>
    function redirectToAcceptAfterInterview(appointmentId) {
        const button = event.target.closest('.complete-interview-btn');
        
        Swal.fire({
            title: 'Complete Interview?',
            text: 'Are you sure you want to mark this interview as completed?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#8A6CE0',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Complete it!',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false,
            showCloseButton: true,
            customClass: {
                popup: 'swal-custom-popup',
                title: 'swal-custom-title',
                content: 'swal-custom-content',
                confirmButton: 'swal-custom-confirm',
                cancelButton: 'swal-custom-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                button.classList.add('loading');
                button.innerHTML = '<i class="fas fa-spinner"></i> Processing...';
                
                // Redirect to accept page
                window.location.href = `accept_after_interview.php?appointment_id=${appointmentId}`;
            }
        });
    }

    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toastContainer');
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        // Add icon based on type
        const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
        
        toast.innerHTML = `
            <i class="fas fa-${icon}"></i>
            <span class="toast-message">${message}</span>
        `;
        
        // Add toast to container
        toastContainer.appendChild(toast);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s ease forwards';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }

    // Show notification if there's a message in session
    <?php if(isset($_SESSION['message'])): ?>
        showToast('<?php echo $_SESSION['message']; ?>', '<?php echo $_SESSION['message_type'] ?? "success"; ?>');
        <?php 
        // Clear the message after displaying
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>
    </script>

    <!-- Add this right after your body tag -->
    <?php if(isset($_SESSION['message'])): ?>
        <div class="toast-container">
            <div class="toast <?php echo $_SESSION['message_type']; ?> show">
                <div class="toast-content">
                    <i class="fas <?php echo $_SESSION['message_type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <div class="toast-message"><?php echo $_SESSION['message']; ?></div>
                </div>
                <div class="toast-progress"></div>
            </div>
        </div>
        <?php 
        // Clear the message after displaying
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>
</body>
</html>