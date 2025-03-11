<?php
    session_start();
    if(!isset($_SESSION['employer_id']))
    {
        header("location:../login/login.php");
    }
    include '../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);
    $employer_id=$_SESSION['employer_id'];
    $sql="SELECT * FROM tbl_employer WHERE employer_id=$employer_id";
    $result=mysqli_query($conn,$sql);
    $row=mysqli_fetch_assoc(mysqli_query($conn,$sql));
    $company_name=$row['company_name'];

    // selecting email from tbl_user
    $sql_email="SELECT email from tbl_login WHERE user_id=$employer_id";
    $result_email=mysqli_query($conn,$sql_email);
    $row_email=mysqli_fetch_assoc($result_email);
    $email=$row_email['email'];

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

        // Update email
        $sql_email_push = "UPDATE tbl_login SET email=? WHERE user_id=?";
        $stmt_email = mysqli_prepare($conn, $sql_email_push);
        mysqli_stmt_bind_param($stmt_email, "si", $email, $employer_id);
        $result_push_email = mysqli_stmt_execute($stmt_email);

        if ($result_push && $result_push_email) {
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
    <title>Employer Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="edit_employer_profile.css">
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
            <img src="logo.png" alt="AutoRecruits.in">
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
    
    <div class="main-content">
        <form method="POST" enctype="multipart/form-data" onsubmit="return check()">
            <!-- Header Section with Animation -->
        <div class="profile-header">
                <div class="header-banner">
                    <div class="banner-overlay"></div>
                </div>
                <div class="profile-info-container">
                    <div class="profile-photo animate-scale-in">
                        <img src="<?php echo !empty($row['profile_photo']) ? htmlspecialchars($row['profile_photo']) : 'company-logo.png'; ?>" alt="Company Logo" id="preview-photo">
                        <div class="photo-upload pulse-animation">
                            <label for="company-logo"><i class="fas fa-camera"></i></label>
                            <input type="file" id="company-logo" name="company_logo" accept="image/*" hidden>
                        </div>
                        <!-- Error message will be inserted here by JavaScript -->
                    </div>
                    <div class="profile-details animate-slide-in">
                        <div class="form-group floating-label">
                            <input type="text" name="company_name" id="company_name" value="<?php echo htmlspecialchars($row['company_name']); ?>" class="form-input" onkeyup="checkcompanyname()">
                            <label>Company Name</label>
                            <span id="company_name_error" class="error-message"></span>
                        </div>
                        <div class="form-row">
                            <div class="form-group floating-label">
                                <label>Company Type</label>
                                <input type="text" name="company_type" id="company_type" value="<?php echo htmlspecialchars($row['type']); ?>" class="form-input" onkeyup="checkcompanytype()">
                                <span id="company_type_error" class="error-message"></span>
                            </div>
                            <div class="form-group floating-label">
                                <input type="text" name="registration_number" id="registration_number" value="<?php echo htmlspecialchars($row['registration_number']); ?>" class="form-input" onkeyup="checkregistrationnumber()">
                                <span id="registration_number_error" class="error-message"></span>
                                <label>Registration Number</label>
                            </div>
                        </div>
                </div>
            </div>
        </div>

            <!-- Main Form Content -->
            <div class="form-grid">
                <!-- Company Information Card -->
                <div class="form-card animate-slide-up">
                    <div class="card-header">
                        <i class="fas fa-building"></i>
                        <h3>Company Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group floating-label">
                                <input type="text" name="location"  id="location" value="<?php echo htmlspecialchars($row['location']); ?>" class="form-input" onkeyup="checklocation()">
                                <label>Location</label>
                                <span id="location_error" class="error-message"></span>
                            </div>
                            <div class="form-group floating-label">
                                <input type="number" name="establishment_year" id="establishment_year"  value="<?php echo htmlspecialchars($row['establishment_year']); ?>" class="form-input" min="1900" max="<?php echo date('Y'); ?>" onkeyup="checkestablishmentyear()">
                                <label>Establishment Year</label>
                                <span id="establishment_year_error" class="error-message"></span>
                            </div>
                    </div>
                        <div class="form-group floating-label">
                            <textarea name="company_description" id="company_description" class="form-input" rows="4" onkeyup="checkcompanydescription()"><?php echo htmlspecialchars($row['shop_description']); ?></textarea>
                            <label>Company Description</label>
                            <span id="company_description_error" class="error-message"></span>
                    </div>
                    </div>
                </div>

                <!-- Contact Information Card -->
                <div class="form-card animate-slide-up" style="animation-delay: 0.2s;">
                    <div class="card-header">
                        <i class="fas fa-address-card"></i>
                        <h3>Contact Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group floating-label">
                                <input type="text" name="contact_person" id="contact_person" value="<?php echo htmlspecialchars($row['contact_person']); ?>" class="form-input" onkeyup="checkcontactperson()">
                                <label>Contact Person</label>
                                <span id="contact_person_error" class="error-message"></span>
                            </div>
                            <div class="form-group floating-label">
                                <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($row['phone_number']); ?>" class="form-input" onkeyup="checkphonenumber()">
                                <label>Phone Number</label>
                                <span id="phone_error" class="error-message"></span>
            </div>
                </div>
                        <div class="form-group floating-label">
                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($row_email['email']); ?>" class="form-input" onkeyup="checkemail()">
                            <label>Email Address</label>
                            <span id="email_error" class="error-message"></span>
                        </div>
                        <div class="form-group floating-label">
                            <textarea name="address" id="address" class="form-input" rows="3" onkeyup="checkaddress()"><?php echo htmlspecialchars($row['address']); ?></textarea>
                            <label>Address</label>
                            <span id="address_error" class="error-message"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions animate-fade-in">
                <button type="button" class="cancel-btn" onclick="window.location.href='employer_profile.php'">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="save-btn">
                    <i class="fas fa-check"></i> Save Changes
                </button>
        </div>
        </form>
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

        function checkemail()
        {
            const email=document.getElementById("email").value.trim();
            const email_error=document.getElementById("email_error");
            if (email[0] === " ") 
            {
                 error.textContent = "E-mail must start with a letter.";
                return false;
            }

            // Check if email has valid format and domain
            // const emailRegex = ;
            if (!/^[a-zA-Z0-9][^\s@]*@(gmail\.com|yahoo\.com|hotmail\.com|amaljyothi\.ac\.in|mca\.ajce\.in)$/.test(email)) 
            {
                // Check if it's the domain that's invalid
                if (email.includes('@')) 
                {
                    const domain = email.split('@')[1];
                    if (domain !== 'gmail.com' && domain !== 'yahoo.com') 
                    {
                        email_error.textContent = "Invalid domain";
                        return false;
                    }
                }
                error.textContent = "Invalid email address.";
                return false;
            }
            email_error.textContent="";
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
            const email_valid=checkemail();
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
                   email_valid && 
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

        // Modified file upload validation code
        document.getElementById('company-logo').addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('preview-photo');
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            // Create error message element if it doesn't exist
            let errorElement = document.getElementById('logo-error');
            if (!errorElement) {
                errorElement = document.createElement('span');
                errorElement.id = 'logo-error';
                errorElement.className = 'photo-error-message';
                const profilePhotoDiv = document.querySelector('.profile-photo');
                profilePhotoDiv.appendChild(errorElement);
            }

            // Clear previous error
            errorElement.textContent = '';

            // Validate file
            if (!file) {
                return;
            }

            // Check file type
            if (!allowedTypes.includes(file.type)) {
                errorElement.textContent = 'Only JPG, JPEG, and PNG files are allowed';
                this.value = ''; // Clear the input
                preview.src = 'company-logo.png'; // Reset to default image
                return false;
            }

            // Check file size
            if (file.size > maxSize) {
                errorElement.textContent = 'File size must be less than 5MB';
                this.value = ''; // Clear the input
                preview.src = 'company-logo.png'; // Reset to default image
                return false;
            }

            // Preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                errorElement.textContent = ''; // Clear error message on success
            };
            reader.readAsDataURL(file);
            return true;
        });
    </script>
</body>
</html>