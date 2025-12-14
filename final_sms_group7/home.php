<?php
// home.php - Trang chủ giới thiệu nhóm
session_start();

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_name = $_SESSION['user_name'];

// --- CẤU HÌNH LINK CỦA BẠN TẠI ĐÂY ---
$youtube_link = "https://www.youtube.com/watch?v=VIDEO_ID_CUA_BAN"; // Thay link YouTube demo vào đây
$github_link  = "https://github.com/LINK_GITHUB_CUA_BAN";           // Thay link GitHub vào đây
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - Group 7 Introduction</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- 1. GLOBAL STYLES (Giống Dashboard) --- */
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .app-container { display: flex; height: 100vh; overflow: hidden; }

        /* --- 2. SIDEBAR STYLE --- */
        .sidebar { 
            width: 260px; background: #0f172a; color: #94a3b8; 
            display: flex; flex-direction: column; transition: width 0.3s ease; 
            flex-shrink: 0; white-space: nowrap; overflow: hidden;
        }
        .sidebar.collapsed { width: 70px; }
        .sidebar.collapsed .logo-group, .sidebar.collapsed .sidebar-menu span, .sidebar.collapsed .user-info, .sidebar.collapsed .logout-text { display: none; }
        .sidebar.collapsed .sidebar-header, .sidebar.collapsed .sidebar-menu a, .sidebar.collapsed .logout-btn { justify-content: center; }
        .sidebar.collapsed .sidebar-menu i { margin-right: 0; font-size: 1.4rem; }

        .sidebar-header { padding: 20px; display: flex; align-items: center; justify-content: space-between; color: #fff; border-bottom: 1px solid #1e293b; height: 60px; box-sizing: border-box; }
        .logo-group { display: flex; align-items: center; gap: 10px; }
        .logo-group h3 { margin: 0; font-size: 1.2rem; font-weight: bold; }
        #toggle-btn { cursor: pointer; color: #fff; font-size: 1.2rem; }

        .sidebar-menu { list-style: none; padding: 10px 0; margin: 0; flex: 1; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 20px; color: #94a3b8; text-decoration: none; transition: 0.2s; font-size: 0.95rem; }
        .sidebar-menu a:hover { background: #1e293b; color: #fff; }
        .sidebar-menu a.active { background: #1e293b; color: #3b82f6; border-left: 3px solid #3b82f6; }
        .sidebar-menu i { width: 25px; margin-right: 10px; text-align: center; }

        .sidebar-footer { padding: 20px; border-top: 1px solid #1e293b; text-align: center; background: #0f172a; }
        .user-info { color: #fff; font-weight: bold; margin-bottom: 5px; font-size: 0.9rem; }
        .logout-btn { color: #ef4444; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 5px; }

        /* --- 3. MAIN CONTENT (HOME SPECIFIC) --- */
        .main-content { flex: 1; overflow-y: auto; padding: 40px; display: flex; flex-direction: column; align-items: center; }

        /* Welcome Section */
        .welcome-section { text-align: center; margin-bottom: 50px; }
        .welcome-section h1 { font-size: 2.5rem; color: #1e293b; margin-bottom: 10px; }
        .welcome-section p { color: #64748b; font-size: 1.1rem; }

        /* Link Cards */
        .link-container { display: flex; gap: 30px; justify-content: center; flex-wrap: wrap; margin-bottom: 60px; width: 100%; max-width: 900px; }
        
        .link-card { 
            flex: 1; min-width: 300px; 
            background: #fff; border-radius: 15px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            text-decoration: none; color: inherit; 
            overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #e2e8f0;
            display: flex; flex-direction: column; align-items: center;
            padding: 40px 20px;
        }
        
        .link-card:hover { transform: translateY(-10px); box-shadow: 0 20px 25px rgba(0,0,0,0.1); }
        
        /* YouTube Specific */
        .card-youtube:hover { border-color: #ff0000; }
        .icon-youtube { font-size: 5rem; color: #ff0000; margin-bottom: 20px; }
        
        /* GitHub Specific */
        .card-github:hover { border-color: #333; }
        .icon-github { font-size: 5rem; color: #333; margin-bottom: 20px; }

        .card-title { font-size: 1.5rem; font-weight: bold; color: #334155; margin-bottom: 10px; }
        .card-desc { color: #64748b; text-align: center; }

        /* Member List Section */
        .members-section { 
            width: 100%; max-width: 800px; 
            background: #fff; padding: 30px; border-radius: 15px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
            border-top: 5px solid #3b82f6;
        }
        
        .members-header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }
        .members-header h2 { margin: 0; color: #1e293b; font-size: 1.8rem; }
        .group-badge { background: #3b82f6; color: white; padding: 5px 15px; border-radius: 20px; font-size: 1rem; font-weight: bold; display: inline-block; margin-bottom: 10px; }

        .member-list { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .member-item { 
            background: #f8fafc; padding: 15px; border-radius: 8px; 
            text-align: center; font-weight: 600; color: #475569; 
            border: 1px solid #e2e8f0; transition: 0.2s;
        }
        .member-item:hover { background: #e0f2fe; color: #0284c7; border-color: #bae6fd; }
        .member-item i { margin-right: 8px; color: #94a3b8; }

    </style>
</head>
<body>

<div class="app-container">
    
    <aside class="sidebar" id="mySidebar">
        <div class="sidebar-header">
            <div class="logo-group">
                <i class="fas fa-graduation-cap" style="font-size: 1.5rem;"></i> 
                <h3>SMS Portal</h3>
            </div>
            <i class="fas fa-bars" id="toggle-btn" onclick="toggleSidebar()"></i>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="home.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> <span>Home</span>
                </a>
            </li>

            <li>
                <a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php' || basename($_SERVER['PHP_SELF']) == 'student_list.php') ? 'active' : ''; ?>">
                    <i class="fas fa-desktop"></i> <span>Class Management</span>
                </a>
            </li>

            <li>
                <a href="search_student.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i> <span>Search Students</span>
                </a>
            </li>

            <li>
                <a href="class_ranking.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'class_ranking.php' ? 'active' : ''; ?>">
                    <i class="fas fa-trophy"></i> <span>Class Ranking</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-info">User: <?php echo htmlspecialchars($user_name); ?></div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> <span class="logout-text">Logout</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        
        <div class="welcome-section">
            <h1>Welcome to Student Management System</h1>
            <p>Project Presentation & Source Code</p>
        </div>

        <div class="link-container">
            <a href="<?php echo $youtube_link; ?>" target="_blank" class="link-card card-youtube">
                <i class="fab fa-youtube icon-youtube"></i>
                <div class="card-title">Watch Demo</div>
                <div class="card-desc">Click to watch our project demonstration video on YouTube.</div>
            </a>

            <a href="<?php echo $github_link; ?>" target="_blank" class="link-card card-github">
                <i class="fab fa-github icon-github"></i>
                <div class="card-title">Source Code</div>
                <div class="card-desc">View the full source code and documentation on GitHub.</div>
            </a>
        </div>

        <div class="members-section">
            <div class="members-header">
                <span class="group-badge">Group 7</span>
                <h2>Development Team</h2>
            </div>
            
            <ul class="member-list">
                <li class="member-item"><i class="fas fa-user"></i> Dang Phuong Nam</li>
                <li class="member-item"><i class="fas fa-user"></i> Nguyen Quang Thai</li>
                <li class="member-item"><i class="fas fa-user"></i> Phan Hong Quang</li>
                <li class="member-item"><i class="fas fa-user"></i> Nguyen Minh Hieu</li>
                <li class="member-item"><i class="fas fa-user"></i> Nguyen Anh Quan</li>
            </ul>
        </div>

    </main>
</div>

<script>
    function toggleSidebar() {
        document.getElementById("mySidebar").classList.toggle("collapsed");
    }
</script>

</body>
</html>