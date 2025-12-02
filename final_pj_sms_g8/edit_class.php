<?php
// edit-class.php
session_start();
include 'db.php';
include 'functions.php';

// protect
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // get data from FORM
    $class_id = (int)$_POST['class_id'];
    $class_name = $_POST['class_name'];
    $grade_block = $_POST['grade_block'];
    $teacher_name = $_POST['teacher_name'];
    $old_photo_path = $_POST['old_photo_path'];
    
    $teacher_photo_path = $old_photo_path; // keep old photo by default

    // check if new photo was uploaded?
    if (isset($_FILES['teacher_photo']) && $_FILES['teacher_photo']['error'] == UPLOAD_ERR_OK) {
        
        $new_photo_path = upload_photo('teacher_photo', 'uploads/');
        
        if ($new_photo_path) {
            $teacher_photo_path = $new_photo_path; // use new photo
            
            // delete old photo
            if ($old_photo_path && file_exists($old_photo_path)) {
                unlink($old_photo_path);
            }
        }
    }

    // update into DB
    $stmt = $conn->prepare("UPDATE classes SET class_name = ?, grade_block = ?, teacher_name = ?, teacher_photo = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $class_name, $grade_block, $teacher_name, $teacher_photo_path, $class_id);
    
    if ($stmt->execute()) {
        // back to dashboard
        header('Location: dashboard.php');
        exit;
    } else {
        echo "Error updating record: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
    exit; //end script
}

// show FORM

// Lấy ID của lớp từ URL
if (!isset($_GET['id'])) {
    die("Error: Class ID is missing.");
}
$class_id = (int)$_GET['id'];

// get new info 
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();
$class = $result->fetch_assoc();
$stmt->close();
$conn->close(); // close 

if (!$class) {
    die("Error: Class not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Class</title>
    <link rel="stylesheet" href="style_simple.css">
</head>
<body>
    <div class="form-box">
        <h2>Edit Class: <?php echo htmlspecialchars($class['class_name']); ?></h2>
        
        <form action="" method="POST" enctype="multipart/form-data">
            
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">

            <div class="input-group">
                <label for="class_name">Class Name:</label>
                <input type="text" id="class_name" name="class_name" 
                       value="<?php echo htmlspecialchars($class['class_name']); ?>" required>
            </div>
            
            <div class="input-group">
                <label for="grade_block">Grade Block:</label>
                <input type="text" id="grade_block" name="grade_block" 
                       value="<?php echo htmlspecialchars($class['grade_block']); ?>" required>
            </div>

            <div class="input-group">
                <label for="teacher_name">Head Teacher's Name:</label>
                <input type="text" id="teacher_name" name="teacher_name" 
                       value="<?php echo htmlspecialchars($class['teacher_name']); ?>" required>
            </div>

            <div class="input-group">
                <label>Current Teacher's Photo:</label>
                <img src="<?php echo htmlspecialchars($class['teacher_photo'] ?? 'images/default_avatar.png'); ?>" 
                     alt="Current Photo" class="profile-photo-small" style="margin-bottom: 10px;">
            </div>

            <div class="input-group">
                <label for="teacher_photo">Upload New Photo (Optional):</label>
                <input type="file" id="teacher_photo" name="teacher_photo" accept="image/*">
                <input type="hidden" name="old_photo_path" value="<?php echo htmlspecialchars($class['teacher_photo']); ?>">
            </div>

            <button type="submit" class="btn">Update Class</button>
            <a href="dashboard.php" class="btn-back" style="display: block; text-align: center; margin-top: 1rem;">Cancel</a>
        </form>
    </div>
</body>
</html>