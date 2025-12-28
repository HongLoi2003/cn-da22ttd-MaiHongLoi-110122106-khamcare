<?php
/**
 * Lưu kết quả khám bệnh trực tiếp
 */

session_start();
require_once 'db_config.php';

// Kiểm tra quyền truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $appointmentId = (int)($_POST['appointment_id'] ?? 0);
    $action = $_POST['action'] ?? 'save';
    
    if ($appointmentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID lịch hẹn không hợp lệ']);
        exit;
    }
    
    // Lấy thông tin bác sĩ
    $stmt = $pdo->prepare("SELECT d.id FROM doctors d 
                           JOIN users u ON d.user_id = u.id 
                           WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch();
    
    if (!$doctor) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin bác sĩ']);
        exit;
    }
    
    // Kiểm tra quyền truy cập appointment
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND doctor_id = ?");
    $stmt->execute([$appointmentId, $doctor['id']]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy lịch hẹn hoặc bạn không có quyền']);
        exit;
    }
    
    try {
        // Chuẩn bị dữ liệu khám bệnh
        $examinationData = [
            'appointment_id' => $appointmentId,
            'doctor_id' => $doctor['id'],
            'patient_id' => $appointment['patient_id'],
            'temperature' => !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null,
            'pulse' => !empty($_POST['pulse']) ? (int)$_POST['pulse'] : null,
            'blood_pressure' => !empty($_POST['blood_pressure']) ? trim($_POST['blood_pressure']) : null,
            'weight' => !empty($_POST['weight']) ? (float)$_POST['weight'] : null,
            'examination_result' => !empty($_POST['examination_result']) ? trim($_POST['examination_result']) : null,
            'prescription' => !empty($_POST['prescription']) ? trim($_POST['prescription']) : null,
            'follow_up_date' => !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null,
            'examination_date' => date('Y-m-d H:i:s')
        ];
        
        // Kiểm tra xem đã có bản ghi examination chưa
        $stmt = $pdo->prepare("SELECT id FROM examinations WHERE appointment_id = ?");
        $stmt->execute([$appointmentId]);
        $existingExam = $stmt->fetch();
        
        if ($existingExam) {
            // Cập nhật bản ghi hiện có
            $stmt = $pdo->prepare("UPDATE examinations SET 
                                   temperature = ?, pulse = ?, blood_pressure = ?, weight = ?,
                                   examination_result = ?, prescription = ?, follow_up_date = ?,
                                   updated_at = NOW()
                                   WHERE appointment_id = ?");
            $stmt->execute([
                $examinationData['temperature'],
                $examinationData['pulse'], 
                $examinationData['blood_pressure'],
                $examinationData['weight'],
                $examinationData['examination_result'],
                $examinationData['prescription'],
                $examinationData['follow_up_date'],
                $appointmentId
            ]);
        } else {
            // Tạo bản ghi mới
            $stmt = $pdo->prepare("INSERT INTO examinations 
                                   (appointment_id, doctor_id, patient_id, temperature, pulse, 
                                    blood_pressure, weight, examination_result, prescription, 
                                    follow_up_date, examination_date) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $examinationData['appointment_id'],
                $examinationData['doctor_id'],
                $examinationData['patient_id'],
                $examinationData['temperature'],
                $examinationData['pulse'],
                $examinationData['blood_pressure'],
                $examinationData['weight'],
                $examinationData['examination_result'],
                $examinationData['prescription'],
                $examinationData['follow_up_date'],
                $examinationData['examination_date']
            ]);
        }
        
        // Nếu action là complete, cập nhật trạng thái appointment
        if ($action === 'complete') {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'completed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$appointmentId]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Đã hoàn thành phiên khám và lưu kết quả thành công'
            ]);
        } else {
            echo json_encode([
                'success' => true, 
                'message' => 'Đã lưu kết quả khám thành công'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Lỗi khi lưu dữ liệu: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
}
?>