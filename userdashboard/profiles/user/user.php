<?php
    session_start();
    include '../../../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM tbl_login WHERE id='$user_id'"; // Changed from tbl_user to tbl_login

    // Check if query was successful
    $result = mysqli_query($conn, $sql);
    $user_data=mysqli_fetch_assoc($result);
    $username=$user_data['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="user.css">
</head>
<body>
    <div class="sidebar">
        <ul>
        <li><a href="../../../userdashboard/userdashboard.php">Home</a></li>
            <li><a href="settings.php">Settings</a></li>
            <li>
                <div class="footer">
                    <img src="../../profile.png" id="profile">
                    <p id="username"><?php echo '<br>'.$username; ?></p>
                    <a href="../../../login/logout.php"><img src="../logout1.png" id="logout-image"></a>
                </div>
            </li>
        </ul>
    </div>
    <div class="right-side">
        <div class="right-side-one-block">
            <span>
                <img src="../../profile.png" id="profile1">
            </span>
        </div>
        <div class="right-side-one-block1">
            <img src="../background.webp" id="profilebig">
        </div>
    </div>
</body>
</html>