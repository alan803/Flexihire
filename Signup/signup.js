function validateForm() {
    // Get form elements
    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    const confirmPassword = document.getElementById("confirmpassword").value.trim();

    // Error elements
    const nameError = document.getElementById("nameError");
    const emailError = document.getElementById("emailError");
    const passwordError = document.getElementById("passwordError");
    const confirmPasswordError = document.getElementById("confirmpasswordError");

    // Clear previous error messages
    nameError.textContent = "";
    emailError.textContent = "";
    passwordError.textContent = "";
    confirmPasswordError.textContent = "";

    // Validation flags
    let isValid = true;

    // Name validation
    if (name === "") {
        nameError.textContent = "Name is required.";
        isValid = false;
    } else if (!/^[a-zA-Z ]*$/.test(name)) {
        nameError.textContent = "Name should not contain digits or special characters.";
        isValid = false;
    } else if (name.length < 5) {
        nameError.textContent = "Minimum length for the name should be 5.";
        isValid = false;
    }

    // Email validation
    if (email === "") {
        emailError.textContent = "E-mail is required.";
        isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        emailError.textContent = "Invalid email address.";
        isValid = false;
    }

    // Password validation
    if (password === "") {
        passwordError.textContent = "Password is required.";
        isValid = false;
    } else if (password.length < 6) {
        passwordError.textContent = "Password must be at least 6 characters long.";
        isValid = false;
    } else if (!/[!@#$%^&*()_+=-]/.test(password)) {
        passwordError.textContent = "Password must include at least one special character.";
        isValid = false;
    }

    // Confirm password validation
    if (confirmPassword === "") {
        confirmPasswordError.textContent = "Confirm password is required.";
        isValid = false;
    } else if (confirmPassword !== password) {
        confirmPasswordError.textContent = "Passwords do not match.";
        isValid = false;
    }

    // Prevent form submission if validation fails
    return isValid;
}
