<?php
// session_start();
include("../../config/database.php");
include("../../includes/header.php");
include("../../includes/admin_sidebar.php");

// Security check
if (!isset($_SESSION['user']) || $_SESSION['role'] != "admin") {
    header("Location: ../../auth/login.php");
    exit();
}

// Handle update student
if (isset($_POST['update_student'])) {
    $id = intval($_POST['student_id']);
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $sql = "UPDATE students SET name='$name', email='$email', phone='$phone' WHERE student_id=$id";
    if ($conn->query($sql) === TRUE) {
        $success = "Student updated successfully!";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM students WHERE student_id=$id");
    header("Location: manage_students.php");
    exit();
}

// Fetch all students
$students = $conn->query("SELECT * FROM students ORDER BY student_id DESC");
?>

<div class="col-md-9 col-lg-10">
    <div class="container-fluid mt-4">
        <h3><i class="fas fa-users"></i> Manage Students</h3>
        <p class="text-muted">View, edit, and delete student accounts</p>
        <hr>

        <?php if (isset($success)) { ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" id="alertBox">
                <?php echo $success; ?>
            </div>
        <?php } ?>

        <?php if (isset($error)) { ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" id="alertBox">
                <?php echo $error; ?>
            </div>
        <?php } ?>


        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-list"></i> Students List
            </div>
            <div class="card-body">
                <table class="table table-striped table-hover">
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
                        <?php
                        $i = 1;
                        while ($row = $students->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $i++ ?></td>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo $row['reg_number']; ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo $row['phone']; ?></td>
                                <td>
                                    <!-- Edit Button triggers modal -->
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editStudentModal<?php echo $row['student_id']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="manage_students.php?delete=<?php echo $row['student_id']; ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this student?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>

                            <!-- Edit Student Modal -->
                            <div class="modal fade" id="editStudentModal<?php echo $row['student_id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header bg-warning">
                                                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Student</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="student_id" value="<?php echo $row['student_id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Name</label>
                                                    <input type="text" name="name" class="form-control" value="<?php echo $row['name']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" name="email" class="form-control" value="<?php echo $row['email']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Phone</label>
                                                    <input type="text" name="phone" class="form-control" value="<?php echo $row['phone']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" name="update_student" class="btn btn-success"><i class="fas fa-save"></i> Save</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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

<?php include("../../includes/footer.php"); ?>