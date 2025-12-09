<?php
// search_student.php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_role = $_SESSION['user_role'] ?? 'user';
$user_name = $_SESSION['user_name'] ?? 'User';

$search_results = null;
$search_name = $_GET['student_name'] ?? '';
$search_class = $_GET['class_name'] ?? '';

if (isset($_GET['btn_search'])) {
    $sql = "SELECT students.*, classes.class_name 
            FROM students 
            JOIN classes ON students.class_id = classes.id 
            WHERE students.full_name LIKE ? AND classes.class_name LIKE ?
            ORDER BY classes.class_name, students.full_name";
    $stmt = $conn->prepare($sql);
    $n = "%$search_name%"; $c = "%$search_class%";
    $stmt->bind_param("ss", $n, $c);
    $stmt->execute();
    $search_results = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Students</title>
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
            <div class="header-bar">
                <h2>Student Directory</h2>
            </div>

            <div class="search-card">
                <form action="" method="GET" class="search-form-grid">
                    <div class="form-group">
                        <label>Student Name</label>
                        <input type="text" name="student_name" value="<?php echo htmlspecialchars($search_name); ?>" class="form-control" placeholder="Enter name...">
                    </div>
                    <div class="form-group">
                        <label>Class Name</label>
                        <input type="text" name="class_name" value="<?php echo htmlspecialchars($search_class); ?>" class="form-control" placeholder="e.g. 10A1">
                    </div>
                    <button type="submit" name="btn_search" class="btn-search-lg"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>

            <?php if ($search_results): ?>
                <h3>Found: <?php echo $search_results->num_rows; ?> students</h3>
                <table class="table-data">
                    <thead>
                        <tr>
                            <th>Photo</th> <th>Full Name</th> <th>Class</th> 
                            <th>Academic</th> <th>DOB</th> <th>Phone</th> <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $search_results->fetch_assoc()): ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($row['student_photo'] ?? 'images/default_avatar.png'); ?>" class="profile-photo-small"></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><span style="background:#dfe6e9; padding:4px 8px; border-radius:4px; font-weight:600;"><?php echo htmlspecialchars($row['class_name']); ?></span></td>
                            
                            <td>
                                <a href="grade_view.php?student_id=<?php echo $row['id']; ?>" class="btn" style="background:#0984e3;">
                                    <i class="fas fa-chart-bar"></i> View Grades
                                </a>
                            </td>
                            
                            <td><?php echo htmlspecialchars($row['dob']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td class="actions">
                                <?php if ($user_role == 'admin'): ?>
                                    <a href="student_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">Edit</a>
                                    <a href="delete-student.php?id=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Delete?');">Delete</a>
                                <?php else: ?>
                                    <span style="color:#999;">View Only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>