<?php
session_start();
include("../../config/app.php");
include("../../config/database.php");

if (!isset($_SESSION['user']) || $_SESSION['role'] != "student") {
    header("Location: ../../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$current_semester = "SEM1"; // later automate

// ==========================
// ACTIVE ALLOCATION CHECK
// ==========================
$activeAlloc = $conn->query("
    SELECT allocation_id FROM allocations
    WHERE student_id = $student_id
    AND status = 'ACTIVE'
")->num_rows;

// ==========================
// PENDING REQUEST CHECK
// ==========================
$pendingReq = $conn->query("
    SELECT request_id FROM room_requests
    WHERE student_id = $student_id
    AND status = 'PENDING'
")->num_rows;

// ==========================
// HANDLE SUBMIT
// ==========================
if (isset($_POST['submit_request'])) {

    $type = trim($_POST['room_type']);
    $reason = trim($_POST['reason']);

    // prevent duplicate
    if ($pendingReq > 0) {
        $_SESSION['error'] = "You already have a pending room request.";
        header("Location: request_room.php");
        exit();
    }

    // re-request logic
    $is_re_request = ($activeAlloc > 0) ? 1 : 0;

    $stmt = $conn->prepare("
        INSERT INTO room_requests 
        (student_id, preferred_room_type, reason, semester, is_re_request, status)
        VALUES (?, ?, ?, ?, ?, 'PENDING')
    ");

    $stmt->bind_param(
        "isssi",
        $student_id,
        $type,
        $reason,
        $current_semester,
        $is_re_request
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = $is_re_request
            ? "Re-request submitted successfully."
            : "Room request submitted successfully.";
    } else {
        $_SESSION['error'] = "Failed to submit request.";
    }

    header("Location: request_room.php");
    exit();
}

include("../../includes/header.php");
include("../../includes/sidebar.php");
?>

<div class="col-md-9 col-lg-10">
    <div class="container mt-4">

        <h3>Request Hostel Room</h3>

        <?php if (!empty($_SESSION['success'])) { ?>
            <div class="alert alert-success">
                <?= $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php } ?>

        <?php if (!empty($_SESSION['error'])) { ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php } ?>

        <div class="alert alert-info">
            <?php if ($activeAlloc > 0) { ?>
                You currently have a room allocation (eligible for re-request).
            <?php } else { ?>
                You do not have any active room allocation.
            <?php } ?>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">

                <form method="POST">

                    <div class="mb-3">
                        <label>Preferred Room Type</label>
                        <select name="room_type" class="form-control" required>
                            <option value="">-- Select --</option>
                            <option>Single</option>
                            <option>Double</option>
                            <option>Shared</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Reason</label>
                        <textarea name="reason" class="form-control" required></textarea>
                    </div>

                    <button class="btn btn-primary" name="submit_request">
                        Submit Request
                    </button>

                </form>

            </div>
        </div>

    </div>
</div>

<?php include("../../includes/footer.php"); ?>