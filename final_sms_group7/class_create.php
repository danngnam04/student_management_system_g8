<?php
// class-create.php (Full Update with Phone & Email)
session_start();
include 'db.php';

// --- 1. SECURITY CHECK: Only Admin can access ---
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("❌ <strong>ACCESS DENIED:</strong> You do not have permission to perform this action. <a href='dashboard.php'>Back to Dashboard</a>");
}

// Function to upload photo
function upload_photo($file_key, $target_dir = 'uploads/') {
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] != UPLOAD_ERR_OK) {
        return null;
    }
    $file = $_FILES[$file_key];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_name = uniqid() . '_' . time() . '.' . $ext;
    $target_path = $target_dir . $unique_name;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $target_path;
    }
    return null;
}

// --- 2. HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get Class Info
    $class_name = $_POST['class_name'];
    $grade_block = $_POST['grade_block'];
    
    // Get Teacher Info
    $teacher_name = $_POST['teacher_name'];
    $teacher_phone = $_POST['teacher_phone']; // New
    $teacher_email = $_POST['teacher_email']; // New

    // Handle Photo
    $teacher_photo_path = upload_photo('teacher_photo', 'uploads/');

    // Insert into Database
    // Note: Added teacher_phone and teacher_email columns
    $sql = "INSERT INTO classes (class_name, grade_block, teacher_name, teacher_phone, teacher_email, teacher_photo) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    // "ssssss" means 6 strings
    $stmt->bind_param("ssssss", $class_name, $grade_block, $teacher_name, $teacher_phone, $teacher_email, $teacher_photo_path);
    
    if ($stmt->execute()) {
        header('Location: dashboard.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
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
                <label>Class Name (e.g., 10A1):</label>
                <input type="text" name="class_name" required>
            </div>
            
            <div class="input-group">
                <label>Grade Block (e.g., 10):</label>
                <input type="text" name="grade_block" required>
            </div>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 1.5rem 0;">
            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--primary-color);">Head Teacher Info</h3>

            <div class="input-group">
                <label>Teacher's Name:</label>
                <input type="text" name="teacher_name" required>
            </div>

            <div class="input-group">
                <label>Teacher's Phone:</label>
                <input type="text" name="teacher_phone" placeholder="e.g. 0912345678">
            </div>

            <div class="input-group">
                <label>Teacher's Email:</label>
                <input type="email" name="teacher_email" placeholder="e.g. teacher@school.edu">
            </div>

            <div class="input-group">
                <label>Teacher's Photo:</label>
                <input type="file" name="teacher_photo" accept="image/*">
            </div>

            <button type="submit" class="btn">Save Class</button>
            <a href="dashboard.php" class="btn-back" style="display: block; text-align: center; margin-top: 1rem;">Back to Dashboard</a>
        </form>
    </div>
</body>
</html>