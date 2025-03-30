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

// Message handling
$message = '';
$messageType = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
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
    $where_clause = "WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR l.email LIKE ? OR u.phone_number LIKE ?";
    $search_param = "%{$search}%";
    $params = [$search_param, $search_param, $search_param, $search_param];
}

// Main query with prepared statement
$sql = "SELECT 
            u.user_id,
            u.first_name,
            u.last_name,
            l.email,
            u.phone_number,
            u.profile_image,
            u.created_at,
            l.status
        FROM tbl_user u
        LEFT JOIN tbl_login l ON u.user_id = l.user_id 
        {$where_clause}
        ORDER BY u.created_at DESC 
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
$total_sql = "SELECT COUNT(*) as count FROM tbl_user u LEFT JOIN tbl_login l ON u.user_id = l.user_id {$where_clause}";
$total_stmt = mysqli_prepare($conn, $total_sql);
if ($where_clause) {
    mysqli_stmt_bind_param($total_stmt, "ssss", ...$params);
}
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_records = mysqli_fetch_assoc($total_result)['count'];
$total_pages = ceil($total_records / $records_per_page);

// User statistics
$stats = [
    'total' => $total_records,
    'active' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as count FROM tbl_applications"))['count'],
    'new' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tbl_user WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"))['count']
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
    <link rel="stylesheet" href="manage_users.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-section">
                <h1>FlexiHire</h1>
                <div class="admin-badge">
                    <i class="fas fa-user-shield"></i>
                    <span>Admin Dashboard</span>
                </div>
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
                    <div class="search-box">
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search users..." 
                               value="<?= htmlspecialchars($search) ?>" 
                               aria-label="Search users">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
            </header>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>" id="statusMessage">
                    <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="close-alert" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <section class="users-stats" aria-label="User statistics">
    <div class="stat-item">
        <span class="stat-value"><?= number_format($stats['total']) ?></span>
        <span class="stat-label">Total Users</span>
    </div>
    <div class="stat-item">
        <span class="stat-value"><?= number_format($stats['active']) ?></span>
        <span class="stat-label">Active Users</span>
    </div>
    <div class="stat-item">
        <span class="stat-value"><?= number_format($stats['new']) ?></span>
        <span class="stat-label">New This Month</span>
    </div>
</section>
            <section class="users-table-container">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Joined Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <?php
                                            $profileImage = '../assets/images/default-avatar.png'; // Default image
                                            if (!empty($row['profile_image'])) {
                                                $imagePath = '../database/profile_picture/' . $row['profile_image'];
                                                if (file_exists($imagePath)) {
                                                    $profileImage = $imagePath;
                                                }
                                            }
                                            ?>
                                            <img src="<?= htmlspecialchars($profileImage) ?>" 
                                                 alt="Profile" 
                                                 class="user-avatar"
                                                 onerror="this.src='../assets/images/default-avatar.png'">
                                            <span><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td>
                                        <span class="status-badge <?= strtolower($row['status']) === 'active' ? 'active' : 'inactive' ?>">
                                            <?= ucfirst(strtolower($row['status'] ?? 'inactive')) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="view_user.php?user_id=<?= $row['user_id'] ?>" class="view-btn">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <!-- <button class="edit-btn" onclick="editUser(<?= $row['user_id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button> -->
                                            <?php if(strtolower($row['status']) === 'active'): ?>
                                                <button type="button" class="delete-btn" onclick="showConfirmation(<?= $row['user_id'] ?>)">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="activate-btn" onclick="showActivateConfirmation(<?= $row['user_id'] ?>)">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-results-container">
                        <div class="no-results-content">
                            <div class="no-results-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3>No Users Found</h3>
                            <?php if ($search): ?>
                                <p>We couldn't find any users matching "<span class="search-term"><?= htmlspecialchars($search) ?></span>"</p>
                                <button onclick="clearSearch()" class="clear-search-btn">
                                    <i class="fas fa-times"></i> Clear Search
                                </button>
                            <?php else: ?>
                                <p>There are no users in the system yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
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
            <div class="confirmation-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="confirmation-text">
                <h3>Deactivate Account?</h3>
                <p>This user will no longer be able to access their account.</p>
            </div>
            <div class="confirmation-actions">
                <button class="cancel-action" onclick="hideConfirmation()">Cancel</button>
                <a href="#" id="confirmDeactivate" class="confirm-action">Deactivate</a>
            </div>
        </div>
    </div>

    <div class="confirmation-panel" id="activatePanel">
        <div class="confirmation-content">
            <div class="confirmation-icon activate">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="confirmation-text">
                <h3>Activate Account?</h3>
                <p>This user will be able to access their account again.</p>
            </div>
            <div class="confirmation-actions">
                <button class="cancel-action" onclick="hideActivateConfirmation()">Cancel</button>
                <a href="#" id="confirmActivate" class="confirm-action activate">Activate</a>
            </div>
        </div>
    </div>

    <script src="manage_users.js" defer></script>
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