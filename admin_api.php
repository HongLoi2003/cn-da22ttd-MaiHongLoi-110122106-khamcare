<?php
/**
 * Admin API - CRUD operations for Admin Panel
 * Handles: Users, Doctors, Specialties, Appointments
 */

session_start();
require_once 'db_config.php';

header('Content-Type: application/json; charset=utf-8');

// Check admin authentication
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$entity = $_GET['entity'] ?? $_POST['entity'] ?? '';

try {
    switch ($entity) {
        case 'users':
            handleUsers($pdo, $action);
            break;
        case 'doctors':
            handleDoctors($pdo, $action);
            break;
        case 'specialties':
            handleSpecialties($pdo, $action);
            break;
        case 'appointments':
            handleAppointments($pdo, $action);
            break;
        default:
            throw new Exception('Invalid entity');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ==================== USERS CRUD ====================
function handleUsers($pdo, $action) {
    switch ($action) {
        case 'list':
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT id, username, email, full_name, phone, role, status, created_at 
                    FROM users WHERE 1=1";
            $params = [];
            
            if ($search) {
                $sql .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if ($role) {
                $sql .= " AND role = ?";
                $params[] = $role;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll();
            
            // Get total count
            $countSql = "SELECT COUNT(*) FROM users WHERE 1=1";
            $countParams = array_slice($params, 0, -2); // Remove limit and offset
            if ($search) {
                $countSql .= " AND (u