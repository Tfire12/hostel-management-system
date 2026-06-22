<?php
session_start();
include("../config/database.php");

$token = $_GET['token'] ?? "";
$error = "";
$success = "";

// Hakiki Token 
$stmt = $conn->prepare("
    SELECT * FROM password_resets
    WHERE token = ?
    AND expires_at > NOW()
    LIMIT 1
");
$stmt->bind_param("s", $token);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h3>Invalid token or expired.</h3><p>Please request a new reset link.</p></div>");
}

if (isset($_POST['reset'])) {

    $password = trim($_POST['password']);

    if (strlen($password) < 6) {
        $error = "Password has to be at least 6 characters.";
    } else {

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $email = $data['email']; // 

        $conn->begin_transaction();

        try {
            // 1. Update Student Password
            $stmt = $conn->prepare("
                UPDATE students 
                SET password = ? 
                WHERE email = ?
            ");
            $stmt->bind_param("ss", $hashed, $email);
            $stmt->execute();

            // 2. Update Staff Password 
            $stmt = $conn->prepare("
                UPDATE staff 
                SET password = ? 
                WHERE username = ?
            ");
            $stmt->bind_param("ss", $hashed, $email);
            $stmt->execute();

            // 3. Delete token for security
            $stmt = $conn->prepare("
                DELETE FROM password_resets 
                WHERE token = ?
            ");
            $stmt->bind_param("s", $token);
            $stmt->execute();

            $conn->commit();

            $success = "Password Reset Successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Exception Error. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-header bg-success text-white text-center">
                        Reset Password
                    </div>
                    <div class="card-body">
                        <?php if ($success) echo "<div class='alert alert-success'>$success <br><a href='login.php'>Click to Login</a></div>"; ?>
                        <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

                        <?php if (!$success) { ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label>New Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button class="btn btn-success w-100" name="reset">
                                    Reset Password
                                </button>
                            </form>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>