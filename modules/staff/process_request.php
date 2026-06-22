<?php
session_start();

include("../../config/app.php");
include("../../config/database.php");

if (!isset($_SESSION['user']) || $_SESSION['role'] != "staff") {
    header("Location: ../../auth/login.php");
    exit();
}

$request_id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

// GET REQUEST
$request = $conn->query("
    SELECT * FROM room_requests WHERE request_id=$request_id
")->fetch_assoc();

if (!$request) {
    die("Request not found");
}

$student_id = $request['student_id'];


 // REJECT
 
if ($action == "reject") {

    $conn->query("
        UPDATE room_requests
        SET status='REJECTED'
        WHERE request_id=$request_id
    ");

    $_SESSION['success'] = "Request rejected successfully!";
    header("Location: room_requests.php");
    exit();
}

 // APPROVE + FULL AUTOMATION

if ($action == "approve") {

    $conn->begin_transaction();

    try {

        /**
         * 1. GET REQUEST DATA
         */
        $preferred_type = $request['preferred_room_type'];

        /**
         * 2. FIND AVAILABLE ROOM BASED ON TYPE + CAPACITY
         */
        $room = $conn->query("
            SELECT r.room_id, r.room_type
            FROM rooms r
            WHERE (
                SELECT COUNT(*) FROM allocations a
                WHERE a.room_id = r.room_id
            ) < r.capacity
            AND (r.room_type = '$preferred_type' OR r.room_type IS NULL)
            ORDER BY 
                CASE WHEN r.room_type = '$preferred_type' THEN 1 ELSE 2 END
            LIMIT 1
            FOR UPDATE
        ")->fetch_assoc();

        /**
         * 3. FALLBACK IF NO MATCHING TYPE ROOM
         */
        if (!$room) {

            $room = $conn->query("
                SELECT r.room_id
                FROM rooms r
                WHERE (
                    SELECT COUNT(*) FROM allocations a
                    WHERE a.room_id = r.room_id
                ) < r.capacity
                LIMIT 1
                FOR UPDATE
            ")->fetch_assoc();

            if (!$room) {
                throw new Exception("No available rooms found");
            }
        }

        $room_id = $room['room_id'];

        /**
         * 4. CREATE ALLOCATION
         */
        $stmt = $conn->prepare("
            INSERT INTO allocations (student_id, room_id, allocation_date, status)
            VALUES (?, ?, CURDATE(), 'ACTIVE')
        ");
        $stmt->bind_param("ii", $student_id, $room_id);
        $stmt->execute();

        /**
         * 5. UPDATE REQUEST
         */
        $stmt = $conn->prepare("
            UPDATE room_requests
            SET status='ALLOCATED'
            WHERE request_id=?
        ");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();

        /**
         * 6. CREATE INVOICE
         */
        $invoice_no = "INV-" . date("Ymd") . "-" . rand(1000, 9999);
        $amount = 500000;

        $stmt = $conn->prepare("
            INSERT INTO invoices
            (student_id, invoice_no, academic_year, amount, status, expires_at)
            VALUES (?, ?, '2025/2026', ?, 'PENDING', DATE_ADD(NOW(), INTERVAL 30 DAY))
        ");

        $stmt->bind_param("isd", $student_id, $invoice_no, $amount);
        $stmt->execute();

        /**
         * 7. CONTROL NUMBER
         */
        $invoice_id = $conn->insert_id;
        $control_number = "HN" . date("Y") . rand(100000, 999999);

        $conn->query("
            UPDATE invoices
            SET control_number='$control_number'
            WHERE invoice_id=$invoice_id
        ");

        $conn->commit();

        $_SESSION['success'] =
            "Approved successfully! Room matched by type + capacity allocation done.";

    } catch (Exception $e) {

        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }

    header("Location: room_requests.php");
    exit();
}