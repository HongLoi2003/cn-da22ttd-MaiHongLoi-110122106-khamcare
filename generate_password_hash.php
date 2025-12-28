<?php
/**
 * Generate password hash cho 123456789
 */
$password = '123456789';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Mật khẩu: $password\n";
echo "Hash: $hash\n";
echo "\n";

// Verify
if (password_verify($password, $hash)) {
    echo "✅ Verify thành công!\n";
} else {
    echo "❌ Verify thất bại!\n";
}

// Test với hash cũ
$oldHash = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm';
echo "\nTest hash cũ: ";
if (password_verify($password, $oldHash)) {
    echo "✅ Hash cũ OK!\n";
} else {
    echo "❌ Hash cũ KHÔNG ĐÚNG!\n";
}
?>
