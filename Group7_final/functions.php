<?php

/**
 * Xử lý việc upload file (ảnh)
 * @param string 
 * @param string 
 * @return string|null 
 */
function upload_photo($file_key, $target_dir = 'uploads/') {
    // 1. Kiểm tra xem file có được tải lên và không bị lỗi
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] != UPLOAD_ERR_OK) {
        return null; // Không có file hoặc file bị lỗi
    }

    $file = $_FILES[$file_key];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];


    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
    $target_path = $target_dir . $unique_name;

    if (move_uploaded_file($file_tmp, $target_path)) {
        return $target_path; 
    } else {
        return null; 
    }
}
?>