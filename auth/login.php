<?php
session_start();
include("../config/database.php");

// Redirect if already logged in
if (isset($_SESSION['user'])) {

    if ($_SESSION['role'] == 'student') {
        header("Location: ../modules/students/index.php");
    } elseif ($_SESSION['role'] == 'admin') {
        header("Location: ../modules/admin/index.php");
    } else {
        header("Location: ../modules/staff/index.php");
    }

    exit();
}

$error = "";

// LOGIN PROCESS
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {

        $error = "Please enter username and password.";
    } else {

        // STUDENT LOGIN
        $stmt = $conn->prepare("
            SELECT *
            FROM students
            WHERE reg_number = ?
            OR email = ?
            LIMIT 1
        ");

        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();

        $student = $stmt->get_result()->fetch_assoc();

        if ($student && password_verify($password, $student['password'])) {

            session_regenerate_id(true);

            $_SESSION['user'] = $student['name'];
            $_SESSION['role'] = 'student';
            $_SESSION['user_id'] = $student['student_id'];
            $_SESSION['reg_number'] = $student['reg_number'];

            header("Location: ../modules/students/index.php");
            exit();
        }

        // STAFF / ADMIN LOGIN
        $stmt = $conn->prepare("
            SELECT *
            FROM staff
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $staff = $stmt->get_result()->fetch_assoc();

        if ($staff && password_verify($password, $staff['password'])) {

            session_regenerate_id(true);

            $_SESSION['user'] = $staff['name'];
            $_SESSION['role'] = $staff['role'];
            $_SESSION['user_id'] = $staff['staff_id'];

            if ($staff['role'] === 'admin') {
                header("Location: ../modules/admin/index.php");
            } else {
                header("Location: ../modules/staff/index.php");
            }

            exit();
        }

        $error = "Invalid username or password.";
    }
}

include("../includes/header.php");
?>

<div class="container-fluid bg-light min-vh-100">

    <div class="row justify-content-center align-items-center min-vh-100">

        <div class="col-md-5 col-lg-4">

            <div class="card shadow border-0">

                <div class="card-header bg-primary text-white text-center py-4">

                    <h3 class="mb-0">
                        Hostel Management System
                    </h3>

                    <small>Login to your account</small>

                </div>

                <div class="card-body p-4">

                    <!-- Success Alert -->
                    <?php if (!empty($_SESSION['success'])) { ?>

                        <div class="alert alert-success alert-dismissible fade show">

                            <i class="fas fa-check-circle"></i>
                            <?= $_SESSION['success']; ?>

                            <button type="button"
                                class="btn-close"
                                data-bs-dismiss="alert">
                            </button>

                        </div>

                    <?php
                        unset($_SESSION['success']);
                    } ?>

                    <!-- Error Alert -->
                    <?php if (!empty($error)) { ?>

                        <div class="alert alert-danger alert-dismissible fade show">

                            <i class="fas fa-exclamation-circle"></i>
                            <?= $error; ?>

                            <button type="button"
                                class="btn-close"
                                data-bs-dismiss="alert">
                            </button>

                        </div>

                    <?php } ?>

                    <form method="POST" id="loginForm">

                        <div class="mb-3">

                            <label class="form-label">
                                Username / Email
                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>

                                <input
                                    type="text"
                                    name="username"
                                    class="form-control"
                                    required
                                    autocomplete="username">

                            </div>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Password
                            </label>

                            <div class="input-group">

                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>

                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="form-control"
                                    required
                                    autocomplete="current-password">

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    onclick="togglePassword()">

                                    <i class="fas fa-eye" id="eyeIcon"></i>

                                </button>

                            </div>

                        </div>

                        <button
                            type="submit"
                            name="login"
                            class="btn btn-primary w-100">

                            <i class="fas fa-sign-in-alt me-2"></i>
                            Login

                        </button>

                    </form>

                    <hr>

                    <div class="text-center">

                        <p class="mb-1">
                            Don't have an account?
                        </p>

                        <a href="../modules/students/register_student.php"
                            class="btn btn-outline-success btn-sm">

                            Register Student

                        </a>

                        <br><br>

                        <a href="../auth/forgot_password.php" class="text-secondary">
                            Forgot Password?
                        </a>

                    </div>

                </div>

            </div>

            <div class="text-center mt-3 text-muted">
                <small>
                    © <?= date('Y') ?> HMS
                </small>
            </div>

        </div>

    </div>

</div>

<script>
    function togglePassword() {

        let password =
            document.getElementById("password");

        let icon =
            document.getElementById("eyeIcon");

        if (password.type === "password") {

            password.type = "text";

            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");

        } else {

            password.type = "password";

            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");

        }
    }

    // Auto Hide Alerts
    setTimeout(() => {

        document.querySelectorAll(".alert").forEach(alert => {

            alert.classList.remove("show");

            setTimeout(() => {
                alert.remove();
            }, 500);

        });

    }, 4000);
</script>

<?php include("../includes/footer.php"); ?>