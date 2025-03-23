<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);

    if(!isset($_SESSION['employer_id']))
    {
        header("Location: ../login/loginvalidation.php");
        exit();
    }

    // Fetch employer details for sidebar
    $employer_id = $_SESSION['employer_id'];
    $sql = "SELECT e.*, l.email 
            FROM tbl_employer e 
            JOIN tbl_login l ON e.employer_id = l.employer_id 
            WHERE e.employer_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $employer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    $company_name = $row['company_name'] ?? 'Company Name Not Set';
    $email = $row['email'] ?? '';

    // Fetch certificates
    $application_id = $_GET['application_id'];
    $sql = "SELECT 
            c.*, 
            a.user_id, 
            u.first_name, 
            u.last_name, 
            j.job_title,
            c.file_path,
            c.certificate_type
        FROM tbl_certificates c
        JOIN tbl_applications a ON c.application_id = a.id
        JOIN tbl_user u ON a.user_id = u.user_id
        JOIN tbl_jobs j ON a.job_id = j.job_id
        WHERE c.application_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $application_id);
    mysqli_stmt_execute($stmt);
    $certificates = mysqli_stmt_get_result($stmt);
    $applicant_data = mysqli_fetch_assoc($certificates);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Certificates</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="employerdashboard.css">
    <link rel="stylesheet" href="view_certificates.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-container">
                <?php if(!empty($row['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($row['profile_image']); ?>" 
                         alt="<?php echo htmlspecialchars($company_name); ?>"
                         onerror="this.src='../assets/images/company-logo.png';">
                <?php else: ?>
                    <img src="../assets/images/company-logo.png" alt="AutoRecruits.in">
                <?php endif; ?>
            </div>
            <div class="company-info">
                <span><?php echo htmlspecialchars($company_name); ?></span>
                <span style="font-size: 13px; color: var(--light-text);"><?php echo htmlspecialchars($email); ?></span>
            </div>
            <!-- Add your existing sidebar navigation here -->
        </div>

        <!-- Main Content -->
        <div class="main-container">
            <div class="header">
                <h1>Applicant Certificates</h1>
                <a href="applicants.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Applicants
                </a>
            </div>

            <div class="applicant-info-card">
                <div class="applicant-header">
                    <h2><?php echo htmlspecialchars($applicant_data['first_name'] . ' ' . $applicant_data['last_name']); ?></h2>
                    <span class="job-title"><?php echo htmlspecialchars($applicant_data['job_title']); ?></span>
                </div>
            </div>

            <div class="certificates-grid">
                <?php 
                mysqli_data_seek($certificates, 0);
                while($cert = mysqli_fetch_assoc($certificates)): 
                    // Update path to point to uploads directory with correct column name
                    $certificate_path = "../uploads/" . $cert['file_path'];
                ?>
                <div class="certificate-card">
                    <div class="certificate-header">
                        <i class="fas fa-certificate"></i>
                        <h3><?php echo htmlspecialchars($cert['certificate_type']); ?></h3>
                    </div>
                    <div class="certificate-content">
                        <i class="fas fa-file-image"></i>
                    </div>
                    <div class="certificate-actions">
                        <button class="view-btn" onclick="openImageModal('<?php echo htmlspecialchars($certificate_path); ?>')">
                            <i class="fas fa-eye"></i> View Full Image
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Add the accept/reject buttons below certificates grid -->
            <div class="action-buttons-container">
                <a href="accept.php?application_id=<?php echo $application_id; ?>&status=accepted" 
                    class="action-btn accept-btn">
                    <i class="fas fa-check"></i>
                    Accept
                </a>
                <a href="accept.php?application_id=<?php echo $application_id; ?>&status=rejected" 
                    class="action-btn reject-btn">
                    <i class="fas fa-times"></i>
                    Reject
                </a>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <span class="close-modal">&times;</span>
        <img id="modalImage" src="" alt="Certificate">
    </div>

    <script>
        // Modal functionality
        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = "flex";
            modalImg.src = imageSrc;
        }

        // Close modal
        document.querySelector('.close-modal').onclick = function() {
            document.getElementById('imageModal').style.display = "none";
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>