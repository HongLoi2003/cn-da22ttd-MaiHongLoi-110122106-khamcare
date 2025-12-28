<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// -----------------------------------------------------------
// HÀM TRẢ JSON NHANH
// -----------------------------------------------------------
function json_res($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// -----------------------------------------------------------
// 1️⃣ NHẬN DỮ LIỆU THANH TOÁN TỪ FORM HOẶC JSON
// -----------------------------------------------------------
$ct = $_SERVER['CONTENT_TYPE'] ?? '';
$input = [];

if (strpos($ct, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
} else {
    $input = $_POST;
}

// Lấy các trường cơ bản
$order_id       = trim((string)($input['order_id'] ?? ''));
$amount         = isset($input['amount']) ? (float)$input['amount'] : 0.0;
$currency       = strtoupper(trim((string)($input['currency'] ?? 'VND')));
$description    = trim((string)($input['description'] ?? ''));
$customer_name  = trim((string)($input['customer_name'] ?? ''));
$customer_email = trim((string)($input['customer_email'] ?? ''));

// Kiểm tra dữ liệu
if ($order_id === '' || $amount <= 0) {
    json_res(['success' => false, 'message' => 'order_id và amount là bắt buộc'], 400);
}

// -----------------------------------------------------------
// 2️⃣ KHỞI TẠO DB (SQLite DEMO)
// -----------------------------------------------------------
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}
$dbFile = $dataDir . '/payments.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id TEXT UNIQUE,
        amount REAL,
        currency TEXT,
        description TEXT,
        customer_name TEXT,
        customer_email TEXT,
        status TEXT,
        gateway_request TEXT,
        gateway_response TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME
    )");
} catch (Exception $e) {
    json_res(['success' => false, 'message' => 'Không thể kết nối DB', 'error' => $e->getMessage()], 500);
}

// -----------------------------------------------------------
// 3️⃣ LƯU THÔNG TIN ĐƠN HÀNG BAN ĐẦU
// -----------------------------------------------------------
try {
    $stmt = $pdo->prepare("
        INSERT OR IGNORE INTO payments (order_id, amount, currency, description, customer_name, customer_email, status)
        VALUES (:order_id, :amount, :currency, :description, :customer_name, :customer_email, 'created')
    ");
    $stmt->execute([
        ':order_id' => $order_id,
        ':amount' => $amount,
        ':currency' => $currency,
        ':description' => $description,
        ':customer_name' => $customer_name,
        ':customer_email' => $customer_email,
    ]);

    $stmt = $pdo->prepare("SELECT id FROM payments WHERE order_id = :order_id LIMIT 1");
    $stmt->execute([':order_id' => $order_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $paymentId = $row ? (int)$row['id'] : null;
} catch (Exception $e) {
    json_res(['success' => false, 'message' => 'Lỗi lưu đơn hàng', 'error' => $e->getMessage()], 500);
}

// -----------------------------------------------------------
// 4️⃣ CHUẨN BỊ DỮ LIỆU GỬI TỚI CỔNG THANH TOÁN (DEMO)
// -----------------------------------------------------------
$GATEWAY_URL      = 'https://httpbin.org/post'; // demo endpoint
$GATEWAY_MERCHANT = 'demo_merchant';
$GATEWAY_SECRET   = 'demo_secret_key';

$gatewayPayload = [
    'merchant_id'    => $GATEWAY_MERCHANT,
    'order_id'       => $order_id,
    'amount'         => (int)round($amount),
    'currency'       => $currency,
    'description'    => $description,
    'customer_name'  => $customer_name,
    'customer_email' => $customer_email,
    'timestamp'      => time(),
];

// Tạo chữ ký
$gatewayPayload['signature'] = hash_hmac(
    'sha256',
    json_encode($gatewayPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    $GATEWAY_SECRET
);

// Lưu bản request vào DB
try {
    $stmt = $pdo->prepare("
        UPDATE payments SET gateway_request = :req, status = 'sent', updated_at = CURRENT_TIMESTAMP WHERE id = :id
    ");
    $stmt->execute([':req' => json_encode($gatewayPayload, JSON_UNESCAPED_UNICODE), ':id' => $paymentId]);
} catch (Exception $e) {
    // Không dừng tiến trình
}

// -----------------------------------------------------------
// 5️⃣ GỬI REQUEST TỚI CỔNG THANH TOÁN (DEMO)
// -----------------------------------------------------------
$ch = curl_init($GATEWAY_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
$body = json_encode($gatewayPayload, JSON_UNESCAPED_UNICODE);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
]);
$resp = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$gatewayResponse = $resp === false ? json_encode(['error' => $curlErr]) : $resp;

// Cập nhật kết quả phản hồi
try {
    $newStatus = ($httpCode >= 200 && $httpCode < 300) ? 'processing' : 'failed';
    $stmt = $pdo->prepare("
        UPDATE payments SET gateway_response = :resp, status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id
    ");
    $stmt->execute([':resp' => $gatewayResponse, ':status' => $newStatus, ':id' => $paymentId]);
} catch (Exception $e) {
    // ignore
}

// -----------------------------------------------------------
// 6️⃣ PHÂN TÍCH KẾT QUẢ VÀ TRẢ JSON CHO FRONTEND
// -----------------------------------------------------------
$decodedResp = json_decode($gatewayResponse, true);
$result = [
    'success' => ($httpCode >= 200 && $httpCode < 300),
    'status' => ($httpCode >= 200 && $httpCode < 300) ? 'processing' : 'failed',
    'order_id' => $order_id,
    'amount' => $amount,
    'currency' => $currency,
    'gateway_http_code' => $httpCode,
    'gateway_response' => $decodedResp !== null ? $decodedResp : $gatewayResponse,
];

// Gửi kết quả cho người dùng
json_res($result, ($httpCode >= 200 && $httpCode < 300) ? 200 : 502);


// ====================================================================
// 7️⃣ PHẦN CALLBACK: CỔNG THANH TOÁN GỌI LẠI SAU KHI XỬ LÝ XONG
// ====================================================================
// Nếu bạn muốn test callback, gọi endpoint này với ?callback=1
// và gửi JSON { "order_id": "...", "status": "success", "signature": "..." }

if (isset($_GET['callback']) && $_GET['callback'] == '1') {
    $raw = file_get_contents('php://input');
    $callbackData = json_decode($raw, true) ?? [];

    $orderId = $callbackData['order_id'] ?? '';
    $statusFromGateway = strtolower($callbackData['status'] ?? '');
    $signature = $callbackData['signature'] ?? '';

    $expectedSig = hash_hmac(
        'sha256',
        json_encode($callbackData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $GATEWAY_SECRET
    );

    if ($signature !== $expectedSig) {
        json_res(['success' => false, 'message' => 'Chữ ký không hợp lệ'], 400);
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE payments SET status = :status, gateway_response = :resp, updated_at = CURRENT_TIMESTAMP
            WHERE order_id = :oid
        ");
        $stmt->execute([
            ':status' => $statusFromGateway,
            ':resp' => json_encode($callbackData, JSON_UNESCAPED_UNICODE),
            ':oid' => $orderId
        ]);
    } catch (Exception $e) {
        json_res(['success' => false, 'message' => 'Lỗi cập nhật trạng thái', 'error' => $e->getMessage()], 500);
    }

    // Nếu thanh toán thành công → cập nhật bảng datlich trong MySQL
    if ($statusFromGateway === 'success') {
        try {
            require_once __DIR__ . '/db_config.php';
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $conn->set_charset("utf8mb4");

            if ($conn->connect_error) {
                throw new Exception('Không thể kết nối MySQL: ' . $conn->connect_error);
            }

            $sql = "UPDATE datlich SET trangthai_thanhtoan = 'Đã thanh toán' WHERE order_id = ?";
            $stmt2 = $conn->prepare($sql);
            $stmt2->bind_param('s', $orderId);
            $stmt2->execute();

            $stmt2->close();
            $conn->close();
        } catch (Exception $e) {
            error_log("Lỗi cập nhật lịch khám sau thanh toán: " . $e->getMessage());
        }
    }

    json_res(['success' => true, 'message' => 'Callback xử lý thành công']);
}
