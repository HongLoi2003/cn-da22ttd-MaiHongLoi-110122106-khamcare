<?php
/**
 * Doctor ID Mapping Functions
 * ID bác sĩ đã thống nhất: 1-18 trong cả database và view
 */

// Không cần mapping - ID đã thống nhất 1-18
function mapViewIdToDatabaseId($viewId) {
    // Nếu là ID cũ 1001-1018, convert về 1-18
    if ($viewId >= 1001 && $viewId <= 1018) {
        return $viewId - 1000;
    }
    return $viewId;
}

function mapDatabaseIdToViewId($databaseId) {
    return $databaseId; // ID 1-18 dùng trực tiếp
}

function isViewId($id) {
    return $id >= 1 && $id <= 18;
}

function isDatabaseId($id) {
    return $id >= 1 && $id <= 18;
}

function normalizeToViewId($id) {
    // Nếu là ID cũ 1001-1018, convert về 1-18
    if ($id >= 1001 && $id <= 1018) {
        return $id - 1000;
    }
    return $id;
}

function normalizeToDatabaseId($id) {
    // Nếu là ID cũ 1001-1018, convert về 1-18
    if ($id >= 1001 && $id <= 1018) {
        return $id - 1000;
    }
    return $id;
}
?>
