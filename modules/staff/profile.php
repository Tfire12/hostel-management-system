<?php
session_start();

include("../../config/app.php");
include("../../config/database.php");

// Security check
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'staff') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// Get staff ID from session (IMPORTANT FIX)
$staff_id = intval($_SESSION['user_id'] ?? 0);

// Fetch staff data
$stmt = $conn->prepare("
    SELECT staff_id, name, username, role, created_at
    FROM staff
    WHERE staff_id = ?
");

$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$stmt->close();

// If no data found
if (!$staff) {
    die("Staff profile not found or session invalid.");
}

include("../../includes/header.php");
?>

<div class="container-fluid">
<div class="row">

<?php include("../../includes/staff_sidebar.php"); ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
        <h2 class="fw-bold">
            <i class="fas fa-user-circle text-primary"></i>
            My Profile
        </h2>
        <small class="text-muted">Account information overview</small>
    </div>

    <!-- Profile Card -->
    <div class="row">

        <div class="col-lg-6">

            <div class="card shadow-lg border-0 rounded-4">

                <div class="card-body text-center p-4">

                    <!-- Avatar -->
                    <div class="mb-3">
                        <div class="bg-primary text-white rounded-circle d-inline-flex justify-content-center align-items-center"
                             style="width:90px; height:90px; font-size:35px;">
                            <?php echo strtoupper(substr($staff['name'], 0, 1)); ?>
                        </div>
                    </div>

                    <h4 class="fw-bold mb-1">
                        <?php echo htmlspecialchars($staff['name']); ?>
                    </h4>

                    <p class="text-muted mb-3">
                        <?php echo htmlspecialchars($staff['role']); ?>
                    </p>

                    <hr>

                    <!-- Info -->
                    <div class="text-start">

                        <p>
                            <strong>Username:</strong><br>
                            <?php echo htmlspecialchars($staff['username']); ?>
                        </p>

                        <p>
                            <strong>Role:</strong><br>
                            <span class="badge bg-success">
                                <?php echo htmlspecialchars($staff['role']); ?>
                            </span>
                        </p>

                        <p>
                            <strong>Member Since:</strong><br>
                            <?php echo date("d M Y", strtotime($staff['created_at'])); ?>
                        </p>

                    </div>

                    <hr>

                    <!-- Actions -->
                    <a href="<?php echo BASE_URL; ?>modules/staff/change_password.php"
                       class="btn btn-warning w-100 mb-2">

                        <i class="fas fa-key"></i>
                        Change Password
                    </a>

                </div>
            </div>

        </div>

    </div>

</main>

</div>
</div>

<?php include("../../includes/footer.php"); ?>