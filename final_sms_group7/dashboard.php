<?php
// dashboard.php - FINAL VERSION (With Home Tab)
session_start();
include 'db.php';

// 1. CHECK LOGIN
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'] ?? 'student';

// 2. LOGIC
$sql = "SELECT classes.*, COUNT(students.id) AS student_count 
        FROM classes 
        LEFT JOIN students ON classes.id = students.class_id ";

$search_keyword = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_keyword = $_GET['search'];
    $sql .= " WHERE classes.class_name LIKE '%$search_keyword%' ";
}

$sql .= " GROUP BY classes.id ORDER BY classes.class_name";
$result = $conn->query($sql);

// Helper function to check active page (for Sidebar)
function isActive($page) {
    return basename($_SERVER['PHP_SELF']) == $page ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Class Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- 1. GLOBAL STYLES --- */
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .app-container { display: flex; height: 100vh; overflow: hidden; }

        /* --- 2. SIDEBAR (FIXED) --- */
        .sidebar { width: 260px; background: #0f172a; color: #94a3b8; display: flex; flex-direction: column; transition: width 0.3s ease; flex-shrink: 0; white-space: nowrap; overflow: hidden;}
        .sidebar.collapsed { width: 70px; }
        .sidebar.collapsed .logo-group, .sidebar.collapsed .sidebar-menu span, .sidebar.collapsed .user-info, .sidebar.collapsed .logout-text { display: none; }
        .sidebar.collapsed .sidebar-header, .sidebar.collapsed .sidebar-menu a, .sidebar.collapsed .logout-btn { justify-content: center; padding-left: 0; padding-right: 0; }
        .sidebar.collapsed .sidebar-menu i { margin-right: 0; font-size: 1.4rem; }
        .sidebar.collapsed .logout-btn { justify-content: center; }

        .sidebar-header { padding: 20px; display: flex; align-items: center; justify-content: space-between; color: #fff; border-bottom: 1px solid #1e293b; height: 60px; box-sizing: border-box; }
        .logo-group { display: flex; align-items: center; gap: 10px; }
        .logo-text { margin: 0; font-size: 1.2rem; font-weight: bold; }
        #toggle-btn { cursor: pointer; color: #fff; font-size: 1.2rem; }

        .sidebar-menu { list-style: none; padding: 10px 0; margin: 0; flex: 1; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 20px; color: #94a3b8; text-decoration: none; transition: 0.2s; font-size: 0.95rem; }
        .sidebar-menu a:hover { background: #1e293b; color: #fff; }
        .sidebar-menu a.active { background: #1e293b; color: #3b82f6; border-left: 3px solid #3b82f6; }
        .sidebar-menu i { width: 25px; margin-right: 10px; text-align: center; }

        .sidebar-footer { padding: 20px; border-top: 1px solid #1e293b; text-align: center; background: #0f172a; }
        .user-info { color: #fff; font-weight: bold; margin-bottom: 5px; font-size: 0.9rem; }
        .logout-btn { color: #ef4444; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 5px; }

        /* --- 3. MAIN CONTENT --- */
        .main-content { flex: 1; overflow-y: auto; padding: 30px; display: flex; flex-direction: column; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { margin: 0; color: #1e293b; font-size: 1.8rem; }
        
        .action-bar { display: flex; gap: 15px; }
        .search-form { display: flex; }
        .search-input { padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px 0 0 6px; outline: none; width: 250px; }
        .btn-search { padding: 10px 15px; background: #3b82f6; color: white; border: none; border-radius: 0 6px 6px 0; cursor: pointer; }
        .btn-add { padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; display: flex; align-items: center; gap: 5px; }
        .btn-add:hover { background: #059669; }

        /* CARD STYLES */
        .class-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        
        .class-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden; transition: 0.2s; border: 1px solid #e2e8f0; display: flex; flex-direction: column; }
        .class-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px rgba(0,0,0,0.1); border-color: #3b82f6; }
        
        /* THIS LINK WRAPS THE HEADER AND BODY */
        .card-main-link { text-decoration: none; color: inherit; display: flex; flex-direction: column; flex: 1; cursor: pointer; }
        
        .card-header { background: #3b82f6; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { margin: 0; font-size: 1.3rem; }
        .grade-badge { background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        
        .card-body { padding: 20px; flex: 1; }
        .teacher-info { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .teacher-img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }
        .info-row { font-size: 0.9rem; color: #64748b; margin-bottom: 5px; display: flex; align-items: center; gap: 8px; }
        .student-count { background: #f1f5f9; color: #334155; padding: 5px 10px; border-radius: 5px; font-weight: bold; font-size: 0.85rem; display: inline-block; margin-top: 10px;}
        
        /* Footer stays outside the main link so buttons work independently */
        .card-footer { padding: 15px 20px; border-top: 1px solid #f1f5f9; background: #f8fafc; display: flex; gap: 10px; }
        
        .btn { flex: 1; padding: 8px; text-align: center; border-radius: 5px; text-decoration: none; font-size: 0.85rem; font-weight: 500; transition: 0.2s; }
        /* "View" button is removed/hidden if clicking card works, or kept as visual cue */
        .btn-view { background: #e0f2fe; color: #0284c7; flex: 2; } .btn-view:hover { background: #bae6fd; }
        .btn-edit { background: #fef3c7; color: #d97706; } .btn-edit:hover { background: #fde68a; }
        .btn-delete { background: #fee2e2; color: #dc2626; } .btn-delete:hover { background: #fecaca; }

    </style>
</head>
<body>

<div class="app-container">
    
    <aside class="sidebar" id="mySidebar">
        <div class="sidebar-header">
            <div class="logo-group">
                <i class="fas fa-graduation-cap" style="font-size: 1.5rem;"></i> 
                <h3 class="logo-text">SMS Portal</h3>
            </div>
            <i class="fas fa-bars" id="toggle-btn" onclick="toggleSidebar()"></i>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="home.php" class="<?php echo isActive('home.php'); ?>">
                    <i class="fas fa-home"></i> <span>Home</span>
                </a>
            </li>

            <li>
                <a href="dashboard.php" class="<?php echo isActive('dashboard.php'); ?>">
                    <i class="fas fa-desktop"></i> <span>Class Management</span>
                </a>
            </li>

            <li>
                <a href="search_student.php" class="<?php echo isActive('search_student.php'); ?>">
                    <i class="fas fa-search"></i> <span>Search Students</span>
                </a>
            </li>

            <li>
                <a href="class_ranking.php" class="<?php echo isActive('class_ranking.php'); ?>">
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
        <div class="page-header">
            <h2 class="page-title"><i class="fas fa-chalkboard"></i> Class Overview</h2>
            <div class="action-bar">
                <form action="" method="GET" class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="Search class..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                </form>
                <?php if ($user_role == 'admin'): ?>
                    <a href="class_create.php" class="btn-add"><i class="fas fa-plus"></i> Add Class</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="class-grid">
            <?php 
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $t_img = !empty($row['teacher_photo']) ? $row['teacher_photo'] : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
            ?>
                <div class="class-card">
                    
                    <a href="student_list.php?class_id=<?php echo $row['id']; ?>" class="card-main-link" title="Click to view students">
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($row['class_name']); ?></h3>
                            <span class="grade-badge">Grade <?php echo htmlspecialchars($row['grade_block']); ?></span>
                        </div>
                        
                        <div class="card-body">
                            <div class="teacher-info">
                                <img src="<?php echo $t_img; ?>" class="teacher-img">
                                <div style="font-weight:bold; color:#334155; font-size:1.1rem;">
                                    <?php echo htmlspecialchars($row['teacher_name']); ?>
                                </div>
                            </div>
                            
                            <div class="info-row"><i class="fas fa-phone-alt"></i> <?php echo $row['teacher_phone'] ?: 'N/A'; ?></div>
                            <div class="info-row"><i class="fas fa-envelope"></i> <?php echo $row['teacher_email'] ?: 'N/A'; ?></div>
                            
                            <div class="student-count">
                                <i class="fas fa-user-graduate"></i> <?php echo $row['student_count']; ?> Students
                            </div>
                        </div>
                    </a>
                    <div class="card-footer">
                        <a href="student_list.php?class_id=<?php echo $row['id']; ?>" class="btn btn-view">
                            <i class="fas fa-list"></i> View
                        </a>

                        <?php if ($user_role == 'admin'): ?>
                            <a href="edit_class.php?id=<?php echo $row['id']; ?>" class="btn btn-edit" title="Edit">
                                <i class="fas fa-pen"></i>
                            </a>
                            <a href="delete-class.php?id=<?php echo $row['id']; ?>" class="btn btn-delete" title="Delete" onclick="return confirm('Delete class <?php echo $row['class_name']; ?>?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php 
                }
            } else {
                echo "<p style='color:#64748b; font-size:1.2rem; width:100%; text-align:center;'>No classes found matching your search.</p>";
            }
            ?>
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