<?php
session_start();
$employer_id = $_SESSION['employer_id'];
include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);
$error = "";

if (!isset($_SESSION['employer_id'])) {
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

if ($result && mysqli_num_rows($result) > 0) {
    $employer_data = mysqli_fetch_array($result);
    $email = $employer_data['email'];
    $username = $employer_data['company_name'];
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $job_title = $_POST['job_title'];
    $location = $_POST['location']; 
    $town = $_POST['town']; // Capturing the selected town
    $job_description = $_POST['job_description'];
    $working_hour = $_POST['working_hour'];
    $vacancy_date = $_POST['date'];
    $vacancy = $_POST['vacancy'];
    $salary = $_POST['salary'];
    $application_deadline = $_POST['last_date'];
    $interview = isset($_POST['interview']) ? $_POST['interview'] : null;

    $stmt = $conn->prepare("INSERT INTO tbl_jobs (job_title, location, town, job_description, working_hour, vacancy_date, vacancy, salary, application_deadline, interview, employer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssi", $job_title, $location, $town, $job_description, $working_hour, $vacancy_date, $vacancy, $salary, $application_deadline, $interview, $employer_id);
    
    if ($stmt->execute()) {
        header("Location: postjob.php?message=Job added successfully!");
        exit();
    } else {
        header("Location: postjob.php?message=Error posting job.");
        exit();
    }

    if (isset($stmt) && $stmt !== false) {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <style>
        .dropdown-menu {
            min-width: 200px;
        }
        .dropdown-submenu {
            position: relative;
        }
        .dropdown-submenu > .dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: -5px;
            display: none;
        }
        .dropdown-submenu:hover > .dropdown-menu {
            display: block;
        }
        .dropdown-toggle::after {
            float: right;
            margin-top: 7px;
        }
        .error {
            color: red;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo-container">
            <img src="logo.png" alt="AutoRecruits.in">
        </div>
        <div class="company-info">
            <span><?php echo $username;?></span>
        </div>
        <nav class="nav-menu">
            <div class="nav-item active">
                <i class="fas fa-th-large"></i>
                <a href="employerdashboard.php" id="link">Home</a>
            </div>
            <div class="nav-item">
                <i class="fas fa-plus-circle"></i>
                <a href="postjob.php" id="link">Post a Job</a>
            </div>
            <div class="nav-item">
                <i class="fas fa-briefcase"></i>
                <a href="myjoblist.php" id="link">My Jobs</a>
            </div>
            <div class="nav-item">
                <i class="fas fa-calendar-check"></i>
                <a id="link">Interviews</a>
            </div>
        </nav>
        <div class="settings-section">
            <div class="nav-item">
                <i class="fas fa-user"></i>
                <a id="link">My Profile</a>
            </div>
            <div class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <a href="../login/logout.php" id="link">Logout</a>
            </div>
        </div>
    </div>

    <div class="form-container">
        <form method="post" id="add_job" onsubmit="return validateForm()">
            <input type="text" name="job_title" id="job_title" placeholder="Job title" onkeyup="validateJobTitle()">
            <p id="titleerror" class="error"></p>

            <!-- Kerala District & Town Dropdown -->
            <label for="location">Select Location:</label>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="districtDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    Select District
                </button>
                <ul class="dropdown-menu" aria-labelledby="districtDropdown">
                    <li class="dropdown-submenu">
                        <a class="dropdown-item dropdown-toggle" href="#">Thiruvananthapuram</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Neyyattinkara</a></li>
                            <li><a class="dropdown-item" href="#">Attingal</a></li>
                            <li><a class="dropdown-item" href="#">Varkala</a></li>
                        </ul>
                    </li>
                    <li class="dropdown-submenu">
                        <a class="dropdown-item dropdown-toggle" href="#">Ernakulam</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Aluva</a></li>
                            <li><a class="dropdown-item" href="#">Kochi</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            
            <input type="text" name="job_description" id="job_description" placeholder="Job Description" onkeyup="validateJobDescription()">
            <p id="descriptionerror" class="error"></p>

            <input type="text" name="working_hour" id="working_hour" placeholder="Time limit of the job" onkeyup="validateWorkingHour()">
            <p id="workinghourerror" class="error"></p>

            <label>Vacancy date:</label>
            <input type="date" name="date" id="date" onchange="validateDate()">
            <p id="dateerror" class="error"></p>

            <input type="text" name="vacancy" id="vacancy" placeholder="No of vacancy" onkeyup="validateVacancy()">
            <p id="vacancyerror" class="error"></p>

            <input type="text" name="salary" id="salary" placeholder="Salary" onkeyup="validateSalary()">
            <p id="salaryerror" class="error"></p>

            <input type="submit" value="Add Job">
        </form>
    </div>
</body>
</html>
