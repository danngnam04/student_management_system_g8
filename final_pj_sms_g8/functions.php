<?php
// functions.php

/**
 * Xử lý việc upload file (ảnh)
 * @param string $file_key - Tên của input (ví dụ: 'teacher_photo')
 * @param string $target_dir - Thư mục để lưu (ví dụ: 'uploads/')
 * @return string|null - Trả về đường dẫn file nếu thành công, NULL nếu thất bại
 */
function upload_photo($file_key, $target_dir = 'uploads/') {
    // 1. Kiểm tra xem file có được tải lên và không bị lỗi
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] != UPLOAD_ERR_OK) {
        return null; // Không có file hoặc file bị lỗi
    }

    $file = $_FILES[$file_key];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];

    // 2. (Bảo mật) Tạo một tên file duy nhất để tránh bị ghi đè
    // Ví dụ: 65f9a..._teacher_avatar.jpg
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
    $target_path = $target_dir . $unique_name;

    // 3. Di chuyển file từ thư mục tạm vào thư mục 'uploads/'
    if (move_uploaded_file($file_tmp, $target_path)) {
        return $target_path; // Trả về đường dẫn (ví dụ: 'uploads/65f9a_...jpg')
    } else {
        return null; // Di chuyển file thất bại
    }
}
?>