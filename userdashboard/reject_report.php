<?php
session_start();
include '../database/connectdatabase.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../loginvalidation.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    $report_id = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
    $resolution_notes = filter_input(INPUT_POST, 'resolution_notes', FILTER_SANITIZE_STRING);
    
    if ($report_id && $resolution_notes) {
        // Update report status and notes
        $sql = "UPDATE tbl_reports 
                SET status = 'rejected', 
                    resolution_notes = ?, 
                    rejected_at = NOW() 
                WHERE report_id = ?";
                
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $resolution_notes, $report_id);
        
        if (mysqli_stmt_execute($stmt)) 
        {
            mysqli_stmt_close($stmt);
            // Success
            header('Location: reports.php?success=1');
            exit();
        } 
        else 
        {
            mysqli_stmt_close($stmt);
            // Error
            header('Location: reports.php?error=1');
            exit();
        }
    } 
    else 
    {
        header('Location: reports.php?error=2');
        exit();
    }
}

mysqli_close($conn);
?>
