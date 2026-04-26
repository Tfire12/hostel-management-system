<?php include_once("../../config/app.php"); ?>

<!-- Desktop Sidebar -->
<div class="col-md-3 col-lg-2 p-0 bg-dark text-white vh-100 d-none d-md-block">
    <nav class="d-flex flex-column flex-shrink-0 p-3">
        <a href="../students/index.php" class="d-flex align-items-center mb-3 text-white text-decoration-none">
            <span class="fs-4"><i class="fas fa-hotel me-2"></i> HMS Student</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item"><a href=" <?php echo BASE_URL; ?>modules/students/index.php" class="nav-link text-white"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>modules/students/profile.php" class="nav-link text-white"><i class="fas fa-user me-2"></i> My Profile</a></li>
            <li><a href="<?php echo BASE_URL; ?>modules/rooms/my_room.php" class="nav-link text-white"><i class="fas fa-door-open me-2"></i> My Room</a></li>
            <li><a href="<?php echo BASE_URL; ?>modules/payments/my_payments.php" class="nav-link text-white"><i class="fas fa-credit-card me-2"></i> Payments</a></li>
            <li><a href="<?php echo BASE_URL; ?>modules/complaints/my_complaints.php" class="nav-link text-white"><i class="fas fa-comment-dots me-2"></i> Complaints</a></li>
        </ul>
        <hr>
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-danger w-100"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</div>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="sidebarMenuMobile">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title"><i class="fas fa-hotel me-2"></i> HMS Student</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item"><a href="<?php echo BASE_URL; ?>modules/students/index.php" class="nav-link text-white"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>modules/students/profile.php" class="nav-link text-white"><i class="fas fa-user me-2"></i> My Profile</a></li>
            <li><a href="<?php echo BASE_URL; ?>modules/rooms/my_room.php" class="nav-link text-white"><i class="fas fa-door-open me-2"></i> My Room</a></li>
            <li><a href="<?php echo BASE_URL; ?>modules/payments/my_payments.php" class="nav-link text-white"><i class="fas fa-credit-card me-2"></i> Payments</a></li>
            <li><a href="<?php echo BASE_URL; ?>modules/complaints/my_complaints.php" class="nav-link text-white"><i class="fas fa-comment-dots me-2"></i> Complaints</a></li>
        </ul>
        <hr>
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-danger w-100"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
