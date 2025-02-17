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
                <div class="user-profile">
                    <?php if (!empty($user_data['profile_image'])): ?>
                        <img src="<?php echo $profile_image_path; ?>" alt="User" class="user-avatar" style="width: 32px; height: 32px; object-fit: cover;">
                    <?php else: ?>
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='32' height='32'%3E%3Cpath fill='%23666' d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z'/%3E%3C/svg%3E" alt="User" class="user-avatar">
                    <?php endif; ?>
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