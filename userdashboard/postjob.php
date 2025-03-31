<?php
    session_start();
    $employer_id = $_SESSION['employer_id'];
    include '../database/connectdatabase.php';
    $dbname = "project";
    mysqli_select_db($conn, $dbname);
    $error = "";

    if (!isset($_SESSION['employer_id'])) 
    {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    $sql = "SELECT u.company_name, l.email, u.profile_image 
        FROM tbl_login AS l
        JOIN tbl_employer AS u ON l.employer_id = u.employer_id
        WHERE u.employer_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) 
    {
        $employer_data = mysqli_fetch_array($result);
        $email = $employer_data['email'];
        $username = $employer_data['company_name'];
        $profile_image = $employer_data['profile_image'];
    }

    if ($_SERVER['REQUEST_METHOD'] == "POST") 
    {
        $category = $_POST['category'];
        
        // Handle license_required - set to NULL if empty
        $license = !empty($_POST['license_required']) ? $_POST['license_required'] : NULL;
        
        // Handle badge_required - set to NULL if empty
        $badge = !empty($_POST['badge_required']) ? $_POST['badge_required'] : NULL;
        
        $job_title = $_POST['job_title'];
        $job_description = $_POST['job_description'];
        $location = $_POST['location'];
        $town = $_POST['town'];
        $vacancy_date = $_POST['date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $working_days = $_POST['working_days'];
        $vacancy = $_POST['vacancy'];
        $salary = $_POST['salary'];
        $application_deadline = $_POST['last_date'];
        $contact = $_POST['phone'];
        $interview = isset($_POST['interview']) ? $_POST['interview'] : NULL;
        $status = 'pending'; // Set initial status as pending
        
        // Update the SQL statement to handle NULL values
        $stmt = $conn->prepare("INSERT INTO tbl_jobs (category, job_title, location, job_description, vacancy_date, vacancy, salary, application_deadline, interview, employer_id, town, start_time, end_time, working_days, contact_no, license_required, badge_required, status, is_deleted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
        
        // Fix the bind_param statement to match the number of parameters
        $stmt->bind_param("sssssssssississsss", 
            $category, 
            $job_title, 
            $location, 
            $job_description, 
            $vacancy_date, 
            $vacancy, 
            $salary, 
            $application_deadline, 
            $interview, 
            $employer_id, 
            $town, 
            $start_time, 
            $end_time, 
            $working_days, 
            $contact, 
            $license, 
            $badge, 
            $status
        );
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Job posted successfully and is pending admin approval.";
            $_SESSION['message_type'] = "success";
            header("Location: postjob.php");
            exit();
        } else {
            $_SESSION['message'] = "Error posting job: " . $conn->error;
            $_SESSION['message_type'] = "error";
            header("Location: postjob.php");
            exit();
        }

        if (isset($stmt) && $stmt !== false) 
        {
            $stmt->close();
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Job</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="employerdashboard.css">
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-container">
                <?php if(!empty($profile_image)): ?>
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" 
                         alt="<?php echo htmlspecialchars($username); ?>"
                         onerror="this.src='../assets/images/company-logo.png';">
                <?php else: ?>
                    <img src="../assets/images/company-logo.png" alt="AutoRecruits.in">
                <?php endif; ?>
            </div>
            <div class="company-info">
                <span><?php echo htmlspecialchars($username); ?></span>
                <span><?php echo htmlspecialchars($email); ?></span>
            </div>
            <nav class="nav-menu">
                <div class="nav-item"><i class="fas fa-th-large"></i><a href="employerdashboard.php">Dashboard</a></div>
                <div class="nav-item active"><i class="fas fa-plus-circle"></i><a href="postjob.php">Post a Job</a></div>
                <div class="nav-item"><i class="fas fa-briefcase"></i><a href="myjoblist.php">My Jobs</a></div>
                <div class="nav-item"><i class="fas fa-users"></i><a href="applicants.php">Applicants</a></div>
                <div class="nav-item"><i class="fas fa-calendar-check"></i><a href="interviews.php">Interviews</a></div>
            </nav>
            <div class="settings-section">
                <div class="nav-item"><i class="fas fa-user-cog"></i><a href="employer_profile.php">My Profile</a></div>
                <div class="nav-item"><i class="fas fa-sign-out-alt"></i><a href="../login/logout.php">Logout</a></div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-container">
            <div class="header">
                <h1>Post a New Job - <?php echo htmlspecialchars($username); ?></h1>
            </div>

            <!-- Form Container -->
            <div class="content-card">
                <form method="post" id="add_job" onsubmit="return validateForm()">
                    <?php if (isset($_GET['message'])): ?>
                        <div class="alert <?php echo strpos($_GET['message'], 'successfully') !== false ? 'alert-success' : 'alert-error'; ?>">
                            <?php echo htmlspecialchars($_GET['message']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-container">
                        <!-- Basic Job Information -->
                        <div class="form-section">
                            <h3 class="section-title">Basic Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Job Category</label>
                                    <select name="category" id="category" onchange="validatecategory()">
                                        <option value="">Select Category</option>
                                        <option value="Delivery and logistics">Delivery & Logistics</option>
                                        <option value="Hospitality and catering">Hospitality & Catering</option>
                                        <option value="Housekeeping and cleaning">Housekeeping & Cleaning</option>
                                        <option value="Retail and store jobs">Retail & Store Jobs</option>
                                        <option value="Warehouse and factory jobs">Warehouse & Factory Jobs</option>
                                        <option value="Maintenance">Maintenance</option>
                                    </select>
                                    <p id="categoryerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Job Title</label>
                                    <input type="text" name="job_title" id="job_title" placeholder="Enter job title" onkeyup="validateJobTitle()">
                                    <p id="titleerror" class="error"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Documents Section -->
                        <div id="delivery-docs" class="form-section" style="display: none;">
                            <h3 class="section-title">Required Documents</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Driving License Required</label>
                                    <select name="license_required" id="license_required">
                                        <option value="">Select License Type</option>
                                        <option value="two_wheeler">Two Wheeler License</option>
                                        <option value="four_wheeler">Four Wheeler License</option>
                                        <option value="both">Both Required</option>
                                        <option value="not_required">Not Required</option>
                                    </select>
                                    <p id="licenseerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Badge Required</label>
                                    <select name="badge_required" id="badge_required">
                                        <option value="">Select Badge Requirement</option>
                                        <option value="yes">Yes</option>
                                        <option value="no">No</option>
                                    </select>
                                    <p id="badgeerror" class="error"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Job Description -->
                        <div class="form-section">
                            <h3 class="section-title">Job Description</h3>
                            <div class="form-group">
                                <textarea name="job_description" id="job_description" placeholder="Enter detailed job description" onkeyup="validateJobDescription()"></textarea>
                                <p id="descriptionerror" class="error"></p>
                            </div>
                        </div>

                        <!-- Location Information -->
                        <div class="form-section">
                            <h3 class="section-title">Location Details</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>District</label>
                                    <select name="location" id="location" onchange="showTowns()">
                                        <option value="">Select District</option>
                                        <option value="Thiruvananthapuram">Thiruvananthapuram</option>
                                        <option value="Kollam">Kollam</option>
                                        <option value="Pathanamthitta">Pathanamthitta</option>
                                        <option value="Alappuzha">Alappuzha</option>
                                        <option value="Kottayam">Kottayam</option>
                                        <option value="Idukki">Idukki</option>
                                        <option value="Ernakulam">Ernakulam</option>
                                        <option value="Thrissur">Thrissur</option>
                                        <option value="Palakkad">Palakkad</option>
                                        <option value="Malappuram">Malappuram</option>
                                        <option value="Kozhikode">Kozhikode</option>
                                        <option value="Wayanad">Wayanad</option>
                                        <option value="Kannur">Kannur</option>
                                        <option value="Kasaragod">Kasaragod</option>
                                    </select>
                                    <p id="locationerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label id="townLabel" style="display:none;">Town</label>
                                    <select name="town" id="tvm_towns" style="display:none;">
                                        <option value="">Select Town</option>
                                    </select>
                                    <p id="townerror" class="error"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Information -->
                        <div class="form-section">
                            <h3 class="section-title">Work Schedule</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Vacancy Date</label>
                                    <input type="date" name="date" id="date" onchange="validateDate()">
                                    <p id="dateerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Working Hours</label>
                                    <div class="time-inputs">
                                        <select id="start-time" name="start_time">
                                            <option value="">Start Time</option>
                                        </select>
                                        <span class="time-separator">to</span>
                                        <select id="end-time" name="end_time">
                                            <option value="">End Time</option>
                                        </select>
                                    </div>
                                    <p id="timeerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Working Days</label>
                                    <select name="working_days" id="working_days" onchange="validateWorkingDays()">
                                        <option value="">Select Working Days</option>
                                        <option value="part_time">Part Time</option>
                                        <option value="full_day">Full Day</option>
                                        <option value="shift">Shift-based</option>
                                    </select>
                                    <p id="workingdayserror" class="error"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Job Details -->
                        <div class="form-section">
                            <h3 class="section-title">Additional Details</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Number of Vacancies</label>
                                    <input type="text" name="vacancy" id="vacancy" placeholder="Enter number of vacancies" onkeyup="validateVacancy()">
                                    <p id="vacancyerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Salary</label>
                                    <input type="text" name="salary" id="salary" placeholder="Enter salary" onkeyup="validateSalary()">
                                    <p id="salaryerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Application Deadline</label>
                                    <input type="date" name="last_date" id="last_date" onchange="validateLastDate()">
                                    <p id="lastdateerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Contact Number</label>
                                    <input type="text" name="phone" id="phone" placeholder="Enter contact number" onkeyup="validatePhone()">
                                    <p id="phoneerror" class="error"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Interview Option -->
                        <div class="form-section">
                            <h3 class="section-title">Interview Preference</h3>
                            <div class="radio-group">
                                <label>Interview Required?</label>
                                <div class="radio-options">
                                    <div class="radio-item">
                                        <input type="radio" id="yes" name="interview" value="yes">
                                        <label for="yes">Yes</label>
                                    </div>
                                    <div class="radio-item">
                                        <input type="radio" id="no" name="interview" value="no">
                                        <label for="no">No</label>
                                    </div>
                                </div>
                                <p id="interviewerror" class="error"></p>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-section submit-section">
                            <button type="submit" class="submit-btn">Post Job</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* Professional Form Styling */
        .content-card {
            background: #f8fafc;
            padding: 30px;
            border-radius: 16px;
        }

        .form-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
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

        .section-title::before {
            content: '';
            width: 4px;
            height: 24px;
            background: #3b82f6;
            border-radius: 2px;
            display: inline-block;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 500;
            color: #475569;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #1e293b;
            background-color: #fff;
            transition: all 0.2s ease;
        }

        input:hover, select:hover, textarea:hover {
            border-color: #cbd5e1;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        input::placeholder, select::placeholder, textarea::placeholder {
            color: #94a3b8;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.5;
        }

        .time-inputs {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .time-inputs select {
            flex: 1;
        }

        .time-separator {
            color: #64748b;
            font-weight: 500;
            margin: 0 4px;
        }

        .radio-group {
            padding: 12px 0;
        }

        .radio-options {
            display: flex;
            gap: 32px;
            margin-top: 12px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .radio-item input[type="radio"] {
            width: 20px;
            height: 20px;
            margin: 0;
            cursor: pointer;
            border: 2px solid #cbd5e1;
        }

        .radio-item input[type="radio"]:checked {
            border-color: #3b82f6;
            background-color: #3b82f6;
        }

        .radio-item label {
            margin: 0;
            cursor: pointer;
            user-select: none;
        }

        .submit-section {
            text-align: center;
            border: none;
            box-shadow: none;
            padding-top: 0;
        }

        .submit-btn {
            background-color: #3b82f6;
            color: white;
            padding: 14px 36px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
        }

        .submit-btn:hover {
            background-color: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .error {
            color: #dc2626;
            font-size: 0.85rem;
            margin-top: 6px;
            display: flex;
            align-items: center;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 28px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert::before {
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 1.1rem;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-success::before {
            content: '\f00c';
            color: #22c55e;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-error::before {
            content: '\f071';
            color: #ef4444;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .content-card {
                padding: 20px;
            }
            
            .form-container {
                padding: 10px;
            }
            
            .form-section {
                padding: 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .time-inputs {
                flex-direction: column;
            }
            
            .time-separator {
                display: none;
            }
            
            .submit-btn {
                width: 100%;
                padding: 16px;
            }
            
            .radio-options {
                flex-direction: column;
                gap: 16px;
            }
        }

        /* Optional: Add smooth scrolling for better UX */
        html {
            scroll-behavior: smooth;
        }

        /* Optional: Custom scrollbar for modern browsers */
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 6px;
            border: 3px solid #f1f5f9;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Add this to your existing style section */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            background: white;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            max-width: 400px;
            animation: slideIn 0.3s ease forwards;
            border-left: 4px solid #4CAF50;
        }

        .toast.error {
            border-left: 4px solid #dc2626;
        }

        .toast i {
            font-size: 24px;
            color: #4CAF50;
        }

        .toast.error i {
            color: #dc2626;
        }

        .toast-message {
            color: #1e293b;
            font-size: 15px;
            font-weight: 500;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    </style>

    <!-- Keep your existing scripts -->
    <script src="postjob.js"></script>
    <script>
    // Auto-hide alert messages after 3 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.querySelector('.alert');
        if (alert) {
            setTimeout(function() {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }, 3000);
        }
    });

    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toastContainer');
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        // Add icon based on type
        const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
        
        toast.innerHTML = `
            <i class="fas fa-${icon}"></i>
            <span class="toast-message">${message}</span>
        `;
        
        // Add toast to container
        toastContainer.appendChild(toast);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s ease forwards';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }

    // Show notification if there's a message in session
    <?php if(isset($_SESSION['message'])): ?>
        showToast(
            '<?php echo addslashes($_SESSION['message']); ?>', 
            '<?php echo $_SESSION['message_type'] ?? "success"; ?>'
        );
        <?php 
        // Clear the message after displaying
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>
    </script>
</body>
</html>