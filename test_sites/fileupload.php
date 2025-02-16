<?php
include '../database/connectdatabase.php'; // Database connection

// Create table if not exists
$sql = "CREATE TABLE IF NOT EXISTS tbl_image_upload (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    login_id INT NOT NULL UNIQUE,
    image_name VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (login_id) REFERENCES tbl_login(login_id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql);

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) {
    $login_id = 1; // Replace with logged-in user's ID
    $target_dir = "uploads/";
    $file_name = basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Allowed image formats
    $allowed_formats = ["jpg", "jpeg", "png"];

    // Validation checks
    if (!in_array($imageFileType, $allowed_formats)) {
        die("Error: Only JPG, JPEG, and PNG files are allowed.");
    }
    if (!is_uploaded_file($_FILES["image"]["tmp_name"])) {
        die("Error: File upload failed.");
    }

    // Move file to uploads directory
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        // Insert or update image record
        $sql = "INSERT INTO tbl_image_upload (login_id, image_name, image_path) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE image_name = VALUES(image_name), image_path = VALUES(image_path)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iss", $login_id, $file_name, $target_file);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "Image uploaded successfully!";
        } else {
            echo "Error storing image in database.";
        }
    } else {
        echo "Error: Failed to move uploaded file.";
    }
}

// HTML Form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Profile Image</title>
</head>
<body>
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="image">Select an image:</label>
        <input type="file" name="image" id="image" required>
        <button type="submit">Upload</button>
    </form>
</body>
</html>
<?php mysqli_close($conn); ?>
