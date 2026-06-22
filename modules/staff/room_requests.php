<?php
session_start();

include("../../config/app.php");
include("../../config/database.php");

if (!isset($_SESSION['user']) || $_SESSION['role'] != "staff") {
    header("Location: ../../auth/login.php");
    exit();
}

// FETCH REQUESTS
$requests = $conn->query("
    SELECT rr.*, s.name, s.reg_number
    FROM room_requests rr
    JOIN students s ON rr.student_id = s.student_id
    ORDER BY rr.created_at DESC
");

include("../../includes/header.php");
include("../../includes/staff_sidebar.php");
?>

<div class="col-md-9 col-lg-10">
    <div class="container mt-4">

        <h3><i class="fas fa-door-open"></i> Room Requests Panel</h3>

        <div class="card shadow-sm mt-3">
            <div class="card-body p-0">

                <table class="table table-hover mb-0">

                    <thead class="table-dark">
                        <tr>
                            <th>Student</th>
                            <th>Reg No</th>
                            <th>Type</th>
                            <th>Reason</th>
                            <th>Request Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($r = $requests->fetch_assoc()) { ?>

                            <tr>

                                <td><?= htmlspecialchars($r['name']) ?></td>
                                <td><?= htmlspecialchars($r['reg_number']) ?></td>
                                <td><?= htmlspecialchars($r['preferred_room_type']) ?></td>
                                <td><?= htmlspecialchars($r['reason']) ?></td>

                                <!-- REQUEST TYPE -->
                                <td>
                                    <?php if (!empty($r['is_re_request']) && $r['is_re_request'] == 1) { ?>
                                        <span class="badge bg-warning text-dark">RE-REQUEST</span>
                                    <?php } else { ?>
                                        <span class="badge bg-info">NEW</span>
                                    <?php } ?>
                                </td>

                                <!-- STATUS -->
                                <td>
                                    <?php
                                    $status = $r['status'] ?? 'PENDING';

                                    $badge = match ($status) {
                                        "PENDING" => "warning text-dark",
                                        "APPROVED" => "success",
                                        "REJECTED" => "danger",
                                        "ALLOCATED" => "primary",
                                        default => "secondary"
                                    };
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= $status ?></span>
                                </td>

                                <!-- ACTION -->
                                <td>

                                    <?php if ($status == "PENDING") { ?>

                                        <a href="process_request.php?id=<?= $r['request_id'] ?>&action=approve"
                                            class="btn btn-sm btn-success">Approve</a>

                                        <a href="process_request.php?id=<?= $r['request_id'] ?>&action=reject"
                                            class="btn btn-sm btn-danger">Reject</a>

                                    <?php } else { ?>
                                        <small class="text-muted">Processed</small>
                                    <?php } ?>

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