<?php
session_start();

include("../../config/app.php");
include("../../config/database.php");

// Security
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'staff') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

// ==========================
// CHANGE PASSWORD PROCESS
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid security token.";
        header("Location: " . BASE_URL . "modules/staff/change_password.php");
        exit();
    }

    // FIX: USE user_id NOT staff_id
    $staff_id = intval($_SESSION['user_id'] ?? 0);

    $current = trim($_POST['current_password'] ?? '');
    $new = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    // Basic validation
    if (empty($current) || empty($new) || empty($confirm)) {

        $_SESSION['error'] = "All fields are required.";
    } elseif ($new !== $confirm) {

        $_SESSION['error'] = "New password and confirmation do not match.";
    } elseif (strlen($new) < 8) {

        $_SESSION['error'] = "Password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Z]/', $new)) {

        $_SESSION['error'] = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $new)) {

        $_SESSION['error'] = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $new)) {

        $_SESSION['error'] = "Password must contain at least one number.";
    } elseif ($current === $new) {

        $_SESSION['error'] = "New password cannot be the same as current password.";
    } else {

        // Get current password
        $stmt = $conn->prepare("SELECT password FROM staff WHERE staff_id = ?");
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $staff = $result->fetch_assoc();
        $stmt->close();

        // FIX: ensure row exists
        if (!$staff) {

            $_SESSION['error'] = "User not found. Session issue detected.";
        } elseif (!password_verify($current, $staff['password'])) {

            $_SESSION['error'] = "Current password is incorrect.";
        } else {

            // Hash new password
            $hash = password_hash($new, PASSWORD_DEFAULT);

            $update = $conn->prepare("
                UPDATE staff
                SET password = ?
                WHERE staff_id = ?
            ");

            $update->bind_param("si", $hash, $staff_id);

            if ($update->execute()) {

                $_SESSION['success'] = "Password changed successfully.";
            } else {

                $_SESSION['error'] = "Failed to update password.";
            }

            $update->close();
        }
    }

    header("Location: " . BASE_URL . "modules/staff/change_password.php");
    exit();
}

include("../../includes/header.php");
?>

<div class="container-fluid">
    <div class="row">

        <?php include("../../includes/staff_sidebar.php"); ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

            <div class="pt-4 pb-2 mb-4 border-bottom">
                <h2 class="fw-bold">
                    Change Password
                </h2>
                <p class="text-muted mb-0">
                    Keep your account secure by using a strong password.
                </p>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" id="alertBox">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" id="alertBox">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row ">

                <div class="col-lg-7">

                    <div class="card shadow-lg border-0 rounded-4">

                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="mb-0">
                                <i class="fas fa-lock"></i>
                                Security Settings
                            </h5>
                        </div>

                        <div class="card-body p-4">

                            <form method="POST"
                                action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                                onsubmit="return validateForm();">

                                <input type="hidden"
                                    name="csrf_token"
                                    value="<?php echo $csrf_token; ?>">

                                <div class="mb-3">

                                    <label class="form-label fw-semibold">
                                        Current Password
                                    </label>

                                    <div class="input-group">

                                        <input type="password"
                                            name="current_password"
                                            id="current_password"
                                            class="form-control"
                                            required>

                                        <button type="button"
                                            class="btn btn-outline-secondary"
                                            onclick="togglePassword('current_password')">

                                            <i class="fas fa-eye"></i>

                                        </button>

                                    </div>

                                </div>

                                <div class="mb-3">

                                    <label class="form-label fw-semibold">
                                        New Password
                                    </label>

                                    <div class="input-group">

                                        <input type="password"
                                            name="new_password"
                                            id="new_password"
                                            class="form-control"
                                            required>

                                        <button type="button"
                                            class="btn btn-outline-secondary"
                                            onclick="togglePassword('new_password')">

                                            <i class="fas fa-eye"></i>

                                        </button>

                                    </div>

                                    <div class="progress mt-2">
                                        <div class="progress-bar"
                                            id="strengthBar"
                                            style="width:0%">
                                        </div>
                                    </div>

                                    <small id="strengthText" class="text-muted"></small>

                                </div>

                                <div class="mb-3">

                                    <label class="form-label fw-semibold">
                                        Confirm Password
                                    </label>

                                    <div class="input-group">

                                        <input type="password"
                                            name="confirm_password"
                                            id="confirm_password"
                                            class="form-control"
                                            required>

                                        <button type="button"
                                            class="btn btn-outline-secondary"
                                            onclick="togglePassword('confirm_password')">

                                            <i class="fas fa-eye"></i>

                                        </button>

                                    </div>

                                </div>

                                <div id="validationMessage"
                                    class="text-danger small mb-3">
                                </div>

                                <button type="submit"
                                    name="change_password"
                                    class="btn btn-success w-100">

                                    <i class="fas fa-save"></i>
                                    Update Password

                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </main>
    </div>
</div>

<script>
    function togglePassword(id) {

        let field = document.getElementById(id);

        if (field.type === "password") {
            field.type = "text";
        } else {
            field.type = "password";
        }
    }

    document.getElementById('new_password')
        .addEventListener('keyup', function() {

            let password = this.value;
            let score = 0;

            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            let bar = document.getElementById('strengthBar');
            let text = document.getElementById('strengthText');

            bar.style.width = (score * 20) + "%";

            if (score <= 2) {

                bar.className = "progress-bar bg-danger";
                text.innerHTML = "Weak Password";

            } else if (score <= 4) {

                bar.className = "progress-bar bg-warning";
                text.innerHTML = "Medium Password";

            } else {

                bar.className = "progress-bar bg-success";
                text.innerHTML = "Strong Password";
            }

        });

    function validateForm() {

        const current =
            document.getElementById('current_password').value;

        const newPass =
            document.getElementById('new_password').value;

        const confirm =
            document.getElementById('confirm_password').value;

        const msg =
            document.getElementById('validationMessage');

        msg.innerHTML = "";

        if (current === "") {
            msg.innerHTML = "Current password is required.";
            return false;
        }

        if (newPass.length < 8) {
            msg.innerHTML = "Password must be at least 8 characters.";
            return false;
        }

        if (newPass !== confirm) {
            msg.innerHTML = "Passwords do not match.";
            return false;
        }

        return true;
    }

    setTimeout(() => {

        let alertBox = document.getElementById('alertBox');

        if (alertBox) {

            let alert =
                new bootstrap.Alert(alertBox);

            alert.close();
        }

    }, 4000);
</script>

<?php include("../../includes/footer.php"); ?>