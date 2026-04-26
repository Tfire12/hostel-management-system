<?php
session_start();
include("../../config/database.php");
include("../../includes/header.php");
include("../../includes/sidebar.php");

// Hakikisha ni mwanafunzi aliye login
if (!isset($_SESSION['user']) || $_SESSION['role'] != "student") {
    header("Location: ../../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student = $conn->query("SELECT * FROM students WHERE student_id=$student_id")->fetch_assoc();

// Room allocation
$allocation = $conn->query("SELECT Rooms.room_number, Rooms.room_type 
                            FROM allocations 
                            JOIN Rooms ON allocations.room_id = Rooms.room_id 
                            WHERE allocations.student_id=$student_id")->fetch_assoc();

// Payments
$payments = $conn->query("SELECT SUM(amount) AS total_paid FROM payments WHERE student_id=$student_id")->fetch_assoc();
$total_paid = $payments['total_paid'] ?? 0;
$balance = 500000 - $total_paid; // mfano hostel fee ni 500,000 TZS

// Complaints
$complaints = $conn->query("SELECT COUNT(*) AS total_complaints FROM complaints WHERE student_id=$student_id")->fetch_assoc();
?>
<div class="col-md-9 col-lg-10">
<div class="container-fluid mt-4">
    <h3 class="mb-3">Hello, <?php echo $student['name']; ?></h3>
    <p class="text-muted">Welcome to your Hostel Management Dashboard</p>

    <div class="row g-3">
        <!-- My Room -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <i class="fas fa-door-open fa-2x text-info mb-2"></i>
                    <h5 class="card-title">My Room</h5>
                    <p class="card-text">
                        <?php 
                        if ($allocation) {
                            echo "Room: <strong>" . $allocation['room_number'] . "</strong><br>Type: " . $allocation['room_type'];
                        } else {
                            echo "No room allocated yet.";
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Payments -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <i class="fas fa-credit-card fa-2x text-success mb-2"></i>
                    <h5 class="card-title">Payments</h5>
                    <p class="card-text">
                        Paid: <strong>TZS <?php echo number_format($total_paid, 2); ?></strong><br>
                        Balance: <strong>TZS <?php echo number_format($balance, 2); ?></strong>
                    </p>
                </div>
            </div>
        </div>

        <!-- Complaints -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <i class="fas fa-comment-dots fa-2x text-warning mb-2"></i>
                    <h5 class="card-title">Complaints</h5>
                    <p class="card-text"><?php echo $complaints['total_complaints']; ?> submitted</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Section -->
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-user"></i> My Profile
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <tr><th>Name</th><td><?php echo $student['name']; ?></td></tr>
                <tr><th>Reg Number</th><td><?php echo $student['reg_number']; ?></td></tr>
                <tr><th>Email</th><td><?php echo $student['email']; ?></td></tr>
                <tr><th>Phone</th><td><?php echo $student['phone']; ?></td></tr>
            </table>
        </div>
    </div>
</div>
</div>

<?php include("../../includes/footer.php"); ?>
