<?php
session_start();
include '../../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/loginvalidation.php");
    exit();
}

// Get user data with proper error handling
$user_id = $_SESSION['user_id'];

// Validate user_id
if (!$user_id || !is_numeric($user_id)) {
    error_log("Invalid or missing user_id in session. Session data: " . print_r($_SESSION, true));
    header("Location: ../../login/loginvalidation.php");
    exit();
}

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
    $display_name = !empty($user_data['username']) ? $user_data['username'] : 
                   $user_data['first_name'] . " " . $user_data['last_name'];
    $profile_image = $user_data['profile_image'];
} else {
    error_log("User not found in database for ID: $user_id");
    header("Location: ../../login/loginvalidation.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-brand">
            <img src="../../userdashboard/logowithoutbcakground.png" alt="Logo" class="logo">
            <h1>FlexiHire</h1>
        </div>
        
        <div class="nav-right">
            <div class="profile-info">
                <span class="nav-username"><?php echo htmlspecialchars($display_name); ?></span>
                <div class="profile-container">
                    <?php if (!empty($profile_image)): ?>
                        <img src="/mini%20project/database/profile_picture/<?php echo htmlspecialchars($profile_image); ?>" class="profile-pic" alt="Profile">
                    <?php else: ?>
                        <img src="../../userdashboard/profile.png" class="profile-pic" alt="Profile">
                    <?php endif; ?>
                    <div class="dropdown-menu">
                        <a href="userprofile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="../../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <style>
    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 2rem;
        background-color: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .nav-brand {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .logo {
        height: 40px;
        width: auto;
    }

    .nav-brand h1 {
        margin: 0;
        color: #333;
        font-size: 1.5rem;
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
        border: none;
        outline: none;
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

    <!-- Rest of your profile page content goes here -->
</body>
</html> 