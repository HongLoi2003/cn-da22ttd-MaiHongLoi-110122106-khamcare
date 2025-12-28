<?php
session_start();
require_once 'db_config.php';
require_once 'doctor_data_helper.php';

header('Content-Type: application/json; charset=utf-8');

function json_ok($data = [], $extra = []) {
    echo json_encode(array_merge(['success' => true, 'data' => $data], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function json_err($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_specialties': {
            $specialties = getSpecialties();
            json_ok($specialties);
        }
        case 'get_doctors': {
            $keyword = trim($_GET['keyword'] ?? '');
            $specialtyId = isset($_GET['specialty_id']) && $_GET['specialty_id'] !== '' ? (int)$_GET['specialty_id'] : null;
            
            // Sử dụng getTopDoctorsFromViewDoctor để lấy 18 bác sĩ chuẩn từ TrangChu.php
            $allDoctors = getTopDoctorsFromViewDoctor(18);
            
            // Lọc theo specialty_id hoặc keyword nếu có
            $filteredDoctors = $allDoctors;
            
            if ($specialtyId !== null) {
                // Mapping specialty_id -> specialty_name
                $specialtyMap = [
                    1 => 'Tim Mạch',
                    2 => 'Thần Kinh',
                    3 => 'Nhi Khoa',
                    4 => 'Da Liễu',
                    5 => 'Sản Phụ Khoa',
                    6 => 'Nội Tổng Quát'
                ];
                
                $targetSpecialty = $specialtyMap[$specialtyId] ?? '';
                if ($targetSpecialty) {
                    $filteredDoctors = array_filter($allDoctors, function($doctor) use ($targetSpecialty) {
                        return $doctor['specialty_name'] === $targetSpecialty;
                    });
                    $filteredDoctors = array_values($filteredDoctors); // Re-index array
                }
            }
            
            if ($keyword) {
                $filteredDoctors = array_filter($filteredDoctors, function($doctor) use ($keyword) {
                    $keywordLower = mb_strtolower($keyword, 'UTF-8');
                    $nameLower = mb_strtolower($doctor['full_name'], 'UTF-8');
                    $specialtyLower = mb_strtolower($doctor['specialty_name'], 'UTF-8');
                    return strpos($nameLower, $keywordLower) !== false || 
                           strpos($specialtyLower, $keywordLower) !== false;
                });
                $filteredDoctors = array_values($filteredDoctors); // Re-index array
            }
            
            json_ok($filteredDoctors);
        }
        case 'get_available_slots': {
            $doctorId = (int)($_GET['doctor_id'] ?? 0);
            $date = $_GET['date'] ?? date('Y-m-d');
            if (!$doctorId) json_err('Thiếu doctor_id');

            $pdo = getDBConnection();
            if (!$pdo) json_err('Lỗi kết nối database');

            // Lấy slot đã bận (pending/confirmed)
            $stmt = $pdo->prepare("SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status IN ('pending','confirmed')");
            $stmt->execute([$doctorId, $date]);
            $busy = array_map(function($r){ return substr($r['appointment_time'],0,5); }, $stmt->fetchAll());

            // Tạo slot 30 phút từ 08:00-16:30
            $slots = [];
            $start = strtotime($date . ' 08:00:00');
            $end = strtotime($date . ' 16:30:00');
            for ($t = $start; $t <= $end; $t += 30*60) {
                $hm = date('H:i', $t);
                if (!in_array($hm, $busy, true)) {
                    $slots[] = ['time' => $hm . ':00', 'display_time' => $hm];
                }
            }
            json_ok($slots);
        }
        case 'book_appointment': {
            $raw = file_get_contents('php://input');
            $payload = json_decode($raw, true);
            if (!is_array($payload)) json_err('Dữ liệu không hợp lệ');

            $patientId = (int)($payload['patient_id'] ?? 0);
            $doctorId = (int)($payload['doctor_id'] ?? 0);
            $date = $payload['appointment_date'] ?? '';
            $time = $payload['appointment_time'] ?? '';
            $notes = $payload['notes'] ?? '';
            $type = $payload['consultation_type'] ?? 'offline';

            if (!$patientId || !$doctorId || !$date || !$time) {
                json_err('Thiếu thông tin bắt buộc');
            }

            $res = bookAppointment($patientId, $doctorId, $date, $time, $notes, $type);
            if (!empty($res['success'])) {
                json_ok([], ['appointment_id' => $res['appointment_id'] ?? null]);
            }
            json_err($res['message'] ?? 'Đặt lịch thất bại');
        }
        default:
            json_err('Action không hợp lệ', 404);
    }
} catch (Throwable $e) {
    json_err('Lỗi: ' . $e->getMessage(), 500);
}
