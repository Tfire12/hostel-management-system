<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once(__DIR__ . "/../config/app.php");

// Security: staff only
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'staff') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

/**
 * ACTIVE LINK FIX (more reliable than basename only)
 */
$currentPath = $_SERVER['REQUEST_URI'];

function isActive($keyword)
{
    global $currentPath;
    return (strpos($currentPath, $keyword) !== false)
        ? 'active bg-primary text-white'
        : '';
}

$staff_name = htmlspecialchars($_SESSION['user'] ?? 'Staff');
?>

<nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse" id="staffSidebar" style="min-height:100vh;">
    <div class="position-sticky pt-3">

        <!-- Profile -->
        <div class="px-3 mb-3 d-flex align-items-center">
            <?php
            $initials = '';
            foreach (explode(' ', trim($staff_name)) as $p) {
                $initials .= strtoupper($p[0] ?? '');
                if (strlen($initials) >= 2) break;
            }
            ?>
            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                style="width:48px;height:48px;font-weight:600;">
                <?php echo $initials; ?>
            </div>

            <div class="ms-2">
                <div class="fw-bold"><?php echo $staff_name; ?></div>
                <small class="text-muted">Staff Panel</small>
            </div>
        </div>

        <ul class="nav flex-column px-2">

            <li class="nav-item mb-1">
                <a class="nav-link <?php echo isActive('staff/index.php'); ?>"
                   href="<?php echo BASE_URL; ?>modules/staff/index.php">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>

            <!-- ROOM REQUESTS -->
            <li class="nav-item mb-1">
                <a class="nav-link <?php echo isActive('room_requests'); ?>"
                   href="<?php echo BASE_URL; ?>modules/staff/room_requests.php">
                    <i class="fas fa-paper-plane me-2"></i> Room Requests
                </a>
            </li>
            <li class="nav-item mb-1">
                <a class="nav-link <?php echo isActive('room_change_requests.php'); ?>"
                   href="<?php echo BASE_URL; ?>modules/staff/room_change_requests.php">
                    <i class="fas fa-exchange-alt me-2"></i> Room Change
                </a>
            </li>

            <!-- ALLOCATIONS -->
            <li class="nav-item mb-1">
                <a class="nav-link <?php echo isActive('allocations.php'); ?>"
                   href="<?php echo BASE_URL; ?>modules/staff/allocations.php">
                    <i class="fas fa-door-open me-2"></i> Allocations
                </a>
            </li>

            <li class="nav-item mb-1">
                <a class="nav-link <?php echo isActive('invoices.php'); ?>"
                   href="<?php echo BASE_URL; ?>modules/staff/invoices.php">
                    <i class="fas fa-file-invoice-dollar me-2"></i> Invoices
                </a>
            </li>

            <li class="nav-item mb-1">
                <a class="nav-link <?php echo isActive('complaints.php'); ?>"
                   href="<?php echo BASE_URL; ?>modules/staff/complaints.php">
                    <i class="fas fa-comment-dots me-2"></i> Complaints
                </a>
            </li>

            <hr>

            <li class="nav-item mb-1">
                <a class="nav-link <?php echo isActive('profile.php'); ?>"
                   href="<?php echo BASE_URL; ?>modules/staff/profile.php">
                    <i class="fas fa-user me-2"></i> Profile
                </a>
            </li>

            <li class="nav-item mb-1">
                <a class="nav-link <?php echo isActive('change_password.php'); ?>"
                   href="<?php echo BASE_URL; ?>modules/staff/change_password.php">
                    <i class="fas fa-key me-2"></i> Change Password
                </a>
            </li>

            <li class="nav-item mt-2">
                <a class="nav-link text-danger"
                   href="<?php echo BASE_URL; ?>auth/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </li>

        </ul>
    </div>
</nav>

<!-- UX helper -->
<style>
.sidebar .nav-link {
    color: #333;
    border-radius: 6px;
    padding: .5rem .75rem;
}

.sidebar .nav-link.active {
    color: #fff !important;
}
</style>