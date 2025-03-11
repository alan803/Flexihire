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
    $vac_date=$print['vacancy_date'];
    $vac=$print['vacancy'];
    $sal=$print['salary'];
    $app_deadline=$print['application_deadline'];
    $itrview=$print['interview'];
    $phone=$print['contact_no'];
    $category=$print['category'];
    $license=$print['license_required'];
    $badge=$print['badge_required'];
    $town=$print['town'];
    $start_time=$print['start_time'];
    $end_time=$print['end_time'];
    $working_days=$print['working_days'];
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
        // Build the SQL query dynamically based on which fields have values
        $updateFields = array();
        $types = "";
        $params = array();

        // Helper function to add field if it has a value
        function addField(&$updateFields, &$types, &$params, $field, $value) {
            if (!empty($value)) {
                $updateFields[] = "$field=?";
                $types .= "s";
                $params[] = $value;
            }
        }

        // Add fields only if they have values
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
        
        // Special handling for fields that might be empty strings but valid
        if (isset($_POST['interview'])) {
            addField($updateFields, $types, $params, "interview", $_POST['interview']);
        }

        // Handle license_required and badge_required based on category
        if ($_POST['category'] === "Delivery and logistics") {
            if (isset($_POST['license_required'])) {
                addField($updateFields, $types, $params, "license_required", $_POST['license_required']);
            }
            if (isset($_POST['badge_required'])) {
                addField($updateFields, $types, $params, "badge_required", $_POST['badge_required']);
            }
        } else {
            // If category is not Delivery and logistics, set these fields to NULL
            $updateFields[] = "license_required=NULL";
            $updateFields[] = "badge_required=NULL";
        }

        if (isset($_POST['working_days'])) {
            addField($updateFields, $types, $params, "working_days", $_POST['working_days']);
        }
        if (!empty($_POST['start_time'])) {
            addField($updateFields, $types, $params, "start_time", $_POST['start_time']);
        }
        if (!empty($_POST['end_time'])) {
            addField($updateFields, $types, $params, "end_time", $_POST['end_time']);
        }

        // Add employer_id to the parameters
        $types .= "i";
        $params[] = $employer_id;

        if (!empty($updateFields)) {
            $sql = "UPDATE tbl_jobs SET " . implode(", ", $updateFields) . " WHERE employer_id=?";
            
            $stmt = $conn->prepare($sql);
            
            // Bind parameters dynamically
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }
        
        if ($stmt->execute()) {
            header("Location: myjoblist.php");
            exit();
            } else {
                header("Location: postjob.php?message=Error updating job.");
            exit();
        }
            
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
    <script src="editjob.js"></script>
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
            <input type="hidden" name="employer_id" value="<?php echo $employer_id; ?>">

            <select name="category" id="category" onchange="validatecategory()">
                <option value="">Select Category</option>
                <option value="Delivery and logistics" <?php if($category == "Delivery and logistics") echo "selected"; ?>>Delivery & Logistics</option>
                <option value="Hospitality and catering" <?php if($category == "Hospitality and catering") echo "selected"; ?>>Hospitality & Catering</option>
                <option value="Housekeeping and cleaning" <?php if($category == "Housekeeping and cleaning") echo "selected"; ?>>Housekeeping & Cleaning</option>
                <option value="Retail and store jobs" <?php if($category == "Retail and store jobs") echo "selected"; ?>>Retail & Store Jobs</option>
                <option value="Warehouse and factory jobs" <?php if($category == "Warehouse and factory jobs") echo "selected"; ?>>Warehouse & Factory Jobs</option>
                <option value="Maintenance" <?php if($category == "Maintenance") echo "selected"; ?>>Maintenance</option>
            </select>
            <p id="categoryerror" class="error"></p>

            <!-- Add this after the category select -->
            <div id="delivery-docs" style="display: <?php echo ($category == 'Delivery and logistics') ? 'block' : 'none'; ?>">
                <div class="upload-container">
                    <label>Driving License Required:</label>
                    <select name="license_required" id="license_required" class="doc-select">
                        <option value="">Select License Type</option>
                        <option value="two_wheeler" <?php if($license == "two_wheeler") echo "selected"; ?>>Two Wheeler License</option>
                        <option value="four_wheeler" <?php if($license == "four_wheeler") echo "selected"; ?>>Four Wheeler License</option>
                        <option value="both" <?php if($license == "both") echo "selected"; ?>>Both Required</option>
                        <option value="not_required" <?php if($license == "not_required") echo "selected"; ?>>Not Required</option>
                    </select>
                    <p id="licenseerror" class="error"></p>
                </div>

                <div class="upload-container">
                    <label>Badge Required:</label>
                    <select name="badge_required" id="badge_required" class="doc-select">
                        <option value="">Select Badge Requirement</option>
                        <option value="yes" <?php if($badge == "yes") echo "selected"; ?>>Yes</option>
                        <option value="no" <?php if($badge == "no") echo "selected"; ?>>No</option>
                    </select>
                    <p id="badgeerror" class="error"></p>
                </div>
            </div>
            <label>Job title</label>
            <input type="text" name="job_title" id="job_title" placeholder="Job title" value="<?php echo $title;?>" onkeyup="validateJobTitle()">
            <p id="titleerror" class="error"></p>

            <label>Job Description</label>
            <input type="text" name="job_description" id="job_description" placeholder="Job Description" value="<?php echo $description;?>" onkeyup="validateJobDescription()">
            <p id="descriptionerror" class="error"></p>

            <label>Location</label>
            <select name="location" id="location" onchange="showTowns()">
                <option value="">Select District</option>
                <option value="Thiruvananthapuram" <?php if($loc == "Thiruvananthapuram") echo "selected"; ?>>Thiruvananthapuram</option>
                <option value="Kollam" <?php if($loc == "Kollam") echo "selected"; ?>>Kollam</option>
                <option value="Pathanamthitta" <?php if($loc == "Pathanamthitta") echo "selected"; ?>>Pathanamthitta</option>
                <option value="Alappuzha" <?php if($loc == "Alappuzha") echo "selected"; ?>>Alappuzha</option>
                <option value="Kottayam" <?php if($loc == "Kottayam") echo "selected"; ?>>Kottayam</option>
                <option value="Idukki" <?php if($loc == "Idukki") echo "selected"; ?>>Idukki</option>
                <option value="Ernakulam" <?php if($loc == "Ernakulam") echo "selected"; ?>>Ernakulam</option>
                <option value="Thrissur" <?php if($loc == "Thrissur") echo "selected"; ?>>Thrissur</option>
                <option value="Palakkad" <?php if($loc == "Palakkad") echo "selected"; ?>>Palakkad</option>
                <option value="Malappuram" <?php if($loc == "Malappuram") echo "selected"; ?>>Malappuram</option>
                <option value="Kozhikode" <?php if($loc == "Kozhikode") echo "selected"; ?>>Kozhikode</option>
                <option value="Wayanad" <?php if($loc == "Wayanad") echo "selected"; ?>>Wayanad</option>
                <option value="Kannur" <?php if($loc == "Kannur") echo "selected"; ?>>Kannur</option>
                <option value="Kasaragod" <?php if($loc == "Kasaragod") echo "selected"; ?>>Kasaragod</option>
            </select>
            <p id="locationerror" class="error"></p>

            <!-- Town selection -->
            <label id="townLabel" style="display:none;">Select Town</label>
            <select name="town" id="tvm_towns" style="display:none;">
                <option value="">Select Town</option>
            </select>
            <p id="townerror" class="error"></p>

            <label>Vacancy date:</label>
            <input type="date" name="date" id="date" onchange="validateDate()" value="<?php echo $vac_date;?>" >
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
                <option value="part_time" <?php if($working_days == "part_time") echo "selected"; ?>>Part Time</option>
                <option value="full_day" <?php if($working_days == "full_day") echo "selected"; ?>>Full day</option>
                <option value="shift" <?php if($working_days == "shift") echo "selected"; ?>>Shift-based</option>
            </select>
            <p id="workingdayserror" class="error"></p>

            <label>Vacancy</label>
            <input type="text" name="vacancy" id="vacancy" placeholder="No of vacancy" value="<?php echo $vac;?>" onkeyup="validateVacancy()">
            <p id="vacancyerror" class="error"></p>

            <label>Salary</label>
            <input type="text" name="salary" id="salary" placeholder="Salary" value="<?php echo floor($sal);?>" onkeyup="validateSalary()">
            <p id="salaryerror" class="error"></p>

            <label>Application time limit:</label>
            <input type="date" name="last_date" id="last_date" value="<?php echo $app_deadline;?>" onchange="validateLastDate()">
            <p id="lastdateerror" class="error"></p>

            <label>Contact number</label>
            <input type="text" id="phone" name="phone"  value ="<?php echo $phone;?>" onkeyup="validatePhone()">
            <p id="phoneerror" class="error"></p>
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

            <button type="button" onclick="window.location.href='myjoblist.php'" id="cancel">Cancel</button>
            <input type="submit" value="Update">
        </form>
    </div>
    <style>
        .error 
        {
            color: red;
            font-size: 14px;
        }
        #cancel
        {
            cursor: pointer;
            background-color: #1e2a4a;
            color:white;
            width:200px;
            position: relative;
            left:150px;
        }
        input[type="submit"] 
        {
            cursor: pointer;
            background-color: #1e2a4a;
            color:white;
            width:200px;
            position: relative;
            left:190px;
        }
    </style>
    <script>
        // District-Town mapping
        const districtTowns = {
            'Thiruvananthapuram': ['Thiruvananthapuram', 'Neyyattinkara', 'Attingal', 'Varkala', 'Nedumangad', 'Kazhakkoottam', 'Kallambalam', 'Kovalam', 'Balaramapuram', 'Pothencode'],
            'Kollam': ['Kollam', 'Paravur', 'Punalur', 'Karunagappally', 'Kottarakkara', 'Chavara', 'Kundara', 'Anchal', 'Oachira', 'Sasthamkotta'],
            'Pathanamthitta': ['Pathanamthitta', 'Adoor', 'Thiruvalla', 'Ranni', 'Pandalam', 'Konni', 'Mallappally', 'Kozhencherry', 'Mannarakulanji', 'Seethathode'],
            'Alappuzha': ['Alappuzha', 'Chengannur', 'Kayamkulam', 'Mavelikkara', 'Haripad', 'Cherthala', 'Ambalappuzha', 'Thakazhi', 'Mannar', 'Edathua'],
            'Kottayam': ['Kottayam', 'Pala', 'Changanassery', 'Vaikom', 'Ettumanoor', 'Erattupetta', 'Kuravilangad', 'Kanjirappally', 'Pampady', 'Kidangoor'],
            'Idukki': ['Thodupuzha', 'Munnar', 'Adimali', 'Kumily', 'Kattappana', 'Nedumkandam', 'Vagamon', 'Devikulam', 'Peermade', 'Udumalpettai'],
            'Ernakulam': ['Kochi', 'Aluva', 'Angamaly', 'Muvattupuzha', 'Perumbavoor', 'Kothamangalam', 'North Paravur', 'Kakkanad', 'Piravom', 'Kaloor'],
            'Thrissur': ['Thrissur', 'Chalakudy', 'Kodungallur', 'Irinjalakuda', 'Guruvayur', 'Kunnamkulam', 'Wadakkanchery', 'Pavaratty', 'Kecheri', 'Mannuthy'],
            'Palakkad': ['Palakkad', 'Ottapalam', 'Chittur', 'Pattambi', 'Shoranur', 'Mannarkkad', 'Alathur', 'Nemmara', 'Cherpulassery', 'Kongad'],
            'Malappuram': ['Malappuram', 'Tirur', 'Ponnani', 'Manjeri', 'Perinthalmanna', 'Nilambur', 'Kottakkal', 'Parappanangadi', 'Edappal', 'Kondotty'],
            'Kozhikode': ['Kozhikode', 'Vadakara', 'Koyilandy', 'Ramanattukara', 'Feroke', 'Koduvally', 'Balussery', 'Mavoor', 'Chelannur', 'Thamarassery'],
            'Wayanad': ['Kalpetta', 'Sulthan Bathery', 'Mananthavady', 'Meenangadi', 'Panamaram', 'Vythiri', 'Pulpally', 'Ambalavayal', 'Muttil', 'Thariode'],
            'Kannur': ['Kannur', 'Thalassery', 'Payyanur', 'Mattannur', 'Iritty', 'Koothuparamba', 'Taliparamba', 'Kuthuparamba', 'Panoor', 'Chirakkal'],
            'Kasaragod': ['Kasaragod', 'Kanhangad', 'Nileshwar', 'Cheruvathur', 'Uppala', 'Manjeshwar', 'Periya', 'Hosdurg', 'Bekal', 'Mogral Puthur']
        };

        function showTowns() {
            const selectedDistrict = document.getElementById('location').value;
            const townSelect = document.getElementById('tvm_towns');
            const townLabel = document.getElementById('townLabel');
            const savedTown = '<?php echo $town; ?>';
            
            // Clear existing options
            townSelect.innerHTML = '<option value="">Select Town</option>';
            
            if (selectedDistrict) {
                // Show town dropdown and label immediately
                townSelect.style.display = 'block';
                townLabel.style.display = 'block';
                
                // Get towns for selected district
                const towns = districtTowns[selectedDistrict] || [];
                
                // Add town options
                towns.forEach(town => {
                    const option = document.createElement('option');
                    option.value = town;
                    option.textContent = town;
                    if (town === savedTown) {
                        option.selected = true;
                    }
                    townSelect.appendChild(option);
                });
            }
        }

        // Function to generate time options
        function generateTimeOptions(selectElement, selectedTime) {
            console.log('Generating options for time:', selectedTime); // Debug log
            
            selectElement.innerHTML = '<option value="">Select Time</option>';
            
            // Format the selectedTime to ensure proper comparison
            let formattedSelectedTime = selectedTime;
            if (selectedTime && selectedTime.length === 8) { // Handle HH:MM:SS format from database
                formattedSelectedTime = selectedTime.substring(0, 5); // Extract HH:MM part
            }
            
            console.log('Formatted time for comparison:', formattedSelectedTime); // Debug log

            for (let hour = 0; hour < 24; hour++) {
                for (let min = 0; min < 60; min += 30) {
                    let formattedHour = hour.toString().padStart(2, '0');
                    let formattedMinute = min.toString().padStart(2, '0');
                    let timeValue = `${formattedHour}:${formattedMinute}`;
                    let displayHour = hour % 12 || 12;
                    let amPm = hour < 12 ? "AM" : "PM";
                    let timeText = `${displayHour}:${formattedMinute} ${amPm}`;
                    
                    let option = document.createElement('option');
                    option.value = timeValue;
                    option.textContent = timeText;
                    
                    // Compare the formatted times
                    if (timeValue === formattedSelectedTime) {
                        console.log('Found matching time:', timeValue); // Debug log
                        option.selected = true;
                    }
                    selectElement.appendChild(option);
                }
            }
        }

        // Initialize everything when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize time selectors with saved values
            const startTime = '<?php echo $start_time; ?>'.trim();
            const endTime = '<?php echo $end_time; ?>'.trim();
            
            // Debug log to check the values
            console.log('Raw Start Time from PHP:', startTime);
            console.log('Raw End Time from PHP:', endTime);
            
            generateTimeOptions(document.getElementById('start-time'), startTime);
            generateTimeOptions(document.getElementById('end-time'), endTime);

            // Initialize town selection if location is already selected
            const selectedDistrict = document.getElementById('location').value;
            if(selectedDistrict) {
                // Show town dropdown and label
                document.getElementById('tvm_towns').style.display = 'block';
                document.getElementById('townLabel').style.display = 'block';
                showTowns();
            }

            // Show delivery docs if category is Delivery and logistics
            if('<?php echo $category; ?>' === 'Delivery and logistics') {
                document.getElementById('delivery-docs').style.display = 'block';
            }
        });
    </script>
</body>
</html>