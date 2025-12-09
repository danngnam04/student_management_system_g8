<?php
// dashboard.php - MONOCHROME LAYOUT
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'] ?? 'student';

// XỬ LÝ TÌM KIẾM
$search_keyword = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_keyword = $_GET['search'];
    $sql = "SELECT classes.*, COUNT(students.id) AS student_count 
            FROM classes 
            LEFT JOIN students ON classes.id = students.class_id 
            WHERE classes.class_name LIKE '%$search_keyword%' 
            GROUP BY classes.id ORDER BY classes.class_name";
} else {
    $sql = "SELECT classes.*, COUNT(students.id) AS student_count 
            FROM classes 
            LEFT JOIN students ON classes.id = students.class_id 
            GROUP BY classes.id ORDER BY classes.class_name";
}
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style_simple.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    
    <div class="app-container">
        
        
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fas fa-graduation-cap"></i> <h3>SMS Portal</h3></div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chalkboard"></i> Class Management
                    </a>
                </li>
                <li>
                    <a href="search_student.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                        <i class="fas fa-search"></i> Search Students
                    </a>
                </li>
                <li>
                    <a href="class_ranking.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'class_ranking.php' ? 'active' : ''; ?>">
                        <i class="fas fa-trophy"></i> Class Ranking
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
            
                <p style="text-align:center; font-size:0.9rem;">User: <strong><?php echo htmlspecialchars($user_name); ?></strong></p>
                <a href="logout.php" style="display:block; text-align:center; color:#ff7675; text-decoration:none; margin-top:5px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            
            <div class="top-navbar">
                <h2 class="page-title">Class Management</h2>
                </div>

            <div class="content-body">
                <div class="dashboard-container">
                    
                    <div class="header-bar">
                        <div>
                            <?php if ($user_role == 'admin'): ?>
                                <a href="class_create.php" class="btn btn-create">
                                    <i class="fas fa-plus" style="margin-right:5px;"></i> Add New Class
                                </a>
                            <?php endif; ?>
                        </div>

                        <form action="" method="GET" class="search-form-inline">
                            <input type="text" name="search" placeholder="Search class..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                            <button type="submit" class="btn btn-create">Search</button>
                        </form>
                    </div>

                    <table class="table-data">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Class Name</th>
                                <th>Grade</th>
                                <th>Head Teacher</th>
                                <th>Students</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                $i = 1; 
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $i . "</td>"; 
                                    echo "<td><a href='student_list.php?class_id=" . $row['id'] . "' style='font-weight:bold; border-bottom:1px solid #000;'>" . htmlspecialchars($row['class_name']) . "</a></td>";
                                    echo "<td>" . htmlspecialchars($row['grade_block']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['teacher_name']) . "</td>";
                                    echo "<td>" . $row['student_count'] . "</td>";
                                    
                                    echo "<td>";
                                    if ($user_role == 'admin') {
                                        echo "<a href='edit_class.php?id=" . $row['id'] . "' class='btn btn-edit' style='margin-right:5px;'>Edit</a>";
                                        echo "<a href='delete-class.php?id=" . $row['id'] . "' class='btn btn-delete' onclick=\"return confirm('Delete?');\">Delete</a>";
                                    } else {
                                        echo "<span style='color:#ccc; font-size:0.8rem;'>View Only</span>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                    $i++; 
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align:center; padding:30px;'>No classes found.</td></tr>"; 
                            }
                            ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </main>
    </div>

</body>
</html>