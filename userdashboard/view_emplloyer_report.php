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
    if (isset($_GET['employer_id'])) 
    {
        $employer_id = filter_input(INPUT_GET, 'employer_id', FILTER_VALIDATE_INT);
        if ($employer_id === false || $employer_id === null) 
        {
            header('Location: manage_employers.php');
            exit();
        }
        
        // Updated query to use tbl_selected for hired users count
        $sql = "SELECT e.employer_id, e.company_name, e.phone_number, e.address, 
                       e.establishment_year, e.created_at, e.profile_image, 
                       l.email, l.status,
                       (SELECT COUNT(*) FROM tbl_jobs WHERE employer_id = e.employer_id) as total_jobs,
                       (SELECT COUNT(*) FROM tbl_selected s 
                        INNER JOIN tbl_jobs j ON s.job_id = j.job_id 
                        WHERE j.employer_id = e.employer_id) as hired_users
                FROM tbl_employer e 
                LEFT JOIN tbl_login l ON e.employer_id = l.employer_id 
                WHERE e.employer_id = ?";
                
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $employer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $employer = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    } 
    else 
    {
        header('Location: manage_employers.php');
        exit();
    }

    mysqli_close($conn);

    // // Debug: Print the image details
    // var_dump($employer['profile_image']); // Check what's stored in database

    // // Check if file exists
    // if (!empty($employer['profile_image'])) {
    //     if (file_exists($employer['profile_image'])) {
    //         echo "File exists at: " . $employer['profile_image'];
    //     } else {
    //         echo "File not found at: " . $employer['profile_image'];
    //     }
    // }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>View Employer - FlexiHire Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="view_employer.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar (Matching Admin Dashboard) -->
        <div class="sidebar">
            <div class="logo-section">
                <h1>FlexiHire</h1>
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
                <a href="view_report.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Reports</span>
                </a>
            </div>

            <?php if ($employer): ?>
                <div class="user-profile">
                    <div class="profile-header">
                        <div class="profile-image-container">
                            <img src="<?= htmlspecialchars($employer['profile_image']) ?>" 
                                 alt="Company Profile" 
                                 class="profile-image"
                                 onerror="this.src='../assets/images/default-avatar.png'">
                        </div>
                        <div class="profile-info">
                            <div class="company-details">
                                <h2><?= htmlspecialchars($employer['company_name']) ?></h2>
                                <span class="established">Est. <?= htmlspecialchars($employer['establishment_year']) ?></span>
                            </div>
                            <div class="contact-info">
                                <div class="info-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?= htmlspecialchars($employer['email']) ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?= htmlspecialchars($employer['phone_number'] ?? 'Not provided') ?></span>
                                </div>
                            </div>
                            <div class="status-container">
                                <span class="status-badge <?= strtolower($employer['status']) ?>">
                                    <i class="fas fa-circle"></i>
                                    <?= ucfirst($employer['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="details-grid">
                        <div class="detail-card">
                            <div class="detail-header">
                                <i class="fas fa-map-marker-alt"></i>
                                <h3>Location</h3>
                            </div>
                            <p><?= htmlspecialchars($employer['address'] ?? 'Not provided') ?></p>
                        </div>

                        <div class="detail-card">
                            <div class="detail-header">
                                <i class="fas fa-calendar-alt"></i>
                                <h3>Member Since</h3>
                            </div>
                            <p><?= date('F j, Y', strtotime($employer['created_at'])) ?></p>
                        </div>

                        <div class="detail-card">
                            <div class="detail-header">
                                <i class="fas fa-briefcase"></i>
                                <h3>Total Job Posts</h3>
                            </div>
                            <p class="highlight"><?= htmlspecialchars($employer['total_jobs']) ?></p>
                        </div>

                        <div class="detail-card">
                            <div class="detail-header">
                                <i class="fas fa-user-check"></i>
                                <h3>Hired Users</h3>
                            </div>
                            <p class="highlight"><?= htmlspecialchars($employer['hired_users']) ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Employer not found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="view_employer.js" defer></script>
</body>
</html>