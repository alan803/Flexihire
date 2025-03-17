<?php
    session_start();
    //checking if user id set or not
    if (!isset($_SESSION['user_id'])) 
    {
        header("Location: ../../../login/login.php");
        exit();
    }
    include '../../../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    // retriving data from session
    $user_id=$_SESSION['user_id'];
    $sql = "SELECT u.username, u.last_name, l.email, u.profile_image, u.address, u.phone_number, u.username 
        FROM tbl_login l
        JOIN tbl_user u ON l.user_id = u.user_id
        WHERE l.login_id = '$user_id'";
    $result=mysqli_query($conn,$sql);
    $user_data = mysqli_fetch_assoc($result);

    // storing data to variables
    $username = $user_data['username'];
    $email=$user_data['email'];
    $new_username=$user_data['username'];
    $phone=$user_data['phone_number'];
    $address=$user_data['address'];

    // Get the profile image path for web access
    $profile_image_path = !empty($user_data['profile_image']) 
        ? '/mini project/database/profile_picture/' . $user_data['profile_image']
        : '';

    // At the top of the file after session_start()
    require_once '../../../database/connectdatabase.php';

    // Get the current user's ID from session
    $user_id = $_SESSION['user_id'];

    // Fetch the latest user data from database
    $sql = "SELECT * FROM tbl_user WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Edit</title>
    <link rel="stylesheet" href="userprofile.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-brand">
            <img src="../../logowithoutbcakground.png" alt="Logo" class="logo">
            <h1>FlexiHire</h1>
        </div>
        
        <div class="nav-right">
            <div class="profile-container">
                <?php if (!empty($user_data['profile_image'])): ?>
                    <img src="<?php echo $profile_image_path; ?>" class="profile-pic" alt="Profile">
                <?php else: ?>
                    <img src="profile.png" class="profile-pic" alt="Profile">
                <?php endif; ?>
                <div class="dropdown-menu">
                    <div class="user-info">
                        <span class="username"><?php echo htmlspecialchars($user['first_name']); ?></span>
                        <span class="email"><?php echo $email; ?></span>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="userprofile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../../../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-menu">
                <a href="../../userdashboard.php" class="sidebar-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <!-- <a href="../../userdashboard.php"><i class="fas fa-list"></i> Job List</a> -->
                <!-- <a href="../../sidebar/jobgrid/jobgrid.html"><i class="fas fa-th"></i> Job Grid</a> -->
                <a href="../../applied.php"><i class="fas fa-paper-plane"></i> Applied Job</a>
                <!-- <a href="../../sidebar/jobdetails/jobdetails.html"><i class="fas fa-info-circle"></i> Job Details</a> -->
                <a href="../../bookmark.php"><i class="fas fa-bookmark"></i> Bookmarks</a>
                <a href="../../sidebar/appointment/appointment.html"><i class="fas fa-calendar"></i> Appointments</a>
                <a href="userprofile.php"><i class="fas fa-user"></i> Profile</a>
            </div>
            <div class="logout-container">
                <div class="sidebar-divider"></div>
                <a href="../../../login/logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
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

                    <form method="POST">
                        <div class="profile-details">
                            <div class="detail-item">
                                <div class="detail-label">Username:</div>
                                <input type="text" name="name" class="detail-value" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
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
                                        <input type="tel" name="phone" id="phone" placeholder="Enter phone number" value="<?php echo $phone; ?>" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-item">
                                <div class="detail-label">Address:</div>
                                <input type="text" name="address" class="detail-value" placeholder="Add your address" value="<?php echo $address; ?>" readonly>
                            </div>

                            <button type="submit" class="edit-btn">
                                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='16' height='16'%3E%3Cpath fill='white' d='M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z'/%3E%3C/svg%3E" alt="Edit">
                                <a href="user_profile_update.php">EDIT PROFILE</a>
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profilePic = document.querySelector('.profile-pic');
            const dropdownMenu = document.querySelector('.dropdown-menu');

            profilePic.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!dropdownMenu.contains(e.target)) {
                    dropdownMenu.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>