<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM tbl_login WHERE id='$user_id'"; // Changed from tbl_user to tbl_login

    // Check if query was successful
    $result = mysqli_query($conn, $sql);
    $user_data=mysqli_fetch_assoc($result);
    $username=$user_data['username'];
    $email=$user_data['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="user.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <div class="logo-icon">S</div>
                <div>slothui</div>
            </div>
            
            <nav>
                <div class="nav-item">
                    <span>Home</span>
                    <span class="badge">10</span>
                </div>
                <div class="nav-item">
                    <span>Settings</span>
                </div>
                <div class="footer">
                    <img src="profile.png" id="profile1">
                    <p id="username1"><?php echo '<br>'.$username; ?></p>
                    <a href="../login/logout.php"><img src="logout1.png" id="logout-image"></a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="profile-header">
                <div class="background-image">
                    <img src="profile.png" alt="Background">
                </div>
                <img src="profile.png" alt="Profile" class="profile-image">
                <h1 id="username"><?php echo '<br>'.$username; ?><h1>
                <p id="email"><?php echo '<br>'.$email; ?></p>
            </div>

            <div class="form-container">
                <h2>Personal Info</h2>
                <p style="margin-bottom: 20px; color: #666;">You can change your personal information settings here.</p>

                <form>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" placeholder="Username">
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="Email">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" placeholder="Phone number">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <input type="text" name="status" placeholder="status">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Change Avatar</label>
                        <div class="upload-section">
                            <p>Click here to upload your file or drag.</p>
                            <p style="color: #666; font-size: 14px;">Supported Format: SVG, JPG, PNG (10mb each)</p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>