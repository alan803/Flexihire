<?php
session_start();

// Database connection
require_once '../database/connectdatabase.php';
$dbname = "project";
mysqli_select_db($conn, $dbname) or die("Database selection failed: " . mysqli_error($conn));

// Authentication check
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login/loginvalidation.php");
    exit();
}

// Pagination setup
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$records_per_page = 10;
$offset = max(0, ($page - 1) * $records_per_page);

// Search functionality with prepared statement
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';
$where_clause = '';
$params = [];
if ($search) {
    $where_clause = "WHERE e.company_name LIKE ? OR l.email LIKE ? OR e.phone_number LIKE ?";
    $search_param = "%{$search}%";
    $params = [$search_param, $search_param, $search_param];
}

// Main query with prepared statement
$sql = "SELECT 
            e.employer_id,
            e.company_name,
            e.profile_image,
            e.created_at,
            l.email,
            l.status
        FROM tbl_employer e 
        LEFT JOIN tbl_login l ON e.employer_id = l.employer_id
        {$where_clause}
        ORDER BY e.created_at DESC 
        LIMIT ?, ?";

$stmt = mysqli_prepare($conn, $sql);
if ($where_clause) {
    $merged_params = array_merge($params, [$offset, $records_per_page]);
    mysqli_stmt_bind_param($stmt, "ssssii", ...$merged_params);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $offset, $records_per_page);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Total records for pagination
$total_sql = "SELECT COUNT(*) as count FROM tbl_employer e LEFT JOIN tbl_login l ON e.employer_id = l.employer_id {$where_clause}";
$total_stmt = mysqli_prepare($conn, $total_sql);
if ($where_clause) {
    mysqli_stmt_bind_param($total_stmt, "sss", ...$params);
}
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_records = mysqli_fetch_assoc($total_result)['count'];
$total_pages = ceil($total_records / $records_per_page);

// User statistics
$stats = [
    'total' => $total_records,
    'active' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tbl_employer e JOIN tbl_login l ON e.employer_id = l.employer_id WHERE l.status = 'active'"))['count'],
    'new' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tbl_employer WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"))['count']
];

// Constants
const DEFAULT_AVATAR = '../assets/images/default-avatar.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="manage_employers.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar (Matching Admin Dashboard) -->
        <div class="sidebar">
            <div class="logo-section">
                <h1>FlexiHire</h1>
            </div>
            <nav class="nav-menu">
                <a href="admindashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="manage_users.php" class="nav-item active">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <a href="manage_employers.php" class="nav-item">
                    <i class="fas fa-building"></i>
                    <span>Manage Employers</span>
                </a>
                <a href="manage_jobs.php" class="nav-item">
                    <i class="fas fa-briefcase"></i>
                    <span>Manage Jobs</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="../login/logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header class="page-header">
                <h1>User Management</h1>
                <div class="header-actions">
                    <form class="search-box" method="GET" action="">
                        <input type="search" 
                               name="search" 
                               id="searchInput" 
                               placeholder="Search users..." 
                               value="<?= htmlspecialchars($search) ?>" 
                               aria-label="Search users">
                        <button type="submit" aria-label="Search"><i class="fas fa-search"></i></button>
                    </form>
                    <button class="export-btn" aria-label="Export user data">
                        <i class="fas fa-download"></i> Export Users
                    </button>
                </div>
            </header>

            <section class="users-stats" aria-label="User statistics">
                <div class="stat-item">
                    <span class="stat-value"><?= number_format($stats['total']) ?></span>
                    <span class="stat-label">Total Employers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= number_format($stats['active']) ?></span>
                    <span class="stat-label">Active Employers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= number_format($stats['new']) ?></span>
                    <span class="stat-label">New This Month</span>
                </div>
            </section>

            <section class="users-table-container">
                <table class="employers-table">
                    <thead>
                        <tr>
                            <th>NAME</th>
                            <th>EMAIL</th>
                            <th>STATUS</th>
                            <th>JOINED DATE</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <div class="employer-info">
                                            <?php if (!empty($row['profile_image'])): ?>
                                                <?php
                                                // Debug file path
                                                $imagePath = "employer_pf/" . basename($row['profile_image']);
                                                $fullPath = $_SERVER['DOCUMENT_ROOT'] . "/mini project/userdashboard/" . $imagePath;
                                                
                                                // For debugging
                                                error_log("Image from DB: " . $row['profile_image']);
                                                error_log("Full path: " . $fullPath);
                                                error_log("File exists: " . (file_exists($fullPath) ? 'Yes' : 'No'));
                                                
                                                if (file_exists($fullPath)):
                                                ?>
                                                    <div class="avatar-container">
                                                        <img src="<?= htmlspecialchars('./' . $imagePath) ?>" 
                                                             alt="Profile Picture" 
                                                             class="employer-avatar"
                                                             onload="this.classList.add('loaded')"
                                                             onerror="console.log('Image failed to load:', this.src)">
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Debug message for missing file -->
                                                    <?php error_log("File not found: " . $fullPath); ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <span class="company-name"><?= htmlspecialchars($row['company_name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="email-cell"><?= htmlspecialchars($row['email']) ?></td>
                                    <td>
                                        <span class="status-badge <?= strtolower($row['status']) ?>">
                                            <span class="status-dot"></span>
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn view-btn" onclick="viewEmployer(<?= $row['employer_id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn edit-btn" onclick="editEmployer(<?= $row['employer_id'] ?>)">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <?php if(strtolower($row['status']) === 'active'): ?>
                                                <button class="action-btn delete-btn" onclick="showConfirmation(<?= $row['employer_id'] ?>)">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="action-btn activate-btn" onclick="showActivateConfirmation(<?= $row['employer_id'] ?>)">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-records">No employers found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <?php if ($total_pages > 1): ?>
                <nav class="pagination" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?= $search ? "&search=" . urlencode($search) : '' ?>" 
                           class="page-link" 
                           aria-label="First page">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?page=<?= $page-1 ?><?= $search ? "&search=" . urlencode($search) : '' ?>" 
                           class="page-link" 
                           aria-label="Previous page">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                        <a href="?page=<?= $i ?><?= $search ? "&search=" . urlencode($search) : '' ?>" 
                           class="page-link <?= $i === $page ? 'active' : '' ?>"
                           aria-label="Page <?= $i ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page+1 ?><?= $search ? "&search=" . urlencode($search) : '' ?>" 
                           class="page-link" 
                           aria-label="Next page">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?page=<?= $total_pages ?><?= $search ? "&search=" . urlencode($search) : '' ?>" 
                           class="page-link" 
                           aria-label="Last page">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <div id="confirmationOverlay"></div>
    <div class="confirmation-panel" id="deactivatePanel">
        <div class="confirmation-content">
            <div class="confirmation-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="confirmation-text">
                <h3>Confirm Deactivation</h3>
                <p>Are you sure you want to deactivate this employer's account?</p>
            </div>
            <div class="confirmation-actions">
                <button class="cancel-btn" onclick="hideConfirmation()">Cancel</button>
                <a href="#" id="confirmDeactivate" class="confirm-btn">Confirm</a>
            </div>
        </div>
    </div>

    <div class="confirmation-panel" id="activatePanel">
        <div class="confirmation-content">
            <div class="confirmation-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="confirmation-text">
                <h3>Confirm Activation</h3>
                <p>Are you sure you want to activate this employer's account?</p>
            </div>
            <div class="confirmation-actions">
                <button class="cancel-btn" onclick="hideActivateConfirmation()">Cancel</button>
                <a href="#" id="confirmActivate" class="confirm-btn success">Confirm</a>
            </div>
        </div>
    </div>

    <script src="manage_employers.js" defer></script>
</body>
</html>

<?php
    // Clean up
    mysqli_stmt_close($stmt);
    if (isset($total_stmt)) {
        mysqli_stmt_close($total_stmt);
    }
    mysqli_close($conn);
?>