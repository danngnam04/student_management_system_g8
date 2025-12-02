<?php
// student_list.php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['class_id'])) {
    die("Error: Class ID is missing.");
}
$class_id = (int)$_GET['class_id'];

// get class info
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$class_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$class_info) {
    die("Error: Class not found.");
}

// Lấy danh sách học sinh
$stmt = $conn->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY full_name");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$students_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student List - <?php echo htmlspecialchars($class_info['class_name']); ?></title>
    <link rel="stylesheet" href="style_simple.css">
</head>
<body>
    
    <div class="dashboard-container">
        
        <a href="dashboard.php" class="btn-back">&laquo; Back to All Classes</a>

        <div class="teacher-info-box">
            <img src="<?php echo htmlspecialchars($class_info['teacher_photo'] ?? 'images/default_avatar.png'); ?>" 
                 alt="Teacher Photo" 
                 class="profile-photo-large">
            <div>
                <h2>Class: <?php echo htmlspecialchars($class_info['class_name']); ?></h2>
                <h3>Head Teacher: <?php echo htmlspecialchars($class_info['teacher_name']); ?></h3>
            </div>
        </div>

        <hr>

        <div class="header-bar">
            <h3>Student List</h3>
            <a href="student_create.php?class_id=<?php echo $class_id; ?>" class="btn btn-create">Add New Student</a>
        </div>

        <table class="table-data">
            <thead>
                <tr>
                    <th>No.</th> <th>Photo</th>
                    <th>Full Name</th>
                    <th>Date of Birth</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($students_result->num_rows > 0) {
                    // start counting
                    $i = 1; 
                    while($row = $students_result->fetch_assoc()) {
                        echo "<tr>";
                        // show counting variables
                        echo "<td>" . $i . "</td>"; 
                        
                        echo "<td><img src='" . htmlspecialchars($row['student_photo'] ?? 'images/default_avatar.png') . "' alt='Student Photo' class='profile-photo-small'></td>";
                        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['dob']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                        echo "<td class='actions'>";
                        echo "<a href='student_edit.php?id=" . $row['id'] . "' class='btn btn-edit'>Edit</a>";
                        echo "<a href='delete-student.php?id=" . $row['id'] . "' class='btn btn-delete' onclick=\"return confirm('Are you sure you want to delete this student?');\">Delete</a>";
                        echo "</td>";
                        echo "</tr>";
                        
                        // increase
                        $i++; 
                    }
                } else {
                    // update colspan
                    echo "<tr><td colspan='8'>This class has no students yet.</td></tr>"; 
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>