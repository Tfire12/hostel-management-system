<?php
session_start();
include("../../config/database.php");
include("../../includes/header.php");
include("../../includes/sidebar.php");

// AUTH CHECK
if (!isset($_SESSION['user']) || $_SESSION['role'] != "student") {
    header("Location: ../../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// SUBMIT COMPLAINT (SECURE)
if (isset($_POST['submit_complaint'])) {

    $issue = trim($_POST['issue']);
    $description = trim($_POST['description']);
    $date = date("Y-m-d");

    if (empty($issue) || empty($description)) {
        $error = "All fields are required!";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO complaints (student_id, issue, description, complaint_date, status) 
            VALUES (?, ?, ?, ?, 'OPEN')
        ");

        $stmt->bind_param("isss", $student_id, $issue, $description, $date);

        if ($stmt->execute()) {
            $success = "Complaint submitted successfully!";
        } else {
            $error = "Failed to submit complaint!";
        }
    }
}

// FETCH COMPLAINTS
$complaints = $conn->query("
SELECT * FROM complaints 
WHERE student_id=$student_id 
ORDER BY complaint_id DESC
");
?>

<div class="col-md-9 col-lg-10">
    <div class="container-fluid mt-4">

        <h3><i class="fas fa-comment-dots"></i> My Complaints</h3>
        <p class="text-muted">Submit and track your complaints</p>
        <hr>

        <!-- ALERTS -->
        <?php if (isset($success)) { ?>
            <div class="alert alert-success auto-dismiss"><?php echo $success; ?></div>
        <?php } ?>

        <?php if (isset($error)) { ?>
            <div class="alert alert-danger auto-dismiss"><?php echo $error; ?></div>
        <?php } ?>

        <!-- SUBMIT FORM -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white">
                Submit Complaint
            </div>

            <div class="card-body">

                <form method="POST">

                    <div class="mb-3">
                        <label>Issue</label>
                        <input type="text" name="issue" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>

                    <button type="submit" name="submit_complaint" class="btn btn-success">
                        Submit
                    </button>

                </form>

            </div>
        </div>

        <!-- HISTORY -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-secondary text-white">
                My Complaints History
            </div>

            <div class="card-body">

                <table class="table table-bordered table-hover">

                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Issue</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php
                        if ($complaints):
                            while ($row = $complaints->fetch_assoc()) {
                        ?>

                                <tr>

                                    <td><?php echo $row['complaint_date']; ?></td>

                                    <td><?php echo htmlspecialchars($row['issue']); ?></td>

                                    <td>
                                        <?php echo nl2br(htmlspecialchars($row['description'])); ?>

                                        <?php if (!empty($row['resolution'])) { ?>
                                            <hr>
                                            <small class="text-success">
                                                <strong>Admin Response:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($row['resolution'])); ?>
                                            </small>
                                        <?php } ?>
                                    </td>

                                    <td>
                                        <?php
                                        if ($row['status'] == "RESOLVED") {
                                            echo "<span class='badge bg-success'>Resolved</span>";
                                        } else {
                                            echo "<span class='badge bg-warning text-dark'>Pending</span>";
                                        }
                                        ?>
                                    </td>

                                </tr>

                        <?php }
                        endif; ?>

                    </tbody>
                </table>

            </div>
        </div>

    </div>
</div>

<!-- AUTO DISMISS ALERT -->
<script>
    setTimeout(() => {
        document.querySelectorAll(".auto-dismiss").forEach(el => {
            el.style.opacity = "0";
            setTimeout(() => el.remove(), 500);
        });
    }, 3000);
</script>

<?php include("../../includes/footer.php"); ?>