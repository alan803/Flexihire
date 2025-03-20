<?php
session_start();
include '../database/connectdatabase.php';

if (!isset($_GET['job_id'])) {
    echo json_encode(['error' => 'Job ID not provided']);
    exit();
}

$job_id = (int)$_GET['job_id'];
$query = "SELECT license_required, badge_required FROM tbl_jobs WHERE job_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $job_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$job = mysqli_fetch_assoc($result);

if ($job) {
    $response = [
        'success' => true,
        'needsDocuments' => false,
        'needsLicense' => false,
        'needsBadge' => false
    ];

    // Check if license is required
    if ($job['license_required'] !== 'not_required' && $job['license_required'] !== NULL && $job['license_required'] !== '') {
        $response['needsDocuments'] = true;
        $response['needsLicense'] = true;
    }

    // Check if badge is required
    if ($job['badge_required'] === 'yes') {
        $response['needsDocuments'] = true;
        $response['needsBadge'] = true;
    }

    echo json_encode($response);
} else {
    echo json_encode(['error' => 'Job not found']);
}

mysqli_close($conn);
?> 