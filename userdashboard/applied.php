<?php
    session_start();
    include '../database/connectdatabase.php';

    // Message handling
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $message_type = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) 
    {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    $user_id = $_SESSION['id'] ?? $_SESSION['user_id'];

    // Get user profile info
    $sql = "SELECT l.email, u.first_name, u.last_name, u.username, u.profile_image 
            FROM tbl_login l
            INNER JOIN tbl_user u ON l.user_id = u.user_id
            WHERE l.user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) 
    {
        $user = mysqli_fetch_assoc($result);
        $profile_image = $user['profile_image'];
        
        // Set display name based on available data
        if (!empty($user['username'])) {
            $display_name = $user['username'];
        } else {
            $display_name = $user['first_name'] . " " . $user['last_name'];
        }
    } 
    else 
    {
        // Handle error - user not found
        session_destroy();
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    // Handle application cancellation
    if (isset($_GET['application_id'])) {
        $application_id = (int)$_GET['application_id'];

        $delete_sql = "DELETE FROM tbl_applications WHERE id = ? AND user_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);

        if ($delete_stmt) {
            mysqli_stmt_bind_param($delete_stmt, "ii", $application_id, $user_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                $affected_rows = mysqli_stmt_affected_rows($delete_stmt);
                mysqli_stmt_close($delete_stmt);
                
                if ($affected_rows > 0) {
                    $_SESSION['message'] = "Your job application has been successfully cancelled.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Application not found or already cancelled.";
                    $_SESSION['message_type'] = "error";
                }
            } else {
                $_SESSION['message'] = "Failed to cancel application. Please try again later.";
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "System error. Please try again.";
            $_SESSION['message_type'] = "error";
        }

        header("Location: applied.php");
        exit();
    }

    // Handle reset request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset']) && $_POST['reset'] == 1) {
        //fetching applied jobs with job details
        $applied = "SELECT a.id as application_id, a.status, a.applied_at, 
                   j.job_id, j.job_title, j.job_description, j.salary, 
                   j.location, j.town, j.created_at, j.vacancy_date 
                   FROM tbl_applications a
                   INNER JOIN tbl_jobs j ON a.job_id = j.job_id
                   WHERE a.user_id = ?
                   ORDER BY a.applied_at DESC";
        
        $stmt = mysqli_prepare($conn, $applied);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result_applied = mysqli_stmt_get_result($stmt);

        header('Content-Type: application/json'); // Change to JSON response
        $jobs = array();

        if ($result_applied && mysqli_num_rows($result_applied) > 0) {
            while ($row = mysqli_fetch_assoc($result_applied)) {
                $jobs[] = array(
                    'application_id' => $row['application_id'],
                    'job_title' => $row['job_title'],
                    'salary' => $row['salary'],
                    'job_description' => $row['job_description'],
                    'location' => $row['location'],
                    'town' => $row['town'],
                    'created_at' => date('Y-m-d', strtotime($row['created_at'])),
                    'vacancy_date' => date('Y-m-d', strtotime($row['vacancy_date'])),
                    'status' => $row['status']
                );
            }
            echo json_encode(['success' => true, 'jobs' => $jobs]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No jobs found']);
        }
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="applied.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="userdashboard.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-brand">
            <img src="logowithoutbcakground.png" alt="Logo" class="logo">
            <h1>FlexiHire</h1>
        </div>
        
        <div class="nav-right">
            <div class="profile-info">
                <span class="nav-username"><?php echo htmlspecialchars($display_name); ?></span>
                <div class="profile-container">
                    <?php 
                    $profile_path = "/mini project/database/profile_picture/" . $profile_image;
                    if (!empty($profile_image) && file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_path)): ?>
                        <img src="<?php echo $profile_path; ?>" class="profile-pic" alt="Profile">
                    <?php else: ?>
                        <img src="employer_pf/deafult.webp" class="profile-pic" alt="Default Profile">
                    <?php endif; ?>
                    <div class="dropdown-menu">
                        <a href="profiles/user/userprofile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Add this right after your navbar -->
    <div id="toast" class="toast">
        <div class="toast-content">
            <div class="toast-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="toast-message"></div>
        </div>
        <div class="toast-progress"></div>
    </div>

    <!-- Add this HTML for the message display -->
    <div id="message-container"></div>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-menu">
                <a href="userdashboard.php"><i class="fas fa-home"></i> Home</a>
                <a href="applied.php" class="active"><i class="fas fa-paper-plane"></i> Applied Job</a>
                <a href="bookmark.php"><i class="fas fa-bookmark"></i> Bookmarks</a>
                <a href="appointment.php"><i class="fas fa-calendar"></i> Appointments</a>
                <a href="reportedjobs.php"><i class="fas fa-flag"></i> Reported Jobs</a>
                <a href="reviews.php"><i class="fas fa-star"></i> Reviews</a>
                <a href="profiles/user/userprofile.php"><i class="fas fa-user"></i> Profile</a>
            </div>
            <div class="logout-container">
                <div class="sidebar-divider"></div>
                <a href="../login/logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>
        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Replace the search section with this header -->
            <div class="header-container">
                <div class="header-content">
                    <div class="header-left">
                        <h2>Your Applications</h2>
                        <p>Track and manage your job applications</p>
                    </div>
                    <div class="header-right">
                        <div class="filter-group">
                            <label for="statusFilter">Filter by Status:</label>
                            <select id="statusFilter" class="filter-select">
                                <option value="all">All Applications</option>
                                <option value="pending">Pending</option>
                                <option value="accepted">Accepted</option>
                                <option value="rejected">Rejected</option>
                                <option value="interview scheduled">Interview Scheduled</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Listings Container -->
            <div class="jobs-section">
                <?php
                //fetching applied jobs with job details
                $applied = "SELECT a.id as application_id, a.status, a.applied_at, 
                           j.job_id, j.job_title, j.job_description, j.salary, 
                           j.location, j.town, j.created_at, j.vacancy_date 
                       FROM tbl_applications a
                       INNER JOIN tbl_jobs j ON a.job_id = j.job_id
                       WHERE a.user_id = ?
                       ORDER BY a.applied_at DESC";
                
                $stmt = mysqli_prepare($conn, $applied);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $result_applied = mysqli_stmt_get_result($stmt);

                if ($result_applied && mysqli_num_rows($result_applied) > 0) {
                    while ($row = mysqli_fetch_assoc($result_applied)) {
                        $job_id = $row['job_id'];
                        $_SESSION['job_id'] = $job_id;
                        
                        // Check if user has already applied for this job
                        // $check_sql = "SELECT status FROM tbl_applications WHERE user_id = ? AND job_id = ?";
                        // $check_stmt = mysqli_prepare($conn, $check_sql);
                        // mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $job_id);
                        // mysqli_stmt_execute($check_stmt);
                        // $application_result = mysqli_stmt_get_result($check_stmt);
                        // $has_applied = mysqli_num_rows($application_result) > 0;
                        // $application_status = $has_applied ? mysqli_fetch_assoc($application_result)['status'] : null;
                        ?>
                        <div class="job-card" data-application-id="<?php echo $row['application_id']; ?>">
                            <div class="job-header">
                                <h3 class="job_title"><?php echo htmlspecialchars($row['job_title']); ?></h3>
                                <span class="salary">₹<?php echo number_format($row['salary']); ?></span>
                            </div>
                            <div class="job-body">
                                <p class="description"><?php echo htmlspecialchars($row['job_description']); ?></p>
                                <p class="location">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo htmlspecialchars($row['location']) . ', ' . htmlspecialchars($row['town']); ?>
                                </p>
                                <p class="date">
                                    <i class="fas fa-calendar-plus"></i> 
                                    Posted: <?php echo date('Y-m-d', strtotime($row['created_at'])); ?>
                                </p>
                                <p class="date">
                                    <i class="fas fa-calendar-alt"></i> 
                                    Vacancy Date: <?php echo date('Y-m-d', strtotime($row['vacancy_date'])); ?>
                                </p>
                            </div>
                            <div class="job-footer">
                                <div class="button-group">
                                    <!-- <a href="jobdetails.php?job_id=<?php echo $row['job_id']; ?>" class="details-btn">
                                        <i class="fas fa-info-circle"></i> Details
                                    </a> -->
                                    <button class="applied-btn" data-status="<?php echo strtolower($row['status']); ?>" disabled>
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </button>
                                    <a href="cancel_application.php?application_id=<?php echo $row['application_id']; ?>" 
                                       class="details-btn cancel-hover"
                                       onclick="openModal(<?php echo $row['application_id']; ?>); return false;">
                                        <i class="fas fa-times-circle"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="no-jobs">You haven\'t applied to any jobs yet.</div>';
                }
                ?>
            </div>
        </main>
    </div>
    <script src="applied.js"></script>
    <style>
    /* Professional Status Button Styles */
    .applied-btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        border: none;
        transition: all 0.3s ease;
        color: white;
        cursor: default;
        text-transform: capitalize;
        letter-spacing: 0.3px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        min-width: 120px;
        text-align: center;
    }

    /* Status-specific styles with gradients */
    .applied-btn[data-status="pending"] {
        background: linear-gradient(135deg, #FFA726, #FB8C00);
        border: 1px solid rgba(251, 140, 0, 0.1);
    }

    .applied-btn[data-status="accepted"] {
        background: linear-gradient(135deg, #4CAF50, #43A047);
        border: 1px solid rgba(67, 160, 71, 0.1);
    }

    .applied-btn[data-status="rejected"] {
        background: linear-gradient(135deg, #EF4444, #DC2626);
        border: 1px solid rgba(220, 38, 38, 0.1);
    }

    .applied-btn[data-status="interview scheduled"] {
        background: linear-gradient(135deg, #3B82F6, #2563EB);
        border: 1px solid rgba(37, 99, 235, 0.1);
    }

    /* Hover effect */
    .applied-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* Active state */
    .applied-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Disabled state */
    .applied-btn:disabled {
        opacity: 1;
        cursor: default;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .applied-btn {
            width: 100%;
            padding: 12px 20px;
        }
    }

    /* Add these styles for the cancel button */
    .cancel-btn {
        padding: 12px 24px;
        border-radius: 100px;
        font-weight: 500;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: all 0.3s ease;
        background: #FF4B4B;
        color: white;
        border: none;
        cursor: pointer;
    }

    .cancel-btn:hover {
        background: #ff3333;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(255, 75, 75, 0.2);
    }

    .cancel-btn i {
        font-size: 16px;
    }

    /* Update button-group styles for three buttons */
    .button-group {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .button-group {
            flex-direction: column;
        }
        
        .details-btn, .applied-btn, .cancel-btn {
            width: 100%;
            justify-content: center;
        }
    }

    .message {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 8px;
        background: white;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease-out;
        max-width: 400px;
    }

    .message.success {
        background: #4CAF50;
        color: white;
    }

    .message.error {
        background: #FF4B4B;
        color: white;
    }

    .message.info {
        background: #2196F3;
        color: white;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }

    /* Toast Notification Styles */
    .toast {
        position: fixed;
        top: 80px;
        right: 30px;
        min-width: 300px;
        max-width: 400px;
        background: white;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        border-radius: 12px;
        padding: 16px 20px;
        opacity: 0;
        visibility: hidden;
        transform: translateX(100%);
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .toast.show {
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
    }

    .toast-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .toast-icon {
        flex-shrink: 0;
    }

    .toast-icon i {
        font-size: 24px;
    }

    .toast-message {
        font-size: 14px;
        line-height: 1.5;
        color: #333;
    }

    .toast-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: #e0e0e0;
        border-radius: 0 0 12px 12px;
    }

    .toast-progress::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        transform-origin: left;
        animation: progress 3s linear forwards;
    }

    /* Toast Types */
    .toast.success {
        background: #4CAF50;
        color: white;
    }

    .toast.success .toast-message,
    .toast.success .toast-icon i {
        color: white;
    }

    .toast.success .toast-progress::after {
        background: rgba(255, 255, 255, 0.7);
    }

    .toast.error {
        background: #EF4444;
        color: white;
    }

    .toast.error .toast-message,
    .toast.error .toast-icon i {
        color: white;
    }

    .toast.error .toast-progress::after {
        background: rgba(255, 255, 255, 0.7);
    }

    @keyframes progress {
        0% { width: 100%; }
        100% { width: 0%; }
    }

    @media (max-width: 768px) {
        .toast {
            top: 20px;
            right: 20px;
            left: 20px;
            min-width: unset;
            max-width: unset;
        }
    }
    </style>

    <!-- Add this JavaScript -->
    <script>
    let currentApplicationId = null;

    function openModal(applicationId) {
        console.log('Opening modal for application:', applicationId); // Debug line
        currentApplicationId = applicationId;
        const modal = document.getElementById('confirmationModal');
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        } else {
            console.error('Modal element not found'); // Debug line
        }
    }

    function closeModal() {
        const modal = document.getElementById('confirmationModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            currentApplicationId = null;
        }
    }

    function confirmCancel() {
        if (currentApplicationId) {
            window.location.href = `applied.php?application_id=${currentApplicationId}`;
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('confirmationModal');
        if (event.target == modal) {
            closeModal();
        }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    // Add this to check if JavaScript is loading
    console.log('Modal JavaScript loaded');

    // Toast notification function
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMessage = toast.querySelector('.toast-message');
        const toastIcon = toast.querySelector('.toast-icon i');
        
        // Set message
        toastMessage.textContent = message;
        
        // Set icon based on type
        if (type === 'success') {
            toastIcon.className = 'fas fa-check-circle';
            toast.className = 'toast show success';
        } else if (type === 'error') {
            toastIcon.className = 'fas fa-times-circle';
            toast.className = 'toast show error';
        }
        
        // Show toast
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        // Hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Show toast if PHP message exists
    <?php if (isset($message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('<?php echo addslashes($message); ?>', '<?php echo $message_type; ?>');
        });
    <?php endif; ?>

    // Add this filter function
    document.getElementById('statusFilter').addEventListener('change', function() {
        const selectedStatus = this.value.toLowerCase();
        const jobCards = document.querySelectorAll('.job-card');
        let matchFound = false;

        // Remove any existing "no jobs" message
        document.querySelectorAll('.no-jobs').forEach(el => el.remove());

        jobCards.forEach(card => {
            const statusElement = card.querySelector('.status-badge') || 
                                card.querySelector('.applied-btn');
            const currentStatus = statusElement.textContent.toLowerCase().trim();

            if (selectedStatus === 'all') {
                card.style.display = 'block';
                matchFound = true;
            } else {
                // Check if the status contains our selected status
                // This handles cases like "Status: Pending" or just "Pending"
                if (currentStatus.includes(selectedStatus)) {
                    card.style.display = 'block';
                    matchFound = true;
                } else {
                    card.style.display = 'none';
                }
            }
        });

        // Show "no jobs" message if no matches found
        if (!matchFound && selectedStatus !== 'all') {
            const message = `No applications found with status "${
                selectedStatus.charAt(0).toUpperCase() + selectedStatus.slice(1)
            }"`;
            showNoJobsMessage(message);
        }
    });

    // Add this to your existing showNoJobsMessage function if not already present
    function showNoJobsMessage(message) {
        const jobsSection = document.querySelector('.jobs-section');
        const noJobsMessage = document.createElement('div');
        noJobsMessage.className = 'no-jobs';
        noJobsMessage.innerHTML = `
            <div class="no-jobs-content">
                <i class="fas fa-search"></i>
                <h3>No Applications Found</h3>
                <p>${message}</p>
                <button class="reset-button" onclick="resetFilters()">
                    <i class="fas fa-undo"></i> Reset Filters
                </button>
            </div>
        `;
        jobsSection.appendChild(noJobsMessage);
    }

    // Update your existing resetFilters function
    function resetFilters() {
        // Reset the status filter
        document.getElementById('statusFilter').value = 'all';
        
        // Show all job cards
        const jobCards = document.querySelectorAll('.job-card');
        jobCards.forEach(card => card.style.display = 'block');
        
        // Remove any "no jobs" message
        document.querySelectorAll('.no-jobs').forEach(el => el.remove());
    }
    </script>

    <!-- Add this HTML right before the closing </body> tag -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-exclamation-circle warning-icon"></i>
                <h2>Cancel Application</h2>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this job application?</p>
                <p class="warning-text">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn cancel-btn-secondary" onclick="closeModal()">No, Keep It</button>
                <button class="modal-btn confirm-btn" onclick="confirmCancel()">Yes, Cancel Application</button>
            </div>
        </div>
    </div>

    <!-- Make sure this style is present -->
    <style>
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
        text-align: center;
        margin-bottom: 20px;
    }

    .warning-icon {
        color: #ff4b4b;
        font-size: 48px;
        margin-bottom: 10px;
    }

    .modal-body {
        text-align: center;
        margin-bottom: 20px;
    }

    .warning-text {
        color: #666;
        font-size: 14px;
        margin-top: 10px;
    }

    .modal-footer {
        display: flex;
        justify-content: center;
        gap: 10px;
    }

    .modal-btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .cancel-btn-secondary {
        background-color: #fff;
        color: #666;
        border: 1px solid #ddd;
    }

    .confirm-btn {
        background-color: #ff4b4b;
        color: white;
        border: none;
    }

    .cancel-btn-secondary:hover {
        background-color: #f5f5f5;
    }

    .confirm-btn:hover {
        background-color: #ff3333;
    }
    </style>

    <style>
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

    <style>
    /* Header Styles */
    .header-container {
        background: #fff;
        border-radius: 15px;
        padding: 1.5rem 2rem;
        margin: 0.1rem 0 2rem 0;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06);
        width: 100%;
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 2rem;
    }

    .header-left h2 {
        font-size: 1.5rem;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .header-left p {
        color: #7f8c8d;
        font-size: 1rem;
    }

    .header-right {
        display: flex;
        align-items: center;
    }

    .filter-group {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .filter-group label {
        font-weight: 500;
        color: #2c3e50;
        white-space: nowrap;
    }

    .filter-select {
        padding: 0.75rem;
        border: 1px solid rgba(186, 166, 227, 0.3);
        border-radius: 6px;
        background: #fff;
        min-width: 180px;
        color: #2c3e50;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .header-right {
            width: 100%;
        }

        .filter-group {
            width: 100%;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .filter-select {
            width: 100%;
        }
    }
    </style>
</body>
</html>