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
    if($_SERVER['REQUEST_METHOD']=='POST')
    {
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

        $sql_push = "UPDATE tbl_employer SET company_name=?, type=?, registration_number=?, location=?, establishment_year=?, shop_description=?, contact_person=?, phone_number=?, address=? WHERE employer_id=?";
    
        $stmt = mysqli_prepare($conn, $sql_push);
        mysqli_stmt_bind_param($stmt, "ssssissssi", $company_name, $company_type, $registration_number, $location, $establishment_year, $company_description, $contact_person, $phone_number, $address, $employer_id);
        $result_push = mysqli_stmt_execute($stmt);
    
        // ✅ Corrected Email Update Query
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
        <form method="POST" enctype="multipart/form-data">
            <!-- Header Section with Animation -->
            <div class="profile-header">
                <div class="header-banner">
                    <div class="banner-overlay"></div>
                </div>
                <div class="profile-info-container">
                    <div class="profile-photo animate-scale-in">
                        <img src="company-logo.png" alt="Company Logo" id="preview-photo">
                        <div class="photo-upload pulse-animation">
                            <label for="company-logo"><i class="fas fa-camera"></i></label>
                            <input type="file" id="company-logo" name="company_logo" accept="image/*" hidden>
                        </div>
                    </div>
                    <div class="profile-details animate-slide-in">
                        <div class="form-group floating-label">
                            <input type="text" name="company_name" value="<?php echo htmlspecialchars($row['company_name']); ?>" class="form-input"
                            <label>Company Name</label>
                        </div>
                        <div class="form-row">
                            <div class="form-group floating-label">
                                <label>Company Type</label>
                                <input type="text" name="company_type" value="<?php echo htmlspecialchars($row['type']); ?>" class="form-input">
                            </div>
                            <div class="form-group floating-label">
                                <input type="text" name="registration_number" value="<?php echo htmlspecialchars($row['registration_number']); ?>" class="form-input">
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
                                <input type="text" name="location" value="<?php echo htmlspecialchars($row['location']); ?>" class="form-input">
                                <label>Location</label>
                            </div>
                            <div class="form-group floating-label">
                                <input type="number" name="establishment_year" value="<?php echo htmlspecialchars($row['establishment_year']); ?>" class="form-input" min="1900" max="<?php echo date('Y'); ?>">
                                <label>Establishment Year</label>
                            </div>
                        </div>
                        <div class="form-group floating-label">
                            <textarea name="company_description" class="form-input" rows="4" ><?php echo htmlspecialchars($row['shop_description']); ?></textarea>
                            <label>Company Description</label>
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
                                <input type="text" name="contact_person" value="<?php echo htmlspecialchars($row['contact_person']); ?>" class="form-input">
                                <label>Contact Person</label>
                            </div>
                            <div class="form-group floating-label">
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($row['phone_number']); ?>" class="form-input">
                                <label>Phone Number</label>
                            </div>
                        </div>
                        <div class="form-group floating-label">
                            <input type="email" name="email" value="<?php echo htmlspecialchars($row_email['email']); ?>" class="form-input">
                            <label>Email Address</label>
                        </div>
                        <div class="form-group floating-label">
                            <textarea name="address" class="form-input" rows="3"><?php echo htmlspecialchars($row['address']); ?></textarea>
                            <label>Address</label>
                        </div>
                    </div>
                </div>

                <!-- Additional Details Card -->
                <!-- <div class="form-card animate-slide-up" style="animation-delay: 0.4s;">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i>
                        <h3>Additional Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group floating-label">
                            <input type="text" name="industry" value="<?php //echo htmlspecialchars($row['details']); ?>" class="form-input">
                            <label>Industry/Sector</label>
                        </div>
                    </div>
                </div> -->
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
        
    </script>
</body>
</html>