<?php
    session_start();
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // Get job_id from URL
    $job_id = isset($_GET['job_id']) ? $_GET['job_id'] : null;

    if (!$job_id) {
        header("Location: userdashboard.php");
        exit();
    }

    // Get user ID from session
    $user_id = $_SESSION['id'] ?? $_SESSION['user_id'];

    // Fetch job details
    $sql = "SELECT * FROM tbl_jobs WHERE job_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $job_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $job = mysqli_fetch_assoc($result);

    // Fetch user profile
    $sql_user = "SELECT l.email, u.first_name, u.last_name, u.username, u.profile_image 
                 FROM tbl_login l
                 INNER JOIN tbl_user u ON l.user_id = u.user_id
                 WHERE l.user_id = ?";

    $stmt_user = mysqli_prepare($conn, $sql_user);
    if ($stmt_user) {
        mysqli_stmt_bind_param($stmt_user, "i", $user_id);
        mysqli_stmt_execute($stmt_user);
        $result_user = mysqli_stmt_get_result($stmt_user);
        
        if ($result_user && mysqli_num_rows($result_user) > 0) {
            $user = mysqli_fetch_assoc($result_user);
            $profile_image = $user['profile_image'];
            
            // Set display name based on available data
            if (!empty($user['username'])) {
                $display_name = $user['username'];
            } else {
                $display_name = $user['first_name'] . " " . $user['last_name'];
            }
        } else {
            // Set default values if no user data found
            $profile_image = "";
            $display_name = "User";
        }
        mysqli_stmt_close($stmt_user);
    } else {
        // Handle prepare statement error
        $profile_image = "";
        $display_name = "User";
        error_log("Failed to prepare user fetch statement: " . mysqli_error($conn));
    }

    // Debug
    error_log("User ID: " . $user_id . ", Job ID: " . $job_id);

    // Check if job is already bookmarked
    $check_bookmark_sql = "SELECT * FROM tbl_bookmarks WHERE user_id = ? AND job_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_bookmark_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $job_id);
    mysqli_stmt_execute($check_stmt);
    $bookmark_result = mysqli_stmt_get_result($check_stmt);
    $is_bookmarked = mysqli_num_rows($bookmark_result) > 0;

    // Debug
    error_log("Is Bookmarked: " . ($is_bookmarked ? 'Yes' : 'No'));

    // Same user data fetching code as above
    // Add it right after session_start() and database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details</title>
    <link rel="stylesheet" href="jobdetails.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <h1>Job Details</h1>
        </div>
        
        <div class="nav-right">
            <div class="profile-info">
                <span class="nav-username"><?php echo htmlspecialchars($display_name); ?></span>
                <div class="profile-container">
                    <?php if (!empty($profile_image)): ?>
                        <img src="/mini project/database/profile_picture/<?php echo htmlspecialchars($profile_image); ?>" class="profile-pic" alt="Profile">
                    <?php else: ?>
                        <img src="profile.png" class="profile-pic" alt="Profile">
                    <?php endif; ?>
                    <div class="dropdown-menu">
                        <a href="profiles/user/userprofile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Toast Message Container -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="toast show <?php echo $_SESSION['message_type']; ?>">
            <div class="toast-content">
                <div class="toast-icon">
                    <i class="fas <?php echo $_SESSION['message_type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                </div>
                <div class="toast-message-container">
                    <div class="toast-title">
                        <?php echo $_SESSION['message_type'] === 'success' ? 'Success' : 'Error'; ?>
                    </div>
                    <span id="toast-message"><?php echo $_SESSION['message']; ?></span>
                </div>
            </div>
            <div class="toast-progress"></div>
        </div>
        <?php 
        // Clear the message after displaying
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>

    <div class="dashboard-container">
        <!-- Fixed Sidebar -->
        <div class="sidebar">
            <div class="sidebar-menu">
                <a href="userdashboard.php" class="sidebar-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <!-- <a href="bookmark.php" class="sidebar-link">
                    <i class="fas fa-bookmark"></i>
                    <span>Bookmarks</span>
                </a> -->
                <a href="applied.php" class="sidebar-link">
                    <i class="fas fa-check-circle"></i>
                    <span>Applied Jobs</span>
                </a>
                <a href="bookmark.php" class="sidebar-link">
                    <i class="fas fa-bookmark"></i>
                    <span>Bookmarks</span>
                </a>
                <a href="sidebar/appointment/appointment.html" class="sidebar-link">
                    <i class="fas fa-calendar"></i>
                    <span>Appointments</span>
                </a>
                <a href="reportedjobs.php" class="sidebar-link">
                    <i class="fas fa-flag"></i>
                    <span>Reported Jobs</span>
                </a>
                <a href="profiles/user/userprofile.php" class="sidebar-link">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </div>
            <div class="logout-container">
                <div class="sidebar-divider"></div>
                <a href="../login/logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <div class="job-details-container">
                <!-- Move back button inside -->
                <div class="back-section">
                    <a href="userdashboard.php" class="back-button">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Jobs</span>
                    </a>
                </div>

                <div class="job-header">
                    <h2><?php echo htmlspecialchars($job['job_title']); ?></h2>
                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?>, <?php echo htmlspecialchars($job['town']); ?></span>
                        <span><i class="fas fa-money-bill-wave"></i> ₹<?php echo htmlspecialchars($job['salary']); ?></span>
                        <span><i class="fas fa-calendar-plus"></i> Posted: <?php echo date('Y-m-d', strtotime($job['created_at'])); ?></span>
                        <span><i class="fas fa-calendar-alt"></i> Valid Until: <?php echo date('Y-m-d', strtotime($job['vacancy_date'])); ?></span>
                    </div>
                </div>

                <div class="job-content">
                    <div class="content-section">
                        <h3>Job Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($job['job_description'])); ?></p>
                    </div>

                    <div class="content-section">
                        <h3>Additional Details</h3>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="label">Vacancy:</span>
                                <span class="value"><?php echo htmlspecialchars($job['vacancy']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Working Days:</span>
                                <span class="value"><?php echo htmlspecialchars($job['working_days']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Working Hours:</span>
                                <span class="value"><?php echo htmlspecialchars($job['start_time']); ?> - <?php echo htmlspecialchars($job['end_time']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Category:</span>
                                <span class="value"><?php echo htmlspecialchars($job['category']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="content-section">
                        <h3>Requirements</h3>
                        <div class="requirements-list">
                            <div class="requirement-item">
                                <i class="fas fa-id-card"></i>
                                <span>License Required: <?php if($job['license_required'])
                                                                {
                                                                    echo $job['license_required'];
                                                                }
                                                                else
                                                                {
                                                                    echo 'No';
                                                                }
                                                        ?>
                                </span>
                            </div>
                            <div class="requirement-item">
                                <i class="fas fa-certificate"></i>
                                <span>Badge Required: <?php if($job['badge_required'])
                                                                {
                                                                    echo $job['badge_required'];
                                                                }
                                                                else
                                                                {
                                                                    echo 'No';
                                                                }
                                                        ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="content-section">
                        <h3>Contact Information</h3>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($job['contact_no']); ?></p>
                    </div>

                    <div class="job-actions">
                        <button class="apply-btn">
                            <i class="fas fa-paper-plane"></i>
                            Apply Now
                        </button>
                        <a href="fetch_employer_rating.php?employer_id=<?php echo $job['employer_id']; ?>&job_id=<?php echo $job_id; ?>" class="view-details-btn">
                            <i class="fas fa-user-tie"></i> Employer Details
                        </a>
                        <button onclick="toggleBookmark(<?php echo $job_id; ?>)" class="save-btn <?php echo $is_bookmarked ? 'saved' : ''; ?>">
                            <?php if ($is_bookmarked): ?>
                                <i class="fas fa-bookmark"></i> Saved
                            <?php else: ?>
                                <i class="far fa-bookmark"></i> Save Job
                            <?php endif; ?>
                        </button>
                        <button class="report-btn" onclick="openReportModal()">
                            <i class="fas fa-flag"></i> Report
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add this modal HTML before closing body tag -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-flag"></i>
                <h2>Report Job</h2>
                <span class="close" onclick="closeReportModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="reportReason">Why do you want to report this job?</label>
                    <textarea id="reportReason" 
                             rows="4" 
                             placeholder="Please explain your reason for reporting (letters and basic punctuation only)..."
                             onkeyup="validateReport()"
                             minlength="10"
                             maxlength="500"></textarea>
                    <div class="validation-feedback">
                        <span id="charCount">0/500 characters</span>
                        <span id="validationMessage" class="error-message"></span>
                    </div>
                    <small class="input-help">Only letters, spaces, and basic punctuation (.,!?'"-) are allowed</small>
                </div>
            </div>
            <div class="modal-footer">
                <button class="cancel-btn" onclick="closeReportModal()">Cancel</button>
                <a href="report.php?job_id=<?php echo $job_id; ?>&user_id=<?php echo $user_id; ?>&reason=<?php echo urlencode($reportReason); ?></a>" id="reportLink" class="report-link">
                    <button class="submit-btn" onclick="prepareReport(event)">
                        <i class="fas fa-flag"></i> Submit Report
                    </button>
                </a>
            </div>
        </div>
    </div>

    <style>
    .save-btn {
        transition: all 0.3s ease;
    }

    .save-btn.saved {
        background: #9747FF;
        color: white;
        border-color: #9747FF;
    }

    .save-btn.saved i {
        color: #FFD700;
        animation: bookmarkPop 0.3s ease;
    }

    @keyframes bookmarkPop {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    .save-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(151, 71, 255, 0.2);
    }

    .save-btn.saved:hover {
        background: #8A2BE2;
        border-color: #8A2BE2;
    }

    /* Add animation for initial load if saved */
    .save-btn.saved i {
        animation: initialBookmarkPop 0.5s ease;
    }

    @keyframes initialBookmarkPop {
        0% { transform: scale(0.8); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .report-btn {
        background-color: transparent;
        color: #FF5722;
        border: 1px solid #FF5722;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .report-btn:hover {
        background-color: #FF5722;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 87, 34, 0.2);
    }

    .report-btn i {
        font-size: 16px;
        transition: transform 0.3s ease;
    }

    .report-btn:hover i {
        transform: rotate(20deg);
    }

    .report-btn:active {
        transform: translateY(0);
        box-shadow: none;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .modal-content {
        position: relative;
        background-color: #fff;
        margin: 15% auto;
        padding: 20px;
        width: 90%;
        max-width: 500px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }

    .modal-header h2 {
        margin: 0;
        color: #333;
    }

    .modal-header i {
        color: #FF5722;
        font-size: 24px;
    }

    .close {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 500;
    }

    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        resize: vertical;
        min-height: 100px;
    }

    .form-group textarea:focus {
        outline: none;
        border-color: #FF5722;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .cancel-btn, .submit-btn {
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .cancel-btn {
        background: #fff;
        border: 1px solid #ddd;
        color: #666;
    }

    .submit-btn {
        background: #FF5722;
        border: none;
        color: white;
    }

    .cancel-btn:hover {
        background: #f5f5f5;
    }

    .submit-btn:hover {
        background: #F4511E;
    }

    .validation-feedback {
        display: flex;
        justify-content: space-between;
        margin-top: 8px;
        font-size: 12px;
        color: #666;
    }

    #charCount {
        color: #666;
    }

    .error-message {
        color: #f44336;
        font-size: 12px;
        visibility: hidden;
        opacity: 0;
        transition: visibility 0s, opacity 0.2s linear;
    }

    .error-message.show {
        visibility: visible;
        opacity: 1;
    }

    .form-group textarea.invalid {
        border-color: #f44336;
        animation: borderPulse 0.3s ease-in-out;
    }

    @keyframes borderPulse {
        0% { box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.4); }
        70% { box-shadow: 0 0 0 5px rgba(244, 67, 54, 0); }
        100% { box-shadow: 0 0 0 0 rgba(244, 67, 54, 0); }
    }

    .form-group textarea.valid {
        border-color: #4CAF50;
    }

    .submit-btn:disabled {
        background: #cccccc;
        cursor: not-allowed;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .error-message.shake {
        animation: shake 0.3s ease-in-out;
    }

    .input-help {
        display: block;
        margin-top: 8px;
        font-size: 12px;
        color: #666;
        font-style: italic;
    }

    .report-link {
        text-decoration: none;
    }

    .report-link button {
        width: 100%;
    }

    /* Update these styles for single-line toast */
    .toast {
        visibility: hidden;
        position: fixed;
        top: 20px;
        right: 20px;
        min-width: 300px;
        max-width: 400px;
        z-index: 1000;
        background: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-radius: 8px;
        overflow: hidden;
    }

    .toast-content {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        gap: 12px;
    }

    .toast-icon {
        flex-shrink: 0;
    }

    .toast-message-container {
        flex-grow: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .toast-title {
        display: inline;
        margin-right: 8px;
        font-weight: 500;
    }

    #toast-message {
        display: inline;
    }

    .toast.show {
        visibility: visible;
        animation: slideInRight 0.3s ease-out;
    }

    .toast.success {
        background: #4CAF50;
        color: white;
    }

    .toast.error {
        background: #f44336;
        color: white;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .toast-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        width: 100%;
        background: rgba(255, 255, 255, 0.3);
    }

    .toast-progress::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
        background: rgba(255, 255, 255, 0.7);
        animation: progress 3s linear forwards;
    }

    @keyframes progress {
        from { width: 100%; }
        to { width: 0%; }
    }

    .nav-right {
        display: flex;
        align-items: center;
    }

    .profile-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .nav-username {
        color: #333;
        font-weight: 500;
        font-size: 0.95rem;
    }

    .profile-container {
        display: flex;
        align-items: center;
        position: relative;
        cursor: pointer;
    }

    .profile-pic {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: none;
        outline: none;
    }

    .dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-radius: 8px;
        padding: 8px 0;
        min-width: 180px;
        z-index: 1000;
    }

    .profile-container:hover .dropdown-menu {
        display: block;
    }

    .dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        color: #333;
        text-decoration: none;
        transition: background-color 0.2s;
    }

    .dropdown-menu a:hover {
        background-color: #f5f5f5;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const saveBtn = document.querySelector('.save-btn');
        if (saveBtn.classList.contains('saved')) {
            saveBtn.querySelector('i').style.animation = 'initialBookmarkPop 0.5s ease';
        }
        
        // Auto-hide toast after 3 seconds if it exists
        const toast = document.querySelector('.toast.show');
        if (toast) {
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    });

    function toggleBookmark(jobId) {
        const formData = new FormData();
        formData.append('job_id', jobId);

        // Debug log
        console.log('Sending job_id:', jobId);

        fetch('bookmarkprocess.php', {
            method: 'POST',
            body: formData,
            headers: {
                // Remove Content-Type header to let browser set it with boundary for FormData
            }
        })
        .then(response => {
            console.log('Raw response:', response); // Debug log
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data); // Debug log
            if (data.success) {
                const btn = document.querySelector('.save-btn');
                if (data.action === 'bookmarked') {
                    btn.innerHTML = '<i class="fas fa-bookmark"></i> Saved';
                    btn.classList.add('saved');
                    btn.querySelector('i').style.animation = 'bookmarkPop 0.3s ease';
                    
                    // Show success toast message
                    showToast('Job bookmarked successfully!', 'success');
                } else if (data.action === 'unbookmarked') {
                    btn.innerHTML = '<i class="far fa-bookmark"></i> Save Job';
                    btn.classList.remove('saved');
                    
                    // Show info toast message
                    showToast('Job removed from bookmarks', 'info');
                }
            } else {
                console.error('Error:', data.message); // Debug log
                showToast(data.message || 'Error processing bookmark', 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error); // Debug log
            showToast('Error processing bookmark', 'error');
        });
    }

    // Add this function to show toast messages
    function showToast(message, type = 'info') {
        // Check if toast container exists, if not create it
        let toast = document.getElementById('toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast';
            toast.className = 'toast';
            toast.innerHTML = `
                <div class="toast-content">
                    <div class="toast-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="toast-message-container">
                        <div class="toast-title">Info:</div>
                        <div id="toast-message"></div>
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
        }

        const toastMessage = document.getElementById('toast-message');
        const toastTitle = toast.querySelector('.toast-title');
        const icon = toast.querySelector('.toast-icon i');
        
        // Reset classes and clear any existing animations
        toast.className = 'toast';
        toast.style.animation = 'none';
        
        // Force reflow to ensure animation reset
        void toast.offsetWidth;
        
        // Set type-specific properties
        switch(type) {
            case 'success':
                toastTitle.textContent = 'Success:';
                icon.className = 'fas fa-check-circle';
                toast.classList.add('success');
                break;
            case 'error':
                toastTitle.textContent = 'Error:';
                icon.className = 'fas fa-exclamation-circle';
                toast.classList.add('error');
                break;
            case 'warning':
                toastTitle.textContent = 'Warning:';
                icon.className = 'fas fa-exclamation-triangle';
                toast.classList.add('warning');
                break;
            default:
                toastTitle.textContent = 'Info:';
                icon.className = 'fas fa-info-circle';
                toast.classList.add('info');
        }
        
        toastMessage.textContent = message;
        toast.style.animation = '';
        toast.classList.add('show');
        
        // Manually hide after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    function openReportModal() {
        document.getElementById('reportModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeReportModal() {
        const textarea = document.getElementById('reportReason');
        const validationMessage = document.getElementById('validationMessage');
        const charCount = document.getElementById('charCount');
        
        textarea.classList.remove('valid', 'invalid');
        validationMessage.classList.remove('show');
        charCount.textContent = '0/500 characters';
        
        document.getElementById('reportModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        textarea.value = '';
    }

    function validateReport() {
        const textarea = document.getElementById('reportReason');
        const charCount = document.getElementById('charCount');
        const validationMessage = document.getElementById('validationMessage');
        const submitBtn = document.querySelector('.submit-btn');
        const text = textarea.value.trim();
        const length = text.length;

        // Update character count
        charCount.textContent = `${length}/500 characters`;

        // Check for numbers
        if (/\d/.test(text)) {
            textarea.classList.remove('valid');
            textarea.classList.add('invalid');
            validationMessage.textContent = 'Numbers are not allowed in the report';
            validationMessage.classList.add('show', 'shake');
            submitBtn.disabled = true;
            return false;
        }

        // Check for special characters (allowing only letters, spaces, and basic punctuation)
        if (!/^[a-zA-Z\s.,!?'"-]+$/.test(text)) {
            textarea.classList.remove('valid');
            textarea.classList.add('invalid');
            validationMessage.textContent = 'Only letters and basic punctuation (.,!?\'"-)  are allowed';
            validationMessage.classList.add('show', 'shake');
            submitBtn.disabled = true;
            return false;
        }

        // Validate length
        if (length < 10) {
            textarea.classList.remove('valid');
            textarea.classList.add('invalid');
            validationMessage.textContent = 'Please provide at least 10 characters';
            validationMessage.classList.add('show');
            submitBtn.disabled = true;
            return false;
        } 
        else if (length > 500) {
            textarea.classList.remove('valid');
            textarea.classList.add('invalid');
            validationMessage.textContent = 'Maximum 500 characters allowed';
            validationMessage.classList.add('show');
            submitBtn.disabled = true;
            return false;
        }
        // Check for meaningful content (at least 2 words)
        else if (text.split(/\s+/).filter(word => word.length > 0).length < 2) {
            textarea.classList.remove('valid');
            textarea.classList.add('invalid');
            validationMessage.textContent = 'Please provide a meaningful explanation';
            validationMessage.classList.add('show');
            submitBtn.disabled = true;
            return false;
        }
        else {
            textarea.classList.remove('invalid');
            textarea.classList.add('valid');
            validationMessage.classList.remove('show', 'shake');
            submitBtn.disabled = false;
            return true;
        }
    }

    // Update the input event listener to only validate, not prevent input
    document.getElementById('reportReason').addEventListener('input', function(e) {
        validateReport();
        
        // Remove shake class after animation
        const validationMessage = document.getElementById('validationMessage');
        validationMessage.addEventListener('animationend', function() {
            validationMessage.classList.remove('shake');
        });
    });

    function prepareReport(event) {
        event.preventDefault();
        
        if (!validateReport()) {
            const validationMessage = document.getElementById('validationMessage');
            validationMessage.classList.remove('shake');
            setTimeout(() => validationMessage.classList.add('shake'), 10);
            return;
        }

        const reason = document.getElementById('reportReason').value.trim();
        const reportLink = document.getElementById('reportLink');
        
        // Fix: Update parameter names to match report.php expectations
        const url = `report.php?job_id=<?php echo $job_id; ?>&user_id=<?php echo $user_id; ?>&reason=${encodeURIComponent(reason)}`;
        
        // Update the href and trigger the navigation
        reportLink.href = url;
        window.location.href = url;
    }

    // Close modal when clicking outside
    window.onclick = function(event) 
    {
        const modal = document.getElementById('reportModal');
        if (event.target == modal) {
            closeReportModal();
        }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) 
    {
        if (event.key === 'Escape') {
            closeReportModal();
        }
    });
    </script>

    <!-- Add this right after your existing toast container -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check if there's a message to display
        const toast = document.querySelector('.toast.show');
        if (toast) {
            // Auto-hide after 3 seconds
            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 500);
            }, 3000);
        }
    });
    </script>
</body>
</html>