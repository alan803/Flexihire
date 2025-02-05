<?php
    $role=1;//user
    session_start();
    $name = $email = $password = $confirmpassword = "";
    $error = [];
    include '../database/connectdatabase.php';
    if ($_SERVER['REQUEST_METHOD'] == 'POST') 
    {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        $dbname = "project";
        mysqli_select_db($conn, $dbname);

        // Check if email is already linked with an account
        $check_email = "SELECT * FROM tbl_user WHERE email='$email' OR email='$email'
                    UNION
                    SELECT * FROM tbl_login WHERE email='$email'";
        $result = mysqli_query($conn, $check_email);
            

        if (mysqli_num_rows($result) > 0) 
        {
            $_SESSION['email_error'] = "This email is already registered!";
        } 
        else 
        {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert into tbl_user
            $sql_user = "INSERT INTO tbl_user (name, email, password) VALUES (?, ?, ?)";
            $stmt_user = mysqli_prepare($conn, $sql_user);
            mysqli_stmt_bind_param($stmt_user, "sss", $name, $email, $hashed_password);
        
            if (mysqli_stmt_execute($stmt_user)) 
            {
                // Insert into tbl_login
                $sql_login = "INSERT INTO tbl_login (username, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt_login = mysqli_prepare($conn, $sql_login);
                mysqli_stmt_bind_param($stmt_login, "sssi", $name, $email, $hashed_password, $role);

                if (mysqli_stmt_execute($stmt_login)) 
                {
                    header("Location: ../userdashboard/userdashboard.html");
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
        function validateName() 
        {
            const name = document.getElementById("name").value;
            const error = document.getElementById("nameError");
            if (!name) 
            {
                error.textContent = "Name is required."; //textcontent is used to get content from html element
                return false;
            } 
            else if (!/^[a-zA-Z\s]*$/.test(name)) 
            {
                error.textContent = "Name should not contain digits or special characters.";
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
            const validName = validateName();
            const validEmail = validateEmail();
            const validPassword = validatePassword();
            const validConfirmPassword = validateConfirmPassword();
            return validName && validEmail && validPassword && validConfirmPassword;//The && operator combines the results of all the validations. If any of the individual validations return false, the overall result will also be false.
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
                    <input type="text" id="name" name="name" placeholder="Name"  onkeyup="validateName()"><!-- The onkeyup event is a JavaScript event that triggers when the user releases a key on the keyboard while typing into an input field.  -->
                    <p class="error" id="nameError"></p>
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
