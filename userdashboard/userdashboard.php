<?php
    session_start();

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

    // Fetch active jobs based on selected date and deadline
    $date_filter = isset($_POST['date']) ? $_POST['date'] : null;
    $is_reset = isset($_POST['reset']) && $_POST['reset'] == 1;

    $sql_fetch = "SELECT j.*, e.company_name, e.profile_image,
                  (SELECT COUNT(*) FROM tbl_applications WHERE job_id = j.job_id AND status = 'accepted') AS total_accepted,
                  (SELECT status FROM tbl_applications WHERE job_id = j.job_id AND user_id = ? LIMIT 1) AS application_status
              FROM tbl_jobs j 
              JOIN tbl_employer e ON j.employer_id = e.employer_id
              WHERE j.status = 'approved' 
              AND j.is_deleted = 0
              AND j.vacancy_date >= CURDATE()  /* Only show jobs where deadline hasn't passed */
              ORDER BY j.created_at DESC";

    $stmt = mysqli_prepare($conn, $sql_fetch);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // If it's an AJAX request, return only the job listings
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $job_id = $row['job_id'];
                
                // Check if user has already applied for this job
                $check_sql = "SELECT status FROM tbl_applications WHERE user_id = ? AND job_id = ?";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $job_id);
                mysqli_stmt_execute($check_stmt);
                $application_result = mysqli_stmt_get_result($check_stmt);
                $has_applied = mysqli_num_rows($application_result) > 0;
                $application_status = $has_applied ? mysqli_fetch_assoc($application_result)['status'] : null;
                
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
                echo '<div class="button-group">';
                echo '<a href="jobdetails.php?job_id=' . $job_id . '" class="details-btn"><i class="fas fa-info-circle"></i> Details</a>';
                if ($application_status) {
                    echo '<button class="applied-btn" disabled><i class="fas fa-check-circle"></i> ' . htmlspecialchars($application_status) . '</button>';
                } else {
                    echo '<a href="applyjob.php?user_id=' . $user_id . '&job_id=' . $job_id . '" class="apply-link">';
                    echo '<button class="apply-btn"><i class="fas fa-paper-plane"></i> Apply Now</button>';
                    echo '</a>';
                }
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<div class="no-jobs">No jobs available.</div>';
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

    // Function to check if vacancy is filled
    function isVacancyFilled($conn, $job_id) {
        $sql = "SELECT j.vacancy, 
                (SELECT COUNT(*) FROM tbl_applications WHERE job_id = ? AND status = 'accepted') AS total_accepted 
                FROM tbl_jobs j 
                WHERE j.job_id = ?";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $job_id, $job_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        return $row['total_accepted'] >= $row['vacancy'];
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

    <!-- Toast Notification -->
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
                <a href="applied.php"><i class="fas fa-paper-plane"></i> Applied Job</a>
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
                    <button class="reset-button" onclick="resetFilters()">
                        <i class="fas fa-undo"></i> Reset Filters
                    </button>
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
                                    <?php if (isVacancyFilled($conn, $job_id)): ?>
                                        <div class="job-closed">
                                            <i class="fas fa-user-check"></i> Positions Filled
                                        </div>
                                    <?php else: ?>
                                        <?php if ($has_applied): ?>
                                            <button class="applied-btn" disabled>
                                                <i class="fas fa-check-circle"></i> 
                                                <?php echo htmlspecialchars(ucfirst($application_status)); ?>
                                            </button>
                                        <?php else: ?>
                                            <a href="applyjob.php?user_id=<?php echo $user_id; ?>&job_id=<?php echo $job_id; ?>" class="apply-link">
                                                <button class="apply-btn">
                                                    <i class="fas fa-paper-plane"></i> Apply Now
                                                </button>
                                            </a>
                                        <?php endif; ?>
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

    <script src="shared-search.js"></script>
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

    .job-footer a {
        text-decoration: none !important;
    }

    .toast {
        visibility: hidden;
        position: fixed;
        top: 20px;
        right: 20px;
        min-width: 300px;
        max-width: 500px;
        background-color: lightgreen;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-radius: 8px;
        z-index: 1000;
        overflow: hidden;
    }

    .toast.show {
        visibility: visible;
        animation: slideInRight 0.3s, fadeOut 0.5s 2.5s;
    }

    .toast-content {
        display: flex;
        align-items: center;
        padding: 16px 20px;
        gap: 12px;
    }

    .toast-icon {
        flex-shrink: 0;
    }

    .toast-icon i {
        font-size: 24px;
    }

    .toast.success .toast-icon i {
        color: #4CAF50;
    }

    .toast.error .toast-icon i {
        color: #f44336;
    }

    .toast-message-container {
        flex-grow: 1;
    }

    .toast-title {
        font-weight: 600;
        margin-bottom: 4px;
    }

    #toast-message {
        color: #666;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
        }
        to {
            transform: translateX(0);
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
        }
    }

    .applied-btn {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: default;
        transition: all 0.3s ease;
        opacity: 0.9;
    }

    .applied-btn i {
        color: #fff;
        font-size: 16px;
        animation: checkmark 0.5s ease-in-out;
    }

    @keyframes checkmark {
        0% {
            transform: scale(0);
        }
        50% {
            transform: scale(1.2);
        }
        100% {
            transform: scale(1);
        }
    }

    .applied-btn:disabled {
        opacity: 1;
        background-color: #4CAF50;
        cursor: default;
    }

    .applied-btn:hover {
        box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
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
    // Show toast message if exists
    <?php if (isset($message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('<?php echo addslashes($message); ?>', '<?php echo $message_type; ?>');
        });
    <?php endif; ?>

    function showToast(message, type = 'info') {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toast-message');
        const toastTitle = toast.querySelector('.toast-title');
        const icon = toast.querySelector('i');
        
        // Reset classes
        toast.className = 'toast';
        icon.className = 'fas';
        
        // Set type-specific properties
        switch(type) {
            case 'success':
                toastTitle.textContent = 'Success';
                icon.classList.add('fa-check-circle');
                toast.classList.add('success');
                break;
            case 'error':
                toastTitle.textContent = 'Error';
                icon.classList.add('fa-exclamation-circle');
                toast.classList.add('error');
                break;
            default:
                toastTitle.textContent = 'Information';
                icon.classList.add('fa-info-circle');
                toast.classList.add('info');
        }
        
        toastMessage.textContent = message;
        toast.classList.add('show');
        
        // Hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
    </script>
</body>
</html>