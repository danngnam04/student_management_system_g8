<?php
// class-create.php (Gộp)
session_start();
include 'db.php';

// === SAO CHÉP HÀM UPLOAD VÀO ĐÂY ===
// Giờ bạn có thể xóa file functions.php (sau khi làm file student-create)
function upload_photo($file_key, $target_dir = 'uploads/') {
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] != UPLOAD_ERR_OK) {
        return null;
    }
    $file = $_FILES[$file_key];
    $file_tmp = $file['tmp_name'];
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
    $target_path = $target_dir . $unique_name;
    if (move_uploaded_file($file_tmp, $target_path)) {
        return $target_path;
    } else {
        return null;
    }
}
// === KẾT THÚC HÀM UPLOAD ===

// === PHẦN 1: XỬ LÝ LOGIC (NẾU LÀ POST REQUEST) ===
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Lấy dữ liệu văn bản
    $class_name = $_POST['class_name'];
    $grade_block = $_POST['grade_block'];
    $teacher_name = $_POST['teacher_name'];

    // 2. Xử lý upload ảnh giáo viên (gọi hàm vừa dán ở trên)
    $teacher_photo_path = upload_photo('teacher_photo', 'uploads/');

    // 3. Lưu vào Database
    $stmt = $conn->prepare("INSERT INTO classes (class_name, grade_block, teacher_name, teacher_photo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $class_name, $grade_block, $teacher_name, $teacher_photo_path);
    
    if ($stmt->execute()) {
        header('Location: dashboard.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
    exit; // Dừng script sau khi xử lý POST
}

// === PHẦN 2: HIỂN THỊ FORM (NẾU LÀ GET REQUEST) ===
// (Bảo vệ trang, chỉ ai đăng nhập mới được vào)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Class</title>
    <link rel="stylesheet" href="style_simple.css">
</head>
<body>
    <div class="form-box">
        <h2>Add New Class</h2>
        
        <form action="" method="POST" enctype="multipart/form-data">
            
            <div class="input-group">
                <label for="class_name">Class Name (e.g., 10A1):</label>
                <input type="text" id="class_name" name="class_name" required>
            </div>
            
            <div class="input-group">
                <label for="grade_block">Grade Block (e.g., 10):</label>
                <input type="text" id="grade_block" name="grade_block" required>
            </div>

            <div class="input-group">
                <label for="teacher_name">Head Teacher's Name:</label>
                <input type="text" id="teacher_name" name="teacher_name" required>
            </div>

            <div class="input-group">
                <label for="teacher_photo">Head Teacher's Photo:</label>
                <input type="file" id="teacher_photo" name="teacher_photo" accept="image/*">
            </div>

            <button type="submit" class="btn">Save Class</button>
            <a href="dashboard.php" class="btn-back" style="display: block; text-align: center; margin-top: 1rem;">Back to Dashboard</a>
        </form>
    </div>
</body>
</html>