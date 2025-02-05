<?php
    // Initialize variables and errors
    $name = $email = $password = $confirmpassword = "";
    $error = [];
    
    // Check if form is submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Sanitize and assign form data
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $confirmpassword = trim($_POST['confirmpassword']);

        // Validation for Name
        if (empty($name)) {
            $error['name'] = "Name is required.";
        } else {
            if (!preg_match("/^[a-zA-Z]*$/", $name)) {
                $error['name'] = "Name should not contain digits or special characters.";
            }
            if (strlen($name) < 5) {
                $error['name'] = "Name must be at least 5 characters.";
            }
        }

        // Validation for Email
        if (empty($email)) {
            $error['email'] = "E-mail is required.";
        } else {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error['email'] = "Invalid email address.";
            }
        }

        // Validation for Password
        if (empty($password)) {
            $error['password'] = "Password is required.";
        } else {
            if (strlen($password) < 6) {
                $error['password'] = "Password must be at least 6 characters.";
            }
            if (!preg_match("/^[a-zA-Z0-9]*$/", $password)) {
                $error['password'] = "Password should not contain special characters.";
            }
        }

        // Validation for Confirm Password
        if (empty($confirmpassword)) {
            $error['confirmpassword'] = "Confirm password is required.";
        } else {
            if ($confirmpassword != $password) {
                $error['confirmpassword'] = "Passwords do not match.";
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
</head>
<body>
    <div class="container">
        <div class="left-section">
            <a href="../homepage/homepage.html" class="back-to-website"><span class="arrow-circle">←</span> Back to website</a>
        </div>
        <div class="right-section">
            <h2>Create an account</h2>
            <p>Already have an account? <a href="../Login/login.html" style="color: #665efc; text-decoration: none;">Log in</a></p>
            <form method="post">
                <div>
                    <input type="text" name="name" placeholder="Name" value="<?php echo $name; ?>">
                    <?php if (isset($error['name'])): ?>
                        <p class="error"><?php echo $error['name']; ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <input type="email" name="email" placeholder="E-mail" value="<?php echo $email; ?>">
                    <?php if (isset($error['email'])): ?>
                        <p class="error"><?php echo $error['email']; ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <input type="password" name="password" placeholder="Enter your password" value="<?php echo $password; ?>">
                    <?php if (isset($error['password'])): ?>
                        <p class="error"><?php echo $error['password']; ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <input type="password" name="confirmpassword" placeholder="Confirm your password" value="<?php echo $confirmpassword; ?>">
                    <?php if (isset($error['confirmpassword'])): ?>
                        <p class="error"><?php echo $error['confirmpassword']; ?></p>
                    <?php endif; ?>
                </div>
                <button type="submit">Create Account</button>
            </form>
        </div>
    </div>
</body>
</html>
