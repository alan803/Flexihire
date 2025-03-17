<?php
    session_start();
    // loged or not
    if (!isset($_SESSION['user_id'])) 
    {
        header("Location: ../../../login/login.php");
        exit();
    }

    include '../../../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);
    $user_id = $_SESSION['user_id'];//getting user id from session

    $sql = "SELECT u.username, u.last_name, l.email, u.profile_image, u.address, u.phone_number, u.user_id 
            FROM tbl_login l
            JOIN tbl_user u ON l.user_id = u.user_id
            WHERE l.login_id = '$user_id'";

    $result = mysqli_query($conn, $sql);

    if (!$result) 
    {
        die("Database query failed: " . mysqli_error($conn)); // checking db 
    }

    $user_data = mysqli_fetch_assoc($result);

    if (!$user_data) 
    {
        die("User data not found.");
    }
    $username = $user_data['username'];
    $email = $user_data['email'];
    $profile_image = $user_data['profile_image'];
    $phone = $user_data['phone_number'];
    $address = $user_data['address'];
    $actual_user_id = $user_data['user_id'];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') 
    {
        $isUpdated = false;

        // Handle profile picture upload if a file was selected
        if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] === 0) {
            $upload_dir = "../../../database/profile_picture/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = $_FILES["profile_image"]["name"];
            $file_tmp = $_FILES["profile_image"]["tmp_name"];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Check if file is an actual image
            $check = getimagesize($file_tmp);
            if($check !== false) {
                // Only allow certain file formats
                $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
                if(in_array($file_extension, $allowed_types)) {
                    // Delete old profile picture if it exists
                    if(!empty($profile_image)) {
                        $old_file = $upload_dir . $profile_image;
                        if(file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }

                    // Generate new filename
                    $new_file_name = "user_" . $actual_user_id . "_" . time() . "." . $file_extension;
                    $upload_path = $upload_dir . $new_file_name;

                    // Move the file to the upload directory
                    if (move_uploaded_file($file_tmp, $upload_path)) 
                    {
                        // Update the database with the new profile picture
                        $update_sql = "UPDATE tbl_user SET profile_image = ? WHERE user_id = ?";
                        $stmt = mysqli_prepare($conn, $update_sql);
                        mysqli_stmt_bind_param($stmt, "si", $new_file_name, $actual_user_id);
                        
                        if(mysqli_stmt_execute($stmt)) {
                            $_SESSION['profile_image'] = $new_file_name;
                            $isUpdated = true;
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }

        // Handle profile data updates
        $new_username = trim($_POST['name']);
        $new_phone = trim($_POST['phone']);
        $new_address = trim($_POST['address']);
        $new_email = trim($_POST['email']);

        // Debug
        error_log("New Username: " . $new_username);
        error_log("Actual User ID: " . $actual_user_id);

        if (!empty($new_username) && $new_username !== $username) {
            // Update the username column instead of first_name
            $update_sql = "UPDATE tbl_user SET username = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "si", $new_username, $actual_user_id);
                
                if(mysqli_stmt_execute($stmt)) {
                    error_log("Username update successful");
                    $_SESSION['username'] = $new_username;
                    $isUpdated = true;
                } else {
                    error_log("Username update failed: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            }
        }

        if (!empty($new_phone) && $new_phone !== $phone) 
        {
            $update_sql = "UPDATE tbl_user SET phone_number = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "si", $new_phone, $actual_user_id);
            if(mysqli_stmt_execute($stmt)) {
                $isUpdated = true;
            }
            mysqli_stmt_close($stmt);
        }

        if (!empty($new_address) && $new_address !== $address) 
        {
            $update_sql = "UPDATE tbl_user SET address = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "si", $new_address, $actual_user_id);
            if(mysqli_stmt_execute($stmt)) {
                $isUpdated = true;
            }
            mysqli_stmt_close($stmt);
        }

        if (!empty($new_email) && $new_email !== $email) 
        {
            // Check if the new email already exists in the database
            $check_email_sql = "SELECT COUNT(*) FROM tbl_login WHERE email = ? AND user_id != ?";
            $stmt = mysqli_prepare($conn, $check_email_sql);
            mysqli_stmt_bind_param($stmt, "si", $new_email, $actual_user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $email_count);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if ($email_count > 0) 
            {
                echo "<script>alert('Email already in use by another user.');</script>";
            } 
            else 
            {
                $update_email_sql = "UPDATE tbl_login SET email = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $update_email_sql);
                mysqli_stmt_bind_param($stmt, "si", $new_email, $actual_user_id);
                if(mysqli_stmt_execute($stmt)) {
                    $isUpdated = true;
                }
                mysqli_stmt_close($stmt);
            }
        }

        // If any value was updated, refresh the page
        if ($isUpdated) 
        {
            // Clear any cached username in session
            unset($_SESSION['display_name']);
            
            header("Location: userprofile.php");
            exit();
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="userprofile.css">
    <link rel="stylesheet" href="user_profile_update.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
                <?php if (!empty($profile_image)): ?>
                    <img src="<?php echo $profile_image_path; ?>" class="profile-pic" alt="Profile">
                <?php else: ?>
                    <img src="profile.png" class="profile-pic" alt="Profile">
                <?php endif; ?>
                <div class="dropdown-menu">
                    <div class="user-info">
                        <span class="username"><?php echo $new_username ?? $username; ?></span>
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
        <!-- Sidebar section -->
        <div class="grid-container">
            <img src="../../grid.png" alt="grid" class="grid" id="grid">
        </div>

        <div class="sidebar" id="sidebar">
            <div class="sidebar-menu">
                <a href="../../userdashboard.php" class="sidebar-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <!-- <a href="../../userdashboard.php"><i class="fas fa-list"></i> Job List</a>
                <a href="../../sidebar/jobgrid/jobgrid.html"><i class="fas fa-th"></i> Job Grid</a> -->
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
        </div>

        <!-- Main Content Area -->
        <main class="main-content">
            <section class="profile-section">
                <h2 class="profile-title">Edit Profile</h2>
                
                <div class="profile-content">
                    <div class="profile-image-section">
                        <div class="profile-image">
                            <img id="previewImage" 
                                src="<?php echo !empty($user_data['profile_image']) ? '../../../database/profile_picture/' . $user_data['profile_image'] : '/api/placeholder/250/250'; ?>" 
                                alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        
                        <div class="upload-buttons">
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display: none;" onchange="previewProfileImage(this)">
                            <button type="button" id="changeProfileBtn" class="profile-btn change-button" onclick="document.getElementById('profile_image').click();">
                                <i class="fas fa-camera"></i>
                                Change
                            </button>
                        </div>
                        <p id="imageError" class="error-message"></p>
                    </div>

                    <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm();" class="profile-details">
                        <input type="file" id="hidden_profile_image" name="profile_image" style="display: none;">
                        
                        <div class="detail-item">
                            <div class="detail-label">Username</div>
                            <input type="text" id="username" name="name" class="detail-value" 
                                   placeholder="Enter your username" 
                                   value="<?php echo isset($new_username) ? htmlspecialchars($new_username) : htmlspecialchars($username); ?>" 
                                   onkeyup="validateUsername()">
                            <p class="error" id="usernameError"></p>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <input type="email" id="email" name="email" class="detail-value" 
                                   placeholder="Enter your email" value="<?php echo $email; ?>"
                                   onkeyup="validateEmail()">
                            <p class="error" id="emailError"></p>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Phone Number</div>
                            <div class="phone-input">
                                <span>+91</span>
                                <input type="tel" name="phone" id="phone" 
                                       placeholder="Enter phone number" value="<?php echo $phone; ?>"
                                       onkeyup="validatePhone()">
                            </div>
                            <p class="error" id="phoneError"></p>
                        </div>

                        <div class="detail-item">
                            <div class="detail-label">Address</div>
                            <input type="text" name="address" id="address" class="detail-value" 
                                   placeholder="Enter your address" value="<?php echo $address; ?>"
                                   onkeyup="validateAddress()">
                            <p class="error" id="addressError"></p>
                        </div>

                        <div class="action-buttons">
                            <button type="button" id="cancelBtn" class="profile-btn cancel-button" onclick="window.location.href='userprofile.php'">
                                <i class="fas fa-times"></i>
                                Cancel
                            </button>
                            <button type="submit" id="saveBtn" name="update_profile" class="profile-btn save-button">
                                <i class="fas fa-save"></i>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <div class="sidebar-overlay" id="overlay"></div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
        const imageInput = document.getElementById('profile_image');
        const imageError = document.getElementById('imageError');
        const previewImage = document.getElementById('previewImage');

        imageInput.addEventListener('change', function (event) 
        {
            const file = event.target.files[0];
            if (file) 
            {
                const allowedExtensions = ["jpg", "jpeg", "png"];
                const fileExtension = file.name.split('.').pop().toLowerCase();

                if (!allowedExtensions.includes(fileExtension)) 
                {
                    imageError.textContent = "Invalid file type! Please upload a JPG, JPEG, or PNG image.";
                    imageError.style.color = "red";
                    imageError.style.display = "block";
                    event.target.value = "";
                    previewImage.src = "/api/placeholder/250/250";
                    return;
                }

                imageError.style.display = "none";

                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImage.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    });

    function previewProfileImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                document.getElementById('previewImage').src = e.target.result;
                // Transfer the file to the hidden input in the form
                const hiddenInput = document.getElementById('hidden_profile_image');
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(input.files[0]);
                hiddenInput.files = dataTransfer.files;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }

    function validateUsername() 
    {
        const usernameInput = document.getElementById("username");
        const usernameError = document.getElementById("usernameError");
        const username = usernameInput.value.trim();

        // Validate only if username is entered
        if (username !== "") {
            const usernameRegex = /^[a-zA-Z0-9]{5,}$/;

            if (!usernameRegex.test(username)) 
            {
                usernameError.textContent = "Invalid username. No spaces, must be at least 5 characters.";
                return false;
            }

            usernameError.textContent = "";
            return true;
        }
        return true;  // Skip validation if no input
    }

    function validatePhone() 
    {
        const phoneInput = document.getElementById("phone");
        const phoneError = document.getElementById("phoneError");
        const phone = phoneInput.value.trim();

        // Validate only if phone number is entered
        if (phone !== "") 
        {
            const firstpart = /^[6-9]/;
            const phoneRegex = /^[0-9]{10}$/;

            if (!firstpart.test(phone)) 
            {
                phoneError.textContent = "Should start with 6-9 and should be a digit.";
                return false;
            } 
            else if (!phoneRegex.test(phone)) 
            {
                phoneError.textContent = "Should contain 10 digits.";
                return false;
            }

            phoneError.textContent = "";
            return true;
        }
        return true;  // Skip validation if no input
    }

    function validateAddress() 
    {
        const addressInput = document.getElementById("address");
        const addressError = document.getElementById("addressError");
        const address = addressInput.value.trim();

        // Validate only if address is entered
        if (address !== "") 
        {
            const addressRegex = /^[a-zA-Z\s]+$/;

            if (address.length < 4) 
            {
                addressError.textContent = "Length should be greater than 4.";
                return false;
            } 
            else if (!addressRegex.test(address)) 
            {
                addressError.textContent = "Invalid address. Digits and special characters are not allowed.";
                return false;
            }

            addressError.textContent = "";
            return true;
        }
        return true;  // Skip validation if no input
    }

    function validateEmail() 
    {
        const email = document.getElementById("email").value;
        const error = document.getElementById("emailError");

        // Validate only if email is entered
        if (email !== "") 
        {
            // Check if email starts with a space or invalid character
            if (email[0] === " ") 
            {
                error.textContent = "E-mail must start with a letter.";
                return false;
            }

            // Check if email has valid format and domain
            if (!/^[a-zA-Z0-9][^\s@]*@(gmail\.com|yahoo\.com|hotmail\.com|amaljyothi\.ac\.in|mca\.ajce\.in)$/.test(email)) 
            {
                if (email.includes('@')) 
                {
                    const domain = email.split('@')[1];
                    if (domain !== 'gmail.com' && domain !== 'yahoo.com') 
                    {
                        error.textContent = "Invalid domain";
                        return false;
                    }
                }
                error.textContent = "Invalid email address.";
                return false;
            }

            error.textContent = "";
            return true;
        }
        return true;  // Skip validation if no input
    }

    function validateForm() 
    {
        let isUsernameValid = validateUsername();
        let isPhoneValid = validatePhone();
        let isAddressValid = validateAddress();
        let isEmailValid = validateEmail();

        return isUsernameValid && isPhoneValid && isAddressValid && isEmailValid;
    }

    document.addEventListener("DOMContentLoaded", function() {
        const gridIcon = document.getElementById('grid');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        // Disable transition temporarily
        sidebar.style.transition = "none";
        
        // Get saved state
        const isOpen = localStorage.getItem("sidebarOpen") === "true";
        
        if (isOpen) {
            sidebar.classList.add("show");
            overlay.classList.add("show");
        }
        
        // Re-enable transition
        setTimeout(() => {
            sidebar.style.transition = "all 0.3s ease";
        }, 100);

        gridIcon.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            
            // Save state
            localStorage.setItem("sidebarOpen", sidebar.classList.contains("show"));
        });

        // Close sidebar when clicking overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            localStorage.setItem("sidebarOpen", "false");
        });
    });
    </script>
</body>
</html>