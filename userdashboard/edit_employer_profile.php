<?php
    session_start();
    // Check if user is logged in
    if (!isset($_SESSION['employer_id'])) {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);

    // Fetch employer data including profile image
    $employer_id = $_SESSION['employer_id'];
    $sql = "SELECT e.*, l.email 
            FROM tbl_employer e 
            JOIN tbl_login l ON e.employer_id = l.employer_id 
            WHERE e.employer_id = ?";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $email = $row['email'];
        $company_name = $row['company_name'];
        $profile_image = $row['profile_image'];
    }

    // collecting data from the user throught the form
    if($_SERVER['REQUEST_METHOD']=='POST') {
        $company_name=$_POST['company_name'];
        $company_type=$_POST['company_type'];
        $registration_number=$_POST['registration_number'];
        $location=$_POST['location'];
        $establishment_year=$_POST['establishment_year'];
        $company_description=$_POST['company_description'];
        $contact_person=$_POST['contact_person'];
        $phone_number=$_POST['phone'];
        $email=$_POST['email'];
        $address=$_POST['address'];

        // Handle file upload first
        $upload_success = false;
        if(isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['company_logo']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            // Verify file extension
            if(in_array($filetype, $allowed)) {
                // Check file size - 5MB maximum
                $maxsize = 5 * 1024 * 1024;
                if($_FILES['company_logo']['size'] < $maxsize) {
                    // Create employer_pf directory if it doesn't exist
                    if(!file_exists('employer_pf')) {
                        mkdir('employer_pf', 0777, true);
                    }
                    
                    // Create unique filename
                    $new_filename = "company_logo_" . $employer_id . "." . $filetype;
                    $destination = "employer_pf/" . $new_filename;
                    
                    // Move file to destination
                    if(move_uploaded_file($_FILES['company_logo']['tmp_name'], $destination)) {
                        $upload_success = true;
                    }
                }
            }
        }

        // Update profile information
        if($upload_success) {
            // If we have a new photo
            $sql_push = "UPDATE tbl_employer SET 
                company_name=?, 
                type=?, 
                registration_number=?, 
                location=?, 
                establishment_year=?, 
                shop_description=?, 
                contact_person=?, 
                phone_number=?, 
                address=?,
                profile_image=?
                WHERE employer_id=?";
            
            $stmt = mysqli_prepare($conn, $sql_push);
            mysqli_stmt_bind_param($stmt, "ssssisssssi", 
                $company_name, 
                $company_type, 
                $registration_number, 
                $location, 
                $establishment_year, 
                $company_description, 
                $contact_person, 
                $phone_number, 
                $address,
                $destination,
                $employer_id
            );
        } else {
            // If no new photo
            $sql_push = "UPDATE tbl_employer SET 
                company_name=?, 
                type=?, 
                registration_number=?, 
                location=?, 
                establishment_year=?, 
                shop_description=?, 
                contact_person=?, 
                phone_number=?, 
                address=?
                WHERE employer_id=?";
            
            $stmt = mysqli_prepare($conn, $sql_push);
            mysqli_stmt_bind_param($stmt, "ssssissssi", 
                $company_name, 
                $company_type, 
                $registration_number, 
                $location, 
                $establishment_year, 
                $company_description, 
                $contact_person, 
                $phone_number, 
                $address,
                $employer_id
            );
        }

        $result_push = mysqli_stmt_execute($stmt);

        // Update email only if it has changed and is not empty
        $sql_check_email = "SELECT email FROM tbl_login WHERE employer_id = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check_email);
        mysqli_stmt_bind_param($stmt_check, "i", $employer_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $current_email = mysqli_fetch_assoc($result_check)['email'];

        if (!empty($email) && $email !== $current_email) {
            // Check if new email already exists for another user
            $sql_check_duplicate = "SELECT email FROM tbl_login WHERE email = ? AND employer_id != ?";
            $stmt_check_duplicate = mysqli_prepare($conn, $sql_check_duplicate);
            mysqli_stmt_bind_param($stmt_check_duplicate, "si", $email, $employer_id);
            mysqli_stmt_execute($stmt_check_duplicate);
            $result_duplicate = mysqli_stmt_get_result($stmt_check_duplicate);
            
            if (mysqli_num_rows($result_duplicate) > 0) {
                $_SESSION['message'] = "Email already exists";
                $_SESSION['message_type'] = "error";
                header("Location: edit_employer_profile.php");
                exit();
            }
            
            // Update email if no duplicate found and email is valid
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $sql_email_push = "UPDATE tbl_login SET email=? WHERE employer_id=?";
                $stmt_email = mysqli_prepare($conn, $sql_email_push);
                mysqli_stmt_bind_param($stmt_email, "si", $email, $employer_id);
                $result_push_email = mysqli_stmt_execute($stmt_email);
            } else {
                $_SESSION['message'] = "Invalid email format";
                $_SESSION['message_type'] = "error";
                header("Location: edit_employer_profile.php");
                exit();
            }
        }

        if ($result_push) {
            $_SESSION['update_success'] = true;
            header("Location: employer_profile.php");
            exit();
        } else {
            $_SESSION['update_error'] = "Failed to update profile";
            header("Location: edit_employer_profile.php");
            exit();
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | AutoRecruits</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="employerdashboard.css">
    <style>
        .main-container {
            padding: 2rem;
            margin-left: 250px;
            background-color: #f1f5f9;
            min-height: 100vh;
        }

        .header {
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 1.875rem;
            font-weight: 600;
            color: #1e293b;
        }

        .content-card {
            background: #f8fafc;
            padding: 30px;
            border-radius: 16px;
        }

        .form-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 28px;
            margin-bottom: 28px;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .form-section:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 2px 4px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .section-title {
            color: #1e293b;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #3b82f6;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            color: #475569;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #1e293b;
            background-color: #fff;
            transition: all 0.2s ease;
        }

        .form-group input:hover,
        .form-group textarea:hover {
            border-color: #cbd5e1;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .profile-photo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-photo-container {
            width: 150px;
            height: 150px;
            margin: 0 auto;
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            border: 3px solid #e2e8f0;
        }

        .profile-photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-upload-label {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background-color 0.2s ease;
        }

        .photo-upload-label:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        .button-group {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 2rem;
        }

        .submit-btn,
        .cancel-btn {
            padding: 14px 36px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .submit-btn {
            background-color: #3b82f6;
            color: white;
        }

        .cancel-btn {
            background-color: #ef4444;
            color: white;
        }

        .submit-btn:hover,
        .cancel-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .submit-btn:hover {
            background-color: #2563eb;
        }

        .cancel-btn:hover {
            background-color: #dc2626;
        }

        .error {
            color: #dc2626;
            font-size: 0.85rem;
            margin-top: 6px;
        }

        @media (max-width: 768px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .submit-btn,
            .cancel-btn {
                width: 100%;
            }
        }

        .helper-text {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
            font-style: italic;
        }

        input[disabled] {
            opacity: 0.7;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div id="notification" class="notification" style="display: none;">
        <div class="notification-content">
            <i class="fas fa-check-circle"></i>
            <span id="notification-message"></span>
        </div>
    </div>
    <div class="sidebar">
        <div class="logo-container">
            <?php if(!empty($profile_image) && file_exists($profile_image)): ?>
                <img src="<?php echo htmlspecialchars($profile_image); ?>" 
                     alt="<?php echo htmlspecialchars($company_name); ?>"
                     onerror="this.src='../assets/images/company-logo.png';">
            <?php else: ?>
                <img src="../assets/images/company-logo.png" alt="AutoRecruits.in">
            <?php endif; ?>
        </div>
        <div class="company-info">
            <span><?php echo htmlspecialchars($company_name); ?></span>
        </div>
        <nav class="nav-menu">
            <div class="nav-item active">
                <i class="fas fa-th-large"></i>
                <a href="employerdashboard.php">Home</a>
            </div>
            <div class="nav-item">
                <i class="fas fa-plus-circle"></i>
                <a href="postjob.php">Post a Job</a>
            </div>
            <div class="nav-item">
                <i class="fas fa-briefcase"></i>
                <a href="myjoblist.php">My Jobs</a>
            </div>
            <div class="nav-item">
                    <i class="fas fa-users"></i>
                    <a href="applicants.php">Applicants</a>
                </div>
            <div class="nav-item">
                <i class="fas fa-calendar-check"></i>
                <a>Interviews</a>
            </div>
        </nav>
        <div class="settings-section">
            <div class="nav-item">
                <i class="fas fa-user"></i>
                <a href="employer_profile.php">My Profile</a>
            </div>
            <div class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <a href="../login/logout.php">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="main-container">
        <div class="header">
            <h1>Edit Company Profile</h1>
        </div>

        <div class="content-card">
            <form method="POST" enctype="multipart/form-data" onsubmit="return check()">
                <!-- Company Logo Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-image"></i>
                        Company Logo
                    </h3>
                    <div class="profile-photo-section">
                        <div class="profile-photo-container">
                            <img src="<?php 
                                if (!empty($profile_image) && file_exists($profile_image)) {
                                    echo htmlspecialchars($profile_image);
                                } else {
                                    echo '../assets/images/company-logo.png';
                                }
                            ?>" 
                                 alt="Company Logo" 
                                 id="preview-photo"
                                 onerror="this.src='../assets/images/company-logo.png';">
                            <label for="company-logo" class="photo-upload-label">
                                <i class="fas fa-camera"></i> Change Photo
                            </label>
                            <input type="file" id="company-logo" name="company_logo" accept="image/*" hidden>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-building"></i>
                        Basic Information
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Company Name</label>
                            <input type="text" name="company_name" id="company_name" 
                                   value="<?php echo htmlspecialchars($row['company_name']); ?>" 
                                   onkeyup="checkcompanyname()">
                            <span id="company_name_error" class="error"></span>
                        </div>
                        <div class="form-group">
                            <label>Company Type</label>
                            <input type="text" name="company_type" id="company_type" 
                                   value="<?php echo htmlspecialchars($row['type']); ?>" 
                                   onkeyup="checkcompanytype()">
                            <span id="company_type_error" class="error"></span>
                        </div>
                        <div class="form-group">
                            <label>Registration Number</label>
                            <input type="text" name="registration_number" id="registration_number" 
                                   value="<?php echo htmlspecialchars($row['registration_number']); ?>" 
                                   onkeyup="checkregistrationnumber()">
                            <span id="registration_number_error" class="error"></span>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-address-card"></i>
                        Contact Information
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Contact Person</label>
                            <input type="text" name="contact_person" id="contact_person" 
                                   value="<?php echo htmlspecialchars($row['contact_person']); ?>" 
                                   onkeyup="checkcontactperson()">
                            <span id="contact_person_error" class="error"></span>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" id="phone" 
                                   value="<?php echo htmlspecialchars($row['phone_number']); ?>" 
                                   onkeyup="checkphonenumber()">
                            <span id="phone_error" class="error"></span>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" id="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" 
                                   disabled
                                   style="background-color: #f5f5f5; cursor: not-allowed;"
                                   readonly>
                            <!-- <span class="helper-text">Email cannot be changed</span> -->
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Additional Information
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" id="location" 
                                   value="<?php echo htmlspecialchars($row['location']); ?>" 
                                   onkeyup="checklocation()">
                            <span id="location_error" class="error"></span>
                        </div>
                        <div class="form-group">
                            <label>Establishment Year</label>
                            <input type="number" name="establishment_year" id="establishment_year" 
                                   value="<?php echo htmlspecialchars($row['establishment_year']); ?>" 
                                   onkeyup="checkestablishmentyear()">
                            <span id="establishment_year_error" class="error"></span>
                        </div>
                        <div class="form-group">
                            <label>Company Description</label>
                            <textarea name="company_description" id="company_description" 
                                      onkeyup="checkcompanydescription()" rows="4"><?php echo htmlspecialchars($row['shop_description']); ?></textarea>
                            <span id="company_description_error" class="error"></span>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" id="address" 
                                      onkeyup="checkaddress()" rows="3"><?php echo htmlspecialchars($row['address']); ?></textarea>
                            <span id="address_error" class="error"></span>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" onclick="window.location.href='employer_profile.php'" class="cancel-btn">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-check"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // Animation for elements when they come into view
        document.addEventListener('DOMContentLoaded', function() {
            // Animate Play Button
            const playButton = document.querySelector('.play-button');
            setInterval(() => {
                playButton.style.transform = 'translate(-50%, -50%) scale(1.1)';
                setTimeout(() => {
                    playButton.style.transform = 'translate(-50%, -50%) scale(1)';
                }, 500);
            }, 2000);
            
            // Animate Calculator Dots
            const dots = document.querySelectorAll('.dot');
            let currentActive = 0;
            
            setInterval(() => {
                dots.forEach(dot => dot.classList.remove('active'));
                currentActive = (currentActive + 1) % dots.length;
                dots[currentActive].classList.add('active');
            }, 1500);
        });

        // validation for the form
        function checkcompanyname()
        {
            const company_name=document.getElementById("company_name").value.trim();
            const company_name_error=document.getElementById("company_name_error");
            if(!/^[a-zA-Z0-9 &]+$/.test(company_name))
            {
                company_name_error.textContent="Company name should not contain special characters other than &";
                return false;
            }
            company_name_error.textContent="";
            return true;
        }

        function checkcompanytype()
        {
            const company_type=document.getElementById("company_type").value.trim();
            const company_type_error=document.getElementById("company_type_error");
            if(!/^[a-zA-Z\s]+$/.test(company_type))
            {
                company_type_error.textContent="Company type should not contain special characters or numbers";
                return false;
            }
            company_type_error.textContent="";
            return true;
        }

        function checkregistrationnumber()
        {
            const registration_number=document.getElementById("registration_number").value.trim();
            const registration_number_error=document.getElementById("registration_number_error");
            if(!/^[a-zA-Z0-9 / -]+$/.test(registration_number))
            {
                registration_number_error.textContent="Registration number should not contain special characters and spaces other than / and -";
                return false;
            }
            registration_number_error.textContent="";
            return true;
        }

        function checklocation()
        {
            const location=document.getElementById("location").value.trim();
            const location_error=document.getElementById("location_error");
            if(!/^[a-zA-Z\s]+$/.test(location))
            {
                location_error.textContent="Location should not contain special characters";
                return false;
            }
            location_error.textContent="";
            return true;
        }

        function checkestablishmentyear()
        {
            const establishment_year=document.getElementById("establishment_year").value.trim();
            const establishment_year_error=document.getElementById("establishment_year_error");
            if(!/^[0-9]+$/.test(establishment_year))
            {
                establishment_year_error.textContent="Only digits are allowed";
                return false;
            }
            establishment_year_error.textContent="";
            return true;
        }

        function checkcompanydescription()
        {
            const company_description=document.getElementById("company_description").value.trim();
            const company_description_error=document.getElementById("company_description_error");
            if(company_description.length<6)
            {
                company_description_error.textContent="Company description should be at least 6 characters";
                return false;
            }
            else if(!/^[a-zA-Z0-9\s]+$/.test(company_description))
            {
                company_description_error.textContent="Company description should not contain special characters";
                return false;
            }
            company_description_error.textContent="";
            return true;
        }

        function checkcontactperson()
        {
            const contact_person=document.getElementById("contact_person").value.trim();
            const contact_person_error=document.getElementById("contact_person_error");
            if(!/^[a-zA-Z\s]+$/.test(contact_person))
            {
                contact_person_error.textContent="Only alphabets and spaces are allowed";
                return false;
            }
            contact_person_error.textContent="";
            return true;
        }

        function checkphonenumber() {
            const phone = document.getElementById("phone").value.trim();
            const phone_error = document.getElementById("phone_error");
            
            // Check if empty
            if(phone === "") {
                phone_error.textContent = "Phone number is required";
                return false;
            }
            
            // Check if only digits
            if(!/^\d+$/.test(phone)) {
                phone_error.textContent = "Only digits are allowed";
                return false;
            }
            
            // Check length
            if(phone.length !== 10) {
                phone_error.textContent = "Phone number should be 10 digits";
                return false;
            }
            
            // Check first digit
            if(!/^[6-9]/.test(phone)) {
                phone_error.textContent = "Phone number should start with 6, 7, 8 or 9";
                return false;
            }
            
            // Check for repeated digits
            if(/^(\d)\1{9}$/.test(phone)) {
                phone_error.textContent = "All digits cannot be the same";
                return false;
            }
            
            phone_error.textContent = "";
            return true;
        }

        function checkaddress()
        {
            const address=document.getElementById("address").value.trim();
            const address_error=document.getElementById("address_error");
            if(!/^[a-zA-Z\s]+$/.test(address))
            {
                address_error.textContent="Only characters and spaces are allowed";
                return false;
            }
            address_error.textContent="";
            return true;
        }

        function check()
        {
            const compnay_name_valid=checkcompanyname();
            const company_type_valid=checkcompanytype();
            const registration_number_valid=checkregistrationnumber();
            const location_valid=checklocation();
            const establishment_year_valid=checkestablishmentyear();
            const company_description_valid=checkcompanydescription();
            const contact_person_valid=checkcontactperson();
            const phone_number_valid=checkphonenumber();
            const address_valid=checkaddress();
            
            // Check file upload if a file was selected
            const fileInput = document.getElementById('company-logo');
            if(fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, JPEG, or PNG)');
                    return false;
                }
                
                if (file.size > maxSize) {
                    alert('File size must be less than 5MB');
                    return false;
                }
            }

            return compnay_name_valid && 
                   company_type_valid && 
                   registration_number_valid && 
                   location_valid && 
                   establishment_year_valid && 
                   company_description_valid && 
                   contact_person_valid && 
                   phone_number_valid && 
                   address_valid;
        }

        // Add CSS for error message positioning
        const style = document.createElement('style');
        style.textContent = `
        .profile-photo {
            position: relative;
            margin-bottom: 30px; /* Add space for error message */
        }
        .photo-error-message {
            position: absolute;
            top:-10px;
            left: 0;
            right: 0;
            text-align: center;
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 2px 5px;
        }
        `;
        document.head.appendChild(style);

        // Preview uploaded image
        document.getElementById('company-logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-photo').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>