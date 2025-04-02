<?php
session_start();
include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

// Check if employer is logged in
if (!isset($_SESSION['employer_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

$employer_id = $_SESSION['employer_id'];
$error = "";

// Get job_id from URL and validate it
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

if (!$job_id) {
    header("Location: myjoblist.php?error=Invalid job ID");
    exit();
}

// Fetch the specific job details
$sql = "SELECT * FROM tbl_jobs WHERE job_id = ? AND employer_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['employer_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$print = mysqli_fetch_assoc($result);

// Verify job exists and belongs to current employer
if (!$print) {
    header("Location: myjoblist.php?error=Job not found");
    exit();
}

// Debug output
echo "<!-- Debug: job_id = " . htmlspecialchars($job_id) . " -->";

// Initialize variables to avoid undefined errors
$title = $loc = $description = $vac_date = $vac = $sal = $app_deadline = $itrview = $phone = $category = $license = $badge = $town = $start_time = $end_time = $working_days = "";

if ($print) {
    $title = $print['job_title'];
    $loc = $print['location'];
    $description = $print['job_description'];
    $vac_date = $print['vacancy_date'];
    $vac = $print['vacancy'];
    $sal = $print['salary'];
    $app_deadline = $print['application_deadline'];
    $itrview = $print['interview'];
    $phone = $print['contact_no'];
    $category = $print['category'];
    $license = $print['license_required'];
    $badge = $print['badge_required'];
    $town = $print['town'];
    $start_time = $print['start_time'];
    $end_time = $print['end_time'];
    $working_days = $print['working_days'];
}

// Debug output
echo "<!-- Debug: Location = " . htmlspecialchars($loc) . " -->";
echo "<!-- Debug: Town = " . htmlspecialchars($town) . " -->";

// Fetch employer details
$sql = "SELECT u.company_name, l.email 
        FROM tbl_login AS l
        JOIN tbl_employer AS u ON l.employer_id = u.employer_id
        WHERE u.employer_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $employer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$employer_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$email = $employer_data['email'] ?? '';
$username = $employer_data['company_name'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // Build dynamic update query
    $updateFields = [];
    $types = "";
    $params = [];

    function addField(&$updateFields, &$types, &$params, $field, $value) {
        if (!empty($value)) {
            $updateFields[] = "$field=?";
            $types .= "s";
            $params[] = $value;
        }
    }

    // Collect input values
    addField($updateFields, $types, $params, "job_title", $_POST['job_title']);
    addField($updateFields, $types, $params, "location", $_POST['location']);
    addField($updateFields, $types, $params, "job_description", $_POST['job_description']);
    addField($updateFields, $types, $params, "vacancy_date", $_POST['date']);
    addField($updateFields, $types, $params, "vacancy", $_POST['vacancy']);
    addField($updateFields, $types, $params, "salary", $_POST['salary']);
    addField($updateFields, $types, $params, "application_deadline", $_POST['last_date']);
    addField($updateFields, $types, $params, "category", $_POST['category']);
    addField($updateFields, $types, $params, "contact_no", $_POST['phone']);
    addField($updateFields, $types, $params, "town", $_POST['town']);

    if (isset($_POST['interview'])) {
        addField($updateFields, $types, $params, "interview", $_POST['interview']);
    }

    if ($_POST['category'] === "Delivery and logistics") {
        if (isset($_POST['license_required'])) {
            addField($updateFields, $types, $params, "license_required", $_POST['license_required']);
        }
        if (isset($_POST['badge_required'])) {
            addField($updateFields, $types, $params, "badge_required", $_POST['badge_required']);
        }
    } else {
        $updateFields[] = "license_required=NULL";
        $updateFields[] = "badge_required=NULL";
    }

    addField($updateFields, $types, $params, "working_days", $_POST['working_days']);
    addField($updateFields, $types, $params, "start_time", $_POST['start_time']);
    addField($updateFields, $types, $params, "end_time", $_POST['end_time']);

    // Add job_id and employer_id for WHERE clause
    $types .= "ii"; // Add two more integer parameters
    $params[] = $job_id; // Add job_id
    $params[] = $_SESSION['employer_id']; // Add employer_id

    if (!empty($updateFields)) {
        $sql = "UPDATE tbl_jobs SET " . implode(", ", $updateFields) . 
               " WHERE job_id=? AND employer_id=?"; // Add WHERE clause with both conditions
        
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            if (mysqli_stmt_execute($stmt)) {
                $stmt->close();
                header("location: myjoblist.php?message=Job updated successfully");
                exit();
            } else {
                $stmt->close();
                header("Location: myjoblist.php?error=Error updating job");
                exit();
            }
        } else {
            header("Location: myjoblist.php?error=Database error");
            exit();
        }
    }
}

// Close database connection
// mysqli_close($conn);  // Remove this early close
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="employerdashboard.css">
</head>
<body>
    <!-- <div class="dashboard-container">
        
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
                         onerror="this.src='../assets/images/company-logo.png';">
                <?php else: ?>
                    <img src="../assets/images/company-logo.png" alt="AutoRecruits.in">
                <?php endif; ?>
            </div>
            <div class="company-info">
                <span><?php echo htmlspecialchars($username); ?></span>
                <span style="font-size: 13px; color: var(--light-text);"><?php echo htmlspecialchars($email); ?></span>
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
                <div class="nav-item active">
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
                <div class="nav-item">
                    <i class="fas fa-user"></i>
                    <a href="employer_profile.php">My Profile</a>
                </div>
                <div class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <a href="../login/logout.php">Logout</a>
                </div>
            </div>
        </div> -->
        <?php include 'sidebar.php'; ?>
        <!-- Main Content -->
        <div class="main-container">
            <div class="header">
                <h1>Edit Job - <?php echo htmlspecialchars($username); ?></h1>
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
                                        <option value="Delivery and logistics" <?php echo ($category == "Delivery and logistics") ? "selected" : ""; ?>>Delivery & Logistics</option>
                                        <option value="Hospitality and catering" <?php echo ($category == "Hospitality and catering") ? "selected" : ""; ?>>Hospitality & Catering</option>
                                        <option value="Housekeeping and cleaning" <?php echo ($category == "Housekeeping and cleaning") ? "selected" : ""; ?>>Housekeeping & Cleaning</option>
                                        <option value="Retail and store jobs" <?php echo ($category == "Retail and store jobs") ? "selected" : ""; ?>>Retail & Store Jobs</option>
                                        <option value="Warehouse and factory jobs" <?php echo ($category == "Warehouse and factory jobs") ? "selected" : ""; ?>>Warehouse & Factory Jobs</option>
                                        <option value="Maintenance" <?php echo ($category == "Maintenance") ? "selected" : ""; ?>>Maintenance</option>
                                    </select>
                                    <p id="categoryerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Job Title</label>
                                    <input type="text" name="job_title" id="job_title" placeholder="Enter job title" value="<?php echo $title;?>" onkeyup="validateJobTitle()">
                                    <p id="titleerror" class="error"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Documents Section -->
                        <div id="delivery-docs" class="form-section" style="display: <?php echo ($category == 'Delivery and logistics') ? 'block' : 'none'; ?>">
                            <h3 class="section-title">Required Documents</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Driving License Required</label>
                                    <select name="license_required" id="license_required">
                                        <option value="">Select License Type</option>
                                        <option value="two_wheeler" <?php echo ($license == "two_wheeler") ? "selected" : ""; ?>>Two Wheeler License</option>
                                        <option value="four_wheeler" <?php echo ($license == "four_wheeler") ? "selected" : ""; ?>>Four Wheeler License</option>
                                        <option value="both" <?php echo ($license == "both") ? "selected" : ""; ?>>Both Required</option>
                                        <option value="not_required" <?php echo ($license == "not_required") ? "selected" : ""; ?>>Not Required</option>
                                    </select>
                                    <p id="licenseerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Badge Required</label>
                                    <select name="badge_required" id="badge_required">
                                        <option value="">Select Badge Requirement</option>
                                        <option value="yes" <?php echo ($badge == "yes") ? "selected" : ""; ?>>Yes</option>
                                        <option value="no" <?php echo ($badge == "no") ? "selected" : ""; ?>>No</option>
                                    </select>
                                    <p id="badgeerror" class="error"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Job Description -->
                        <div class="form-section">
                            <h3 class="section-title">Job Description</h3>
                            <div class="form-group">
                                <textarea name="job_description" id="job_description" placeholder="Enter detailed job description" onkeyup="validateJobDescription()"><?php echo $description;?></textarea>
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
                                        <?php
                                        $districts = [
                                            'Thiruvananthapuram', 'Kollam', 'Pathanamthitta', 'Alappuzha', 
                                            'Kottayam', 'Idukki', 'Ernakulam', 'Thrissur', 'Palakkad', 
                                            'Malappuram', 'Kozhikode', 'Wayanad', 'Kannur', 'Kasaragod'
                                        ];
                                        foreach ($districts as $district) {
                                            $selected = ($loc == $district) ? 'selected' : '';
                                            echo "<option value='{$district}' {$selected}>{$district}</option>";
                                        }
                                        ?>
                                    </select>
                                    <p id="locationerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label id="townLabel" style="display:<?php echo $town ? 'block' : 'none'; ?>">Town</label>
                                    <select name="town" id="tvm_towns" style="display:<?php echo $town ? 'block' : 'none'; ?>">
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
                                    <input type="date" name="date" id="date" value="<?php echo $vac_date;?>" onchange="validateDate()">
                                    <p id="dateerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Working Hours</label>
                                    <div class="time-inputs">
                                        <select id="start-time" name="start_time" class="time-select">
                                            <option value="">Start Time</option>
                                        </select>
                                        <span class="time-separator">to</span>
                                        <select id="end-time" name="end_time" class="time-select">
                                            <option value="">End Time</option>
                                        </select>
                                    </div>
                                    <p id="timeerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Working Days</label>
                                    <select name="working_days" id="working_days" onchange="validateWorkingDays()">
                                        <option value="">Select Working Days</option>
                                        <option value="part_time" <?php if($working_days == "part_time") echo "selected"; ?>>Part Time</option>
                                        <option value="full_day" <?php if($working_days == "full_day") echo "selected"; ?>>Full Day</option>
                                        <option value="shift" <?php if($working_days == "shift") echo "selected"; ?>>Shift-based</option>
                                    </select>
                                    <p id="workingdayserror" class="error"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Details -->
                        <div class="form-section">
                            <h3 class="section-title">Additional Details</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Number of Vacancies</label>
                                    <input type="text" name="vacancy" id="vacancy" placeholder="Enter number of vacancies" value="<?php echo $vac;?>" onkeyup="validateVacancy()">
                                    <p id="vacancyerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Salary</label>
                                    <input type="text" name="salary" id="salary" placeholder="Enter salary" value="<?php echo floor($sal);?>" onkeyup="validateSalary()">
                                    <p id="salaryerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Application Deadline</label>
                                    <input type="date" name="last_date" id="last_date" value="<?php echo $app_deadline;?>" onchange="validateLastDate()">
                                    <p id="lastdateerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Contact Number</label>
                                    <input type="text" name="phone" id="phone" placeholder="Enter contact number" value="<?php echo $phone;?>" onkeyup="validatePhone()">
                                    <p id="phoneerror" class="error"></p>
                                </div>

                                <div class="form-group">
                                    <label>Interview Required?</label>
                                    <div class="radio-options">
                                        <div class="radio-item">
                                            <input type="radio" id="yes" name="interview" value="yes" <?php if ($itrview == "yes") echo "checked"; ?>>
                                            <label for="yes">Yes</label>
                                        </div>
                                        <div class="radio-item">
                                            <input type="radio" id="no" name="interview" value="no" <?php if ($itrview == "no") echo "checked"; ?>>
                                            <label for="no">No</label>
                                        </div>
                                    </div>
                                    <p id="interviewerror" class="error"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="form-section submit-section">
                            <div class="button-group">
                                <button type="button" onclick="window.location.href='myjoblist.php'" class="cancel-btn">Cancel</button>
                                <button type="submit" class="submit-btn">Update Job</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Include the same CSS from postjob.php -->
    <style>
        /* Main container adjustments */
        .main-container {
            margin-left: 280px; /* Sidebar width */
            margin-top: 60px;  /* Navbar height */
            padding: 20px;
            min-height: calc(100vh - 60px);
            background: #f8f9fa;
        }

        /* Content card adjustments */
        .content-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }

        /* Header adjustments */
        .header {
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 24px;
            color: #333;
            margin: 0;
        }

        /* Form container adjustments */
        .form-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Alert message positioning */
        .alert {
            margin-bottom: 20px;
        }

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
            padding: 20px 0 0;
            border: none;
            box-shadow: none;
        }

        .button-group {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 20px;
        }

        .submit-btn, .cancel-btn {
            padding: 14px 36px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .submit-btn {
            background-color: #3b82f6;
            color: white;
        }

        .cancel-btn {
            background-color: #ef4444;
            color: white;
        }

        .submit-btn:hover, .cancel-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .submit-btn:hover {
            background-color: #2563eb;
        }

        .cancel-btn:hover {
            background-color: #dc2626;
        }

        .submit-btn:active, .cancel-btn:active {
            transform: translateY(0);
        }

        .error {
            color: #dc2626;
            font-size: 0.85rem;
            margin-top: 6px;
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

        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
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
            
            .button-group {
                flex-direction: column;
                gap: 16px;
            }
            
            .submit-btn, .cancel-btn {
                width: 100%;
                padding: 16px;
            }
        }

        /* Custom scrollbar */
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
    </style>

    <!-- Include the same JavaScript for alert auto-hide -->
    <script>
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
    </script>

    <!-- Keep your existing script includes -->
    <script src="editjob.js"></script>
</body>
</html>
<?php
// Close the connection here, after all database operations are complete
mysqli_close($conn);
?>