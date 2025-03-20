<?php
    session_start();

    // Debug: Log the session message if it exists
    if (isset($_SESSION['message'])) {
        error_log("Message in session: " . $_SESSION['message']);
        error_log("Message type in session: " . $_SESSION['message_type']);
    }

    // Store message in variables and clear session
    $message = '';
    $message_type = '';

    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $message_type = $_SESSION['message_type'];
        
        // Clear the session messages
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) 
    {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // Initialize variables
    $username = '';
    $email = '';

    // Get user data with updated query
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT l.email, u.first_name, u.last_name, u.username, u.profile_image, u.user_id
            FROM tbl_login l
            INNER JOIN tbl_user u ON l.user_id = u.user_id
            WHERE l.user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);
        $email = $user_data['email'];
        
        // Set display name based on available data
        if (!empty($user_data['username'])) 
        {
            $display_name = $user_data['username'];
        } 
        else 
        {
            $display_name = $user_data['first_name'] . " " . $user_data['last_name'];
        }
        
        $profile_image = $user_data['profile_image'];
        $employer_id = $user_data['user_id'];
        
        // Store in session for consistency
        $_SESSION['display_name'] = $display_name;
    } 
    else 
    {
        error_log("Database error or user not found for ID: $user_id");
        session_destroy();
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    // Fetch active jobs based on selected date
    $date_filter = isset($_POST['date']) ? $_POST['date'] : null;

    $sql_fetch = "SELECT job_id, job_title, job_description, location, town, salary, vacancy_date, created_at 
                FROM tbl_jobs 
                WHERE is_deleted = 0";

    if (!empty($date_filter)) {
        $sql_fetch .= " AND DATE(vacancy_date) = ?";
    }

    $stmt = mysqli_prepare($conn, $sql_fetch);

    if (!empty($date_filter)) {
        mysqli_stmt_bind_param($stmt, "s", $date_filter);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // If it's an AJAX request, return only the job listings
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<div class="job-card">';
                echo '<div class="job-header">';
                echo '<h3 class="job_title">' . htmlspecialchars($row['job_title']) . '</h3>';
                echo '<span class="salary">₹' . htmlspecialchars($row['salary']) . '</span>';
                echo '</div>';
                echo '<div class="job-body">';
                echo '<p class="description">' . htmlspecialchars($row['job_description']) . '</p>';
                echo '<p class="location"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($row['location']) . ', ' . htmlspecialchars($row['town']) . '</p>';
                echo '<p class="date"><i class="fas fa-calendar-plus"></i> Posted: ' . date('Y-m-d', strtotime($row['created_at'])) . '</p>';
                echo '<p class="date"><i class="fas fa-calendar-alt"></i> Vacancy Date: ' . date('Y-m-d', strtotime($row['vacancy_date'])) . '</p>';
                echo '</div>';
                echo '<div class="job-footer">';
                echo '<a href="jobdetails.php?job_id=' . $row['job_id'] . '" class="details-btn"><i class="fas fa-info-circle"></i> Details</a>';
                echo '<button class="apply-btn"><i class="fas fa-paper-plane"></i> Apply Now</button>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<div class="no-jobs">No jobs available on this date.</div>';
        }
        exit();
    }

    // Only process error messages if they came from a redirect
    if (isset($_GET['error']) && isset($_SERVER['HTTP_REFERER'])) {
        $error_message = '';
        switch ($_GET['error']) {
            case 'already_applied':
                $error_message = 'You have already applied for this job';
                break;
            case 'job_not_found':
                $error_message = 'Job listing not found';
                break;
            case 'application_failed':
                $error_message = 'Failed to submit application. Please try again';
                break;
            default:
                $error_message = 'An error occurred';
        }
        // Store the message in session and redirect to clean URL
        $_SESSION['error_message'] = $error_message;
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit();
    }

    // Display message from session if exists and then clear it
    if (isset($_SESSION['error_message'])) {
        $error_message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="userdashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Update the toast container -->
    <?php if (!empty($message)): ?>
    <div id="toast-container">
        <div id="toast" class="toast <?php echo $message_type; ?>">
            <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <span id="toast-message"><?php echo htmlspecialchars($message); ?></span>
        </div>
    </div>
    <?php endif; ?>

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
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="toast-message-container">
                <div class="toast-title"></div>
                <span id="toast-message"></span>
            </div>
        </div>
    </div>

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
            <!-- Search Bar -->
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search jobs..." id="search" name="search" oninput="filterjobs()">
                </div>
                <div class="filter-box">
                    <input type="text" placeholder="Location" id="location" name="location" oninput="filterlocation()">
                    <div class="salary-range">
                        <input type="number" placeholder="Min Salary" id="minsalary" name="minsalary" oninput="filterminsalary()">
                        <input type="number" placeholder="Max Salary" id="maxsalary" name="maxsalary" oninput="filtermaxsalary()">
                    </div>
                    <input type="date" id="date" name="date" oninput="filterdate()">
                </div>
            </div>

            <!-- Job Listings -->
            <div class="job-listings">
                <?php
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) 
                    {
                        $job_id = $row['job_id'];
                        $_SESSION['job_id'] = $job_id;
                        
                        // Check if user has already applied for this job
                        $check_sql = "SELECT status FROM tbl_applications WHERE user_id = ? AND job_id = ?";
                        $check_stmt = mysqli_prepare($conn, $check_sql);
                        mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $job_id);
                        mysqli_stmt_execute($check_stmt);
                        $application_result = mysqli_stmt_get_result($check_stmt);
                        $has_applied = mysqli_num_rows($application_result) > 0;
                        $application_status = $has_applied ? mysqli_fetch_assoc($application_result)['status'] : null;
                        ?>
                        <div class="job-card">
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
                                    <a href="jobdetails.php?job_id=<?php echo $job_id; ?>" class="details-btn">
                                        <i class="fas fa-info-circle"></i> Details
                                    </a>
                                    <?php if ($has_applied): ?>
                                        <button class="applied-btn" disabled>
                                            <i class="fas fa-check-circle"></i> 
                                            Applied
                                            <?php if ($application_status): ?>
                                                (<?php echo htmlspecialchars($application_status); ?>)
                                            <?php endif; ?>
                                        </button>
                                    <?php else: ?>
                                        <a href="applyjob.php?job_id=<?php echo $job_id; ?>" class="apply-link">
                                            <button class="apply-btn">
                                                <i class="fas fa-paper-plane"></i> Apply Now
                                            </button>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="no-jobs">No active jobs available.</div>';
                }
                ?>
            </div>
        </main>
    </div>

    <script src="userdashboard.js"></script>

    <style>
    .job-footer {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .button-group {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .details-btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        color: #9747FF;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .details-btn:hover {
        background: rgba(151, 71, 255, 0.1);
    }

    .apply-btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        background: #9747FF;
        color: white;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .apply-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(151, 71, 255, 0.2);
    }

    .apply-btn i {
        font-size: 16px;
    }

    .job-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }

    .job-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    /* Add this to ensure no underline on any links */
    .job-footer a {
        text-decoration: none !important;
    }

    /* Updated toast styles with enforced white text */
    #toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }

    .toast {
        min-width: 300px;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        animation: slideIn 0.3s ease-out;
    }

    .toast.success {
        background-color: #10B981;
        color: white;
    }

    .toast.error {
        background-color: #EF4444;
        color: white;
    }

    .toast-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .toast i {
        font-size: 20px;
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
        to {
            opacity: 0;
            transform: translateY(-100%);
        }
    }
    </style>

    <script>
    // Add this script to handle toast animations
    document.addEventListener('DOMContentLoaded', function() {
        const toast = document.getElementById('toast');
        if (toast) {
            setTimeout(() => {
                toast.style.animation = 'fadeOut 0.5s ease forwards';
                setTimeout(() => {
                    toast.remove();
                }, 500);
            }, 3000);
        }
    });
    </script>

    <!-- Updated Upload Modal HTML -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="header-content">
                    <i class="fas fa-file-upload"></i>
                    <h2>Required Documents</h2>
                </div>
                <span class="close" onclick="closeUploadModal()">
                    <i class="fas fa-times"></i>
                </span>
            </div>
            <div class="modal-body">
                <div class="requirements-info">
                    <i class="fas fa-info-circle"></i>
                    <p>Please upload the required documents to complete your application.</p>
                </div>
                <form id="uploadForm" action="process_documents.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="job_id" id="upload_job_id">
                    
                    <!-- License Upload Section -->
                    <div class="form-group license-group">
                        <div class="upload-container">
                            <div class="upload-header">
                                <div class="upload-title">
                                    <i class="fas fa-id-card"></i>
                                    <h3>Driving License</h3>
                                </div>
                                <span class="required-badge">Required</span>
                            </div>
                            <div class="upload-box" id="licenseBox">
                                <input type="file" name="license" id="license" accept=".pdf,.jpg,.jpeg,.png" class="file-input">
                                <div class="upload-content">
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="upload-text">
                                        <span class="primary-text">Click or drag file to upload</span>
                                    </div>
                                </div>
                            </div>
                            <div class="validation-message" id="licenseValidation"></div>
                            <div class="upload-help">
                                <i class="fas fa-info-circle"></i>
                                <span>Accepted formats: PDF, JPG, PNG (Max size: 5MB)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Badge Upload Section -->
                    <div class="form-group badge-group">
                        <div class="upload-container">
                            <div class="upload-header">
                                <div class="upload-title">
                                    <i class="fas fa-certificate"></i>
                                    <h3>Badge Certificate</h3>
                                </div>
                                <span class="required-badge">Required</span>
                            </div>
                            <div class="upload-box" id="badgeBox">
                                <input type="file" name="badge" id="badge" accept=".pdf,.jpg,.jpeg,.png" class="file-input">
                                <div class="upload-content">
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="upload-text">
                                        <span class="primary-text">Click or drag file to upload</span>
                                    </div>
                                </div>
                            </div>
                            <div class="validation-message" id="badgeValidation"></div>
                            <div class="upload-help">
                                <i class="fas fa-info-circle"></i>
                                <span>Accepted formats: PDF, JPG, PNG (Max size: 5MB)</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn cancel-btn" onclick="closeUploadModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn submit-btn">
                            <i class="fas fa-paper-plane"></i> Submit Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
    }

    .modal-content {
        background: #fff;
        margin: 2% auto;
        width: 80%;
        max-width: 400px;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        animation: modalSlideIn 0.3s ease;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 20px;
        border-bottom: 1px solid #e5e7eb;
    }

    .header-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .header-content i {
        font-size: 24px;
        color: #9747FF;
    }

    .header-content h2 {
        font-size: 20px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .close {
        cursor: pointer;
        padding: 8px;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .close:hover {
        background: #f1f5f9;
    }

    .modal-body {
        padding: 16px;
    }

    .requirements-info {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: #f8fafc;
        border-radius: 12px;
        margin-bottom: 16px;
    }

    .requirements-info i {
        color: #9747FF;
        font-size: 20px;
    }

    .upload-container {
        margin-bottom: 8px;
        width: 100%;
    }

    .upload-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .upload-title {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .upload-title i {
        color: #9747FF;
    }

    .upload-title h3 {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
        color: #1e293b;
    }

    .required-badge {
        background: #fee2e2;
        color: #ef4444;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
    }

    .upload-box {
        padding: 8px;
        height: 100px;
        width: 90%;
        margin: 0 auto;
        border: 2px dashed #e2e8f0;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .upload-box:hover {
        border-color: #9747FF;
        background: rgba(151, 71, 255, 0.02);
    }

    .upload-content {
        width: 100%;
        max-width: 300px;
        margin: 0 auto;
        text-align: center;
    }

    .upload-text {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
    }

    .primary-text {
        font-size: 11px;
        color: #475569;
    }

    .secondary-text {
        display: none; /* Remove "or" text */
    }

    .browse-btn {
        display: none; /* Remove browse button */
    }

    .upload-icon i {
        font-size: 24px;
        color: #9747FF;
        margin-bottom: 4px;
    }

    .file-input {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        opacity: 0;
        cursor: pointer;
    }

    .file-info {
        display: none;
        width: 100%;
        padding: 12px;
        background: #f8fafc;
        border-radius: 8px;
    }

    .file-info.show {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .file-preview {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .file-preview i {
        color: #9747FF;
    }

    .file-size {
        color: #64748b;
        font-size: 14px;
    }

    .upload-help {
        width: 70%;
        margin: 4px auto 0;
        font-size: 9px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .upload-help i {
        font-size: 10px;
        color: #9747FF;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        cursor: pointer;
        border: none;
    }

    .cancel-btn {
        background: #f1f5f9;
        color: #64748b;
    }

    .cancel-btn:hover {
        background: #e2e8f0;
    }

    .submit-btn {
        background: #9747FF;
        color: white;
    }

    .submit-btn:hover {
        background: #8035e0;
        transform: translateY(-1px);
    }

    @keyframes modalSlideIn {
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
    function showUploadModal(jobId, needsLicense, needsBadge) {
        document.getElementById('upload_job_id').value = jobId;
        document.querySelector('.license-group').style.display = needsLicense ? 'block' : 'none';
        document.querySelector('.badge-group').style.display = needsBadge ? 'block' : 'none';
        document.getElementById('uploadModal').style.display = 'block';
    }

    function closeUploadModal() {
        const modal = document.getElementById('uploadModal');
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
            modal.style.opacity = '1';
            document.getElementById('uploadForm').reset();
            document.getElementById('licenseInfo').textContent = 'No file chosen';
            document.getElementById('badgeInfo').textContent = 'No file chosen';
        }, 300);
    }

    // Update file info when files are selected
    document.querySelectorAll('.file-input').forEach(input => {
        input.addEventListener('change', function(e) {
            const fileName = this.files[0]?.name || 'No file chosen';
            const infoElement = this.id === 'license' ? 
                document.getElementById('licenseInfo') : 
                document.getElementById('badgeInfo');
            infoElement.textContent = fileName;
        });
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('uploadModal');
        if (event.target === modal) {
            closeUploadModal();
        }
    }
    </script>
</body>
</html>