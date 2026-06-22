<?php
session_start();
include("../config/database.php");

$message = "";
$error = "";

if (isset($_POST['send'])) {

    $identifier = trim($_POST['identifier']); // Tunatumia 'identifier' badala ya email tu

    if (empty($identifier)) {
        $error = "Tafadhali weka Email au Namba ya Usajili (Reg Number)";
    } else {
        $resolved_email = "";

        // 1. Mtafute Mwanafunzi kwa Email au Reg Number
        $stmt = $conn->prepare("SELECT email FROM students WHERE email = ? OR reg_number = ?");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();

        if ($student) {
            $resolved_email = $student['email']; // Chukua email halisi
        } else {
            // 2. Kama sio mwanafunzi, mtafute Staff kwa Username
            $stmt = $conn->prepare("SELECT username FROM staff WHERE username = ?");
            $stmt->bind_param("s", $identifier);
            $stmt->execute();
            $staff = $stmt->get_result()->fetch_assoc();

            if ($staff) {
                $resolved_email = $staff['username']; // Chukua username halisi ya staff
            }
        }

        if (!$resolved_email) {
            $error = "Akaunti haijapatikana. Hakikisha taarifa ni sahihi.";
        } else {
            // Tengeneza token
            $token = bin2hex(random_bytes(32));

            // Futa tokens za zamani za huyu user
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->bind_param("s", $resolved_email);
            $del->execute();

            // Ingiza token mpya. Tumia MySQL NOW() kuzuia kupishana kwa muda (Timezone issues)
            $stmt = $conn->prepare("
                INSERT INTO password_resets (email, token, expires_at)
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))
            ");
            $stmt->bind_param("ss", $resolved_email, $token);
            $stmt->execute();

            // Tengeneza link ya kureset (Hakikisha path ipo sahihi)
            $link = "http://localhost/hostel-management-system/auth/reset_password.php?token=" . urlencode($token);

            $message = "Link ya kureset password imetumwa: <br><a href='$link' class='btn btn-sm btn-outline-primary mt-2'>Bofya Hapa</a>";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        Forgot Password
                    </div>
                    <div class="card-body">
                        <?php if ($message) echo "<div class='alert alert-success'>$message</div>"; ?>
                        <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label>Email or (Reg Number)</label>
                                <input type="text" name="identifier" class="form-control" required>
                            </div>
                            <button class="btn btn-primary w-100" name="send">
                                Send Reset Link
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>