<?php
session_start();
include("../../config/database.php");

// Set header for JSON response since we are using AJAX
header('Content-Type: application/json');

// Security: only staff
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit();
}

// Get the user performing the action for the audit trail
$performed_by = is_array($_SESSION['user']) ? ($_SESSION['user']['name'] ?? 'Staff') : $_SESSION['user'];

// CSRF Token validation
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid security token. Please refresh the page.']);
    exit();
}

// 1. HANDLE DELETE
if (isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    
    // Fetch allocation details before deleting for audit
    $stmt = $conn->prepare("SELECT student_id, room_id FROM allocations WHERE allocation_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Allocation not found.']);
        exit();
    }
    
    $alloc_data = $result->fetch_assoc();
    $stmt->close();

    // Delete the allocation
    $del_stmt = $conn->prepare("DELETE FROM allocations WHERE allocation_id = ?");
    $del_stmt->bind_param("i", $delete_id);
    
    if ($del_stmt->execute()) {
        // Audit log
        $audit_details = "Deleted allocation for Student ID: {$alloc_data['student_id']} in Room ID: {$alloc_data['room_id']}";
        $audit_stmt = $conn->prepare("INSERT INTO allocations_audit (allocation_id, action, performed_by, details) VALUES (?, 'DELETE', ?, ?)");
        $audit_stmt->bind_param("iss", $delete_id, $performed_by, $audit_details);
        $audit_stmt->execute();
        $audit_stmt->close();

        echo json_encode(['success' => 'Allocation deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete allocation.']);
    }
    $del_stmt->close();
    exit();
}

// ---------------------------------------------------------
// 2. HANDLE ADD / EDIT (SAVE)
// ---------------------------------------------------------
if (isset($_POST['student_id'], $_POST['room_id'], $_POST['allocation_date'])) {
    $allocation_id = !empty($_POST['allocation_id']) ? intval($_POST['allocation_id']) : 0;
    $student_id = intval($_POST['student_id']);
    $room_id = intval($_POST['room_id']);
    $allocation_date = $_POST['allocation_date'];
    $status = $_POST['status'] ?? 'ACTIVE';

    // CHECK 1: Ensure room capacity is not exceeded (only count ACTIVE statuses)
    $cap_stmt = $conn->prepare("
        SELECT r.capacity, 
               (SELECT COUNT(*) FROM allocations a WHERE a.room_id = r.room_id AND a.status = 'ACTIVE' AND a.allocation_id != ?) as current_occupants 
        FROM rooms r WHERE r.room_id = ?
    ");
    $cap_stmt->bind_param("ii", $allocation_id, $room_id);
    $cap_stmt->execute();
    $room_info = $cap_stmt->get_result()->fetch_assoc();
    $cap_stmt->close();

    if (!$room_info) {
        echo json_encode(['success' => false, 'error' => 'Selected room does not exist.']);
        exit();
    }

    if ($status === 'ACTIVE' && $room_info['current_occupants'] >= $room_info['capacity']) {
        echo json_encode(['success' => false, 'error' => 'Room capacity exceeded. This room is full.']);
        exit();
    }

    // CHECK 2: Ensure student doesn't already have an active allocation (unless editing the same one)
    if ($status === 'ACTIVE') {
        $stud_stmt = $conn->prepare("SELECT allocation_id FROM allocations WHERE student_id = ? AND status = 'ACTIVE' AND allocation_id != ?");
        $stud_stmt->bind_param("ii", $student_id, $allocation_id);
        $stud_stmt->execute();
        if ($stud_stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Student already has an ACTIVE room allocation.']);
            $stud_stmt->close();
            exit();
        }
        $stud_stmt->close();
    }

    // Process Insert or Update
    if ($allocation_id > 0) {
        // UPDATE
        $update_stmt = $conn->prepare("UPDATE allocations SET student_id=?, room_id=?, allocation_date=?, status=? WHERE allocation_id=?");
        $update_stmt->bind_param("iissi", $student_id, $room_id, $allocation_date, $status, $allocation_id);
        
        if ($update_stmt->execute()) {
            // Audit log
            $audit_details = "Updated allocation details to Room ID: $room_id, Status: $status";
            $audit_stmt = $conn->prepare("INSERT INTO allocations_audit (allocation_id, action, performed_by, details) VALUES (?, 'UPDATE', ?, ?)");
            $audit_stmt->bind_param("iss", $allocation_id, $performed_by, $audit_details);
            $audit_stmt->execute();
            $audit_stmt->close();

            echo json_encode(['success' => 'Allocation updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error during update.']);
        }
        $update_stmt->close();

    } else {
        // INSERT
        $insert_stmt = $conn->prepare("INSERT INTO allocations (student_id, room_id, allocation_date, status) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("iiss", $student_id, $room_id, $allocation_date, $status);
        
        if ($insert_stmt->execute()) {
            $new_id = $insert_stmt->insert_id;
            
            // Audit log
            $audit_details = "Created new allocation for Student ID: $student_id in Room ID: $room_id";
            $audit_stmt = $conn->prepare("INSERT INTO allocations_audit (allocation_id, action, performed_by, details) VALUES (?, 'CREATE', ?, ?)");
            $audit_stmt->bind_param("iss", $new_id, $performed_by, $audit_details);
            $audit_stmt->execute();
            $audit_stmt->close();

            echo json_encode(['success' => 'Allocation added successfully.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error during insert.']);
        }
        $insert_stmt->close();
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request parameters.']);
}
?>