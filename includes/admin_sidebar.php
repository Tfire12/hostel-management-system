<?php
session_start();
include_once("../../config/app.php");

// Security check: only admin can access sidebar
if (!isset($_SESSION['user']) || $_SESSION['role'] != "admin") {
    header("Location: ../../auth/login.php");
    exit();
}
?>

<!-- Admin Sidebar -->
<div class="col-md-3 col-lg-2 p-0 bg-dark text-white vh-100 d-none d-md-block">
    <nav class="d-flex flex-column flex-shrink-0 p-3">
        <a href="<?php echo BASE_URL; ?>modules/admin/index.php" class="d-flex align-items-center mb-3 text-white text-decoration-none">
            <span class="fs-4"><i class="fas fa-user-shield me-2"></i> HMS Admin</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li><a href="<?php echo BASE_URL; ?>modules/admin/index.php" class="nav-link text-white"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>modules/admin/manage_students.php" class="nav-link text-white"><i class="fas fa-users me-2"></i> Manage Students</a></li>
            <li><a href="<?php echo BASE_URL; ?>modules/admin/manage_staff.php" class="nav-link text-white"><i class="fas fa-user-tie me-2"></i> Manage Staff</a></li>
            <li><a href="<?php echo BASE_URL; ?>modules/admin/manage_rooms.php" class="nav-link text-white"><i class="fas fa-door-open me-2"></i> Manage Rooms</a></li>
            <li><a href="<?php echo BASE_URL; ?>modules/admin/manage_payments.php" class="nav-link text-white"><i class="fas fa-credit-card me-2"></i> Manage Payments</a></li>
            <li><a href="<?php echo BASE_URL; ?>modules/admin/manage_complaints.php" class="nav-link text-white"><i class="fas fa-comment-dots me-2"></i> Complaints</a></li>
            <li><a href="<?php echo BASE_URL; ?>modules/admin/reports.php" class="nav-link text-white"><i class="fas fa-chart-line me-2"></i> Reports</a></li>
        </ul>
        <hr>
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-danger w-100"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</div>
