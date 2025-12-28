<?php
/**
 * API lưu hồ sơ bệnh án - Sử dụng bảng tiếng Việt
 */

session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

// Chỉ cho phép bác sĩ
if (strtolower($_SESSION['role']) !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Chỉ bác sĩ mới có quyền tạo hồ sơ bệnh án']);
    exit;
}

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Không thể kết nối database');
    }
    
    // Lấy thông tin bác sĩ từ bảng bac_si (tiếng Việt)
    $stmt = $pdo->prepare("SELECT id FROM bac_si WHERE nguoi_dung_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch();
    
    if (!$doctor) {
        throw new Exception('Không tìm thấy thông tin bác sĩ');
    }
    
    $doctor_id = $doctor['id'];
    
    // Lấy dữ liệu từ request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $data = $_POST; // Fallback to POST data
    }
    
    // Validate dữ liệu bắt buộc
    $appointment_id = isset($data['appointment_id']) ? (int)$data['appointment_id'] : 0;
    $patient_id = isset($data['patient_id']) ? (int)$data['patient_id'] : 0;
    $diagnosis = isset($data['diagnosis']) ? trim($data['diagnosis']) : '';
    
    if ($appointment_id <= 0) {
        throw new Exception('Appointment ID không hợp lệ');
    }
    
    if ($patient_id <= 0) {
        throw new Exception('Patient ID không hợp lệ');
    }
    
    if (empty($diagnosis)) {
        throw new Exception('Vui lòng nhập chẩn đoán');
    }
    
    // Kiểm tra lịch hẹn có thuộc về bác sĩ này không (bảng lich_hen)
    $stmt = $pdo->prepare("SELECT id FROM lich_hen WHERE id = ? AND bac_si_id = ? LIMIT 1");
    $stmt->execute([$appointment_id, $doctor_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Lịch hẹn không tồn tại hoặc không thuộc về bạn');
    }
    
    // Kiểm tra xem đã có hồ sơ cho lịch hẹn này chưa (bảng ho_so_benh_an)
    $stmt = $pdo->prepare("SELECT id FROM ho_so_benh_an WHERE lich_hen_id = ? LIMIT 1");
    $stmt->execute([$appointment_id]);
    $existingRecord = $stmt->fetch();
    
    // Chuẩn bị dữ liệu
    $symptoms_reported = isset($data['symptoms_reported']) ? trim($data['symptoms_reported']) : null;
    $examination_findings = isset($data['examination_findings']) ? trim($data['examination_findings']) : null;
    $treatment_plan = isset($data['treatment_plan']) ? trim($data['treatment_plan']) : null;
    $notes = isset($data['notes']) ? trim($data['notes']) : null;
    $follow_up_date = isset($data['follow_up_date']) && !empty($data['follow_up_date']) ? trim($data['follow_up_date']) : null;
    
    // Xử lý đơn thuốc
    $prescription_text = '';
    if (isset($data['medicines']) && is_array($data['medicines']) && count($data['medicines']) > 0) {
        $prescription_lines = [];
        foreach ($data['medicines'] as $med) {
            $line = $med['medicine_name'] ?? '';
            if (!empty($med['dosage'])) $line .= ' - ' . $med['dosage'];
            if (!empty($med['frequency'])) $line .= ' - ' . $med['frequency'];
            if (!empty($med['duration'])) $line .= ' - ' . $med['duration'];
            if (!empty($med['instructions'])) $line .= ' (' . $med['instructions'] . ')';
            if (!empty($line)) $prescription_lines[] = $line;
        }
        $prescription_text = implode("\n", $prescription_lines);
    }
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    try {
        if ($existingRecord) {
            // Cập nhật hồ sơ đã tồn tại
            $stmt = $pdo->prepare("
                UPDATE ho_so_benh_an SET
                    trieu_chung = ?,
                    chan_doan = ?,
                    don_thuoc = ?,
                    ngay_tai_kham = ?,
                    ghi_chu = ?,
                    ngay_cap_nhat = NOW()
                WHERE lich_hen_id = ?
            ");
            
            // Gộp examination_findings và treatment_plan vào ghi_chu
            $full_notes = '';
            if (!empty($examination_findings)) {
                $full_notes .= "Kết quả khám: " . $examination_findings . "\n\n";
            }
            if (!empty($treatment_plan)) {
                $full_notes .= "Phương pháp điều trị: " . $treatment_plan . "\n\n";
            }
            if (!empty($notes)) {
                $full_notes .= "Ghi chú: " . $notes;
            }
            
            $stmt->execute([
                $symptoms_reported,
                $diagnosis,
                $prescription_text,
                $follow_up_date,
                trim($full_notes),
                $appointment_id
            ]);
            
            $medical_record_id = $existingRecord['id'];
            $message = 'Cập nhật hồ sơ bệnh án thành công';
        } else {
            // Tạo hồ sơ mới
            // Gộp examination_findings và treatment_plan vào ghi_chu
            $full_notes = '';
            if (!empty($examination_findings)) {
                $full_notes .= "Kết quả khám: " . $examination_findings . "\n\n";
            }
            if (!empty($treatment_plan)) {
                $full_notes .= "Phương pháp điều trị: " . $treatment_plan . "\n\n";
            }
            if (!empty($notes)) {
                $full_notes .= "Ghi chú: " . $notes;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO ho_so_benh_an (
                    lich_hen_id, benh_nhan_id, bac_si_id,
                    trieu_chung, chan_doan, don_thuoc, 
                    ngay_tai_kham, ghi_chu,
                    ngay_tao, ngay_cap_nhat
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $appointment_id,
                $patient_id,
                $doctor_id,
                $symptoms_reported,
                $diagnosis,
                $prescription_text,
                $follow_up_date,
                trim($full_notes)
            ]);
            
            $medical_record_id = $pdo->lastInsertId();
            $message = 'Lưu hồ sơ bệnh án thành công';
        }
        
        // Cập nhật trạng thái lịch hẹn thành hoàn thành
        $stmt = $pdo->prepare("UPDATE lich_hen SET trang_thai = 'hoan_thanh' WHERE id = ?");
        $stmt->execute([$appointment_id]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'medical_record_id' => $medical_record_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
