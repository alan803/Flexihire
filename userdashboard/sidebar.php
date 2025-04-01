<?php
// session_start();
// if (!isset($_SESSION['employer_id'])) {
//     header("Location: ../login/loginvalidation.php");
//     exit();
// }

include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

$employer_id = $_SESSION['employer_id'];
$sql = "SELECT u.company_name, l.email, u.profile_image 
        FROM tbl_login AS l
        JOIN tbl_employer AS u ON l.employer_id = u.employer_id
        WHERE u.employer_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $employer_data = mysqli_fetch_array($result);
    $email = $employer_data['email'];
    $username = $employer_data['company_name'];
    $profile_image = $employer_data['profile_image'];
} else {
    error_log("Database error or user not found for ID: $employer_id");
    session_destroy();
    header("Location: ../login/logout.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f4f7fa;
            --text-color: #333;
            --light-text: #666;
            --danger-color: #e74c3c;
            --sidebar-width: 280px;
            --sidebar-mobile-width: 250px;
            --transition-speed: 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--secondary-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: fixed;
            height: 100%;
            overflow-y: auto;
            z-index: 1000;
        }

        .logo-container {
            text-align: center;
            padding: 20px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(138, 108, 224, 0.1);
        }

        .logo-container img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 4px 15px rgba(138, 108, 224, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .logo-container img:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(138, 108, 224, 0.2);
        }

        .company-info {
            text-align: center;
            margin: 20px 0;
            padding: 0 10px;
        }

        .company-info span:first-child {
            display: block;
            font-size: 18px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 5px;
        }

        .company-info span:last-child {
            font-size: 13px;
            color: var(--light-text);
        }

        .nav-menu {
            margin: 30px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin: 8px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background-color: var(--primary-color);
            transform: scaleY(0);
            transition: transform 0.3s ease;
            border-radius: 0 2px 2px 0;
        }

        .nav-item:hover {
            background: rgba(74, 144, 226, 0.08);
        }

        .nav-item.active {
            background: rgba(74, 144, 226, 0.1);
        }

        .nav-item.active:hover {
            background: rgba(74, 144, 226, 0.15);
            box-shadow: 0 2px 8px rgba(74, 144, 226, 0.15);
        }

        .nav-item.active::before {
            transform: scaleY(1);
        }

        .nav-item i {
            margin-right: 12px;
            color: #4a90e2;
            font-size: 18px;
            width: 24px;
            text-align: center;
            transition: all 0.3s ease;
            opacity: 0.8;
        }

        .nav-item a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            transition: all 0.3s ease;
            flex-grow: 1;
        }

        .nav-item:hover i,
        .nav-item.active i {
            color: #4a90e2;
            opacity: 1;
            transform: scale(1.1);
        }

        .nav-item.active a {
            color: #4a90e2;
        }

        .nav-item:hover a {
            transform: translateX(3px);
        }

        .nav-item.active:hover a {
            transform: translateX(5px);
            letter-spacing: 0.3px;
        }

        .settings-section .nav-item:last-child::before {
            background-color: var(--danger-color);
        }

        .settings-section .nav-item:last-child:hover {
            background: rgba(231, 76, 60, 0.08);
        }

        .settings-section .nav-item:last-child.active {
            background: rgba(231, 76, 60, 0.1);
        }

        .settings-section .nav-item:last-child.active:hover {
            background: rgba(231, 76, 60, 0.15);
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.15);
        }

        .nav-item.active {
            box-shadow: inset 0 0 20px rgba(74, 144, 226, 0.05);
        }

        .settings-section {
            position: absolute;
            bottom: 20px;
            width: calc(100% - 40px);
            padding-top: 15px;
            border-top: 1px solid rgba(74, 144, 226, 0.1);
        }

        .settings-section .nav-item:last-child {
            color: var(--danger-color);
        }

        .settings-section .nav-item:last-child i {
            color: var(--danger-color);
        }

        .settings-section .nav-item:hover:last-child {
            background: rgba(231, 76, 60, 0.1);
        }

        @media screen and (max-width: 1200px) {
            .sidebar {
                width: 240px;
            }

            .logo-container img {
                width: 80px;
                height: 80px;
            }

            .company-info span:first-child {
                font-size: 16px;
            }

            .company-info span:last-child {
                font-size: 12px;
            }

            .nav-item {
                padding: 10px 12px;
            }

            .nav-item i {
                font-size: 16px;
            }
        }

        @media screen and (max-width: 992px) {
            .sidebar {
                width: 220px;
            }

            .nav-item {
                padding: 8px 10px;
                margin: 6px 0;
            }

            .settings-section {
                bottom: 15px;
                width: calc(100% - 30px);
            }
        }

        @media screen and (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-mobile-width);
                transition: transform var(--transition-speed);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .logo-container {
                padding: 15px 0;
            }

            .logo-container img {
                width: 70px;
                height: 70px;
            }

            .nav-menu {
                margin: 20px 0;
            }

            .nav-item {
                padding: 10px;
                margin: 5px 0;
            }

            .nav-item i {
                margin-right: 10px;
            }

            .company-info {
                margin: 15px 0;
            }

            .settings-section {
                position: relative;
                bottom: auto;
                margin-top: 30px;
            }
        }

        @media screen and (max-width: 480px) {
            .sidebar {
                width: 85%;
                max-width: var(--sidebar-mobile-width);
            }

            .logo-container img {
                width: 60px;
                height: 60px;
            }

            .company-info span:first-child {
                font-size: 15px;
            }

            .company-info span:last-child {
                font-size: 11px;
            }

            .nav-item {
                padding: 8px;
            }

            .nav-item i {
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="logo-container">
        <?php if(!empty($profile_image)): ?>
            <img src="<?php echo htmlspecialchars($profile_image); ?>" 
                 alt="<?php echo htmlspecialchars($username); ?>"
                 onerror="this.src='../assets/images/company-logo.png';">
        <?php else: ?>
            <img src="../assets/images/company-logo.png" alt="AutoRecruits.in">
        <?php endif; ?>
    </div>
    <div class="company-info">
        <span><?php echo htmlspecialchars($username); ?></span>
        <span><?php echo htmlspecialchars($email); ?></span>
    </div>
    <nav class="nav-menu">
        <div class="nav-item active"><i class="fas fa-th-large"></i><a href="employerdashboard.php">Dashboard</a></div>
        <div class="nav-item"><i class="fas fa-plus-circle"></i><a href="postjob.php">Post a Job</a></div>
        <div class="nav-item"><i class="fas fa-briefcase"></i><a href="myjoblist.php">My Jobs</a></div>
        <div class="nav-item"><i class="fas fa-users"></i><a href="applicants.php">Applicants</a></div>
        <div class="nav-item"><i class="fas fa-calendar-check"></i><a href="interviews.php">Interviews</a></div>
    </nav>
    <div class="settings-section">
        <div class="nav-item"><i class="fas fa-user-cog"></i><a href="employer_profile.php">My Profile</a></div>
        <div class="nav-item"><i class="fas fa-sign-out-alt"></i><a href="../login/logout.php">Logout</a></div>
    </div>
</div>

    <script src="sidebar.js"></script>
</body>
</html>