<?php
// class_ranking.php - MONTHLY RESET VERSION (ENGLISH)
session_start();
include 'db.php'; // Ensure this file connects to DB variable $conn

// Check login
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_role = $_SESSION['user_role'] ?? 'user';
$user_name = $_SESSION['user_name'];

// --- 1. HANDLE: ADD POINTS/PENALTIES (Admin Only) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_point' && $user_role == 'admin') {
    $class_id = $_POST['class_id'];
    $student_id = !empty($_POST['student_id']) ? $_POST['student_id'] : NULL;
    $description = $_POST['description'];
    $point_type = $_POST['point_type']; // 'minus' or 'plus'
    
    // Get absolute integer value
    $point_val = abs((int)$_POST['point_value']); 

    // Calculate final value (Negative for penalties)
    if ($point_type == 'minus') {
        $final_point = -1 * $point_val;
    } else {
        $final_point = $point_val;
    }

    // Save to DB (System automatically saves 'created_at')
    $stmt = $conn->prepare("INSERT INTO class_points (class_id, student_id, description, point_change) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisi", $class_id, $student_id, $description, $final_point);
    $stmt->execute();
    
    // Redirect to avoid resubmission
    header("Location: class_ranking.php?view_class_id=$class_id");
    exit;
}

// --- 2. HANDLE: DELETE RECORD (Admin Only) ---
if (isset($_GET['delete_id']) && $user_role == 'admin') {
    $id = (int)$_GET['delete_id'];
    $cid = (int)$_GET['view_class_id'];
    $conn->query("DELETE FROM class_points WHERE id=$id");
    header("Location: class_ranking.php?view_class_id=$cid");
    exit;
}

// --- 3. GET RANKING DATA (CURRENT MONTH ONLY) ---
// Logic: Only SUM points where 'created_at' is in the current month & year.
$sql_ranking = "
    SELECT c.id, c.class_name, c.teacher_name,
    (100 + COALESCE(SUM(p.point_change), 0)) as total_score
    FROM classes c
    LEFT JOIN class_points p ON c.id = p.class_id 
        AND MONTH(p.created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(p.created_at) = YEAR(CURRENT_DATE())
    GROUP BY c.id
    ORDER BY total_score DESC, c.class_name ASC
";
$ranking_result = $conn->query($sql_ranking);

// --- 4. GET SINGLE CLASS DETAILS (CURRENT MONTH ONLY) ---
$view_class = null;
$logs_result = null;
$students_in_class = null;

if (isset($_GET['view_class_id'])) {
    $cid = (int)$_GET['view_class_id'];
    
    // Get class info and Total Score for THIS MONTH
    $stmt = $conn->prepare("
        SELECT c.*, (100 + COALESCE(SUM(p.point_change), 0)) as total_score 
        FROM classes c 
        LEFT JOIN class_points p ON c.id = p.class_id 
            AND MONTH(p.created_at) = MONTH(CURRENT_DATE()) 
            AND YEAR(p.created_at) = YEAR(CURRENT_DATE())
        WHERE c.id = ? 
        GROUP BY c.id
    ");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $view_class = $stmt->get_result()->fetch_assoc();

    // Get History Log (Show ALL history for reference, ordered by newest)
    $stmt_logs = $conn->prepare("
        SELECT p.*, s.full_name as student_name 
        FROM class_points p 
        LEFT JOIN students s ON p.student_id = s.id 
        WHERE p.class_id = ? 
        ORDER BY p.created_at DESC
    ");
    $stmt_logs->bind_param("i", $cid);
    $stmt_logs->execute();
    $logs_result = $stmt_logs->get_result();

    // Get Student list for the dropdown (Admin only)
    if ($user_role == 'admin') {
        $stmt_students = $conn->prepare("SELECT id, full_name FROM students WHERE class_id = ? ORDER BY full_name");
        $stmt_students->bind_param("i", $cid);
        $stmt_students->execute();
        $students_in_class = $stmt_students->get_result();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Ranking - Monthly</title>
    <link rel="stylesheet" href="style_simple.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Specific CSS for Ranking Page */
        .ranking-layout { display: flex; height: 100vh; overflow: hidden; }
        .ranking-left { width: 350px; border-right: 1px solid #ddd; overflow-y: auto; background: #fff; display: flex; flex-direction: column; }
        .ranking-right { flex: 1; overflow-y: auto; padding: 30px; background: #f9f9f9; }

        .rank-item {
            padding: 15px 20px; border-bottom: 1px solid #eee; cursor: pointer;
            display: flex; align-items: center; justify-content: space-between;
            transition: 0.2s;
        }
        .rank-item:hover { background: #f0f0f0; }
        .rank-item.active { background: #eef2ff; border-left: 4px solid #000; }
        
        .rank-badge {
            width: 25px; height: 25px; background: #333; color: #fff; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;
        }
        .rank-1 .rank-badge { background: #FFD700; color: #000; }
        .rank-2 .rank-badge { background: #C0C0C0; color: #000; }
        .rank-3 .rank-badge { background: #CD7F32; color: #fff; }

        .total-score { font-size: 1.2rem; font-weight: bold; }
        .score-positive { color: #2ecc71; }
        .score-negative { color: #e74c3c; }

        /* Scoring Form */
        .point-form { background: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="app-container">
        
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fas fa-graduation-cap"></i> <h3>SMS Portal</h3></div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-chalkboard"></i> Class Management</a></li>
                <li><a href="search_student.php"><i class="fas fa-search"></i> Search Students</a></li>
                <li><a href="class_ranking.php" class="active"><i class="fas fa-trophy"></i> Class Ranking</a></li>
            </ul>
            <div class="sidebar-footer">
                <p style="text-align:center; font-size:0.9rem;">User: <strong><?php echo htmlspecialchars($user_name); ?></strong></p>
                <a href="logout.php" style="display:block; text-align:center; color:#ff7675; text-decoration:none; margin-top:5px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

        <main class="main-content" style="flex-direction: row; padding: 0;"> 
            
            <div class="ranking-left">
                <div style="padding: 20px; border-bottom: 1px solid #ddd; background: #fff;">
                    <h3 style="margin:0;"><i class="fas fa-list-ol"></i> Leaderboard</h3>
                    <small style="color:#666">
                        Current Month: <strong><?php echo date('F Y'); ?></strong> 
                        
                    </small>
                </div>
                
                <div style="flex:1; overflow-y:auto;">
                    <?php 
                    if ($ranking_result->num_rows > 0) {
                        $rank = 1;
                        while($row = $ranking_result->fetch_assoc()) {
                            $is_active = (isset($cid) && $cid == $row['id']) ? 'active' : '';
                            $rank_class = ($rank <= 3) ? "rank-$rank" : "";
                            
                            echo "<div class='rank-item $is_active $rank_class' onclick=\"window.location.href='class_ranking.php?view_class_id={$row['id']}'\">";
                            
                            // Left side: Rank + Class Name
                            echo "<div style='display:flex; align-items:center; gap:10px;'>";
                            echo "<div class='rank-badge'>$rank</div>";
                            echo "<div><div style='font-weight:bold;'>{$row['class_name']}</div><div style='font-size:0.8rem; color:#888;'>{$row['teacher_name']}</div></div>";
                            echo "</div>";

                            // Right side: Score
                            $score_color = ($row['total_score'] >= 100) ? 'score-positive' : 'score-negative';
                            echo "<div class='total-score $score_color'>{$row['total_score']}</div>";
                            
                            echo "</div>";
                            $rank++;
                        }
                    } else {
                        echo "<div style='padding:20px; text-align:center; color:#999;'>No classes found.</div>";
                    }
                    ?>
                </div>
            </div>

            <div class="ranking-right">
                <?php if ($view_class): ?>
                    
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h2 style="margin:0; font-size:2rem;"><?php echo htmlspecialchars($view_class['class_name']); ?></h2>
                        <div style="text-align:right;">
                            <span style="font-size:0.9rem; color:#666; text-transform:uppercase; font-weight:bold;">
                                Score for <?php echo date('F'); ?>
                            </span>
                            <div style="font-size:3rem; font-weight:bold; line-height:1; <?php echo ($view_class['total_score'] >= 100) ? 'color:#2ecc71' : 'color:#e74c3c'; ?>">
                                <?php echo $view_class['total_score']; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($user_role == 'admin'): ?>
                    <div class="point-form">
                        <h4 style="margin-top:0; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">
                            <i class="fas fa-pen-nib"></i> Add Record (<?php echo date('F'); ?>)
                        </h4>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="add_point">
                            <input type="hidden" name="class_id" value="<?php echo $view_class['id']; ?>">
                            
                            <div style="display:flex; gap:15px; margin-bottom:15px;">
                                <div style="flex:1;">
                                    <label style="display:block; font-weight:bold; font-size:0.8rem; margin-bottom:5px;">TYPE</label>
                                    <select name="point_type" class="form-control" style="height:42px;">
                                        <option value="minus">🔴 Penalty (-)</option>
                                        <option value="plus">🟢 Reward (+)</option>
                                    </select>
                                </div>
                                <div style="flex:1;">
                                    <label style="display:block; font-weight:bold; font-size:0.8rem; margin-bottom:5px;">POINTS (Unlimited)</label>
                                    <input type="number" name="point_value" class="form-control" placeholder="e.g. 5, 100..." required min="1" style="height:42px;">
                                </div>
                            </div>

                            <div style="margin-bottom:15px;">
                                <label style="display:block; font-weight:bold; font-size:0.8rem; margin-bottom:5px;">STUDENT (Optional)</label>
                                <select name="student_id" class="form-control" style="height:42px;">
                                    <option value="">-- Whole Class --</option>
                                    <?php 
                                    if($students_in_class) {
                                        while($st = $students_in_class->fetch_assoc()) {
                                            echo "<option value='{$st['id']}'>{$st['full_name']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div style="margin-bottom:15px;">
                                <label style="display:block; font-weight:bold; font-size:0.8rem; margin-bottom:5px;">DESCRIPTION</label>
                                <input type="text" name="description" class="form-control" placeholder="Reason (e.g. Late, Good answer...)" required style="height:42px;">
                            </div>
                            
                            <button type="submit" class="btn btn-create" style="width:100%;">SUBMIT RECORD</button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <div style="background:#fff; border:1px solid #ddd; padding:20px;">
                        <h3 style="margin-top:0; margin-bottom:15px;"><i class="fas fa-history"></i> History Log (All Time)</h3>
                        <table class="table-data" style="margin-top:0;">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Student</th>
                                    <th>Description</th>
                                    <th>Points</th>
                                    <?php if($user_role=='admin') echo "<th>Action</th>"; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($logs_result && $logs_result->num_rows > 0) {
                                    while($log = $logs_result->fetch_assoc()) {
                                        // Highlight current month records
                                        $logTime = strtotime($log['created_at']);
                                        $isCurrentMonth = (date('mY', $logTime) == date('mY'));
                                        $rowStyle = $isCurrentMonth ? "background:#f9fff9;" : "";

                                        echo "<tr style='$rowStyle'>";
                                        // Format date: Dec 09, 14:30
                                        echo "<td style='color:#888; font-size:0.9rem;'>" . date('M d, H:i', strtotime($log['created_at'])) . "</td>";
                                        
                                        $who = $log['student_name'] ? "<strong>{$log['student_name']}</strong>" : "<span style='font-style:italic; color:#aaa;'>Whole Class</span>";
                                        echo "<td>$who</td>";
                                        echo "<td>{$log['description']}</td>";
                                        
                                        $p = $log['point_change'];
                                        $color = ($p >= 0) ? '#2ecc71' : '#e74c3c';
                                        $sign = ($p >= 0) ? '+' : '';
                                        echo "<td style='color:$color; font-weight:bold;'>$sign$p</td>";
                                        
                                        if ($user_role == 'admin') {
                                            echo "<td><a href='class_ranking.php?view_class_id={$view_class['id']}&delete_id={$log['id']}' style='color:#e74c3c; font-weight:bold; font-size:1.2rem; text-decoration:none;' onclick=\"return confirm('Are you sure you want to delete this record?');\">&times;</a></td>";
                                        }
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' style='text-align:center; padding:20px; color:#999;'>No history records found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>
                    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#aaa;">
                        <i class="fas fa-chart-bar" style="font-size:5rem; margin-bottom:20px;"></i>
                        <h2>Select a Class</h2>
                        <p>Choose a class from the list on the left to manage points.</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</body>
</html>