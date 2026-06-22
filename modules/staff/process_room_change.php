<?php
session_start();
include("../../config/app.php");
include("../../config/database.php");

if (!isset($_SESSION['user']) || $_SESSION['role'] != "staff") {
    header("Location: ../../auth/login.php");
    exit();
}

$id = intval($_GET['id']);
$action = $_GET['action'];

// GET REQUEST
$request = $conn->query("
    SELECT * FROM room_change_requests WHERE id=$id
")->fetch_assoc();

if (!$request) {
    die("Request not found");
}

$student_id = $request['student_id'];

# =========================
# REJECT
# =========================
if ($action == "reject") {

    $conn->query("
        UPDATE room_change_requests
        SET status='REJECTED'
        WHERE id=$id
    ");

    $_SESSION['success'] = "Request rejected successfully";
    header("Location: room_change_requests.php");
    exit();
}

# =========================
# APPROVE + ROOM SWAP
# =========================
if ($action == "approve") {

    // find new available room
    $room = $conn->query("
        SELECT r.room_id
        FROM rooms r
        WHERE (
            SELECT COUNT(*) 
            FROM allocations a 
            WHERE a.room_id = r.room_id 
            AND a.status='ACTIVE'
        ) < r.capacity
        LIMIT 1
    ")->fetch_assoc();

    if (!$room) {
        $_SESSION['error'] = "No available rooms";
        header("Location: room_change_requests.php");
        exit();
    }

    $new_room_id = $room['room_id'];

    // UPDATE OLD ALLOCATION -> RELEASE
    $conn->query("
        UPDATE allocations 
        SET status='RELEASED'
        WHERE student_id=$student_id
        AND status='ACTIVE'
    ");

    // CREATE NEW ALLOCATION
    $conn->query("
        INSERT INTO allocations (student_id, room_id, status, allocation_date)
        VALUES ($student_id, $new_room_id, 'ACTIVE', NOW())
    ");

    // UPDATE REQUEST
    $conn->query("
        UPDATE room_change_requests
        SET status='APPROVED'
        WHERE id=$id
    ");

    $_SESSION['success'] = "Room changed successfully";
    header("Location: room_change_requests.php");
    exit();
}
