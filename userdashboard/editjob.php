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
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: myjoblist.php?message=Job updated successfully");
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
    <div class="form-container">
        <form method="post" id="add_job" onsubmit="return validateForm()">
            <input type="hidden" name="employer_id" value="<?php echo $employer_id; ?>">
            <?php if (isset($_GET['message'])): ?>
                <div class="alert" id="alertMessage">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>
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

            <!-- Add this after the category select -->
            <div id="delivery-docs" style="display: <?php echo ($category == 'Delivery and logistics') ? 'block' : 'none'; ?>">
                <div class="upload-container">
                    <label>Driving License Required:</label>
                    <select name="license_required" id="license_required" class="doc-select">
                        <option value="">Select License Type</option>
                        <option value="two_wheeler" <?php echo ($license == "two_wheeler") ? "selected" : ""; ?>>Two Wheeler License</option>
                        <option value="four_wheeler" <?php echo ($license == "four_wheeler") ? "selected" : ""; ?>>Four Wheeler License</option>
                        <option value="both" <?php echo ($license == "both") ? "selected" : ""; ?>>Both Required</option>
                        <option value="not_required" <?php echo ($license == "not_required") ? "selected" : ""; ?>>Not Required</option>
                    </select>
                    <p id="licenseerror" class="error"></p>
                </div>

                <div class="upload-container">
                    <label>Badge Required:</label>
                    <select name="badge_required" id="badge_required" class="doc-select">
                        <option value="">Select Badge Requirement</option>
                        <option value="yes" <?php echo ($badge == "yes") ? "selected" : ""; ?>>Yes</option>
                        <option value="no" <?php echo ($badge == "no") ? "selected" : ""; ?>>No</option>
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
            console.log('showTowns function called'); // Debug log
            const selectedDistrict = document.getElementById('location').value;
            const townSelect = document.getElementById('tvm_towns');
            const townLabel = document.getElementById('townLabel');
            const savedTown = '<?php echo $town; ?>';
            
            console.log('Selected District:', selectedDistrict); // Debug log
            console.log('Saved Town:', savedTown); // Debug log
            
            // Clear existing options
            townSelect.innerHTML = '<option value="">Select Town</option>';
            
            if (selectedDistrict) {
                // Show town dropdown and label
                townSelect.style.display = 'block';
                townLabel.style.display = 'block';
                
                // Get towns for selected district
                const towns = districtTowns[selectedDistrict] || [];
                console.log('Available Towns:', towns); // Debug log
                
                // Add town options
                towns.forEach(town => {
                    const option = document.createElement('option');
                    option.value = town;
                    option.textContent = town;
                    if (town === savedTown) {
                        option.selected = true;
                        console.log('Setting selected town:', town); // Debug log
                    }
                    townSelect.appendChild(option);
                });
            } else {
                // Hide town dropdown and label if no district is selected
                townSelect.style.display = 'none';
                townLabel.style.display = 'none';
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
            console.log('DOM Content Loaded'); // Debug log
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

        function validatecategory() {
            const category = document.getElementById('category').value;
            const deliveryDocs = document.getElementById('delivery-docs');
            
            console.log('Selected category:', category); // Debug log
            
            if (category === 'Delivery and logistics') {
                deliveryDocs.style.display = 'block';
            } else {
                deliveryDocs.style.display = 'none';
                // Reset delivery-specific fields
                if (document.getElementById('license_required')) {
                    document.getElementById('license_required').value = '';
                }
                if (document.getElementById('badge_required')) {
                    document.getElementById('badge_required').value = '';
                }
            }
        }

        // Call validatecategory on page load
        document.addEventListener('DOMContentLoaded', function() {
            validatecategory();
        });
    </script>
</body>
</html>
<?php
// Close the connection here, after all database operations are complete
mysqli_close($conn);
?>