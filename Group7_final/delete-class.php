<?php
// delete-class.php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


if ($_SESSION['user_role'] !== 'admin') {
    die(" <strong>ACCESS DENIED:</strong> You do not have permission to perform this action. <a href='dashboard.php'>Back to Dashboard</a>");
}
include 'db.php';

// only user can delete
if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

//get class id
if (!isset($_GET['id'])) {
    die("Error: Class ID is missing.");
}
$class_id = (int)$_GET['id'];

// get photo before delete
$photo_paths = [];

// get 
$stmt_teacher = $conn->prepare("SELECT teacher_photo FROM classes WHERE id = ?");
$stmt_teacher->bind_param("i", $class_id);
$stmt_teacher->execute();
$result_teacher = $stmt_teacher->get_result();
if ($row_teacher = $result_teacher->fetch_assoc()) {
    if (!empty($row_teacher['teacher_photo']) && file_exists($row_teacher['teacher_photo'])) {
        $photo_paths[] = $row_teacher['teacher_photo'];
    }
}
$stmt_teacher->close();

// get all student photo
$stmt_students = $conn->prepare("SELECT student_photo FROM students WHERE class_id = ?");
$stmt_students->bind_param("i", $class_id);
$stmt_students->execute();
$result_students = $stmt_students->get_result();
while ($row_student = $result_students->fetch_assoc()) {
    if (!empty($row_student['student_photo']) && file_exists($row_student['student_photo'])) {
        $photo_paths[] = $row_student['student_photo'];
    }
}
$stmt_students->close();

// delete class means delete student, use CASCADE
$stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
$stmt->bind_param("i", $class_id);

if ($stmt->execute()) {
    // if delete DB, delete photo file
    foreach ($photo_paths as $path) {
        unlink($path); // delete file
    }
    // back to dashboard
    header('Location: dashboard.php');
    exit;
} else {
    echo "Error deleting record: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>