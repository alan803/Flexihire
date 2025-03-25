<?php
session_start();
include '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname);

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if report_id is provided
if (!isset($_GET['report_id'])) {
    echo json_encode(['success' => false, 'message' => 'Report ID is required']);
    exit();
}

$report_id = $_GET['report_id'];

// Fetch report details
$sql = "SELECT r.*, 
        u.first_name as reporter_name,
        u.profile_image as reporter_picture,
        l.email as reporter_email,
        CASE 
            WHEN r.reported_job_id IS NOT NULL THEN 'Job'
            WHEN r.reported_employer_id IS NOT NULL THEN 'Employer'
            WHEN r.reported_user_id IS NOT NULL THEN 'User'
        END as report_type,
        CASE 
            WHEN r.reported_job_id IS NOT NULL THEN j.job_title
            WHEN r.reported_employer_id IS NOT NULL THEN e.company_name
            WHEN r.reported_user_id IS NOT NULL THEN CONCAT(u2.first_name, ' ', u2.last_name)
        END as reported_entity
        FROM tbl_reports r 
        LEFT JOIN tbl_user u ON r.reporter_id = u.user_id
        LEFT JOIN tbl_login l ON u.user_id = l.user_id
        LEFT JOIN tbl_jobs j ON r.reported_job_id = j.job_id
        LEFT JOIN tbl_employer e ON r.reported_employer_id = e.employer_id
        LEFT JOIN tbl_user u2 ON r.reported_user_id = u2.user_id
        WHERE r.report_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $report_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($report = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'success' => true,
        'report' => [
            'report_id' => $report['report_id'],
            'reporter_name' => $report['reporter_name'],
            'reporter_picture' => $report['reporter_picture'] ? '../database/profile_picture/' . $report['reporter_picture'] : null,
            'reporter_email' => $report['reporter_email'],
            'report_type' => $report['report_type'],
            'reported_entity' => $report['reported_entity'],
            'reason' => $report['reason'],
            'status' => $report['status'],
            'created_at' => $report['created_at'],
            'resolution_notes' => $report['resolution_notes']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Report not found']);
}

mysqli_close($conn);
?> 