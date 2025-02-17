<?php
    session_start();
    include '../../../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    // retriving data from session
    $user_id=$_SESSION['user_id'];
    $sql = "SELECT u.first_name, u.last_name, l.email, u.profile_image, u.address, u.phone_number, u.username 
        FROM tbl_login l
        JOIN tbl_user u ON l.user_id = u.user_id
        WHERE l.login_id = '$user_id'";
    $result=mysqli_query($conn,$sql);
    $user_data = mysqli_fetch_assoc($result);

    // storing data to variables
    $username = $user_data['first_name'];
    $email=$user_data['email'];
    $new_username=$user_data['username'];

    // Get the profile image path for web access
    $profile_image_path = !empty($user_data['profile_image']) 
        ? '/mini project/database/profile_picture/' . $user_data['profile_image']
        : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Edit</title>
    <link rel="stylesheet" href="userprofile.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <a href="../../userdashboard.php" id="back"><span class="arrow-circle">←</span>&nbsp;&nbsp;Back</a>
            
            <div class="user-menu">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='24' height='24'%3E%3Cpath fill='%23666' d='M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.07.63-.07.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z'/%3E%3C/svg%3E" alt="Settings" class="settings-icon">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='24' height='24'%3E%3Cpath fill='%23666' d='M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z'/%3E%3C/svg%3E" alt="Notifications" class="notification-icon">
                <div class="user-profile">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='32' height='32'%3E%3Cpath fill='%23666' d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z'/%3E%3C/svg%3E" alt="User" class="user-avatar">
                    <span>
                        <?php echo isset($new_username) && !empty($new_username) ? $new_username : $username; ?>
                    </span>
                </div>
            </div>
        </header>

        <section class="profile-section">
            <h2 class="profile-title">Profile</h2>
            
            <div class="profile-content">
                <div class="profile-image-section">
                    <div class="profile-image">
                        <?php if (!empty($user_data['profile_image'])): ?>
                            <img src="<?php echo $profile_image_path; ?>" alt="Profile Picture" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='24' height='24'%3E%3Cpath fill='%23666' d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E" alt="Profile Picture">
                        <?php endif; ?>
                        <div class="camera-icon">
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='20' height='20'%3E%3Cpath fill='%234355FF' d='M12 15.2c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z'/%3E%3Cpath fill='%234355FF' d='M9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9zm3 15c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z'/%3E%3C/svg%3E" alt="Camera">
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button class="logo-btn">LOGO</button>
                    </div>
                </div>
                <!-- form -->
                <form  method="POST">
                    <div class="profile-details">
                        <div class="detail-item">
                                <div class="detail-label">Username:</div>
                                    <input type="text" name="name" class="detail-value" value="<?php echo isset($new_username) && !empty($new_username) ? $new_username : $username; ?>" readonly>
                                </div>

                            <div class="detail-item">
                                <div class="detail-label">Email:</div>
                                    <input type="email" name="email" class="detail-value" value="<?php echo$email;?>" readonly>
                                </div>

                            <div class="detail-item">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <div class="phone-input">
                                        <span>+91</span>
                                        <input type="tel" name="phone" id="phone" placeholder="Enter phone number" readonly>
                                    </div>
                                </div>

                            <div class="detail-item">
                            <div class="detail-label">Address:</div>
                                <input type="text" name="address" class="detail-value" placeholder="Add your address" readonly>
                            </div>

                            <button type="submit" class="edit-btn">
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='16' height='16'%3E%3Cpath fill='white' d='M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z'/%3E%3C/svg%3E" alt="Edit">
                                <a href="user_profile_update.php">EDIT PROFILE</a>
                            </button>
                        </div>
                </form>

            </div>
        </section>
    </div>
</body>
</html>