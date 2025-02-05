<?php
    $role=2;//employer
    session_start();  // Ensure session is started before using session variables
    include '../database/connectdatabase.php';

    $email = $password = $confirmpassword = $companyname = $district = "";
    $error = [];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') 
    {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirmpassword = $_POST['confirmpassword'];
        $companyname = trim($_POST['companyname']);
        $district = $_POST['districtSelect'];

        // Select the database
        $dbname = "project";
        mysqli_select_db($conn, $dbname);

        // Check if the email is already registered
        $check_email_query = "SELECT email FROM tbl_employer WHERE email=?
                    UNION
                    SELECT email FROM tbl_login WHERE email=?";
        $stmt = mysqli_prepare($conn, $check_email_query);

        mysqli_stmt_bind_param($stmt, "ss", $email, $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) 
        {
            $_SESSION['email_error'] = "This email is already registered!";
        } 
        else 
        {
            // Ensure passwords match
            if ($password !== $confirmpassword) 
            {
                $_SESSION['password_error'] = "Passwords do not match!";
            } 
            else 
            {
                // Hash the password for security
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new employer
            $insert_query = "INSERT INTO tbl_employer (email, company_name, district, password) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "ssss", $email, $companyname, $district, $hashed_password);

            if (mysqli_stmt_execute($stmt)) 
            {
                $sql_login = "INSERT INTO tbl_login (username, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt_login = mysqli_prepare($conn, $sql_login);
                mysqli_stmt_bind_param($stmt_login, "sssi", $companyname, $email, $hashed_password, $role);
                // Redirect on successful registration
                if (mysqli_stmt_execute($stmt_login))
                {
                    header("Location: ../userdashboard/employerdashboard.html");
                    exit();
                }
                else
                {
                    echo "Error inserting into tbl_login: " . mysqli_error($conn);
                }
            } else 
            {
                exit();
            }
        }
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account</title>
    <link rel="stylesheet" href="employersignup.css">
    <script>
        

        function validateEmail() 
        {
            const email = document.getElementById("email").value;
            const error = document.getElementById("emailError");

            // Check if email is empty
            if (!email) 
            {
                error.textContent = "E-mail is required.";
                return false;
            }

            // Check if email starts with a space or invalid character
            if (email[0] === " ") 
            {
                 error.textContent = "E-mail must start with a letter.";
                return false;
            }

            // Check if email has valid format and domain
        if (!/^[a-zA-Z0-9][^\s@]*@(gmail\.com|yahoo\.com|hotmail\.com|amaljyothi\.ac\.in|mca\.ajce\.in)$/.test(email)) 
        {
            // Check if it's the domain that's invalid
            if (email.includes('@')) 
            {
                const domain = email.split('@')[1];
                if (domain !== 'gmail.com' && domain !== 'yahoo.com') 
                {
                    error.textContent = "Invalid domain";
                    return false;
                }
            }
            error.textContent = "Invalid email address.";
            return false;
        }

    // Clear error message if all validations pass
        error.textContent = "";
        return true;
    }
        function validateCompanyName() 
        {
            const companyname = document.getElementById("companyname").value;
            const error = document.getElementById("companynameError");
            if (!companyname) 
            {
                error.textContent = "Company name is required.";
                return false;
            }
            // Check maximum length (50 characters)
            if (companyname.length > 50) 
            {
                error.textContent = "Company name cannot exceed 50 characters";
                return false;
            }
            // Check for valid characters (letters, numbers, spaces, and common symbols)
            const validFormat = /^[a-zA-Z\s&'-\.]+$/;
            if (!validFormat.test(companyname)) 
            {
                error.textContent = "Company name contains invalid characters";
                return false;
            }
    
            // Check if it starts with a letter
            if (!/^[a-zA-Z]/.test(companyname)) 
            {
                error.textContent = "Company name must start with a letter";
                return false;
            }
            error.textContent = "";
            return true;
        }



        function validateDistrict() 
        {
            const district = document.getElementById("districtSelect").value;
            const error = document.getElementById("districtError");
            if (!district || district === "") 
            {
                error.textContent = "Please select a district";
                return false;
            } 
            else 
            {
                error.textContent = ""; // Clear error message when valid selection is made
                return true;
            }
        }

        function validatePassword() 
        {
            const password = document.getElementById("password").value;
            const error = document.getElementById("passwordError");
            if (!password) 
            {
                error.textContent = "Password is required.";
                return false;
            } 
            else if (password.length < 6) 
            {
                error.textContent = "Password must be at least 6 characters.";
                return false;
            } 
            else if (!/[^a-zA-Z0-9]/.test(password)) 
            {
                error.textContent = "Password should  contain atleast one special character.";
                return false;
            }
            error.textContent = "";
            return true;
        }

        function validateConfirmPassword() 
        {
            const password = document.getElementById("password").value;
            const confirmpassword = document.getElementById("confirmpassword").value;
            const error = document.getElementById("confirmpasswordError");
            if (!confirmpassword) 
            {
                error.textContent = "Confirm password is required.";
                return false;
            } 
            else if (confirmpassword !== password) 
            {
                error.textContent = "Passwords do not match.";
                return false;
            }
            error.textContent = "";
            return true;
        }

        function validateForm() // It ensures that all fields in the form meet their respective validation criteria before allowing the form to be submitted.
        {
            const validEmail = validateEmail();
            const validCompanyName = validateCompanyName();
            const isDistrictValid = validateDistrict();
            const validPassword = validatePassword();
            const validConfirmPassword = validateConfirmPassword();
            return  validEmail && validCompanyName && isDistrictValid && validPassword && validConfirmPassword;//The && operator combines the results of all the validations. If any of the individual validations return false, the overall result will also be false.
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <a href="../homepage/homepage.html" class="back-to-website"><span class="arrow-circle">←</span> Back to website</a>
        </div>
        <div class="right-section">
            <h2>Create an account</h2>
            <button id="user1"><a href="../Signup/signup.php" id="user">User</a></button>
            <button id="employer1"><a href="../Signup/employersignup.php" id="employer">Employer</a></button>
            <p>Already have an account? <a href="../Login/login.php" style="color: #665efc; text-decoration: none;">Log in</a></p>
            <form method="post" onsubmit="return validateForm()">
                <div>
                    <input type="text" id="companyname" name="companyname" placeholder="Company Name"  onkeyup="validateCompanyName()">
                    <p class="error" id="companynameError"></p>
                </div>
                <div>
                    <input type="email" id="email" name="email" placeholder="E-mail"  onkeyup="validateEmail()">
                    <p class="error" id="emailError">
                    <?php
                            if(isset($_SESSION['email_error']))
                            {
                                echo $_SESSION['email_error'];
                                unset($_SESSION['email_error']);
                            }
                    ?>
                    </p>
                </div>
                <div class="form-group">
                    <select id="districtSelect" name="districtSelect" class="form-control">
                        <option value="" disabled selected>Select District</option>
                        <option value="ALP">Alappuzha</option>
                        <option value="ERN">Ernakulam</option>
                        <option value="IDK">Idukki</option>
                        <option value="KNR">Kannur</option>
                        <option value="KSR">Kasaragod</option>
                        <option value="KLM">Kollam</option>
                        <option value="KTM">Kottayam</option>
                        <option value="KKD">Kozhikode</option>
                        <option value="MLP">Malappuram</option>
                        <option value="PKD">Palakkad</option>
                        <option value="PTA">Pathanamthitta</option>
                        <option value="TVM">Thiruvananthapuram</option>
                        <option value="TSR">Thrissur</option>
                        <option value="WYD">Wayanad</option>
                    </select>
                    <span id="districtError" class="error"></span>
                </div>
                <div>
                    <input type="password" id="password" name="password" placeholder="Enter your password"  onkeyup="validatePassword()">
                    <p class="error" id="passwordError"></p>
                </div>
                <div>
                    <input type="password" id="confirmpassword" name="confirmpassword" placeholder="Confirm your password"  onkeyup="validateConfirmPassword()">
                    <p class="error" id="confirmpasswordError"></p>
                </div>
                <button type="submit">Create Account</button>
            </form>
        </div>
    </div>
</body>
</html>