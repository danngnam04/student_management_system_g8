<?php
// student_list.php
session_start();
include 'db.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// Get User Role
$user_role = $_SESSION['user_role'] ?? 'user';

// Get Class ID
if (!isset($_GET['class_id'])) { die("Error: Class ID is missing."); }
$class_id = (int)$_GET['class_id'];

// 2. Fetch Class & Teacher Info
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$class_info = $stmt->get_result()->fetch_assoc();
if (!$class_info) { die("Error: Class not found."); }

// 3. Fetch Students
$stmt = $conn->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY full_name");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$students_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class <?php echo htmlspecialchars($class_info['class_name']); ?></title>
    <link rel="stylesheet" href="style_simple.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    
    <div class="dashboard-container">
        
        <a href="dashboard.php" class="btn-back">&laquo; Back to Dashboard</a>

        <div class="teacher-profile-card">
            <div class="profile-left">
                <img src="<?php echo htmlspecialchars($class_info['teacher_photo'] ?? 'images/default_avatar.png'); ?>" 
                     alt="Teacher Photo" 
                     class="profile-photo-large">
                
                <div class="profile-details">
                    <h2><?php echo htmlspecialchars($class_info['teacher_name']); ?></h2>
                    <p class="role-label">Head Teacher</p>
                    
                    <div style="font-size: 0.95rem; color: #555; margin-bottom: 0.5rem; display: flex; flex-direction: column; gap: 4px;">
                        
                        <?php if(!empty($class_info['teacher_phone'])): ?>
                            <div>
                                <i class="fas fa-phone-alt" style="color: var(--primary-color); width: 20px;"></i> 
                                <?php echo htmlspecialchars($class_info['teacher_phone']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($class_info['teacher_email'])): ?>
                            <div>
                                <i class="fas fa-envelope" style="color: var(--primary-color); width: 20px;"></i> 
                                <?php echo htmlspecialchars($class_info['teacher_email']); ?>
                            </div>
                        <?php endif; ?>

                    </div>

                    <div class="class-meta">
                        <span><strong>Class:</strong> <?php echo htmlspecialchars($class_info['class_name']); ?></span>
                        <span>&bull;</span>
                        <span><strong>Grade:</strong> <?php echo htmlspecialchars($class_info['grade_block']); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($user_role == 'admin'): ?>
            <div class="profile-actions">
                <a href="edit_class.php?id=<?php echo $class_id; ?>" class="btn btn-edit">Edit Info</a>
                <a href="delete-class.php?id=<?php echo $class_id; ?>" 
                   class="btn btn-delete"
                   onclick="return confirm('Warning: Deleting this class will also delete ALL students inside it. Are you sure?');">
                    Delete Class
                </a>
            </div>
            <?php endif; ?>
        </div>

        <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #eee;">

        <div class="header-bar">
            <h3>Student List (<?php echo $students_result->num_rows; ?>)</h3>
            <?php if ($user_role == 'admin'): ?>
                <a href="student_create.php?class_id=<?php echo $class_id; ?>" class="btn btn-create">Add New Student</a>
            <?php endif; ?>
        </div>

        <table class="table-data">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Photo</th>
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
                    $i = 1; 
                    while($row = $students_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $i . "</td>"; 
                        echo "<td><img src='" . htmlspecialchars($row['student_photo'] ?? 'images/default_avatar.png') . "' class='profile-photo-small'></td>";
                        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['dob']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                        
                        echo "<td class='actions'>";
                        if ($user_role == 'admin') {
                            echo "<a href='student_edit.php?id=" . $row['id'] . "' class='btn btn-edit'>Edit</a>";
                            echo "<a href='delete-student.php?id=" . $row['id'] . "' class='btn btn-delete' onclick=\"return confirm('Delete this student?');\">Delete</a>";
                        } else {
                            echo "<span style='color:#999; font-size:0.85rem;'>View Only</span>";
                        }
                        echo "</td>";
                        echo "</tr>";
                        $i++; 
                    }
                } else {
                    echo "<tr><td colspan='8' style='text-align:center; padding: 2rem;'>No students found.</td></tr>"; 
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>