<?php
/**
 * Trang xem hồ sơ bệnh án của bệnh nhân
 */

session_start();
require_once 'db_config.php';

// Yêu cầu đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: TaiKhoan.php?redirect=patient_medical_records.php');
    exit;
}

// Chỉ cho phép role patient
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'patient') {
    header('Location: TaiKhoan.php?redirect=patient_medical_records.php&error=not_patient');
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo 'Lỗi kết nối database';
    exit;
}

$patient_id = $_SESSION['user_id'];

// Lấy thông tin bệnh nhân
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if (!$patient) {
    echo 'Không tìm thấy thông tin bệnh nhân.';
    exit;
}

// Lấy danh sách lịch hẹn chưa khám (confirmed)
$stmt = $pdo->prepare("
    SELECT a.*, 
           d.id as doctor_id,
           u.full_name as doctor_name,
           s.name as specialty_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    LEFT JOIN specialties s ON d.specialty_id = s.id
    WHERE a.patient_id = ? 
      AND a.status IN ('pending', 'confirmed')
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$patient_id]);
$pending_appointments = $stmt->fetchAll();

// Lấy danh sách hồ sơ đã khám
$stmt = $pdo->prepare("
    SELECT mr.*, 
           a.appointment_date, a.appointment_time,
           d.id as doctor_id,
           u.full_name as doctor_name,
           s.name as specialty_name
    FROM medical_records mr
    JOIN appointments a ON mr.appointment_id = a.id
    JOIN doctors d ON mr.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    LEFT JOIN specialties s ON d.specialty_id = s.id
    WHERE mr.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$patient_id]);
$medical_records = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ Sơ Bệnh Án - <?php echo htmlspecialchars($patient['full_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="global-font-override.css">
    <link rel="stylesheet" href="square-icons-override.css">
    <link rel="stylesheet" href="fix-icons-visibility.css">
    <style>
        
        
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --light: #f8fafc;
            --dark: #1e293b;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .container {
            max-width: 1200px;
        }
        
        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
            background: linear-gradient(135deg, var(--primary) 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            border-radius: 16px 16px 0 0 !important;
            padding: 1.5rem;
        }
        
        .nav-tabs {
            border: none;
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: var(--dark);
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            background: var(--light);
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }
        
        .appointment-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--warning);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .medical-record-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--success);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .btn {
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-file-medical me-2"></i>Hồ Sơ Bệnh Án
                    </h1>
                    <p class="text-muted mb-0">Xem lịch sử khám bệnh và hồ sơ của bạn</p>
                </div>
                <a href="TrangChu.php" class="btn btn-outline-primary">
                    <i class="fas fa-home me-2"></i>Trang chủ
                </a>
            </div>
        </div>
        
        <ul class="nav nav-tabs mb-4" id="medicalRecordsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                    <i class="fas fa-clock me-2"></i>Chưa khám
                    <span class="badge bg-warning ms-2"><?php echo count($pending_appointments); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab">
                    <i class="fas fa-check-circle me-2"></i>Đã khám
                    <span class="badge bg-success ms-2"><?php echo count($medical_records); ?></span>
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="medicalRecordsTabContent">
            <!-- Tab: Chưa khám -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel">
                <?php if (count($pending_appointments) > 0): ?>
                    <?php foreach ($pending_appointments as $apt): ?>
                        <div class="appointment-card">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-2">
                                        <i class="fas fa-user-md text-primary me-2"></i>
                                        BS. <?php echo htmlspecialchars($apt['doctor_name']); ?>
                                    </h5>
                                    <p class="mb-1">
                                        <i class="fas fa-stethoscope text-muted me-2"></i>
                                        <strong>Chuyên khoa:</strong> <?php echo htmlspecialchars($apt['specialty_name'] ?? 'Chưa rõ'); ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-calendar text-muted me-2"></i>
                                        <strong>Ngày khám:</strong> <?php echo date('d/m/Y', strtotime($apt['appointment_date'])); ?>
                                        <span class="badge bg-primary ms-2"><?php echo substr($apt['appointment_time'], 0, 5); ?></span>
                                    </p>
                                    <?php if (!empty($apt['chief_complaint'])): ?>
                                        <p class="mb-0">
                                            <i class="fas fa-notes-medical text-muted me-2"></i>
                                            <strong>Lý do khám:</strong> <?php echo htmlspecialchars($apt['chief_complaint']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-<?php echo $apt['status'] === 'pending' ? 'warning' : 'success'; ?> mb-2">
                                        <?php echo $apt['status'] === 'pending' ? 'Chờ xác nhận' : 'Đã xác nhận'; ?>
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Đặt lúc: <?php echo date('d/m/Y H:i', strtotime($apt['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-check"></i>
                        <h4>Không có lịch hẹn nào</h4>
                        <p>Bạn chưa có lịch hẹn nào đang chờ khám</p>
                        <a href="DatLichK.php" class="btn btn-primary mt-3">
                            <i class="fas fa-plus me-2"></i>Đặt lịch khám
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tab: Đã khám -->
            <div class="tab-pane fade" id="completed" role="tabpanel">
                <?php if (count($medical_records) > 0): ?>
                    <?php foreach ($medical_records as $record): ?>
                        <div class="medical-record-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-2">
                                        <i class="fas fa-user-md text-success me-2"></i>
                                        BS. <?php echo htmlspecialchars($record['doctor_name']); ?>
                                    </h5>
                                    <p class="mb-1">
                                        <i class="fas fa-stethoscope text-muted me-2"></i>
                                        <strong>Chuyên khoa:</strong> <?php echo htmlspecialchars($record['specialty_name'] ?? 'Chưa rõ'); ?>
                                    </p>
                                    <p class="mb-0">
                                        <i class="fas fa-calendar text-muted me-2"></i>
                                        <strong>Ngày khám:</strong> <?php echo date('d/m/Y', strtotime($record['appointment_date'])); ?>
                                        <span class="badge bg-info ms-2"><?php echo substr($record['appointment_time'], 0, 5); ?></span>
                                    </p>
                                </div>
                                <button class="btn btn-sm btn-primary" onclick="viewMedicalRecord(<?php echo $record['id']; ?>)">
                                    <i class="fas fa-eye me-1"></i>Xem chi tiết
                                </button>
                            </div>
                            
                            <div class="border-top pt-3">
                                <div class="row">
                                    <div class="col-md-12 mb-2">
                                        <strong><i class="fas fa-diagnoses text-success me-2"></i>Chẩn đoán:</strong>
                                        <p class="mb-0 ms-4"><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></p>
                                    </div>
                                    
                                    <?php if (!empty($record['prescription'])): ?>
                                        <div class="col-md-12 mb-2">
                                            <strong><i class="fas fa-pills text-primary me-2"></i>Đơn thuốc:</strong>
                                            <p class="mb-0 ms-4" style="white-space: pre-line;"><?php echo htmlspecialchars($record['prescription']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($record['follow_up_date'])): ?>
                                        <div class="col-md-12">
                                            <strong><i class="fas fa-calendar-check text-warning me-2"></i>Ngày tái khám:</strong>
                                            <span class="badge bg-warning ms-2"><?php echo date('d/m/Y', strtotime($record['follow_up_date'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-medical"></i>
                        <h4>Chưa có hồ sơ bệnh án</h4>
                        <p>Bạn chưa có hồ sơ khám bệnh nào</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal xem chi tiết hồ sơ -->
    <div class="modal fade" id="medicalRecordDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-medical me-2"></i>Chi Tiết Hồ Sơ Bệnh Án
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="medicalRecordDetailContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="printMedicalRecord()">
                        <i class="fas fa-print me-2"></i>In hồ sơ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View medical record detail
        function viewMedicalRecord(recordId) {
            $('#medicalRecordDetailModal').modal('show');
            $('#medicalRecordDetailContent').html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>');
            
            // Load medical record detail
            $.get('api_get_medical_record_detail.php', {
                record_id: recordId
            }, function(response) {
                if (response.success) {
                    const record = response.data;
                    let html = `
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Bác sĩ:</strong><br>
                                BS. ${record.doctor_name}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Chuyên khoa:</strong><br>
                                ${record.specialty_name || 'Chưa rõ'}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Ngày khám:</strong><br>
                                ${new Date(record.appointment_date).toLocaleDateString('vi-VN')}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Giờ khám:</strong><br>
                                ${record.appointment_time ? record.appointment_time.substring(0,5) : ''}
                            </div>
                        </div>
                        <hr>
                    `;
                    
                    if (record.symptoms_reported) {
                        html += `
                            <div class="mb-3">
                                <strong><i class="fas fa-notes-medical text-primary me-2"></i>Triệu chứng:</strong>
                                <p class="ms-4">${record.symptoms_reported}</p>
                            </div>
                        `;
                    }
                    
                    if (record.examination_findings) {
                        html += `
                            <div class="mb-3">
                                <strong><i class="fas fa-stethoscope text-info me-2"></i>Kết quả khám:</strong>
                                <p class="ms-4">${record.examination_findings}</p>
                            </div>
                        `;
                    }
                    
                    html += `
                        <div class="mb-3">
                            <strong><i class="fas fa-diagnoses text-success me-2"></i>Chẩn đoán:</strong>
                            <p class="ms-4">${record.diagnosis}</p>
                        </div>
                    `;
                    
                    if (record.treatment_plan) {
                        html += `
                            <div class="mb-3">
                                <strong><i class="fas fa-clipboard-list text-warning me-2"></i>Phác đồ điều trị:</strong>
                                <p class="ms-4">${record.treatment_plan}</p>
                            </div>
                        `;
                    }
                    
                    if (record.vital_signs) {
                        const vital = JSON.parse(record.vital_signs);
                        html += `
                            <div class="mb-3">
                                <strong><i class="fas fa-heartbeat text-danger me-2"></i>Chỉ số sinh tồn:</strong>
                                <div class="ms-4">
                                    ${vital.blood_pressure ? `<p class="mb-1">Huyết áp: ${vital.blood_pressure} mmHg</p>` : ''}
                                    ${vital.heart_rate ? `<p class="mb-1">Nhịp tim: ${vital.heart_rate} bpm</p>` : ''}
                                    ${vital.temperature ? `<p class="mb-1">Nhiệt độ: ${vital.temperature} °C</p>` : ''}
                                    ${vital.weight ? `<p class="mb-1">Cân nặng: ${vital.weight} kg</p>` : ''}
                                    ${vital.height ? `<p class="mb-1">Chiều cao: ${vital.height} cm</p>` : ''}
                                    ${vital.spo2 ? `<p class="mb-1">SpO2: ${vital.spo2} %</p>` : ''}
                                </div>
                            </div>
                        `;
                    }
                    
                    if (record.prescription) {
                        html += `
                            <div class="mb-3">
                                <strong><i class="fas fa-pills text-primary me-2"></i>Đơn thuốc:</strong>
                                <pre class="ms-4 bg-light p-3 rounded">${record.prescription}</pre>
                            </div>
                        `;
                    }
                    
                    if (record.follow_up_date) {
                        html += `
                            <div class="mb-3">
                                <strong><i class="fas fa-calendar-check text-warning me-2"></i>Ngày tái khám:</strong>
                                <span class="badge bg-warning ms-2">${new Date(record.follow_up_date).toLocaleDateString('vi-VN')}</span>
                            </div>
                        `;
                    }
                    
                    if (record.notes) {
                        html += `
                            <div class="mb-3">
                                <strong><i class="fas fa-comment-medical text-secondary me-2"></i>Ghi chú:</strong>
                                <p class="ms-4">${record.notes}</p>
                            </div>
                        `;
                    }
                    
                    $('#medicalRecordDetailContent').html(html);
                } else {
                    $('#medicalRecordDetailContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${response.message || 'Không thể tải thông tin hồ sơ'}
                        </div>
                    `);
                }
            }, 'json').fail(function() {
                $('#medicalRecordDetailContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Có lỗi xảy ra khi tải thông tin hồ sơ
                    </div>
                `);
            });
        }
        
        // Print medical record
        function printMedicalRecord() {
            const content = document.getElementById('medicalRecordDetailContent').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Hồ sơ bệnh án</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            body { padding: 20px; font-family: Arial, sans-serif; }
                            @media print { .btn { display: none; } }
                        </style>
                    </head>
                    <body>
                        <h3 class="mb-4">Hồ Sơ Bệnh Án</h3>
                        ${content}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>
