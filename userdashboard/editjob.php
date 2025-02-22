<?php
    session_start();
    $employer_id=$_SESSION['employer_id'];
    include '../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);
    $error="";

    if (!isset($_SESSION['employer_id'])) 
    {
        header("Location: ../login/loginvalidation.php");
        exit();
    }
    // for displaying the already inputed value
    $sqlprint="SELECT * FROM tbl_jobs WHERE employer_id='$employer_id'";
    $resultprint=mysqli_query($conn,$sqlprint);
    $print=mysqli_fetch_assoc($resultprint);
    $title=$print['job_title'];
    $loc=$print['location'];
    $description=$print['job_description'];
    $wrk_hour=$print['working_hour'];
    $vac_date=$print['vacancy_date'];
    $vac=$print['vacancy'];
    $sal=$print['salary'];
    $app_deadline=$print['application_deadline'];
    $itrview=$print['interview'];

    // for inserting value
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
    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $job_title = $_POST['job_title'];
        $location = $_POST['location'];
        $job_description = $_POST['job_description'];
        $working_hour = $_POST['working_hour'];
        $vacancy_date = $_POST['date'];
        $vacancy = $_POST['vacancy'];
        $salary = $_POST['salary'];
        $application_deadline = $_POST['last_date'];
        $interview = isset($_POST['interview']) ? $_POST['interview'] : null;
    
        $stmt = $conn->prepare("UPDATE tbl_jobs SET job_title=?, location=?, job_description=?, working_hour=?, vacancy_date=?, vacancy=?, salary=?, application_deadline=?, interview=? WHERE employer_id=?");
        $stmt->bind_param("sssssssssi", $job_title, $location, $job_description, $working_hour, $vacancy_date, $vacancy, $salary, $application_deadline, $interview,$employer_id);
        
        if ($stmt->execute()) {
            // Redirect to the same page with a success message
            header("Location: myjoblist.php");
            exit();
        } 
        else 
        {
            // Redirect with an error message if something goes wrong
            header("Location: postjob.php?message=Error posting job.");
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
                <a>My Profile</a>
            </div>
            <div class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <a href="../login/logout.php">Logout</a>
            </div>
        </div>
    </div>
    <?php if (isset($_GET['message'])): ?>
        <div class="alert">
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <form method="post" id="add_job" onsubmit="return validateForm()">
            <input type="text" name="job_title" id="job_title" placeholder="Job title" value="<?php echo $title;?>" onkeyup="validateJobTitle()">
            <p id="titleerror" class="error"></p>

            <input type="text" name="location" id="location" placeholder="Location" value="<?php echo $loc;?>" onkeyup="validateLocation()">
            <p id="locationerror" class="error"></p>

            <input type="text" name="job_description" id="job_description" placeholder="Job Description" value="<?php echo $description;?>" onkeyup="validateJobDescription()">
            <p id="descriptionerror" class="error"></p>

            <input type="text" name="working_hour" id="working_hour" placeholder="Time limit of the job" value="<?php echo $wrk_hour;?>" onkeyup="validateWorkingHour()">
            <p id="workinghourerror" class="error"></p>

            <label>Vacancy date:</label>
            <input type="date" name="date" id="date" onchange="validateDate()" value="<?php echo $vac_date;?>" >
            <p id="dateerror" class="error"></p>

            <input type="text" name="vacancy" id="vacancy" placeholder="No of vacancy" value="<?php echo $vac;?>" onkeyup="validateVacancy()">
            <p id="vacancyerror" class="error"></p>

            <input type="text" name="salary" id="salary" placeholder="Salary" value="<?php echo floor($sal);?>" onkeyup="validateSalary()">
            <p id="salaryerror" class="error"></p>

            <label>Application time limit:</label>
            <input type="date" name="last_date" id="last_date" value="<?php echo $app_deadline;?>" onchange="validateLastDate()">
            <p id="lastdateerror" class="error"></p>

            <div class="form-group">
                <label for="interview">Interview?</label>
                <div class="radio-group">
                    <input type="radio" id="yes" name="interview" value="yes" <?php if ($itrview == "yes") echo "checked"; ?> >
                    <label id="yess">Yes</label>

                    <input type="radio" id="no" name="interview" value="no" <?php if ($itrview == "no") echo "checked"; ?> >
                    <label id="noo">No</label>

                </div>
                <p id="interviewerror" class="error"></p>
            </div>

            <input type="submit" value="Update">
        </form>
    </div>

    <script>
        function validateJobTitle() {
            const jobTitle = document.getElementById('job_title').value.trim();
            const error = document.getElementById("titleerror");

            if (!jobTitle) {
                error.textContent = "Job title is required.";
                return false;
            } else if (!/^[A-Za-z\s]+$/.test(jobTitle)) {
                error.textContent = "Only letters are allowed.";
                return false;
            }

            error.textContent = "";
            return true;
        }

        function validateLocation() {
            const location = document.getElementById('location').value.trim();
            const error = document.getElementById("locationerror");

            if (!location) {
                error.textContent = "Location is required.";
                return false;
            } else if (!/^[A-Za-z\s]+$/.test(location)) {
                error.textContent = "Only letters are allowed.";
                return false;
            }

            error.textContent = "";
            return true;
        }

        function validateJobDescription() 
        {
            const jobDescription = document.getElementById('job_description').value.trim();
            const error = document.getElementById("descriptionerror");

            if (!jobDescription) {
                error.textContent = "Job description is required.";
                return false;
            } else if (!/^[A-Za-z\s()]+$/.test(jobDescription)) { 
                error.textContent = "Only letters, spaces, and brackets () are allowed.";
                return false;
            }

            error.textContent = "";
            return true;
        }


        function validateWorkingHour() 
        {
            const workingHour = document.getElementById('working_hour').value.trim();
            const error = document.getElementById('workinghourerror');

            const regex = /^\d+\s*(hour|hours|week|weeks|month|months)$/i; // Accepts formats like "4 hours" or "3 weeks"

            if (!workingHour) {
                error.textContent = "Working hour is required.";
                return false;
            } else if (!regex.test(workingHour)) {
                error.textContent = "Should be in hour  or week or month.";
                return false;
            }

            error.textContent = "";
            return true;
        }


        function validateDate() 
        {
        const dateInput = document.getElementById('date').value;
        const error = document.getElementById('dateerror');

        if (!dateInput) {
            error.textContent = "Vacancy date is required.";
            return false;
        }

        const selectedDate = new Date(dateInput);
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Normalize time to avoid timezone issues

        const oneMonthLater = new Date(today);
        oneMonthLater.setMonth(oneMonthLater.getMonth() + 1); // One month from today

        // Check if the selected date is in the past
        if (selectedDate < today) {
            error.textContent = "The selected date cannot be in the past.";
            return false;
        }

        // Check if the selected date is more than 1 month ahead
        if (selectedDate > oneMonthLater) {
            error.textContent = "The selected date cannot be more than 1 month from today.";
            return false;
        }

        //If valid, clear error message
        error.textContent = "";
        return true;
    }


        function validateVacancy() 
        {
            const vacancy = document.getElementById('vacancy').value.trim();
            const error = document.getElementById('vacancyerror');

            if (!vacancy) {
                error.textContent = "Vacancy is required.";
                return false;
            } else if (!/^\d+$/.test(vacancy)) {
                error.textContent = "Only numbers are allowed.";
                return false;
            }

            error.textContent = "";
            return true;
        }

        function validateSalary() {
            const salary = document.getElementById('salary').value.trim();
            const error = document.getElementById("salaryerror");

            if (!salary) {
                error.textContent = "Salary is required.";
                return false;
            } else if (!/^\d+$/.test(salary)) {
                error.textContent = "Only numbers are allowed.";
                return false;
            }

            error.textContent = "";
            return true;
        }

        function validateLastDate() 
        {
            const lastDate = document.getElementById('last_date').value;
            const vacancyDate = document.getElementById('date').value;
            const error = document.getElementById('lastdateerror');

            if (!lastDate) {
                error.textContent = "Application last date is required.";
                return false;
            }

            const selectedLastDate = new Date(lastDate);
            const selectedVacancyDate = new Date(vacancyDate);
            const today = new Date();

            if (selectedLastDate < today) {
                error.textContent = "The selected last date cannot be in the past.";
                return false;
            } else if (vacancyDate && selectedLastDate > selectedVacancyDate) {
                error.textContent = "The application deadline must be before the vacancy date.";
                return false;
            } else if (vacancyDate && selectedLastDate.getTime() === selectedVacancyDate.getTime()) {
                error.textContent = "The application deadline and vacancy date cannot be the same.";
                return false;
            }

            error.textContent = "";
            return true;
        }



        function validateInterview() {
            const radios = document.getElementsByName("interview");
            const error = document.getElementById("interviewerror");
            let isChecked = false;

            for (let radio of radios) {
                if (radio.checked) {
                    isChecked = true;
                    break;
                }
            }

            if (!isChecked) {
                error.textContent = "Please select an interview option.";
                return false;
            }

            error.textContent = "";
            return true;
        }

        function validateForm() {
    let valid = true;  // Assume all fields are valid

    if (!validateJobTitle()) valid = false;
    if (!validateLocation()) valid = false;
    if (!validateJobDescription()) valid = false;
    if (!validateWorkingHour()) valid = false;
    if (!validateDate()) valid = false;
    if (!validateVacancy()) valid = false;
    if (!validateSalary()) valid = false;
    if (!validateLastDate()) valid = false;
    if (!validateInterview()) valid = false;

    return valid; // Prevents form submission if any validation fails
}

    </script>

    <style>
        .error {
            color: red;
            font-size: 14px;
        }
    </style>
</body>
</html>