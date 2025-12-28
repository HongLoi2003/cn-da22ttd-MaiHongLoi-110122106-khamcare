<?php
/**
 * Callback handler cho đăng nhập mạng xã hội
 * Xử lý response từ Facebook và Google OAuth
 */

session_start();
require_once 'db_config.php';
require_once 'social_auth_config.php';

$provider = $_GET['provider'] ?? '';
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

// Kiểm tra state để chống CSRF
if (empty($state) || $state !== ($_SESSION['oauth_state'] ?? '')) {
    die('Invalid state parameter');
}

try {
    if ($provider === 'facebook') {
        // Xử lý Facebook login
        $accessToken = getFacebookAccessToken($code);
        if (!$accessToken) {
            throw new Exception('Failed to get Facebook access token');
        }
        
        $userInfo = getFacebookUserInfo($accessToken);
        if (!$userInfo || !isset($userInfo['email'])) {
            throw new Exception('Failed to get Facebook user info');
        }
        
        // Chuẩn hóa dữ liệu
        $socialData = [
            'provider' => 'facebook',
            'provider_id' => $userInfo['id'],
            'email' => $userInfo['email'],
            'full_name' => $userInfo['name'],
            'avatar' => $userInfo['picture']['data']['url'] ?? null
        ];
        
    } elseif ($provider === 'google') {
        // Xử lý Google login
        $accessToken = getGoogleAccessToken($code);
        if (!$accessToken) {
            throw new Exception('Failed to get Google access token');
        }
        
        $userInfo = getGoogleUserInfo($accessToken);
        if (!$userInfo || !isset($userInfo['email'])) {
            throw new Exception('Failed to get Google user info');
        }
        
        // Chuẩn hóa dữ liệu
        $socialData = [
            'provider' => 'google',
            'provider_id' => $userInfo['id'],
            'email' => $userInfo['email'],
            'full_name' => $userInfo['name'],
            'avatar' => $userInfo['picture'] ?? null
        ];
        
    } else {
        throw new Exception('Invalid provider');
    }
    
    // Xử lý đăng nhập/đăng ký với social data
    $result = loginOrRegisterWithSocial($socialData);
    
    if ($result['success']) {
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['username'] = $result['user']['username'];
        $_SESSION['full_name'] = $result['user']['full_name'];
        $_SESSION['role'] = $result['user']['role'];
        
        // Redirect theo role
        if ($result['user']['role'] === 'admin') {
            header('Location: admin_khamcare.php');
        } elseif ($result['user']['role'] === 'doctor') {
            header('Location: doctor_dashboard.php');
        } else {
            header('Location: TrangChu.php');
        }
        exit;
    } else {
        throw new Exception($result['message']);
    }
    
} catch (Exception $e) {
    $_SESSION['social_login_error'] = $e->getMessage();
    header('Location: TaiKhoan.php');
    exit;
}

/**
 * Đăng nhập hoặc đăng ký với social account
 */
function loginOrRegisterWithSocial($socialData) {
    global $conn;
    
    // Kiểm tra xem user đã tồn tại chưa (theo email hoặc provider_id)
    $stmt = $conn->prepare("
        SELECT * FROM users 
        WHERE email = ? OR (social_provider = ? AND social_provider_id = ?)
        LIMIT 1
    ");
    $stmt->bind_param('sss', 
        $socialData['email'], 
        $socialData['provider'], 
        $socialData['provider_id']
    );
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User đã tồn tại - cập nhật thông tin social nếu cần
        $user = $result->fetch_assoc();
        
        if (empty($user['social_provider'])) {
            // Link social account với tài khoản hiện tại
            $updateStmt = $conn->prepare("
                UPDATE users 
                SET social_provider = ?, social_provider_id = ?, avatar_url = ?
                WHERE id = ?
            ");
            $updateStmt->bind_param('sssi', 
                $socialData['provider'],
                $socialData['provider_id'],
                $socialData['avatar'],
                $user['id']
            );
            $updateStmt->execute();
        }
        
        return [
            'success' => true,
            'user' => $user,
            'message' => 'Đăng nhập thành công!'
        ];
        
    } else {
        // Tạo user mới
        $username = generateUsernameFromEmail($socialData['email']);
        $randomPassword = bin2hex(random_bytes(16)); // Password ngẫu nhiên
        $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
        
        $insertStmt = $conn->prepare("
            INSERT INTO users (username, email, password, full_name, role, social_provider, social_provider_id, avatar_url, created_at)
            VALUES (?, ?, ?, ?, 'patient', ?, ?, ?, NOW())
        ");
        $insertStmt->bind_param('sssssss',
            $username,
            $socialData['email'],
            $hashedPassword,
            $socialData['full_name'],
            $socialData['provider'],
            $socialData['provider_id'],
            $socialData['avatar']
        );
        
        if ($insertStmt->execute()) {
            $userId = $conn->insert_id;
            
            return [
                'success' => true,
                'user' => [
                    'id' => $userId,
                    'username' => $username,
                    'full_name' => $socialData['full_name'],
                    'email' => $socialData['email'],
                    'role' => 'patient'
                ],
                'message' => 'Đăng ký thành công!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Lỗi khi tạo tài khoản: ' . $conn->error
            ];
        }
    }
}

/**
 * Tạo username từ email
 */
function generateUsernameFromEmail($email) {
    global $conn;
    
    $baseUsername = explode('@', $email)[0];
    $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $baseUsername);
    
    $username = $baseUsername;
    $counter = 1;
    
    // Kiểm tra username đã tồn tại chưa
    while (true) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            break;
        }
        
        $username = $baseUsername . $counter;
        $counter++;
    }
    
    return $username;
}
