<?php
// search_student.php - English Version (Added Home Tab)
session_start();
include 'db.php';

// Authentication Check
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_role = $_SESSION['user_role'] ?? 'user';
$user_name = $_SESSION['user_name'] ?? 'User';

// 1. GET CLASS LIST (For the "Type or Choose" Dropdown)
$class_list = $conn->query("SELECT class_name FROM classes ORDER BY class_name ASC");

// 2. SEARCH LOGIC
$search_results = null;
$student_keyword = $_GET['student_name'] ?? '';
$class_keyword   = $_GET['class_name'] ?? '';

if (isset($_GET['btn_search']) || isset($_GET['student_name'])) {
    
    // SQL: JOIN classes only (No need to join grades anymore)
    $sql = "SELECT s.*, c.class_name 
            FROM students s 
            JOIN classes c ON s.class_id = c.id 
            WHERE 1=1";
            
    $types = "";
    $params = [];

    // Filter by Student Name
    if (!empty($student_keyword)) {
        $sql .= " AND s.full_name LIKE ?";
        $types .= "s";
        $params[] = "%" . $student_keyword . "%";
    }

    // Filter by Class Name (Type or Select)
    if (!empty($class_keyword)) {
        $sql .= " AND c.class_name LIKE ?";
        $types .= "s";
        $params[] = "%" . $class_keyword . "%";
    }

    $sql .= " ORDER BY c.class_name, s.full_name";

    // Execute
    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $search_results = $stmt->get_result();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Students</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- 1. GLOBAL SETTINGS --- */
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .app-container { display: flex; height: 100vh; overflow: hidden; }

        /* --- 2. SIDEBAR STYLE (FIXED) --- */
        .sidebar { 
            width: 260px; 
            background: #0f172a; 
            color: #94a3b8; 
            display: flex; flex-direction: column; 
            transition: width 0.3s ease; 
            flex-shrink: 0; 
            white-space: nowrap; 
        }
        .sidebar.collapsed { width: 70px; }
        .sidebar.collapsed .logo-group, .sidebar.collapsed .sidebar-menu span, .sidebar.collapsed .user-info, .sidebar.collapsed .logout-text { display: none; }
        .sidebar.collapsed .sidebar-header, .sidebar.collapsed .sidebar-menu a, .sidebar.collapsed .logout-btn { justify-content: center; }
        .sidebar.collapsed .sidebar-menu i { margin-right: 0; font-size: 1.4rem; }

        .sidebar-header { padding: 20px; display: flex; align-items: center; justify-content: space-between; color: #fff; border-bottom: 1px solid #1e293b; height: 60px; box-sizing: border-box; }
        .logo-group { display: flex; align-items: center; gap: 10px; overflow: hidden; }
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

        /* --- 3. MAIN CONTENT --- */
        .main-content { flex: 1; overflow-y: auto; padding: 30px; }
        
        /* Search Card */
        .search-card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .search-form-grid { display: flex; gap: 15px; align-items: flex-end; }
        .form-group { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 0.9rem; font-weight: bold; color: #64748b; }
        .form-control { padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; outline: none; }
        .btn-search-lg { padding: 10px 25px; height: 42px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.2s; }
        .btn-search-lg:hover { background: #2563eb; }

        /* Table */
        .table-data { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05); font-size: 0.9rem; }
        .table-data th { background: #f8fafc; padding: 15px; text-align: left; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; }
        .table-data td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
        
        .profile-photo-small { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0; }
        .class-badge { background: #e0f2fe; color: #0284c7; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.8rem; }
        
        /* Actions */
        .btn { padding: 6px 12px; border-radius: 4px; text-decoration: none; color: white; font-size: 0.8rem; display: inline-block; margin-right: 5px; }
        .btn-view { background: #3b82f6; }
        .btn-edit { background: #f59e0b; }
        .btn-delete { background: #ef4444; }
    </style>
</head>
<body>

<div class="app-container">
    <aside class="sidebar" id="mySidebar">
        <div class="sidebar-header">
            <div class="logo-group">
                <i class="fas fa-graduation-cap" style="font-size: 1.5rem;"></i> <h3>SMS Portal</h3>
            </div>
            <i class="fas fa-bars" id="toggle-btn" onclick="toggleSidebar()"></i>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="home.php">
                    <i class="fas fa-home"></i> <span>Home</span>
                </a>
            </li>
            
            <li><a href="dashboard.php"><i class="fas fa-desktop"></i> <span>Class Management</span></a></li>
            <li><a href="search_student.php" class="active"><i class="fas fa-search"></i> <span>Search Students</span></a></li>
            <li><a href="class_ranking.php"><i class="fas fa-trophy"></i> <span>Class Ranking</span></a></li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="user-info">User: <?php echo htmlspecialchars($user_name); ?></div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> <span class="logout-text">Logout</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <h2 style="margin-top:0; color: #1e293b; margin-bottom: 20px;"><i class="fas fa-search"></i> Student Directory</h2>

        <div class="search-card">
            <form action="" method="GET" class="search-form-grid">
                <input type="hidden" name="btn_search" value="1">
                
                <div class="form-group" style="flex:2;">
                    <label>Student Name</label>
                    <input type="text" name="student_name" value="<?php echo htmlspecialchars($student_keyword); ?>" class="form-control" placeholder="Enter name...">
                </div>
                
                <div class="form-group" style="flex:1;">
                    <label>Class</label>
                    <input type="text" name="class_name" list="class_options" class="form-control" placeholder="Type or select..." value="<?php echo htmlspecialchars($class_keyword); ?>">
                    <datalist id="class_options">
                        <?php 
                        if ($class_list->num_rows > 0) {
                            while($c = $class_list->fetch_assoc()) {
                                echo "<option value='{$c['class_name']}'>";
                            }
                        }
                        ?>
                    </datalist>
                </div>
                
                <button type="submit" class="btn-search-lg"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>

        <?php if ($search_results): ?>
            <p style="color:#64748b; margin-bottom:10px;">Found: <strong><?php echo $search_results->num_rows; ?></strong> students</p>
            
            <?php if ($search_results->num_rows > 0): ?>
            <table class="table-data">
                <thead>
                    <tr>
                        <th>Photo</th> <th>Full Name</th> <th>Class</th> 
                        <th>DOB</th> <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $search_results->fetch_assoc()): ?>
                    <?php 
                         $photo = !empty($row['student_photo']) ? $row['student_photo'] : 'https://cdn-icons-png.flaticon.com/512/2995/2995620.png';
                    ?>
                    <tr>
                        <td><img src="<?php echo $photo; ?>" class="profile-photo-small"></td>
                        <td style="font-weight:bold; color:#334155;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><span class="class-badge"><?php echo htmlspecialchars($row['class_name']); ?></span></td>
                        
                        <td><?php echo date('d/m/Y', strtotime($row['dob'])); ?></td>
                        <td><?php echo $row['phone']; ?></td>
                        
                        <td class="actions">
                            <a href="grade_view.php?student_id=<?php echo $row['id']; ?>" class="btn btn-view" title="View Grades"><i class="fas fa-chart-bar"></i> View Grades</a>
                            
                            <?php if ($user_role == 'admin'): ?>
                                <a href="student_edit_2.php?id=<?php echo $row['id']; ?>" class="btn btn-edit"><i class="fas fa-pen"></i></a>
                                <a href="delete-student.php?id=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Delete this student?');"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div style="text-align:center; padding:30px; background:#fff; border-radius:10px; color:#94a3b8;">
                    No results found matching your search.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<script>
    function toggleSidebar() {
        document.getElementById("mySidebar").classList.toggle("collapsed");
    }
</script>
</body>
</html>