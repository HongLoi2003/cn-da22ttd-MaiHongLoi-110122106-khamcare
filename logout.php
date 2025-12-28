<?php
session_start();

// Xóa tất cả session
session_destroy();

// Chuyển hướng về trang đăng nhập
header('Location: TaiKhoan.php');
exit;
?>
