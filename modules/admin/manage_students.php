<?php
//session_start();

include("../../config/database.php");
include("../../includes/header.php");
include("../../includes/admin_sidebar.php");

// Security check
if (!isset($_SESSION['user']) || $_SESSION['role'] != "admin") {
    header("Location: ../../auth/login.php");
    exit();
}

/*
-----------------------------------
UPDATE STUDENT
-----------------------------------
*/
if (isset($_POST['update_student'])) {

    $id = intval($_POST['student_id']);
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $stmt = $conn->prepare("
        UPDATE students 
        SET name=?, email=?, phone=? 
        WHERE student_id=?
    ");

    $stmt->bind_param("sssi", $name, $email, $phone, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Student updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update student.";
    }

    header("Location: manage_students.php");
    exit();
}

/*
-----------------------------------
DELETE STUDENT (SAFE)
-----------------------------------
*/
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    try {

        $stmt = $conn->prepare("
            DELETE FROM students
            WHERE student_id = ?
        ");

        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {

            $_SESSION['success'] = "Student deleted successfully.";
        } else {

            $_SESSION['error'] = "Failed to delete student.";
        }
    } catch (mysqli_sql_exception $e) {

        $_SESSION['error'] =
            "Cannot delete this student because related records exist (allocations, payments, invoices, complaints, or room requests).";
    }

    header("Location: manage_students.php");
    exit();
}

/*
-----------------------------------
FETCH STUDENTS
-----------------------------------
*/
$students = $conn->query("SELECT * FROM students ORDER BY student_id DESC");
?>

<div class="col-md-9 col-lg-10">
    <div class="container-fluid mt-4">

        <h3><i class="fas fa-users"></i> Manage Students</h3>
        <p class="text-muted">View, edit, and delete student accounts</p>
        <hr>

        <!-- ALERTS -->
        <?php if (!empty($_SESSION['success'])) { ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['success']; ?>
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php } ?>

        <?php if (!empty($_SESSION['error'])) { ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i>
                <?= $_SESSION['error']; ?>
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php } ?>

        <!-- TABLE -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-list"></i> Students List
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Reg Number</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php $i = 1;
                            while ($row = $students->fetch_assoc()) { ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['reg_number']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>

                                    <td>

                                        <!-- EDIT -->
                                        <button class="btn btn-sm btn-warning"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editStudent<?= $row['student_id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <!-- DELETE -->
                                        <a href="?delete=<?= $row['student_id']; ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this student?');">
                                            <i class="fas fa-trash"></i>
                                        </a>

                                    </td>
                                </tr>

                                <!-- MODAL -->
                                <div class="modal fade" id="editStudent<?= $row['student_id']; ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">

                                            <form method="POST">

                                                <div class="modal-header bg-warning">
                                                    <h5>Edit Student</h5>
                                                    <button class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>

                                                <div class="modal-body">

                                                    <input type="hidden" name="student_id" value="<?= $row['student_id']; ?>">

                                                    <div class="mb-2">
                                                        <label>Name</label>
                                                        <input type="text" name="name"
                                                            value="<?= $row['name']; ?>"
                                                            class="form-control" required>
                                                    </div>

                                                    <div class="mb-2">
                                                        <label>Email</label>
                                                        <input type="email" name="email"
                                                            value="<?= $row['email']; ?>"
                                                            class="form-control" required>
                                                    </div>

                                                    <div class="mb-2">
                                                        <label>Phone</label>
                                                        <input type="text" name="phone"
                                                            value="<?= $row['phone']; ?>"
                                                            class="form-control" required>
                                                    </div>

                                                </div>

                                                <div class="modal-footer">
                                                    <button class="btn btn-success"
                                                        name="update_student">
                                                        Save
                                                    </button>
                                                </div>

                                            </form>

                                        </div>
                                    </div>
                                </div>

                            <?php } ?>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include("../../includes/footer.php"); ?>