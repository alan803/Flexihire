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
    if (!isset($_SESSION['user_id'])) 
    {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

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
            <div class="profile-container">
                <?php if (!empty($profile_image)): ?>
                    <img src="/mini project/database/profile_picture/<?php echo htmlspecialchars($profile_image); ?>" class="profile-pic" alt="Profile">
                <?php else: ?>
                    <img src="profile.png" class="profile-pic" alt="Profile">
                <?php endif; ?>
                <div class="dropdown-menu">
                    <div class="user-info">
                        <span class="username"><?php echo htmlspecialchars($display_name); ?></span>
                        <!-- <span class="email"><?php echo $email; ?></span> -->
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="profiles/user/userprofile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Add this right after your navbar -->
    <div id="toast" class="toast">
        <div class="toast-content">
            <div class="toast-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="toast-message-container">
                <div class="toast-title"></div>
                <span id="toast-message"></span>
            </div>
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
                <a href="userdashboard.php" class="active"><i class="fas fa-home"></i> Home</a>
                <!-- <a href="sidebar/jobgrid/jobgrid.html"><i class="fas fa-th"></i>Job Grid</a> -->
                <a href="applied.php"><i class="fas fa-paper-plane"></i> Applied Job</a>
                <!-- <a href="sidebar/jobdetails/jobdetails.html"><i class="fas fa-info-circle"></i> Job Details</a> -->
                <a href="bookmark.php"><i class="fas fa-bookmark"></i> Bookmarks</a>
                <a href="sidebar/appointment/appointment.html"><i class="fas fa-calendar"></i> Appointments</a>
                <a href="reportedjobs.php"><i class="fas fa-flag"></i> Reported Jobs</a>
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
            <!-- Search Container -->
            <div class="content-wrapper">
                <div class="search-section">
                    <!-- <h2 class="page-title">Applied Jobs</h2> -->
                    
                    <div class="search-container">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search applied jobs..." id="search" name="search">
                        </div>
                        <div class="filter-box">
                            <input type="text" placeholder="Location" id="location" name="location">
                            <div class="salary-range">
                                <input type="number" placeholder="Min Salary" id="minsalary" name="minsalary">
                                <input type="number" placeholder="Max Salary" id="maxsalary" name="maxsalary">
                            </div>
                            <input type="date" id="date" name="date">
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
                                    <span class="salary">₹<?php echo htmlspecialchars($row['salary']); ?></span>
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
                                        <button class="applied-btn" disabled>
                                            <i class="fas fa-check-circle"></i> Applied
                                            <?php if (isset($row['status'])): ?>
                                                (<?php echo htmlspecialchars($row['status']); ?>)
                                            <?php endif; ?>
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
            </div>
        </main>
    </div>
    <script src="userdashboard.js"></script>
    <style>
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

    /* Update the applied button styles */
    .applied-btn {
        padding: 12px 24px;
        border-radius: 100px;
        font-weight: 500;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: all 0.3s ease;
        background: rgba(76, 175, 80, 0.1);  /* Light green background */
        color: #4CAF50;  /* Green text */
        border: none;
        cursor: not-allowed;
    }

    .applied-btn i {
        color: #4CAF50;  /* Green icon */
    }

    /* Status colors within applied button */
    .applied-btn .status-pending {
        color: #FFA500;
    }

    .applied-btn .status-approved {
        color: #4CAF50;
    }

    .applied-btn .status-rejected {
        color: #FF4B4B;
    }

    /* Optional: Add a subtle border */
    .applied-btn {
        border: 1px solid rgba(76, 175, 80, 0.2);
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

    /* Toast styling */
    .toast {
        visibility: hidden;
        position: fixed;
        top: 80px;
        right: 30px;
        min-width: 300px;
        max-width: 500px;
        background-color: white;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        border-radius: 12px;
        z-index: 1000;
        overflow: hidden;
    }

    .toast.show {
        visibility: visible;
        animation: slideInRight 0.3s ease-out;
    }

    .toast-content {
        display: flex;
        align-items: flex-start;
        padding: 16px 20px;
        gap: 15px;
    }

    .toast-icon {
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .toast-icon i {
        font-size: 24px;
    }

    .toast-message-container {
        flex-grow: 1;
    }

    .toast-title {
        font-weight: 600;
        margin-bottom: 4px;
        font-size: 16px;
    }

    #toast-message {
        font-size: 14px;
        line-height: 1.5;
        color: inherit;
    }

    .toast-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
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
        animation: progress 3s linear;
    }

    .toast.success {
        background: #10B981;
        color: white;
    }

    .toast.error {
        background: #EF4444;
        color: white;
    }

    .toast.info {
        background: #3B82F6;
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

    @keyframes progress {
        from {
            width: 100%;
        }
        to {
            width: 0%;
        }
    }

    @media (max-width: 768px) {
        .toast {
            width: 90%;
            right: 5%;
            top: 20px;
        }
    }

    /* Add this modal HTML before closing body tag */
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

    /* Add these styles */
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

    // Add this function to show messages
    function showToast(message, type = 'info') {
        console.log('Showing toast:', message, type); // Debug line
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        const toastTitle = toast.querySelector('.toast-title');
        const icon = toast.querySelector('i');
        
        // Remove existing classes
        toast.className = 'toast';
        icon.className = 'fas';
        
        // Set title and icon based on type
        switch(type) {
            case 'success':
                toastTitle.textContent = 'Success';
                icon.classList.add('fa-check-circle');
                break;
            case 'error':
                toastTitle.textContent = 'Error';
                icon.classList.add('fa-exclamation-circle');
                break;
            default:
                toastTitle.textContent = 'Information';
                icon.classList.add('fa-info-circle');
        }
        
        toast.classList.add(type);
        toastMessage.textContent = message;
        
        // Show toast
        toast.classList.add('show');
        
        // Hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Add this to ensure the function is available
    window.showToast = showToast;

    // When the page loads, check for messages
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($message)): ?>
            showToast('<?php echo addslashes($message); ?>', '<?php echo $message_type; ?>');
        <?php endif; ?>
    });
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
</body>
</html>