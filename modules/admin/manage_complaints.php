<?php
// session_start();
include("../../config/database.php");
include("../../includes/header.php");
include("../../includes/admin_sidebar.php");

// Security
if (!isset($_SESSION['user']) || $_SESSION['role'] != "admin") {
    header("Location: ../../auth/login.php");
    exit();
}

// UPDATE COMPLAINT
if (isset($_POST['update_complaint'])) {

    $id = intval($_POST['complaint_id']);
    $status = $_POST['status'];
    $resolution = trim($_POST['resolution']);

    $stmt = $conn->prepare("
        UPDATE complaints 
        SET status=?, resolution=?, resolved_at=NOW()
        WHERE complaint_id=?
    ");

    $stmt->bind_param("ssi", $status, $resolution, $id);

    if ($stmt->execute()) {
        $success = "Complaint updated successfully!";
    } else {
        $error = "Update failed!";
    }
}


// DELETE (SAFE)
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $check = $conn->query("SELECT complaint_id FROM complaints WHERE complaint_id=$id");

    if ($check && $check->num_rows > 0) {
        $conn->query("DELETE FROM complaints WHERE complaint_id=$id");
        $success = "Complaint deleted!";
    } else {
        $error = "Complaint not found!";
    }
}


// FILTER
$where = "";

if (isset($_GET['filter'])) {
    if ($_GET['filter'] == "open") {
        $where = "WHERE c.status='OPEN'";
    } elseif ($_GET['filter'] == "resolved") {
        $where = "WHERE c.status='RESOLVED'";
    }
}

// FETCH DATA
$complaints = $conn->query("
SELECT c.*, s.name AS student_name 
FROM complaints c
LEFT JOIN students s ON c.student_id = s.student_id
$where
ORDER BY c.complaint_id DESC
");
?>

<div class="col-md-9 col-lg-10">
    <div class="container-fluid mt-4">

        <h3><i class="fas fa-comment-dots"></i> Manage Complaints</h3>
        <p class="text-muted">Track and resolve student issues</p>
        <hr>

        <!-- FILTER BUTTONS -->
        <a href="manage_complaints.php" class="btn btn-secondary btn-sm">All</a>
        <a href="?filter=open" class="btn btn-danger btn-sm">Open</a>
        <a href="?filter=resolved" class="btn btn-success btn-sm">Resolved</a>

        <br><br>

        <!-- ALERTS -->
        <?php if (isset($success)) { ?>
            <div class="alert alert-success auto-dismiss"><?php echo $success; ?></div>
        <?php } ?>

        <?php if (isset($error)) { ?>
            <div class="alert alert-danger auto-dismiss"><?php echo $error; ?></div>
        <?php } ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-danger text-white">Complaints List</div>

            <div class="card-body">

                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Issue</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php
                        $i = 1;
                        if ($complaints):
                            while ($row = $complaints->fetch_assoc()) {
                        ?>

                                <tr>

                                    <td><?php echo $i++; ?></td>

                                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>

                                    <td><?php echo htmlspecialchars($row['issue']); ?></td>

                                    <td>
                                        <?php echo nl2br(htmlspecialchars($row['description'])); ?>

                                        <?php if (!empty($row['resolution'])) { ?>
                                            <hr>
                                            <small class="text-success">
                                                <strong>Resolution:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($row['resolution'])); ?>
                                            </small>
                                        <?php } ?>
                                    </td>

                                    <td>
                                        <?php
                                        if ($row['status'] == "RESOLVED") {
                                            echo "<span class='badge bg-success'>Resolved</span>";
                                        } else {
                                            echo "<span class='badge bg-danger'>Open</span>";
                                        }
                                        ?>
                                    </td>

                                    <td><?php echo $row['complaint_date']; ?></td>

                                    <td>
                                        <button class="btn btn-warning btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modal<?php echo $row['complaint_id']; ?>">
                                            Resolve
                                        </button>

                                        <a href="?delete=<?php echo $row['complaint_id']; ?>"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Delete complaint?');">
                                            Delete
                                        </a>
                                    </td>

                                </tr>

                                <!-- MODAL -->
                                <div class="modal fade" id="modal<?php echo $row['complaint_id']; ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">

                                            <form method="POST">

                                                <div class="modal-header bg-warning">
                                                    <h5>Resolve Complaint</h5>
                                                </div>

                                                <div class="modal-body">

                                                    <input type="hidden" name="complaint_id" value="<?php echo $row['complaint_id']; ?>">

                                                    <label>Status</label>
                                                    <select name="status" class="form-select mb-2">
                                                        <option value="OPEN" <?php if ($row['status'] == "OPEN") echo "selected"; ?>>Open</option>
                                                        <option value="RESOLVED" <?php if ($row['status'] == "RESOLVED") echo "selected"; ?>>Resolved</option>
                                                    </select>

                                                    <label>Resolution</label>
                                                    <textarea name="resolution" class="form-control" required></textarea>

                                                </div>

                                                <div class="modal-footer">
                                                    <button type="submit" name="update_complaint" class="btn btn-success">Save</button>
                                                </div>

                                            </form>

                                        </div>
                                    </div>
                                </div>

                        <?php }
                        endif; ?>

                    </tbody>
                </table>

            </div>
        </div>

    </div>
</div>

<!-- AUTO DISMISS -->
<script>
    setTimeout(() => {
        document.querySelectorAll(".auto-dismiss").forEach(el => {
            el.style.opacity = "0";
            setTimeout(() => el.remove(), 500);
        });
    }, 3000);
</script>

<?php include("../../includes/footer.php"); ?>