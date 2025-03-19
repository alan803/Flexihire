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

    $sql = "SELECT u.company_name, l.email 
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
        
        // Update the SQL statement to handle NULL values
        $stmt = $conn->prepare("INSERT INTO tbl_jobs (category, job_title, location, job_description, vacancy_date, vacancy, salary, application_deadline, interview, employer_id, town, start_time, end_time, working_days, contact_no, license_required, badge_required) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Use "sssssssssissssss" instead of "sssssssssssssssss" to handle NULL values
        $stmt->bind_param("sssssssssississss", $category, $job_title, $location, $job_description, $vacancy_date, $vacancy, $salary, $application_deadline, $interview, $employer_id, $town, $start_time, $end_time, $working_days, $contact, $license, $badge);
        
        if ($stmt->execute()) 
        {
            header("Location: postjob.php?message=Job added successfully!");
            exit();
        } 
        else 
        {
            header("Location: postjob.php?message=Error posting job: " . $stmt->error);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="postjob.css">
    <script src="postjob.js"></script>
</head>
<body>
    <div class="sidebar">
        <div class="logo-container">
            <?php 
            // Fetch profile image
            $sql_profile = "SELECT profile_image FROM tbl_employer WHERE employer_id = ?";
            $stmt_profile = mysqli_prepare($conn, $sql_profile);
            mysqli_stmt_bind_param($stmt_profile, "i", $employer_id);
            mysqli_stmt_execute($stmt_profile);
            $result_profile = mysqli_stmt_get_result($stmt_profile);
            $profile_data = mysqli_fetch_assoc($result_profile);
            ?>
            <?php if(!empty($profile_data['profile_image'])): ?>
                <img src="<?php echo htmlspecialchars($profile_data['profile_image']); ?>" 
                     alt="<?php echo htmlspecialchars($username); ?>"
                     onerror="this.src='company-logo.png';">
            <?php else: ?>
                <img src="company-logo.png" alt="AutoRecruits.in">
            <?php endif; ?>
        </div>
        <div class="company-info">
            <span style="position:relative; left:-70px;font-size:15px;"><?php echo htmlspecialchars($username); ?></span>
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
    <div class="form-container">
        <form method="post" id="add_job" onsubmit="return validateForm()">
            <?php if (isset($_GET['message'])): ?>
                <div class="alert" id="alertMessage">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>
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

            <!-- Add this after the category select -->
            <div id="delivery-docs" style="display: none;">
                <div class="upload-container">
                    <label>Driving License Required:</label>
                    <select name="license_required" id="license_required" class="doc-select">
                        <option value="">Select License Type</option>
                        <option value="two_wheeler">Two Wheeler License</option>
                        <option value="four_wheeler">Four Wheeler License</option>
                        <option value="both">Both Required</option>
                        <option value="not_required">Not Required</option>
                    </select>
                    <p id="licenseerror" class="error"></p>
                </div>

                <div class="upload-container">
                    <label>Badge Required:</label>
                    <select name="badge_required" id="badge_required" class="doc-select">
                        <option value="">Select Badge Requirement</option>
                        <option value="yes">Yes</option>
                        <option value="no">No</option>
                    </select>
                    <p id="badgeerror" class="error"></p>
                </div>
            </div>
            <label>Job title</label>
            <input type="text" name="job_title" id="job_title" placeholder="Job title" onkeyup="validateJobTitle()">
            <p id="titleerror" class="error"></p>

            <label>Job Description</label>
            <input type="text" name="job_description" id="job_description" placeholder="Job Description" onkeyup="validateJobDescription()">
            <p id="descriptionerror" class="error"></p>

            <!-- Location selection -->
            <label>Location</label>
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

            <!-- Town selection -->
            <label id="townLabel" style="display:none;">Select Town</label>
            <select name="town" id="tvm_towns" style="display:none;">
                <option value="">Select Town</option>
            </select>
            <p id="townerror" class="error"></p>

            <label>Vacancy date:</label>
            <input type="date" name="date" id="date" onchange="validateDate()">
            <p id="dateerror" class="error"></p>

            <label>Working hours</label>
            <div class="time-container">
                <div class="time-selects">
                    <select id="start-time" name="start_time" class="time-select">
                        <option value="">Select Start Time</option>
                    </select>

                    <span class="separator">to</span>

                    <select id="end-time" name="end_time" class="time-select">
                        <option value="">Select End Time</option>
                    </select>
                </div>
                <p id="timeerror" class="error"></p>
            </div>

            <label>Working days</label>
            <select name="working_days" id="working_days" onchange="validateWorkingDays()">
                <option value="">Select Working Days</option>
                <option value="part_time">Part Time</option>
                <option value="full_day">Full day</option>
                <option value="shift">Shift-based</option>
            </select>
            <p id="workingdayserror" class="error"></p>

            <label>vacancy</label>
            <input type="text" name="vacancy" id="vacancy" placeholder="No of vacancy" onkeyup="validateVacancy()">
            <p id="vacancyerror" class="error"></p>

            <label>Salary</label>
            <input type="text" name="salary" id="salary" placeholder="Salary" onkeyup="validateSalary()">
            <p id="salaryerror" class="error"></p>

            <label>Application time limit:</label>
            <input type="date" name="last_date" id="last_date" onchange="validateLastDate()">
            <p id="lastdateerror" class="error"></p>

            <label>Contact number</label>
            <input type="text" id="phone" name="phone" placeholder="Phone number" onkeyup="validatePhone()">
            <p id="phoneerror" class="error"></p>

            <div class="form-group">
                <label for="interview">Interview?</label>
                <div class="radio-group">
                    <input type="radio" id="yes" name="interview" value="yes">
                    <label id="yess">Yes</label>

                    <input type="radio" id="no" name="interview" value="no">
                    <label id="noo">No</label>
                </div>
                <p id="interviewerror" class="error"></p>
            </div>
            <input type="submit" value="Add Job">
        </form>
    </div>
    <script>
        // Auto-hide alert message
        document.addEventListener('DOMContentLoaded', function() {
            const alertMessage = document.getElementById('alertMessage');
            if (alertMessage) {
                setTimeout(() => {
                    alertMessage.style.opacity = '0';
                    alertMessage.style.transition = 'opacity 1.0s ease';
                    setTimeout(() => {
                        alertMessage.remove();
                        // Remove the message parameter from URL
                        const url = new URL(window.location.href);
                        url.searchParams.delete('message');
                        window.history.replaceState({}, '', url);
                    }, 500);
                }, 5000); // Changed to 5000ms (5 seconds)
            }
        });
    </script>
</body>
</html>