<?php
// edit-class.php
session_start();
include 'db.php';

// Function upload (giữ nguyên)
function upload_photo($file_key, $target_dir = 'uploads/') {
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] != UPLOAD_ERR_OK) { return null; }
    $file = $_FILES[$file_key];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $path = $target_dir . uniqid() . '_' . time() . '.' . $ext;
    return move_uploaded_file($file['tmp_name'], $path) ? $path : null;
}

// 1. SECURITY CHECK: Only Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("❌ <strong>ACCESS DENIED:</strong> You do not have permission to perform this action. <a href='dashboard.php'>Back to Dashboard</a>");
}

// 2. GET CLASS ID
if (!isset($_GET['id']) && !isset($_POST['class_id'])) { die("Error: ID missing."); }
$class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : (int)$_GET['id'];

// === HANDLE POST REQUEST (UPDATE) ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $class_name = $_POST['class_name'];
    $grade_block = $_POST['grade_block'];
    $teacher_name = $_POST['teacher_name'];
    
    // NEW FIELDS
    $teacher_phone = $_POST['teacher_phone'];
    $teacher_email = $_POST['teacher_email'];
    
    $old_photo_path = $_POST['old_photo_path'];
    $teacher_photo_path = $old_photo_path;

    // Handle Photo Upload
    $new_photo = upload_photo('teacher_photo', 'uploads/');
    if ($new_photo) {
        $teacher_photo_path = $new_photo;
        if ($old_photo_path && file_exists($old_photo_path)) { unlink($old_photo_path); }
    }

    // UPDATE SQL
    $stmt = $conn->prepare("UPDATE classes SET class_name=?, grade_block=?, teacher_name=?, teacher_phone=?, teacher_email=?, teacher_photo=? WHERE id=?");
    $stmt->bind_param("ssssssi", $class_name, $grade_block, $teacher_name, $teacher_phone, $teacher_email, $teacher_photo_path, $class_id);
    
    if ($stmt->execute()) {
        // Quay lại trang danh sách học sinh của lớp đó
        header('Location: student_list.php?class_id=' . $class_id);
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}

// === GET DATA FOR FORM ===
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

if (!$class) { die("Error: Class not found."); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Class</title>
    <link rel="stylesheet" href="style_simple.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="auth-page"> <div class="form-box"> <div style="text-align: center; margin-bottom: 20px;">
            <h2>Edit Class Info</h2>
            <p style="color:#666; margin:0;">Update information for <?php echo htmlspecialchars($class['class_name']); ?></p>
        </div>
        
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">

            <div class="input-group">
                <label>Class Name:</label>
                <input type="text" name="class_name" value="<?php echo htmlspecialchars($class['class_name']); ?>" required>
            </div>
            
            <div class="input-group">
                <label>Grade Block:</label>
                <input type="text" name="grade_block" value="<?php echo htmlspecialchars($class['grade_block']); ?>" required>
            </div>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 1.5rem 0;">
            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--primary-color);">Teacher Information</h3>

            <div class="input-group">
                <label>Teacher Name:</label>
                <input type="text" name="teacher_name" value="<?php echo htmlspecialchars($class['teacher_name']); ?>" required>
            </div>

            <div class="input-group">
                <label>Phone Number:</label>
                <input type="text" name="teacher_phone" value="<?php echo htmlspecialchars($class['teacher_phone'] ?? ''); ?>" placeholder="e.g. 0912345678">
            </div>

            <div class="input-group">
                <label>Email Address:</label>
                <input type="email" name="teacher_email" value="<?php echo htmlspecialchars($class['teacher_email'] ?? ''); ?>" placeholder="e.g. teacher@school.edu">
            </div>

            <div class="input-group">
                <label>Current Photo:</label>
                <div style="margin-top: 5px;">
                    <img src="<?php echo htmlspecialchars($class['teacher_photo'] ?? 'images/default_avatar.png'); ?>" class="profile-photo-small" style="width: 50px; height: 50px;">
                </div>
            </div>

            <div class="input-group">
                <label>Upload New Photo (Optional):</label>
                <input type="file" name="teacher_photo" accept="image/*">
                <input type="hidden" name="old_photo_path" value="<?php echo htmlspecialchars($class['teacher_photo'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn">Update Information</button>
            
            <div style="margin-top: 15px; text-align: center; font-size: 0.9rem;">
                <a href="student_list.php?class_id=<?php echo $class_id; ?>" class="btn-back" style="display: block; margin-bottom: 10px; color: #666;">Cancel</a>
                
                <a href="dashboard.php" style="color: #007bff; text-decoration: none; font-weight: 600;">
                    <i class="fas fa-home"></i> Back to Dashboard
                </a>
            </div>
        </form>
    </div>
</body>
</html>