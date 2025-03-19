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

echo json_encode([
    'needs_license' => ($job['license_required'] !== 'not_required' && $job['license_required'] !== NULL && $job['license_required'] !== ''),
    'needs_badge' => ($job['badge_required'] === 'yes')
]);

mysqli_close($conn);
?> 