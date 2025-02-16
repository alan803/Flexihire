<?php
    $role=1;//user
    session_start();
    $name = $email = $password = $confirmpassword = $lastname=$dob="";
    $error = [];
    include '../database/connectdatabase.php';
    if ($_SERVER['REQUEST_METHOD'] == 'POST') 
    {
        $name = $_POST['name'];
        $lastname=$_POST['lastname'];
        $email = $_POST['email'];
        $dob=$_POST['dob'];
        $password = $_POST['password'];

        $dbname = "project";
        mysqli_select_db($conn, $dbname);

        // Check if email is already linked with an account
        $check_duplicate = "
        SELECT 'email' AS type FROM tbl_login WHERE email = ? 
        UNION 
        SELECT 'user' AS type FROM tbl_user WHERE first_name = ? AND last_name = ? AND dob = ?";

        $stmt_check = mysqli_prepare($conn, $check_duplicate);
        mysqli_stmt_bind_param($stmt_check, "ssss", $email, $name, $lastname, $dob);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) 
        {
            mysqli_stmt_bind_result($stmt_check, $duplicateType);
            mysqli_stmt_fetch($stmt_check);
            if ($duplicateType == 'email') 
            {
                $_SESSION['email_error'] = "This email is already registered!";
            } else 
            {
                $_SESSION['email_error'] = "User with this name and date of birth already exists!";
            }
            header("Location: signup.php");
            exit();
        }
        else 
        {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Insert into tbl_user
            $sql_user = "INSERT INTO tbl_user (first_name,last_name, dob) VALUES (?, ?, ?)";
            $stmt_user = mysqli_prepare($conn, $sql_user);
            mysqli_stmt_bind_param($stmt_user, "sss", $name, $lastname, $dob);
        
            if (mysqli_stmt_execute($stmt_user)) 
            {
                // fetches employer_id
                $user_id = mysqli_insert_id($conn);
                // Insert into tbl_login
                $sql_login = "INSERT INTO tbl_login (email, password, role,user_id) VALUES (?, ?, ?,?)";
                $stmt_login = mysqli_prepare($conn, $sql_login);
                mysqli_stmt_bind_param($stmt_login, "ssii",$email, $hashed_password, $role,$user_id);

                if (mysqli_stmt_execute($stmt_login)) 
                {
                    header("Location: ../Login/loginvalidation.php");
                    exit();
                } 
                else 
                {
                    echo "Error inserting into tbl_login: " . mysqli_error($conn);
                }
            } 
            else 
            {
                echo "Error inserting into tbl_user: " . mysqli_error($conn);
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account</title>
    <link rel="stylesheet" href="signup.css">
    <script>
        function validatename() 
        {
            const name = document.getElementById("name").value;
            const error = document.getElementById("nameError");
            if (!name) 
            {
                error.textContent = "Name is required."; //textcontent is used to get content from html element
                return false;
            } 
            else if (!/^[a-zA-Z]*$/.test(name)) 
            {
                error.textContent = "Should not contain digits or special characters or spaces";
                return false;
            } 
            else if (name.length < 4) 
            {
                error.textContent = "Name must be at least 5 characters.";
                return false;
            }
            error.textContent = "";//used to clear the error message that was previously displayed in the corresponding error <p> element
            return true;
        }

        function validatelastname()
        {
            const name = document.getElementById("lastname").value;
            const error = document.getElementById("lastnameerror");
            if (!name) 
            {
                error.textContent = "Last Name is required."; //textcontent is used to get content from html element
                return false;
            } 
            else if (!/^[a-zA-Z]*$/.test(name)) 
            {
                error.textContent = "Should not contain digits or special characters or spaces";
                return false;
            } 
            error.textContent = "";//used to clear the error message that was previously displayed in the corresponding error <p> element
            return true;
        }

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
            // const emailRegex = ;
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
        function validatedob() 
        {
            const dobField = document.getElementById("dob");//dobField stores the entire input element (<input type="date">).
            const error = document.getElementById("doberror");
            const dob = dobField.value;//dob stores just the user’s entered value (which is a string).

            error.textContent = ""; // Clear previous errors

            if (!dob) 
            {
                error.textContent = "Date of Birth is required.";
                return false;
            }

            const birthdate = new Date(dob);
            const today = new Date();// stores the current date so we can compare the entered DOB against today's date
            let age = today.getFullYear() - birthdate.getFullYear();//calculates actual age
            const monthdiff = today.getMonth() - birthdate.getMonth();
            const daydiff = today.getDate() - birthdate.getDate();

            // Adjust age if birthday hasn't occurred this year
            if (monthdiff < 0 || (monthdiff === 0 && daydiff < 0)) 
            {
                age--;
            }

            let isValid = true;

            // Future Date Check
            if (birthdate > today)
             {
                error.textContent += "Date of Birth cannot be in the future.";
                isValid = false;
            }

            // Age Check (Must be 18+)
            if (age < 18) 
            {
                error.textContent += "Must be at least 18 years old.";
                isValid = false;
            }

            return isValid; 
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
            const validname = validatename();
            const lastname=validatelastname();
            const validEmail = validateEmail();
            const dob=validatedob();
            const validPassword = validatePassword();
            const validConfirmPassword = validateConfirmPassword();
            return validname && lastname && validEmail && dob && validPassword && validConfirmPassword;//The && operator combines the results of all the validations. If any of the individual validations return false, the overall result will also be false.
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
            <p>Already have an account? <a href="../Login/loginvalidation.php" style="color: #665efc; text-decoration: none;">Log in</a></p>
            <form method="post" onsubmit="return validateForm()">
                <div style="display: flex; gap: 10px;">
                    <div>
                        <input type="text" id="name" name="name" placeholder="First Name" onkeyup="validatename()">
                        <p class="error" id="nameError"></p>
                    </div>
                    <div>
                        <input type="text" id="lastname" name="lastname" placeholder="Last Name" onkeyup="validatelastname()">
                        <p class="error" id="lastnameerror"></p>
                    </div>
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
                <div>
                    <input type="date" id="dob" name="dob" placeholder="Date of Birth" onkeyup="validatedob()">
                    <p class="error" id="doberror"></p>
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