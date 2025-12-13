<?php
// class_ranking.php - English Version with Fixed Sidebar
session_start();
include 'db.php'; 

// Authentication Check
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'] ?? 'student';

// 1. HANDLE ADD POINTS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_point') {
    $class_id = $_POST['class_id'];
    $student_id = !empty($_POST['student_id']) ? $_POST['student_id'] : NULL;
    $description = $_POST['description'];
    $point_type = $_POST['point_type']; 
    
    $point_val = abs((int)$_POST['point_value']); 
    $final_point = ($point_type == 'minus') ? -1 * $point_val : $point_val;

    $stmt = $conn->prepare("INSERT INTO class_points (class_id, student_id, description, point_change) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisi", $class_id, $student_id, $description, $final_point);
    $stmt->execute();
    
    header("Location: class_ranking.php?view_class_id=$class_id"); exit;
}

// 2. HANDLE DELETE LOG
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $cid = (int)$_GET['view_class_id'];
    $conn->query("DELETE FROM class_points WHERE id=$id");
    header("Location: class_ranking.php?view_class_id=$cid"); exit;
}

// 3. FETCH RANKING (Current Month Logic)
$sql_ranking = "
    SELECT c.id, c.class_name, c.teacher_name, c.teacher_photo,
    (100 + COALESCE(SUM(p.point_change), 0)) as total_score
    FROM classes c
    LEFT JOIN class_points p ON c.id = p.class_id 
        AND MONTH(p.created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(p.created_at) = YEAR(CURRENT_DATE())
    GROUP BY c.id
    ORDER BY total_score DESC, c.class_name ASC
";
$ranking_result = $conn->query($sql_ranking);

// 4. FETCH CLASS DETAILS
$view_class = null; $logs_result = null; $students_in_class = null;
if (isset($_GET['view_class_id'])) {
    $cid = (int)$_GET['view_class_id'];
    
    // Class Info + Score
    $stmt = $conn->prepare("
        SELECT c.*, (100 + COALESCE(SUM(p.point_change), 0)) as total_score 
        FROM classes c 
        LEFT JOIN class_points p ON c.id = p.class_id 
            AND MONTH(p.created_at) = MONTH(CURRENT_DATE()) 
            AND YEAR(p.created_at) = YEAR(CURRENT_DATE())
        WHERE c.id = ? 
        GROUP BY c.id
    ");
    $stmt->bind_param("i", $cid); $stmt->execute();
    $view_class = $stmt->get_result()->fetch_assoc();

    // History Logs
    $stmt_logs = $conn->prepare("
        SELECT p.*, s.full_name as student_name 
        FROM class_points p 
        LEFT JOIN students s ON p.student_id = s.id 
        WHERE p.class_id = ? 
        ORDER BY p.created_at DESC
    ");
    $stmt_logs->bind_param("i", $cid); $stmt_logs->execute();
    $logs_result = $stmt_logs->get_result();

    // Student List for Dropdown
    $stmt_students = $conn->prepare("SELECT id, full_name FROM students WHERE class_id = ? ORDER BY full_name");
    $stmt_students->bind_param("i", $cid); $stmt_students->execute();
    $students_in_class = $stmt_students->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Ranking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- 1. GLOBAL STYLES (MATCHING DASHBOARD) --- */
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
            overflow: hidden;
        }
        
        /* Collapsed State */
        .sidebar.collapsed { width: 70px; }
        .sidebar.collapsed .logo-text { display: none; opacity: 0; }
        .sidebar.collapsed .sidebar-menu span { display: none; opacity: 0; }
        .sidebar.collapsed .user-info { display: none; opacity: 0; }
        .sidebar.collapsed .logout-text { display: none; opacity: 0; }
        
        /* Centering icons when collapsed */
        .sidebar.collapsed .sidebar-header { justify-content: center; padding-left: 0; padding-right: 0; }
        .sidebar.collapsed .sidebar-menu a { justify-content: center; }
        .sidebar.collapsed .sidebar-menu i { margin-right: 0; font-size: 1.4rem; }
        .sidebar.collapsed .logout-btn { justify-content: center; }

        /* Header */
        .sidebar-header { 
            padding: 20px; display: flex; align-items: center; justify-content: space-between; 
            color: #fff; border-bottom: 1px solid #1e293b; height: 64px; box-sizing: border-box;
        }
        .logo-group { display: flex; align-items: center; gap: 10px; }
        .logo-text { margin: 0; font-size: 1.2rem; font-weight: bold; }
        #toggle-btn { cursor: pointer; color: #fff; font-size: 1.2rem; min-width: 20px; text-align: center; }

        /* Menu */
        .sidebar-menu { list-style: none; padding: 10px 0; margin: 0; flex: 1; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 20px; color: #94a3b8; text-decoration: none; transition: 0.2s; font-size: 0.95rem; }
        .sidebar-menu a:hover { background: #1e293b; color: #fff; }
        .sidebar-menu a.active { background: #1e293b; color: #3b82f6; border-left: 3px solid #3b82f6; }
        .sidebar-menu i { width: 25px; margin-right: 10px; text-align: center; }

        /* Footer */
        .sidebar-footer { padding: 20px; border-top: 1px solid #1e293b; text-align: center; background: #0f172a; }
        .user-info { color: #fff; font-weight: bold; margin-bottom: 5px; font-size: 0.9rem; }
        .logout-btn { color: #ef4444; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; gap: 5px; }

        /* --- 3. PAGE SPECIFIC LAYOUT (SPLIT SCREEN) --- */
        .main-content { flex: 1; display: flex; overflow: hidden; }
        
        /* Left Column */
        .ranking-left { width: 380px; background: #fff; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; }
        .list-header { padding: 20px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
        
        .rank-item { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: 0.2s; }
        .rank-item:hover { background: #f8fafc; }
        .rank-item.active { background: #eff6ff; border-left: 4px solid #3b82f6; }
        
        .rank-badge { width: 28px; height: 28px; background: #e2e8f0; color: #475569; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: bold; }
        .rank-1 .rank-badge { background: #fbbf24; color: #fff; }
        .rank-2 .rank-badge { background: #94a3b8; color: #fff; }
        .rank-3 .rank-badge { background: #d97706; color: #fff; }
        
        /* Right Column */
        .ranking-right { flex: 1; padding: 30px; overflow-y: auto; background: #f1f5f9; }
        .class-header-card { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border: 1px solid #e2e8f0; }
        
        .teacher-info { display: flex; align-items: center; gap: 15px; margin-top: 5px; }
        .teacher-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }
        .total-score { font-size: 2.5rem; font-weight: 800; line-height: 1; }
        
        /* Action Forms */
        .action-card { background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-control { flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; }
        .btn-submit { width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: #2563eb; }

        /* History Table */
        .history-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .history-table th { text-align: left; padding: 12px; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
        .history-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .bg-green { background: #d1fae5; color: #047857; }
        .bg-red { background: #fee2e2; color: #b91c1c; }
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
            <li><a href="dashboard.php"><i class="fas fa-desktop"></i> <span>Class Management</span></a></li>
            <li><a href="search_student.php"><i class="fas fa-search"></i> <span>Search Students</span></a></li>
            <li><a href="class_ranking.php" class="active"><i class="fas fa-trophy"></i> <span>Class Ranking</span></a></li>
        </ul>
        
        <div class="sidebar-footer">
            <div class="user-info">User: <?php echo htmlspecialchars($user_name); ?></div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> <span class="logout-text">Logout</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        
        <div class="ranking-left">
            <div class="list-header">
                <h3 style="margin:0; color: #1e293b;">Leaderboard</h3>
                <small style="color:#64748b;">Month: <strong><?php echo date('F Y'); ?></strong></small>
            </div>
            <div style="flex:1; overflow-y:auto;">
                <?php 
                if ($ranking_result->num_rows > 0) {
                    $rank = 1;
                    while($row = $ranking_result->fetch_assoc()) {
                        $is_active = (isset($cid) && $cid == $row['id']) ? 'active' : '';
                        $rank_class = ($rank <= 3) ? "rank-$rank" : "";
                        
                        echo "<div class='rank-item $is_active $rank_class' onclick=\"window.location.href='class_ranking.php?view_class_id={$row['id']}'\">";
                        echo "<div style='display:flex; align-items:center; gap:12px;'>";
                        echo "<div class='rank-badge'>$rank</div>";
                        echo "<div><div style='font-weight:bold; color:#1e293b;'>{$row['class_name']}</div>";
                        echo "<div style='font-size:0.8rem; color:#64748b;'>{$row['teacher_name']}</div></div></div>";
                        $score_color = ($row['total_score'] >= 100) ? 'color:#10b981' : 'color:#ef4444';
                        echo "<div class='total-score' style='font-size:1.2rem; $score_color'>{$row['total_score']}</div></div>";
                        $rank++;
                    }
                } else { echo "<div style='padding:20px; text-align:center; color:#64748b'>No classes found.</div>"; }
                ?>
            </div>
        </div>

        <div class="ranking-right">
            <?php if ($view_class): ?>
                
                <div class="class-header-card">
                    <div>
                        <h1 style="margin:0; font-size:1.8rem; color:#1e293b;"><?php echo htmlspecialchars($view_class['class_name']); ?></h1>
                        <div class="teacher-info">
                            <?php $t_img = !empty($view_class['teacher_photo']) ? $view_class['teacher_photo'] : 'https://via.placeholder.com/40'; ?>
                            <img src="<?php echo $t_img; ?>" class="teacher-avatar">
                            <div>
                                <div style="font-weight:bold; color:#334155;"><?php echo $view_class['teacher_name']; ?></div>
                                <div style="font-size:0.8rem; color:#64748b;"><?php echo $view_class['teacher_phone']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:0.8rem; color:#64748b; text-transform:uppercase; font-weight:bold;">Monthly Score</div>
                        <?php $sc_color = ($view_class['total_score'] >= 100) ? 'color:#10b981' : 'color:#ef4444'; ?>
                        <div class="total-score" style="<?php echo $sc_color; ?>"><?php echo $view_class['total_score']; ?></div>
                    </div>
                </div>

                <div class="action-card">
                    <h4 style="margin-top:0; margin-bottom:15px; color:#334155;"><i class="fas fa-edit"></i> Manage Points</h4>
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="add_point">
                        <input type="hidden" name="class_id" value="<?php echo $view_class['id']; ?>">
                        
                        <div class="form-row">
                            <select name="point_type" class="form-control" style="flex:1;">
                                <option value="minus">🔴 Penalty (-)</option>
                                <option value="plus">🟢 Reward (+)</option>
                            </select>
                            <input type="number" name="point_value" class="form-control" style="flex:1;" placeholder="Points (e.g. 5, 10)" required min="1">
                        </div>
                        
                        <div class="form-row">
                            <select name="student_id" class="form-control" style="flex:1;">
                                <option value="">-- Apply to Whole Class --</option>
                                <?php if($students_in_class) { while($st = $students_in_class->fetch_assoc()) { echo "<option value='{$st['id']}'>{$st['full_name']}</option>"; } } ?>
                            </select>
                            <input type="text" name="description" class="form-control" style="flex:2;" placeholder="Reason (e.g. Late, Good Job)" required>
                        </div>
                        
                        <button type="submit" class="btn-submit">SUBMIT</button>
                    </form>
                </div>

                <div class="action-card">
                    <h4 style="margin-top:0; margin-bottom:15px; color:#334155;"><i class="fas fa-history"></i> History Log</h4>
                    <table class="history-table">
                        <thead><tr><th>Time</th><th>Target</th><th>Reason</th><th>Points</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php 
                            if ($logs_result && $logs_result->num_rows > 0) {
                                while($log = $logs_result->fetch_assoc()) {
                                    $isCurrent = (date('mY', strtotime($log['created_at'])) == date('mY'));
                                    $bg = $isCurrent ? "background:#f0fdf4;" : ""; 
                                    
                                    echo "<tr style='$bg'>";
                                    echo "<td>" . date('M d, H:i', strtotime($log['created_at'])) . "</td>";
                                    
                                    $who = $log['student_name'] ? "<strong>{$log['student_name']}</strong>" : "<i>Whole Class</i>";
                                    echo "<td>$who</td>";
                                    echo "<td>{$log['description']}</td>";
                                    
                                    $badgeClass = ($log['point_change'] >= 0) ? 'bg-green' : 'bg-red';
                                    $sign = ($log['point_change'] >= 0) ? '+' : '';
                                    echo "<td><span class='badge $badgeClass'>$sign{$log['point_change']}</span></td>";
                                    
                                    echo "<td><a href='class_ranking.php?view_class_id={$view_class['id']}&delete_id={$log['id']}' style='color:#ef4444;' onclick=\"return confirm('Delete this record?');\"><i class='fas fa-trash'></i></a></td>";
                                    echo "</tr>";
                                }
                            } else { echo "<tr><td colspan='5' style='text-align:center; padding:20px; color:#94a3b8;'>No history records found.</td></tr>"; }
                            ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#94a3b8;">
                    <i class="fas fa-chart-bar" style="font-size:4rem; margin-bottom:20px; opacity:0.3;"></i>
                    <h2>Select a class to view details</h2>
                </div>
            <?php endif; ?>
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