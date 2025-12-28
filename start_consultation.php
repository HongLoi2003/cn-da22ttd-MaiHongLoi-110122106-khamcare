<?php
session_start();
require_once __DIR__ . '/db_config.php';
header('Content-Type: application/json');

$pdo = getDBConnection();
if (!$pdo || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'DB error']);
    exit;
}

$patient_id = $_SESSION['user_id'] ?? null;
$doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
$type = (isset($_POST['type']) && in_array($_POST['type'], ['chat','video'])) ? $_POST['type'] : 'chat';

if (!$patient_id) {
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit;
}
if (!$doctor_id) {
    echo json_encode(['success'=>false,'message'=>'Missing doctor_id']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO tu_van (benh_nhan_id, bac_si_id, loai_tu_van, trang_thai) VALUES (?, ?, ?, 'dang_hoat_dong')");
    $stmt->execute([$patient_id, $doctor_id, $type]);
    $consultation_id = (int)$pdo->lastInsertId();
    echo json_encode(['success'=>true,'consultation_id'=>$consultation_id]);
} catch (PDOException $e) {
    error_log('[start_consultation] '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
}