<?php
    session_start();

    // Authentication check
    if (!isset($_SESSION['admin_id']))
    {
        header('Location: ../login/loginvalidation.php');
        exit();
    }

    // Database connection
    require_once '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname) or die("Database selection failed: " . mysqli_error($conn));

    // Get user_id from URL parameter
    if (isset($_GET['user_id'])) 
    {
        $user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
        if ($user_id === false || $user_id === null) {
            header('Location: manage_users.php');
            exit();
        }
        
        // Fetch user details with prepared statement
        $sql = "SELECT u.*, l.email, l.status 
                FROM tbl_user u 
                LEFT JOIN tbl_login l ON u.user_id = l.user_id 
                WHERE u.user_id = ?";
                
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // Fetch jobs user has been hired for
        $hired_jobs_sql = "SELECT j.job_id, j.job_title, j.salary, j.location, j.town, 
                                 e.company_name, s.selected_date
                          FROM tbl_selected s
                          JOIN tbl_jobs j ON s.job_id = j.job_id
                          JOIN tbl_employer e ON j.employer_id = e.employer_id
                          WHERE s.user_id = ?
                          ORDER BY s.selected_date DESC";
        
        $hired_jobs_stmt = mysqli_prepare($conn, $hired_jobs_sql);
        mysqli_stmt_bind_param($hired_jobs_stmt, "i", $user_id);
        mysqli_stmt_execute($hired_jobs_stmt);
        $hired_jobs_result = mysqli_stmt_get_result($hired_jobs_stmt);
        mysqli_stmt_close($hired_jobs_stmt);
    } 
    else 
    {
        header('Location: manage_users.php');
        exit();
    }

    mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>View User - AutoRecruits Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="view_user.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-section">
                <h1>FlexiHire</h1>
                <div class="admin-badge">
                    <i class="fas fa-user-shield"></i>
                    <span>Admin Dashboard</span>
                </div>
            </div>
            <nav class="nav-menu">
                <a href="admindashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="manage_users.php" class="nav-item active">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <a href="manage_employers.php" class="nav-item">
                    <i class="fas fa-building"></i>
                    <span>Manage Employers</span>
                </a>
                <a href="manage_jobs.php" class="nav-item">
                    <i class="fas fa-briefcase"></i>
                    <span>Manage Jobs</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="../login/logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>User Details</h1>
                <a href="manage_users.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            </div>

            <?php if ($user): ?>
                <div class="user-profile">
                    <div class="profile-header">
                        <img src="../database/profile_picture/<?= htmlspecialchars($user['profile_image']) ?>" 
                             alt="Profile" 
                             class="profile-image"
                             onerror="this.src='../assets/images/default-avatar.png'">
                        <div class="profile-info">
                            <h2><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h2>
                            <p class="email"><?= htmlspecialchars($user['email']) ?></p>
                            <span class="status-badge <?= strtolower($user['status']) === 'active' ? 'active' : 'inactive' ?>">
                                <?= ucfirst($user['status']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="user-details">
                        <div class="detail-card">
                            <h3>Phone Number</h3>
                            <p><?= htmlspecialchars($user['phone_number'] ?? 'Not provided') ?></p>
                        </div>
                        <div class="detail-card">
                            <h3>Address</h3>
                            <p><?= htmlspecialchars($user['address'] ?? 'Not provided') ?></p>
                        </div>
                        <div class="detail-card">
                            <h3>Date of Birth</h3>
                            <p><?= htmlspecialchars($user['dob'] ?? 'Not provided') ?></p>
                        </div>
                        <div class="detail-card">
                            <h3>Joined Date</h3>
                            <p><?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                        </div>
                    </div>

                    <!-- Hired Jobs Section -->
                    <div class="hired-jobs-section">
                        <div class="section-header">
                            <h3>Hired Jobs</h3>
                            <span class="job-count"><?= mysqli_num_rows($hired_jobs_result) ?> Position<?= mysqli_num_rows($hired_jobs_result) != 1 ? 's' : '' ?></span>
                        </div>
                        <?php if (mysqli_num_rows($hired_jobs_result) > 0): ?>
                            <div class="hired-jobs-grid">
                                <?php while ($job = mysqli_fetch_assoc($hired_jobs_result)): ?>
                                    <div class="hired-job-card">
                                        <div class="job-header">
                                            <div class="company-logo">
                                                <i class="fas fa-building"></i>
                                            </div>
                                            <div class="job-title-section">
                                                <h4><?= htmlspecialchars($job['job_title']) ?></h4>
                                                <span class="company-name"><?= htmlspecialchars($job['company_name']) ?></span>
                                            </div>
                                        </div>
                                        <div class="job-details">
                                            <div class="detail-row">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?= htmlspecialchars($job['location'] . ', ' . $job['town']) ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <i class="fas fa-money-bill-wave"></i>
                                                <span>₹<?= htmlspecialchars($job['salary']) ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <i class="fas fa-calendar-check"></i>
                                                <span>Hired on <?= date('F j, Y', strtotime($job['selected_date'])) ?></span>
                                            </div>
                                        </div>
                                        <div class="job-footer">
                                            <span class="status-badge hired">Hired</span>
                                            <a href="view_job.php?job_id=<?= htmlspecialchars($job['job_id']) ?>" class="view-job-btn">
                                                View Details <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-jobs-message">
                                <i class="fas fa-briefcase"></i>
                                <p>This user has not been hired for any jobs yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="error-message">User not found.</div>
            <?php endif; ?>
        </div>
    </div>

    <script src="view_user.js" defer></script>
</body>
</html>