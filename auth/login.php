<?php
session_start();
include("../config/database.php");

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check in Students table
    $sql_student = "SELECT * FROM students WHERE reg_number='$username' OR email='$username'";
    $result_student = $conn->query($sql_student);

    if ($result_student && $result_student->num_rows > 0) {
        $row = $result_student->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user'] = $row['name'];
            $_SESSION['role'] = 'student';
            $_SESSION['user_id'] = $row['student_id'];
            $_SESSION['reg_number'] = $row['reg_number'];
            $_SESSION['user_type'] = 'student';
            header("Location: ../modules/students/index.php");
            exit();
        }
    }

    // Check in Staff table
    $sql_staff = "SELECT * FROM staff WHERE username='$username'";
    $result_staff = $conn->query($sql_staff);

    if ($result_staff && $result_staff->num_rows > 0) {
        $row = $result_staff->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user'] = $row['name'];
            $_SESSION['role'] = $row['role']; // staff or admin
            $_SESSION['user_id'] = $row['staff_id'];
            $_SESSION['user_type'] = $row['role'];

            if ($row['role'] === 'admin') {
                header("Location: ../modules/admin/index.php");
            } else {
                header("Location: ../modules/staff/index.php");
            }
            exit();
        }
    }

    $error = "Invalid username or password!";
}
?>

<?php include("../includes/header.php"); ?>

<div class="d-flex justify-content-center align-items-center vh-100 bg-light">
    <div class="card shadow" style="width: 350px;">
        <div class="card-header bg-primary text-white text-center">
            Login
        </div>
        <div class="card-body">
            <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Username / Email</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" name="login" class="btn btn-success w-100">Login</button>
                <div class="mt-2 text-center">
                    <small>If you don’t have an account?</small><br>
                    <a href="../modules/students/register_student.php" class="btn btn-link">Register</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>