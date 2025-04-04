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
            height: calc(100% - 60px);
            top: 60px;
            left: 0;
            overflow-y: auto;
            z-index: 999;
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
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            margin: 20px 0;
            height: 59.4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .company-name {
            font-family: 'Poppins', sans-serif;
            font-size: 20px;
            font-weight: 600;
            color: #000000;
            line-height: 1.3;
            letter-spacing: 0.3px;
            text-transform: capitalize;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin: 0;
            padding: 0 10px;
        }

        .nav-menu {
            margin-top: 10px;
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

        .navbar {
            background: white;
            padding: 10px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1000;
            height: 60px;
            display: flex;
            align-items: center;
        }

        .nav-content {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-logo {
            height: 40px;
            width: auto;
        }

        .brand-name {
            font-family: 'Poppins', sans-serif;
            font-size: 24px;
            font-weight: 600;
            color: #4a90e2;
            margin-left: 5px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .company-name {
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 500;
            color: #333;
            margin: 0;
        }

        .profile-container {
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
            border-radius: 4px;
            width: 150px;
            margin-top: 5px;
            padding: 8px 0;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .dropdown-menu a i {
            margin-right: 8px;
            width: 16px;
            color: #666;
        }

        .dropdown-menu a:hover {
            background-color: #f5f5f5;
        }

        /* Adjust main content padding to account for navbar */
        .dashboard-container {
            padding-top: 80px;
        }
    </style>
</head>
<body>
<div class="navbar">
    <div class="nav-content">
        <div class="nav-left">
            <img src="logowithoutbcakground.png" alt="Logo" class="navbar-logo">
            <span class="brand-name">FlexiHire</span>
        </div>
        <div class="nav-right">
            <span class="company-name"><?php echo htmlspecialchars($username); ?></span>
            <div class="profile-container" id="profileContainer">
                <?php if(!empty($profile_image) && file_exists($profile_image)): ?>
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" class="profile-pic" alt="Profile">
                <?php else: ?>
                    <img src="../userdashboard/employer_pf/deafult.webp" class="profile-pic" alt="Default Profile">
                <?php endif; ?>
                <!-- <div class="dropdown-menu" id="dropdownMenu">
                    <a href="employer_profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div> -->
            </div>
        </div>
    </div>
</div>

<div class="sidebar">
    <nav class="nav-menu">
        <div class="nav-item active"><i class="fas fa-th-large"></i><a href="employerdashboard.php">Dashboard</a></div>
        <div class="nav-item"><i class="fas fa-plus-circle"></i><a href="postjob.php">Post a Job</a></div>
        <div class="nav-item"><i class="fas fa-briefcase"></i><a href="myjoblist.php">My Jobs</a></div>
        <div class="nav-item"><i class="fas fa-users"></i><a href="applicants.php">Applicants</a></div>
        <div class="nav-item"><i class="fas fa-calendar-check"></i><a href="interviews.php">Interviews</a></div>
        <div class="nav-item"><i class="fas fa-user-cog"></i><a href="employer_profile.php">My Profile</a></div>
        <div class="nav-item"><i class="fas fa-sign-out-alt"></i><a href="../login/logout.php">Logout</a></div>
    </nav>
    <div class="settings-section">
        
    </div>      
</div>

    <script src="sidebar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileContainer = document.getElementById('profileContainer');
            const dropdownMenu = document.getElementById('dropdownMenu');

            if (profileContainer && dropdownMenu) {
                profileContainer.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', function(e) {
                    if (!profileContainer.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });
            }
        });
    </script>
</body>
</html>