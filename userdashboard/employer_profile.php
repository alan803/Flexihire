<?php
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['employer_id'])) {
        // Redirect to login page if not logged in
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);
    
    $employer_id = $_SESSION['employer_id'];

    // Use prepared statement to prevent SQL injection
    $sql = "SELECT * FROM tbl_employer WHERE employer_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }
    
    $row = mysqli_fetch_assoc($result);
    
    if (!$row) {
        die("No employer found with ID: " . $employer_id);
    }
    
    // Fetch email from tbl_login
    $sql_email = "SELECT email FROM tbl_login WHERE user_id = ?";
    $stmt_email = mysqli_prepare($conn, $sql_email);
    mysqli_stmt_bind_param($stmt_email, "i", $employer_id);
    mysqli_stmt_execute($stmt_email);
    $result_email = mysqli_stmt_get_result($stmt_email);
    $row_email = mysqli_fetch_assoc($result_email);
    $email = $row_email['email'] ?? '';
    
    // Safely get company name with null coalescing operator
    $company_name = $row['company_name'] ?? 'Company Name Not Set';

    // Check if row data exists
    if ($row) {
        echo "<!-- Debug: Location value = " . htmlspecialchars($row['location']) . " -->";
    } else {
        echo "<!-- Debug: No data found for employer_id = $employer_id -->";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="employerdashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-container">
                <?php if(!empty($row['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($row['profile_image']); ?>" 
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
                <div class="nav-item">
                    <i class="fas fa-th-large"></i>
                    <a href="employerdashboard.php">Dashboard</a>
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
                    <a href="interviews.php">Interviews</a>
                </div>
            </nav>
            <div class="settings-section">
                <div class="nav-item active">
                    <i class="fas fa-user"></i>
                    <a href="employer_profile.php">My Profile</a>
                </div>
                <div class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <a href="../login/logout.php">Logout</a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-container">
            <div class="header">
                <h1>Company Profile</h1>
            </div>

            <!-- Profile Content -->
            <div class="content-card">
                <?php if (isset($_SESSION['update_success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span>Profile Updated Successfully!</span>
                    </div>
                <?php endif; ?>

                <!-- Profile Header Section -->
                <div class="profile-header">
                    <div class="profile-banner"></div>
                    <div class="profile-info">
                        <div class="profile-photo">
                            <?php if(!empty($row['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($row['profile_image']); ?>" 
                                     alt="Company Logo"
                                     onerror="this.src='../assets/images/company-logo.png';">
                            <?php else: ?>
                                <img src="../assets/images/company-logo.png" alt="Default Company Logo">
                            <?php endif; ?>
                        </div>
                        <div class="profile-details">
                            <h2><?php echo htmlspecialchars($company_name); ?></h2>
                            <p class="company-type">Company</p>
                            <div class="company-meta">
                                <span><i class="fas fa-building"></i> Reg: <?php echo htmlspecialchars($row['registration_number'] ?? 'Not Set'); ?></span>
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['location'] ?? 'Location Not Set'); ?></span>
                                <span><i class="fas fa-calendar"></i> Est. <?php echo htmlspecialchars($row['establishment_year'] ?? 'Year Not Set'); ?></span>
                            </div>
                        </div>
                        <div class="profile-actions">
                            <a href="edit_employer_profile.php" class="edit-btn">
                                <i class="fas fa-edit"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Profile Content Grid -->
                <div class="profile-grid">
                    <!-- Contact Information Card -->
                    <div class="info-card">
                        <h3><i class="fas fa-address-card"></i> Contact Information</h3>
                        <div class="info-content">
                            <div class="info-item">
                                <label>Contact Person</label>
                                <p><?php echo htmlspecialchars($row['contact_person'] ?? 'Not Set'); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Phone Number</label>
                                <p><?php echo htmlspecialchars($row['phone_number'] ?? 'Not Set'); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Email</label>
                                <p><?php echo htmlspecialchars($email); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Address</label>
                                <p><?php echo htmlspecialchars($row['address'] ?? 'Not Set'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- About Company Card -->
                    <div class="info-card">
                        <h3><i class="fas fa-building"></i> About Company</h3>
                        <div class="info-content">
                            <p class="description"><?php echo htmlspecialchars($row['shop_description'] ?? 'No description available'); ?></p>
                        </div>
                    </div>

                    <!-- Company Details Card -->
                    <div class="info-card">
                        <h3><i class="fas fa-info-circle"></i> Company Details</h3>
                        <div class="info-content">
                            <div class="info-item">
                                <label>Industry</label>
                                <p><?php echo htmlspecialchars($row['details'] ?? 'Not Set'); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Registration Number</label>
                                <p><?php echo htmlspecialchars($row['registration_number'] ?? 'Not Set'); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Established</label>
                                <p><?php echo htmlspecialchars($row['establishment_year'] ?? 'Not Set'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .content-card {
            background: #f8fafc;
            padding: 30px;
            border-radius: 16px;
            margin: 20px;
        }

        .profile-header {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .profile-banner {
            height: 200px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .profile-info {
            padding: 24px;
            position: relative;
            display: flex;
            align-items: flex-end;
            gap: 24px;
            margin-top: -80px;
        }

        .profile-photo {
            width: 160px;
            height: 160px;
            border-radius: 12px;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-details {
            flex: 1;
        }

        .profile-details h2 {
            color: #1e293b;
            font-size: 1.5rem;
            margin-bottom: 4px;
        }

        .company-type {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }

        .company-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        .company-meta span {
            color: #475569;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .company-meta i {
            color: #3b82f6;
        }

        .profile-actions {
            margin-left: auto;
            align-self: flex-start;
        }

        .edit-btn {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .edit-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .info-card h3 {
            color: #1e293b;
            font-size: 1.1rem;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-card h3 i {
            color: #3b82f6;
        }

        .info-content {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .info-item label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .info-item p {
            color: #1e293b;
            font-size: 0.95rem;
        }

        .description {
            color: #475569;
            line-height: 1.6;
        }

        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeOut 0.5s ease-out 3s forwards;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert i {
            font-size: 1.2rem;
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                visibility: hidden;
            }
        }

        @media (max-width: 768px) {
            .profile-info {
                flex-direction: column;
                align-items: center;
                text-align: center;
                margin-top: -40px;
            }

            .profile-photo {
                width: 120px;
                height: 120px;
            }

            .company-meta {
                justify-content: center;
            }

            .profile-actions {
                margin: 20px 0 0;
                align-self: center;
            }

            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        // Auto-hide success message
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(() => {
                    alert.remove();
                }, 3500);
            }
        });
    </script>
</body>
</html>

<?php
// Clear the session variable after displaying
if(isset($_SESSION['update_success'])) {
    unset($_SESSION['update_success']);
}
?>