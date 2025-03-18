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
                                        <a href="applyjob.php?user_id=<?php echo $user_id; ?>&job_id=<?php echo $job_id; ?>" class="apply-link">
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

    .toast {
        visibility: hidden;
        position: fixed;
        top: 20px;
        right: 20px;
        min-width: 300px;
        max-width: 500px;
        background-color: white;
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
