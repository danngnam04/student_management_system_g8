<?php
// delete-student.php
session_start();
include 'db.php';

// protect
if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

// get student id
if (!isset($_GET['id'])) {
    die("Error: Student ID is missing.");
}
$student_id = (int)$_GET['id'];

// get photo url
$stmt_info = $conn->prepare("SELECT class_id, student_photo FROM students WHERE id = ?");
$stmt_info->bind_param("i", $student_id);
$stmt_info->execute();
$result_info = $stmt_info->get_result();
$student_info = $result_info->fetch_assoc();
$stmt_info->close();

if (!$student_info) {
    die("Student not found.");
}

$class_id_to_return_to = $student_info['class_id'];
$photo_path_to_delete = $student_info['student_photo'];

// delet student
$stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);

if ($stmt->execute()) {
    // if success delete DB, delete photo
    if (!empty($photo_path_to_delete) && file_exists($photo_path_to_delete)) {
        unlink($photo_path_to_delete);
    }
    
    // back to list
    header('Location: student_list.php?class_id=' . $class_id_to_return_to);
    exit;
} else {
    echo "Error deleting record: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>