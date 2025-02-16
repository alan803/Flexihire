<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoRecruits.in - Dashboard</title>
    <style>
        :root {
            --primary-color: #1e2a4a;
            --secondary-color: #f8f9fa;
            --accent-color: #ffc107;
            --text-light: #ffffff;
            --text-dark: #333333;
            --border-radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            transition: all 0.3s ease;
        }

        body {
            background-color: #f5f6f8;
            min-height: 100vh;
        }

        /* Applicants Sidebar Styles */
        /* Applicants Sidebar Styles */
        .applicants-sidebar {
            width: 280px;
            background: white;
            height: 100vh;
            position: fixed;
            right: 0;
            top: 0;
            border-left: 1px solid #eee;
            overflow-y: auto;
            z-index: 1000;
            scrollbar-width: thin;
            scrollbar-color: var(--accent-color) #f5f6f8;
        }

        .applicants-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .applicants-sidebar::-webkit-scrollbar-track {
            background: #f5f6f8;
        }

        .applicants-sidebar::-webkit-scrollbar-thumb {
            background-color: var(--accent-color);
            border-radius: 20px;
        }

        /* Adjust Main Container */
        .container {
            margin-left: 0;
            margin-right: 280px;
            max-width: calc(100% - 280px);
        }

        .applicants-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .applicant-count {
            background: var(--primary-color);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 14px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .applicant-list {
            padding: 20px;
        }

        .applicant-card {
            padding: 12px;
            border: 1px solid #eee;
            border-radius: var(--border-radius);
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .applicant-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-color: var(--accent-color);
        }

        .applicant-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 4px;
            height: 0;
            background: var(--accent-color);
            transition: height 0.3s ease;
        }

        .applicant-card:hover::before {
            height: 100%;
        }

        .applicant-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .applicant-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--primary-color);
        }

        .applicant-name {
            font-weight: 500;
        }

        .applicant-position {
            font-size: 14px;
            color: #666;
        }

        /* Main Container */
        .container {
            display: flex;
            margin-left: 0;
            max-width: calc(100% - 280px);
            padding: 20px;
            gap: 24px;
        }

        /* Main Sidebar Styles */
        .sidebar {
            width: 280px;
            background-color: var(--primary-color);
            border-radius: var(--border-radius);
            padding: 20px;
            color: var(--text-light);
            height: fit-content;
        }

        .logo-container {
            padding: 12px;
            background: white;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
        }

        .logo-container img {
            width: 100%;
            height: auto;
        }

        .company-info {
            display: flex;
            align-items: center;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            margin-bottom: 24px;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            padding: 12px;
            margin: 4px 0;
            border-radius: var(--border-radius);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
            padding-left: 20px;
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            background: white;
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .main-content:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .post-job-btn {
            background: var(--accent-color);
            color: var(--text-dark);
            padding: 12px 24px;
            border-radius: var(--border-radius);
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .post-job-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            background: #ffcd39;
        }

        /* Stats Section */
        .stats-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-dark);
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        /* Recent Jobs Section */
        .recent-jobs {
            margin-top: 32px;
        }

        .recent-jobs-header {
            margin-bottom: 16px;
        }

        .job-card {
            border: 1px solid #eee;
            border-radius: var(--border-radius);
            padding: 16px;
            margin-bottom: 16px;
        }

        .job-title {
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .job-meta {
            display: flex;
            gap: 24px;
            color: #666;
            font-size: 14px;
        }

        .job-stats {
            display: flex;
            gap: 16px;
            margin-top: 16px;
        }

        .stat {
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 13px;
        }

        .view-applicants {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
            float: right;
        }
    </style>
</head>
<body>
    <!-- Applicants Sidebar -->
    <div class="applicants-sidebar">
        <div class="applicants-header">
            <h2>New Applicants</h2>
            <span class="applicant-count">09</span>
        </div>
        <div class="applicant-list">
            <!-- Web Developer Group -->
            <div style="margin-bottom: 20px;">
                <h3 style="margin-bottom: 12px;">Web Developer</h3>
                <div class="applicant-card">
                    <div class="applicant-info">
                        <div class="applicant-avatar">MZ</div>
                        <div>
                            <div class="applicant-name">Muhammad Zahid</div>
                            <div class="applicant-position">React Developer</div>
                        </div>
                    </div>
                </div>
                <div class="applicant-card">
                    <div class="applicant-info">
                        <div class="applicant-avatar">SK</div>
                        <div>
                            <div class="applicant-name">Samra Khawar</div>
                            <div class="applicant-position">Node.JS Developer</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Designer Group -->
            <div style="margin-bottom: 20px;">
                <h3 style="margin-bottom: 12px;">Designer</h3>
                <div class="applicant-card">
                    <div class="applicant-info">
                        <div class="applicant-avatar">BA</div>
                        <div>
                            <div class="applicant-name">Bilal Ahmed</div>
                            <div class="applicant-position">UI/UX Designer</div>
                        </div>
                    </div>
                </div>
                <div class="applicant-card">
                    <div class="applicant-info">
                        <div class="applicant-avatar">ZA</div>
                        <div>
                            <div class="applicant-name">Zohail Ali</div>
                            <div class="applicant-position">Product Designer</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Main Sidebar -->
        <div class="sidebar">
            <div class="logo-container">
                <img src="logo.png" alt="AutoRecruits.in">
            </div>
            <div class="company-info">
                <span>Rahul and Sons T...</span>
            </div>
            <nav class="nav-menu">
                <div class="nav-item">
                    <i class="fas fa-chart-pie"></i>
                    Overview
                </div>
                <div class="nav-item active">
                    <i class="fas fa-th-large"></i>
                    Dashboard
                </div>
                <div class="nav-item">
                    <i class="fas fa-bell"></i>
                    Notifications
                </div>
                <div class="nav-item">
                    <i class="fas fa-comment"></i>
                    Chat
                </div>
                <div class="nav-item">
                    <i class="fas fa-users"></i>
                    Manage Hirings
                </div>
                <div class="nav-item">
                    <i class="fas fa-plus-circle"></i>
                    Post a Job
                </div>
                <div class="nav-item">
                    <i class="fas fa-file-alt"></i>
                    My Job Posts
                </div>
                <div class="nav-item">
                    <i class="fas fa-user-friends"></i>
                    Applicants
                </div>
                <div class="nav-item">
                    <i class="fas fa-calendar-check"></i>
                    Interviews
                </div>
                <div class="nav-item">
                    <i class="fas fa-cog"></i>
                    My Services
                </div>
            </nav>
            <div class="settings-section">
                <div class="nav-item">
                    <i class="fas fa-user"></i>
                    My Profile
                </div>
                <div class="nav-item">
                    <i class="fas fa-shield-alt"></i>
                    Obligations
                </div>
                <div class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
                <button class="post-job-btn">Post a Job</button>
            </div>

            <!-- Overview Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🔵</div>
                    <div>
                        <div class="stat-number">50</div>
                        <div class="stat-label">Active Jobs</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div>
                        <div class="stat-number">42</div>
                        <div class="stat-label">New Applicants</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div>
                        <div class="stat-number">24</div>
                        <div class="stat-label">Shortlisted Reviewed</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div>
                        <div class="stat-number">12</div>
                        <div class="stat-label">Candidates Shortlisted</div>
                    </div>
                </div>
            </div>

            <!-- Recent Jobs Section -->
            <div class="recent-jobs">
                <h2 class="recent-jobs-header">Recently Posted Jobs</h2>
                <div class="job-card">
                    <h3 class="job-title">Workshop Manager</h3>
                    <div class="job-meta">
                        <span>Mehsana, Gujarat</span>
                        <span>Created on: 20 July 2020</span>
                        <span>Expires: 20 July 2020</span>
                    </div>
                    <div class="job-stats">
                        <span class="stat">Applicants: 20</span>
                        <span class="stat">New: 20</span>
                        <span class="stat">Reviewed: 5</span>
                        <span class="stat">Rejected: 15</span>
                        <span class="stat">Hired: 5</span>
                        <a href="#" class="view-applicants">View Applicants</a>
                    </div>
                </div>
                <div class="job-card">
                    <h3 class="job-title">Customer Care Executive Service</h3>
                    <div class="job-meta">
                        <span>Mehsana, Gujarat</span>
                        <span>Created on: 20 July 2020</span>
                        <span>Expires: 20 July 2020</span>
                    </div>
                    <div class="job-stats">
                        <span class="stat">Applicants: 20</span>
                        <span class="stat">New: 20</span>
                        <span class="stat">Reviewed: 5</span>
                        <span class="stat">Rejected: 15</span>
                        <span class="stat">Hired: 5</span>
                        <a href="#" class="view-applicants">View Applicants</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
</body>