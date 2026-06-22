<?php
session_start();
include("../../config/app.php");
include("../../config/database.php");

// SECURITY FIRST
if (!isset($_SESSION['user']) || $_SESSION['role'] != "student") {
    header("Location: ../../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];


// HANDLE REQUEST FIRST

if (isset($_POST['request_change'])) {

    $reason = trim($_POST['reason']);

    // check existing pending request
    $check = $conn->query("
        SELECT * FROM room_change_requests 
        WHERE student_id = $student_id 
        AND status = 'PENDING'
    ");

    if ($check->num_rows > 0) {
        $_SESSION['error'] = "You already have a pending room change request.";
        header("Location: my_room.php");
        exit();
    }

    // get current allocation
    $alloc = $conn->query("
        SELECT room_id FROM allocations 
        WHERE student_id = $student_id 
        AND status = 'ACTIVE'
    ")->fetch_assoc();

    if (!$alloc) {
        $_SESSION['error'] = "You do not have a room allocated.";
        header("Location: my_room.php");
        exit();
    }

    $room_id = $alloc['room_id'];

    $stmt = $conn->prepare("
        INSERT INTO room_change_requests 
        (student_id, current_room_id, reason)
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param("iis", $student_id, $room_id, $reason);
    $stmt->execute();

    $_SESSION['success'] = "Room change request submitted successfully!";
    header("Location: my_room.php");
    exit();
}

//FETCH ROOM DETAILS

$allocation = $conn->query("
    SELECT r.room_number, r.room_type, r.capacity
    FROM allocations a
    JOIN rooms r ON a.room_id = r.room_id
    WHERE a.student_id = $student_id
    AND a.status = 'ACTIVE'
")->fetch_assoc();

include("../../includes/header.php");
include("../../includes/sidebar.php");
?>

<div class="col-md-9 col-lg-10">
    <div class="container-fluid mt-4">

        <h3><i class="fas fa-door-open"></i> My Room</h3>
        <p class="text-muted">View your room details and request changes</p>
        <hr>

        <!-- ALERTS -->
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

        <!-- ROOM DETAILS -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-info text-white">
                <i class="fas fa-bed"></i> Room Details
            </div>

            <div class="card-body">
                <?php if ($allocation) { ?>
                    <p><strong>Room Number:</strong> <?= $allocation['room_number']; ?></p>
                    <p><strong>Room Type:</strong> <?= $allocation['room_type']; ?></p>
                    <p><strong>Capacity:</strong> <?= $allocation['capacity']; ?></p>
                <?php } else { ?>
                    <div class="alert alert-warning">
                        You have not been allocated a room yet.
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- REQUEST FORM -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-secondary text-white">
                <i class="fas fa-exchange-alt"></i> Request Room Change
            </div>

            <div class="card-body">

                <?php if ($allocation) { ?>

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label">Reason for Change</label>
                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                        </div>

                        <button type="submit" name="request_change" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>

                    </form>

                <?php } else { ?>
                    <div class="alert alert-info">
                        You must have a room before requesting changes.
                    </div>
                <?php } ?>

            </div>
        </div>

    </div>
</div>

<?php include("../../includes/footer.php"); ?>