<?php
// session_start();
include("../../config/database.php");
include("../../includes/header.php");
include("../../includes/admin_sidebar.php");

// Security check: only admin can access
if (!isset($_SESSION['user']) || $_SESSION['role'] != "admin") {
    header("Location: ../../auth/login.php");
    exit();
}

// Fetch counts
$total_students = $conn->query("SELECT COUNT(*) AS count FROM students")->fetch_assoc()['count'];
$total_rooms = $conn->query("SELECT COUNT(*) AS count FROM rooms")->fetch_assoc()['count'];
$total_staff = $conn->query("SELECT COUNT(*) AS count FROM staff")->fetch_assoc()['count'];
$total_payments = $conn->query("SELECT SUM(amount) AS total FROM payments")->fetch_assoc()['total'] ?? 0;
$total_complaints = $conn->query("SELECT COUNT(*) AS count FROM complaints")->fetch_assoc()['count'];
?>

<div class="col-md-9 col-lg-10">
    <div class="container-fluid mt-4">
        <h3><i class="fas fa-user-shield"></i> Admin Dashboard</h3>
        <p class="text-muted">Manage students, staff, rooms, payments, and complaints</p>
        <hr>

        <!-- Overview Cards -->
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h5 class="card-title">Students</h5>
                        <p class="card-text"><strong><?php echo $total_students; ?></strong></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-door-open fa-2x text-info mb-2"></i>
                        <h5 class="card-title">Rooms</h5>
                        <p class="card-text"><strong><?php echo $total_rooms; ?></strong></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-user-tie fa-2x text-success mb-2"></i>
                        <h5 class="card-title">Staff</h5>
                        <p class="card-text"><strong><?php echo $total_staff; ?></strong></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-credit-card fa-2x text-warning mb-2"></i>
                        <h5 class="card-title">Payments</h5>
                        <p class="card-text"><strong>TZS <?php echo number_format($total_payments, 2); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Complaints & Reports -->
        <div class="row g-3 mt-3">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-comment-dots fa-2x text-danger mb-2"></i>
                        <h5 class="card-title">Complaints</h5>
                        <p class="card-text"><strong><?php echo $total_complaints; ?></strong> submitted</p>
                        <a href="<?php echo BASE_URL; ?>modules/complaints/manage_complaints.php" class="btn btn-outline-danger btn-sm">
                            Manage Complaints
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-2x text-secondary mb-2"></i>
                        <h5 class="card-title">Reports</h5>
                        <p class="card-text">Generate system reports</p>
                        <a href="<?php echo BASE_URL; ?>modules/reports/index.php" class="btn btn-outline-secondary btn-sm">
                            View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>