<?php
    session_start();
    // loged or not
    if (!isset($_SESSION['user_id'])) 
    {
        die("User is not logged in. Please log in first.");
    }

    include '../../../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);
    $user_id = $_SESSION['user_id'];//getting user id from session

    $sql = "SELECT u.first_name, u.last_name, l.email, u.profile_image, u.address, u.phone_number 
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
    $username = $user_data['first_name'];
    $email = $user_data['email'];
    $profile_image = $user_data['profile_image'];
    $phone = $user_data['phone_number'];
    $address = $user_data['address'];

    // Handle profile picture upload
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES["profile_image"])) {
        $upload_dir = "../../../database/profile_picture/";

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = $_FILES["profile_image"]["name"];
        $file_tmp = $_FILES["profile_image"]["tmp_name"];
        $file_error = $_FILES["profile_image"]["error"];

        if ($file_error === 0) 
        {
            // Rename the file to prevent overwriting
            $new_file_name = "user_" . $user_id . "_" . time() . "." . pathinfo($file_name, PATHINFO_EXTENSION);
            $upload_path = $upload_dir . $new_file_name;

            // Move the file to the upload directory
            if (move_uploaded_file($file_tmp, $upload_path)) 
            {
                // Update the database with the new profile picture
                $update_sql = "UPDATE tbl_user SET profile_image = '$new_file_name' WHERE user_id = '$user_id'";
                mysqli_query($conn, $update_sql);

                // Optionally, update session with new profile image
                $_SESSION['profile_image'] = $new_file_name;

                // Refresh the page to reflect changes
                header("Location: userprofile.php");
                exit();
            }
        }
    }

    // Handle profile data updates (username, phone number, address, email)
    if ($_SERVER['REQUEST_METHOD'] == 'POST') 
    {
        $new_username = trim($_POST['name']);
        $new_phone = trim($_POST['phone']);
        $new_address = trim($_POST['address']);
        $new_email = trim($_POST['email']);

        $isUpdated = false;

        if (!empty($new_username) && $new_username !== $username) 
        {
            $update_sql = "UPDATE tbl_user SET username = '$new_username' WHERE user_id = '$user_id'";
            mysqli_query($conn, $update_sql);
            $_SESSION['username'] = $new_username;
            $isUpdated = true; // Set the flag to true
        }

        if (!empty($new_phone) && $new_phone !== $phone) 
        {
            $update_sql = "UPDATE tbl_user SET phone_number = '$new_phone' WHERE user_id = '$user_id'";
            mysqli_query($conn, $update_sql);
            $isUpdated = true; // Set the flag to true
        }

        if (!empty($new_address) && $new_address !== $address) 
        {
            $update_sql = "UPDATE tbl_user SET address = '$new_address' WHERE user_id = '$user_id'";
            mysqli_query($conn, $update_sql);
            $isUpdated = true; // Set the flag to true
        }

        if (!empty($new_email) && $new_email !== $email) 
        {
            // Check if the new email already exists in the database
            $check_email_sql = "SELECT COUNT(*) FROM tbl_login WHERE email = '$new_email' AND user_id != '$user_id'";
            $email_result = mysqli_query($conn, $check_email_sql);
            $email_exists = mysqli_fetch_row($email_result)[0];

            if ($email_exists > 0) 
            {
                // If the email already exists, show an error and stop further processing
                echo "<script>alert('Email already in use by another user.');</script>";
            } 
            else 
            {
                // Update the email in the database
                $update_email_sql = "UPDATE tbl_login SET email = '$new_email' WHERE user_id = '$user_id'";
                mysqli_query($conn, $update_email_sql);
                $isUpdated = true; // Set the flag to true
            }
        }

        // If any value was updated, refresh the page
        if ($isUpdated) 
        {
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
    <link rel="stylesheet" href="user_profile_update.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="userprofile.php" id="back"><span class="arrow-circle">←</span> Back</a>
        </div>

        <h1 class="title">Edit Profile</h1>

        <div class="profile-section">
            <form method="POST" enctype="multipart/form-data">
                <div class="profile-image">
                    <!-- Dynamically load user's profile picture -->
                    <img id="previewImage" 
                    src="<?php echo !empty($user_data['profile_image']) ? 'database/profile_picture/' . $user_data['profile_image'] : '/api/placeholder/250/250'; ?>" 
                    alt="Profile">

                    <div class="upload-buttons">
                        <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display: none;">
                        <button type="button" class="upload-btn" onclick="document.getElementById('profile_image').click();">
                            CHANGE PROFILE
                        </button>
                    </div>
                    <p id="imageError"></p>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-primary">SAVE</button>
                </div>
            </form>
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm();">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" id="username" name="name" placeholder="Enter your username" onkeyup="validateUsername()">
                        <p class="error" id="usernameError"></p> <!-- Error message element -->
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" onkeyup="validateEmail()">
                        <p class="error" id="emailError"></p> <!-- Error message element -->
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <div class="phone-input">
                            <span>+91</span>
                            <input type="tel" name="phone" id="phone" placeholder="Enter phone number" onkeyup="validatePhone()">
                        </div>
                        <p class="error" id="phoneError"></p>
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" id="address" placeholder="Enter your address" onkeyup="validateAddress()">
                        <p class="error" id="addressError"></p>
                    </div>
                </div>

                <div class="actions">
                    <a href="userprofile.php"><button type="button" class="btn btn-secondary">CANCEL</button></a>
                    <button type="submit" class="btn btn-primary">SAVE</button>
                </div>
            </form>
        </div>
    </div>

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
    </script>
</body>
</html>