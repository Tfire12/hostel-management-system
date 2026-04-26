<?php
session_start();
include("../config/database.php");
include("../includes/header.php");
include("../includes/sidebar.php");

// Check if student is logged in
if (!isset($_SESSION['user']) || $_SESSION['role'] != "student") {
    header("Location: ../auth/login.php");
    exit();
}

// Get student info
$reg_number = $_SESSION['reg_number']; // tunahifadhi reg_number wakati wa login
$student = $conn->query("SELECT * FROM Students WHERE reg_number='$reg_number'")->fetch_assoc();

// Room allocation
$allocation = $conn->query("SELECT Rooms.room_number, Rooms.room_type 
                            FROM allocations 
                            JOIN Rooms ON allocations.room_id = Rooms.room_id 
                            WHERE allocations.student_id=" . $student['student_id'])->fetch_assoc();

// Payments
$payments = $conn->query("SELECT SUM(amount) AS total_paid FROM Payments WHERE student_id=" . $student['student_id'])->fetch_assoc();
$balance = 500000 - $payments['total_paid']; // mfano hostel fee ni 500,000 TZS

// Complaints
$complaints = $conn->query("SELECT COUNT(*) AS total_complaints FROM Complaints WHERE student_id=" . $student['student_id'])->fetch_assoc();
?>

<div class="container mt-4">
    <h3>Hello, <?php echo $student['name']; ?> 👋</h3>
    <p class="text-muted">Welcome to your Hostel Management Dashboard</p>
    <hr>

    <div class="row">
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-door-open"></i> My Room</h5>
                    <p class="card-text">
                        <?php 
                        if ($allocation) {
                            echo "Room: " . $allocation['room_number'] . " (" . $allocation['room_type'] . ")";
                        } else {
                            echo "No room allocated yet.";
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-credit-card"></i> Payments</h5>
                    <p class="card-text">
                        Paid: TZS <?php echo number_format($payments['total_paid'], 2); ?><br>
                        Balance: TZS <?php echo number_format($balance, 2); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-comment-dots"></i> Complaints</h5>
                    <p class="card-text"><?php echo $complaints['total_complaints']; ?> submitted</p>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mt-4">My Profile</h4>
    <table class="table table-striped">
        <tr><th>Name</th><td><?php echo $student['name']; ?></td></tr>
        <tr><th>Reg Number</th><td><?php echo $student['reg_number']; ?></td></tr>
        <tr><th>Email</th><td><?php echo $student['email']; ?></td></tr>
        <tr><th>Phone</th><td><?php echo $student['phone']; ?></td></tr>
    </table>
</div>

<?php include("../includes/footer.php"); ?>
