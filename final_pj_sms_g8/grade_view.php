<?php
// dashboard.php
session_start();
include 'db.php'; 

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_name = $_SESSION['user_name'];

// Logic Tìm kiếm
$search_keyword = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_keyword = $_GET['search'];
    // Tìm theo tên lớp HOẶC tên giáo viên
    $sql = "SELECT classes.*, COUNT(students.id) AS student_count 
            FROM classes 
            LEFT JOIN students ON classes.id = students.class_id 
            WHERE classes.class_name LIKE '%$search_keyword%' 
               OR classes.teacher_name LIKE '%$search_keyword%'
            GROUP BY classes.id 
            ORDER BY classes.grade_block, classes.class_name";
} else {
    // SQL cũ (Mặc định)
    $sql = "SELECT classes.*, COUNT(students.id) AS student_count 
            FROM classes 
            LEFT JOIN students ON classes.id = students.class_id 
            GROUP BY classes.id 
            ORDER BY classes.grade_block, classes.class_name";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Management</title>
    <link rel="stylesheet" href="style_simple.css">
</head>
<body>
        <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-graduation-cap"></i>
            <h3>SMS Portal</h3>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> Class Management
                </a>
            </li>
            <li>
                <a href="search_student.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i> Search Students
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                    <small><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Student'); ?></small>
                </div>
            </div>
            <a href="logout.php" class="btn-logout-block">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </div>
    </aside>
    <div class="dashboard-container">
        
        <div class="header-bar">
            <h2>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
            <a href="logout.php" class="btn btn-logout">Logout</a>
        </div>

        <h3>Class Management</h3>
        
            <form action="" method="GET" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <input type="text" name="search" placeholder="Search class name..." 
            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
            style="padding: 8px; width: 300px;">
            <button type="submit" class="btn" style="width: auto;">Search</button>
            <?php if(isset($_GET['search'])): ?>
                <a href="dashboard.php" class="btn" style="background: #6c757d; width: auto;">Reset</a>
                <?php endif; ?>
            </form>

            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
            <a href="class_create.php" class="btn btn-create">Add New Class</a>
            <?php endif; ?>

        <table class="table-data">
            <thead>
                <tr>
                    <th>No.</th> <th>Class Name</th>
                    <th>Grade Block</th>
                    <th>Head Teacher</th>
                    <th>Student Count</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    // --- BẮT ĐẦU BIẾN ĐẾM ---
                    $i = 1; 
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                    
                        echo "<td>" . $i . "</td>"; 
                        
                        echo "<td><a href='student_list.php?class_id=" . $row['id'] . "' class='link-primary'>" 
                             . htmlspecialchars($row['class_name']) 
                             . "</a></td>";
                        echo "<td>" . htmlspecialchars($row['grade_block']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['teacher_name']) . "</td>";
                        // ... code hiển thị cột sĩ số ...
                        echo "<td>" . $row['student_count'] . "</td>";

                        // Cột Actions
                        echo "<td class='actions'>";

// KIỂM TRA QUYỀN
                    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
    // Nếu là Admin: Hiện nút
                        echo "<a href='edit_class.php?id=" . $row['id'] . "' class='btn btn-edit'>Edit</a>";
                        echo "<a href='delete-class.php?id=" . $row['id'] . "' class='btn btn-delete' onclick=\"return confirm('Delete this class?');\">Delete</a>";
                    } else {
    // Nếu là User: Hiện chữ hoặc để trống
                        echo "<span style='color:#999; font-size:0.85rem;'>View Only</span>";
                    }

                        echo "</td>";
                        echo "</tr>";
                        
                        
                        $i++; 
                    }
                } else {
                
                    echo "<tr><td colspan='6'>No classes found.</td></tr>"; 
                }
                $conn->close(); 
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>