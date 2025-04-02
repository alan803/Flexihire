<?php
    session_start();
    //checking if user id set or not
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../../../login/login.php");
        exit();
    }

    include '../../../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // retriving data from session
    $user_id = $_SESSION['user_id'];
    
    // Debug session
    error_log("Session user_id: " . $user_id);
    
    // First, get the email from tbl_login directly
    $sql = "SELECT email FROM tbl_login WHERE  user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $email_result = mysqli_stmt_get_result($stmt);
    $email_data = mysqli_fetch_assoc($email_result);
    
    // Then get user details
    $sql = "SELECT * FROM tbl_user WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($result);

    // Debug information
    error_log("Email from tbl_login: " . ($email_data['email'] ?? 'No email found'));
    error_log("User data: " . print_r($user_data, true));

    // Initialize variables with empty strings instead of showing warnings
    $username = !empty($user_data['username']) ? $user_data['username'] : '';
    $email = !empty($email_data['email']) ? $email_data['email'] : '';
    $phone = !empty($user_data['phone_number']) ? $user_data['phone_number'] : '';
    $address = !empty($user_data['address']) ? $user_data['address'] : '';
    $new_username = $username;

    // Get the profile image path
    $profile_image_path = !empty($user_data['profile_image']) 
        ? '/mini project/database/profile_picture/' . $user_data['profile_image']
        : 'profile.png';

    // Store user data for later use
    $user = $user_data;
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
    <style>
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

        /* Toast Notification Styles */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(186, 166, 227, 0.2);
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(186, 166, 227, 0.2);
            padding: 1rem;
            display: flex;
            align-items: center;
            z-index: 9999;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            min-width: 300px;
            max-width: 400px;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .toast-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toast-icon i {
            font-size: 1.25rem;
        }

        .toast.success .toast-icon i {
            color: #10B981;
        }

        .toast.error .toast-icon i {
            color: #EF4444;
        }

        .toast-message-container {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #2D1F54;
        }

        #toast-message {
            color: #2D1F54;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <div class="toast-content">
            <div class="toast-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="toast-message-container">
                <div class="toast-title">Success</div>
                <div id="toast-message">Profile updated successfully!</div>
            </div>
        </div>
    </div>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-brand">
        
        <img src="/mini%20project/userdashboard/logowithoutbcakground.png" alt="Logo" class="logo">
            <h1>FlexiHire</h1>
        </div>
        
        <div class="nav-right">
            <div class="profile-info">
                <span class="nav-username">
                    <?php 
                        // Display username if it exists, otherwise show first name
                        if (!empty($user['username'])) {
                            echo htmlspecialchars($user['username']);
                        } elseif (!empty($user['first_name'])) {
                            echo htmlspecialchars($user['first_name']);
                        } else {
                            echo "User";
                        }
                    ?>
                </span>
                <div class="profile-container">
                    <?php if (!empty($user_data['profile_image'])): ?>
                        <img src="<?php echo $profile_image_path; ?>" class="profile-pic" alt="Profile">
                    <?php else: ?>
                        <img src="../../userdashboard/profile.png" class="profile-pic" alt="Profile">
                    <?php endif; ?>
                    <!-- <div class="dropdown-menu">
                        <a href="userprofile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="../../../login/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div> -->
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
                <a href="../../appointment.php"><i class="fas fa-calendar"></i> Appointments</a>
                <a href="../../reportedjobs.php"><i class="fas fa-flag"></i> Reported Jobs</a>
                <a href="../../reviews.php"><i class="fas fa-star"></i> Reviews</a>
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

            // Check for URL parameters to show success message
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === 'true') {
                showToast('Profile updated successfully!');
            }
        });

        // Toast notification function
        function showToast(message) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            
            toastMessage.textContent = message;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>