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

// Get all interviews for this employer
$sql = "SELECT 
            a.*, 
            j.job_title,
            u.first_name,
            u.last_name,
            u.profile_image,
            CONCAT('../database/profile_picture/', u.profile_image) as profile_image_path,
            a.status as status
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
    <div class="toast-container"></div>
    <div class="dashboard-container">
        <!-- Sidebar (Copied from employerdashboard.php) -->
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
        </div>

        <!-- Main Content (Unchanged) -->
        <div class="main-container">
            <div class="interview-container">
                <h2>Scheduled Interviews</h2>

                <?php if(mysqli_num_rows($interviews) > 0): ?>
                    <?php while($interview = mysqli_fetch_assoc($interviews)): ?>
                        <div class="interview-card">
                            <div class="interview-header">
                                <div class="interview-title">
                                    Interview with <?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?>
                                </div>
                                <div class="interview-date">
                                    <?php 
                                        $date = new DateTime($interview['appointment_date']);
                                        $time = new DateTime($interview['appointment_time']);
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
                                    <span class="status-badge status-<?php echo strtolower($interview['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($interview['status'])); ?>
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
                                <?php if(strtolower($interview['status']) == 'accepted'): ?>
                                    <button class="complete-interview-btn completed" disabled>
                                        <i class="fas fa-check-circle"></i> Marked as Completed
                                    </button>
                                <?php else: ?>
                                    <button onclick="redirectToAcceptAfterInterview(<?php echo $interview['appointment_id']; ?>)" 
                                            class="complete-interview-btn">
                                        <i class="fas fa-check-circle"></i> Mark Interview as Completed
                                    </button>
                                <?php endif; ?>
                            </div>

                            <div class="interview-actions">
                                <?php if($interview['status'] === 'pending'): ?>
                                    <a href="reschedule_interview.php?appointment_id=<?php echo $interview['appointment_id']; ?>" 
                                       class="action-btn btn-reschedule">
                                        <i class="fas fa-calendar-alt"></i> Reschedule
                                    </a>
                                    <a href="cancel_interview.php?appointment_id=<?php echo $interview['appointment_id']; ?>" 
                                       class="action-btn btn-cancel"
                                       onclick="return confirm('Are you sure you want to cancel this interview?')">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <?php if($interview['interview_type'] === 'Online'): ?>
                                        <a href="<?php echo htmlspecialchars($interview['location']); ?>" 
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
        padding: 0.8rem 2rem;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .complete-interview-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(138, 108, 224, 0.2);
    }

    .completion-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.8rem 2rem;
        background: #E8F5E9;
        color: #2E7D32;
        border-radius: 8px;
        font-weight: 500;
    }

    .completion-badge i {
        color: #2E7D32;
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

    .complete-interview-btn.completed {
        background: #E8F5E9;
        color: #2E7D32;
        cursor: not-allowed;
        opacity: 0.8;
        border: 1px solid #A5D6A7;
    }

    .complete-interview-btn.completed:hover {
        transform: none;
        box-shadow: none;
    }

    .complete-interview-btn.completed i {
        color: #2E7D32;
    }
    </style>

    <script>
    function redirectToAcceptAfterInterview(appointmentId) {
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
            backdrop: true,
            background: '#fff',
            customClass: {
                container: 'swal-custom-container',
                popup: 'swal-custom-popup',
                backdrop: 'swal-custom-backdrop',
                content: 'swal-custom-content'
            },
            didOpen: () => {
                // Add blur to main content when modal opens
                document.querySelector('.dashboard-container').style.filter = 'blur(5px)';
            },
            willClose: () => {
                // Remove blur when modal closes
                document.querySelector('.dashboard-container').style.filter = 'none';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `accept_after_interview.php?appointment_id=${appointmentId}`;
            }
        });
    }

    // Toast notification function
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <div class="toast-message">${message}</div>
            </div>
            <div class="toast-progress"></div>
        `;
        
        document.querySelector('.toast-container').appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    // Check for session message
    <?php if(isset($_SESSION['message'])): ?>
        showToast('<?php echo $_SESSION['message']; ?>', '<?php echo $_SESSION['message_type']; ?>');
        <?php 
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

    <!-- Add this for debugging -->
    <?php
    // Debug output
    error_log("Interview status: " . $interview['status']);
    ?>
</body>
</html>