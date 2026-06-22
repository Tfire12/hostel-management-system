<?php
session_start();
include("../../config/app.php");
include("../../config/database.php");

if (!isset($_SESSION['user']) || $_SESSION['role'] != "staff") {
    header("Location: ../../auth/login.php");
    exit();
}

// UPDATE REQUEST
if (isset($_POST['update_request'])) {

    $id = intval($_POST['id']);
    $reason = trim($_POST['reason']);

    $stmt = $conn->prepare("
        UPDATE room_change_requests
        SET reason = ?
        WHERE id = ?
    ");

    $stmt->bind_param("si", $reason, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Request updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update request.";
    }

    header("Location: room_change_requests.php");
    exit();
}


// DELETE REQUEST
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("
        DELETE FROM room_change_requests
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Request deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete request.";
    }

    header("Location: room_change_requests.php");
    exit();
}
// FETCH REQUESTS
$requests = $conn->query("
    SELECT rcr.*, s.name, s.reg_number,
           rm.room_number AS current_room
    FROM room_change_requests rcr
    JOIN students s ON rcr.student_id = s.student_id
    LEFT JOIN rooms rm ON rcr.current_room_id = rm.room_id
    ORDER BY rcr.created_at DESC
");

include("../../includes/header.php");
include("../../includes/staff_sidebar.php");
?>

<div class="col-md-9 col-lg-10">
    <div class="container mt-4">

        <h3><i class="fas fa-exchange-alt"></i> Room Change Requests</h3>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php unset($_SESSION['success']);
        endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-times-circle"></i>
                <?= $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php unset($_SESSION['error']);
        endif; ?>

        <div class="card shadow-sm mt-3">
            <div class="card-body p-0">

                <table class="table table-hover mb-0">

                    <thead class="table-dark">
                        <tr>
                            <th>Student</th>
                            <th>Reg No</th>
                            <th>Current Room</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th width="220">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($r = $requests->fetch_assoc()) { ?>

                            <tr>
                                <td><?= $r['name'] ?></td>
                                <td><?= $r['reg_number'] ?></td>
                                <td><?= $r['current_room'] ?? 'N/A' ?></td>
                                <td><?= $r['reason'] ?></td>

                                <td>
                                    <?php if ($r['status'] == "PENDING") { ?>
                                        <span class="badge bg-warning text-dark">PENDING</span>
                                    <?php } elseif ($r['status'] == "APPROVED") { ?>
                                        <span class="badge bg-success">APPROVED</span>
                                    <?php } else { ?>
                                        <span class="badge bg-danger">REJECTED</span>
                                    <?php } ?>
                                </td>

                                <td>

                                    <?php if ($r['status'] == "PENDING") { ?>

                                        <a href="process_room_change.php?id=<?= $r['id'] ?>&action=approve"
                                            class="btn btn-success btn-sm">Approve
                                        </a>

                                        <a href="process_room_change.php?id=<?= $r['id'] ?>&action=reject"
                                            class="btn btn-danger btn-sm">Reject
                                        </a>

                                    <?php } ?>

                                    <a href="?delete=<?= $r['id'] ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Delete this request?')">
                                        <i class="fas fa-trash"></i>
                                    </a>

                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>
        </div>

    </div>
</div>

<?php include("../../includes/footer.php"); ?>