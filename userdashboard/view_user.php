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
                </div>
            <?php else: ?>
                <div class="error-message">User not found.</div>
            <?php endif; ?>
        </div>
    </div>

    <script src="view_user.js" defer></script>
</body>
</html>