<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname="project";

    if(!isset($_SESSION['user_id']))
    {
        header("Location: ../login/login.php");
        exit;
    }

    // Get user data
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT l.email, u.first_name, u.last_name, u.username, u.profile_image 
            FROM tbl_login l
            INNER JOIN tbl_user u ON l.user_id = u.user_id
            WHERE l.user_id = ?";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);
        // Set display name based on available data
        if (!empty($user_data['username'])) {
            $display_name = $user_data['username'];
        } else {
            $display_name = $user_data['first_name'] . " " . $user_data['last_name'];
        }
        $profile_image = $user_data['profile_image'];
    } else {
        $display_name = "User";
        $profile_image = "";
    }

    // Handle reset request first, before any HTML output
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset']) && $_POST['reset'] == 1) {
        header('Content-Type: application/json');
        
        $user_id = isset($_SESSION['id']) ? $_SESSION['id'] : $_SESSION['user_id'];
        
        $reported_sql = "SELECT j.*, r.status as report_status 
                        FROM tbl_jobs j 
                        INNER JOIN tbl_reports r ON j.job_id = r.reported_job_id 
                        WHERE r.reporter_id = ?";
        
        $stmt = mysqli_prepare($conn, $reported_sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Error preparing query']);
            exit;
        }

        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (!mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => false, 'message' => 'Error executing query']);
            exit;
        }

        $result = mysqli_stmt_get_result($stmt);
        $jobs = array();

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $jobs[] = array(
                    'job_id' => $row['job_id'],
                    'job_title' => $row['job_title'],
                    'salary' => $row['salary'],
                    'job_description' => $row['job_description'],
                    'location' => $row['location'],
                    'created_at' => date('Y-m-d', strtotime($row['created_at'])),
                    'report_status' => $row['report_status'] ?? 'Pending'
                );
            }
            echo json_encode(['success' => true, 'jobs' => $jobs]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No reported jobs found']);
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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="userdashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar" style="margin-bottom: 0px;">
        <div class="nav-brand">
            <img src="logowithoutbcakground.png" alt="Logo" class="logo">
            <h1>FlexiHire</h1>
        </div>
        
        <div class="nav-right">
            <span class="nav-username"><?php echo htmlspecialchars($display_name); ?></span>
            <div class="profile-container">
                <?php if (!empty($profile_image)): ?>
                    <img src="/mini project/database/profile_picture/<?php echo htmlspecialchars($profile_image); ?>" class="profile-pic" alt="Profile">
                <?php else: ?>
                    <img src="profile.png" class="profile-pic" alt="Profile">
                <?php endif; ?>
                <div class="dropdown-menu">
                    <div class="user-info">
                        <span class="username"><?php echo htmlspecialchars($display_name); ?></span>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="profiles/user/userprofile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-menu">
                <a href="userdashboard.php" class="sidebar-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <!-- <a href="userdashboard.php"><i class="fas fa-list"></i> Job List</a> -->
                <!-- <a href="sidebar/jobgrid/jobgrid.html"><i class="fas fa-th"></i>Job Grid</a> -->
                <a href="applied.php"><i class="fas fa-paper-plane"></i> Applied Job</a>
                <a href="bookmark.php" class="active"><i class="fas fa-bookmark"></i> Bookmarks</a>
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
                    <button class="reset-filters-btn" onclick="resetFilters()">
                        <i class="fas fa-undo"></i> Reset Filters
                    </button>
                </div>
            </div>

            <!-- Job Listings -->
            <div class="job-listings">
                <?php
                // Debug statements
                error_log("Session ID: " . print_r($_SESSION, true));
                
                // Use either id or user_id from session
                $user_id = isset($_SESSION['id']) ? $_SESSION['id'] : $_SESSION['user_id'];
                
                // Fetch reported jobs with error checking
                $bookmark_sql = "SELECT j.*, r.status as report_status 
                                FROM tbl_jobs j 
                                INNER JOIN tbl_reports r ON j.job_id = r.reported_job_id 
                                WHERE r.reporter_id = ?";
                
                $stmt = mysqli_prepare($conn, $bookmark_sql);
                if (!$stmt) {
                    error_log("Prepare failed: " . mysqli_error($conn));
                    echo '<div class="no-jobs">Error preparing query</div>';
                    exit;
                }

                mysqli_stmt_bind_param($stmt, "i", $user_id);
                if (!mysqli_stmt_execute($stmt)) {
                    error_log("Execute failed: " . mysqli_stmt_error($stmt));
                    echo '<div class="no-jobs">Error executing query</div>';
                    exit;
                }

                $result = mysqli_stmt_get_result($stmt);
                error_log("Number of rows found: " . mysqli_num_rows($result));

                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        // Debug row data
                        error_log("Job data: " . print_r($row, true));
                        
                        echo '<div class="job-card" data-job-id="' . $row['job_id'] . '">';
                        echo '<div class="job-header">';
                        echo '<h3 class="job_title">' . htmlspecialchars($row['job_title']) . '</h3>';
                        echo '<span class="salary">₹' . htmlspecialchars($row['salary']) . '</span>';
                        echo '</div>';
                        echo '<div class="job-body">';
                        echo '<p class="description">' . htmlspecialchars($row['job_description']) . '</p>';
                        echo '<p class="location"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($row['location']) . '</p>';
                        echo '<p class="date"><i class="fas fa-calendar-alt"></i> Posted: ' . date('Y-m-d', strtotime($row['created_at'])) . '</p>';
                        echo '</div>';
                        echo '<div class="job-footer">';
                        // Update status button to use report_status
                        $status_class = strtolower($row['report_status'] ?? 'pending'); // Default to pending if null
                        echo '<button class="status-btn ' . $status_class . '">';
                        echo '<i class="fas fa-clock"></i> Status: ' . htmlspecialchars($row['report_status'] ?? 'Pending');
                        echo '</button>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="no-jobs">No reported jobs found.</div>';
                }
                ?>
            </div>
        </main>
    </div>
    <script src="shared-search.js"></script>
    <script src="reportedjobs.js"></script>
    <!-- Add these styles -->
    <style>
    .status-btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        border: none;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: default;
    }

    .status-btn i {
        font-size: 16px;
    }

    /* Status-specific styles */
    .status-btn.pending {
        background-color: #FFF3E0;
        color: #FF9800;
    }

    .status-btn.reviewing {
        background-color: #E3F2FD;
        color: #2196F3;
    }

    .status-btn.resolved {
        background-color: #E8F5E9;
        color: #4CAF50;
    }

    .status-btn.rejected {
        background-color: #FFEBEE;
        color: #F44336;
    }

    /* Animation for pending status */
    .status-btn.pending i {
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
        100% {
            opacity: 1;
        }
    }

    .job-footer {
        display: flex;
        justify-content: flex-end;
        padding-top: 15px;
        border-top: 1px solid #eee;
        margin-top: 15px;
    }

    /* Hover effect */
    .status-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    </style>
    <!-- Add search functionality styles -->
    <style>
        /* No Jobs Message Styles */
        .no-jobs {
            text-align: center;
            padding: 40px 20px;
            background: #fff;
            border-radius: 12px;
            margin: 20px auto;
            max-width: 1400px;
            width: 90%;
        }

        .no-jobs-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            padding: 20px;
        }

        .search-icon {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }

        .search-icon i {
            font-size: 32px;
            color: #8B5CF6;
        }

        .no-jobs h2 {
            color: #1F2937;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .no-jobs p {
            color: #6B7280;
            margin: 0;
            font-size: 16px;
        }

        .reset-filters-btn {
            background: #FF4757;
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(255, 71, 87, 0.2);
            width: 100%;
            min-height: 42px;
        }

        .reset-filters-btn:hover {
            background: #FF6B81;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(255, 71, 87, 0.3);
        }

        .reset-filters-btn i {
            font-size: 14px;
        }

        /* Search Container Styles */
        .search-container {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            width: 100%;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(186, 166, 227, 0.3);
            background: var(--white);
            border-radius: 6px;
            margin-bottom: 1rem;
            width: 100%;
        }

        .search-box i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .search-box input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 1rem;
            width: 100%;
        }

        .filter-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            width: 100%;
            align-items: end;
        }

        .filter-box input {
            padding: 0.75rem;
            border: 1px solid rgba(186, 166, 227, 0.3);
            background: var(--white);
            border-radius: 6px;
            outline: none;
            width: 100%;
            font-size: 0.95rem;
        }

        .salary-range {
            display: flex;
            gap: 0.5rem;
            width: 100%;
        }

        .salary-range input {
            flex: 1;
        }

        /* CSS Variables */
        :root {
            --primary-color: #8A6CE0;
            --secondary-color: #BAA6E3;
            --background-gradient: linear-gradient(135deg, #BAA6E3 0%, #E3D8FC 50%, #FFD6E3 100%);
            --text-color: #2D1F54;
            --gray-light: #E3D8FC;
            --white: #ffffff;
            --shadow: 0 8px 16px rgba(186, 166, 227, 0.2);
            --card-bg: rgba(255, 255, 255, 0.9);
        }

        @media (max-width: 768px) {
            .filter-box {
                grid-template-columns: 1fr;
            }

            .salary-range {
                flex-direction: row;
            }

            .search-container {
                padding: 1rem;
            }
        }
    </style>
    <style>
    .nav-right {
        display: flex;
        align-items: center;
        gap: 1rem;
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
    </style>
</body>
</html>