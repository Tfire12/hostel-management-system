<?php
session_start();
include("../../config/database.php");

// SECURITY
if (!isset($_SESSION['user']) || $_SESSION['role'] != "student") {
    header("Location: ../../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// ==========================
// STUDENT INFO
// ==========================
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();


// ==========================
// ACTIVE ROOM
// ==========================
$room = $conn->query("
    SELECT r.room_number, r.room_type, r.capacity
    FROM allocations a
    JOIN rooms r ON a.room_id = r.room_id
    WHERE a.student_id = $student_id
    AND a.status = 'ACTIVE'
    LIMIT 1
")->fetch_assoc();


// ==========================
// PAYMENTS (REAL INVOICE BASED)
// ==========================
$pay = $conn->query("
    SELECT 
        SUM(p.amount) AS total_paid
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.invoice_id
    WHERE p.student_id = $student_id
")->fetch_assoc();

$total_paid = $pay['total_paid'] ?? 0;
$hostel_fee = 500000;
$balance = $hostel_fee - $total_paid;


// ==========================
// LATEST INVOICE + CONTROL NUMBER
// ==========================
$invoice = $conn->query("
    SELECT invoice_no, control_number, amount, status
    FROM invoices
    WHERE student_id = $student_id
    ORDER BY created_at DESC
    LIMIT 1
")->fetch_assoc();


// ==========================
// COMPLAINTS
// ==========================
$comp = $conn->query("
    SELECT COUNT(*) AS total
    FROM complaints
    WHERE student_id = $student_id
")->fetch_assoc();

include("../../includes/header.php");
include("../../includes/sidebar.php");
?>

<div class="col-md-9 col-lg-10">
    <div class="container-fluid mt-4">

        <!-- HEADER -->
        <div class="card shadow-sm border-0 mb-4 bg-gradient bg-primary text-white">
            <div class="card-body">
                <h4 class="mb-0">Welcome, <?= htmlspecialchars($student['name']); ?> </h4>
                <small>Hostel Management System Dashboard</small>
            </div>
        </div>

        <!-- CARDS -->
        <div class="row g-3">

            <!-- ROOM -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-door-open fa-2x text-info mb-2"></i>
                        <h5>My Room</h5>
                        <p class="text-muted">
                            <?php if ($room) { ?>
                                Room <b><?= $room['room_number']; ?></b><br>
                                Type: <?= $room['room_type']; ?>
                            <?php } else { ?>
                                No room allocated yet
                            <?php } ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- PAYMENTS (FIXED + REAL DATA) -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body text-center">
                        <i class="fas fa-credit-card fa-2x text-success mb-2"></i>
                        <h5>Payments</h5>

                        <p class="mb-1">
                            Paid: <b class="text-success">
                                TZS <?= number_format($total_paid, 2); ?>
                            </b>
                        </p>

                        <p class="mb-1">
                            Balance: <b class="text-danger">
                                TZS <?= number_format($balance, 2); ?>
                            </b>
                        </p>

                        <?php if ($invoice) { ?>
                            <hr>
                            <small class="text-muted">Latest Invoice</small><br>
                            <b><?= $invoice['invoice_no']; ?></b><br>
                            <small>Control No: <b><?= $invoice['control_number']; ?></b></small>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- COMPLAINTS -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-comment-dots fa-2x text-warning mb-2"></i>
                        <h5>Complaints</h5>
                        <h3><?= $comp['total']; ?></h3>
                        <small class="text-muted">submitted</small>
                    </div>
                </div>
            </div>

        </div>

        <!-- PROFILE -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-dark text-white">
                <i class="fas fa-user"></i> Profile
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><b>Name:</b> <?= htmlspecialchars($student['name']); ?></p>
                        <p><b>Reg No:</b> <?= htmlspecialchars($student['reg_number']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><b>Email:</b> <?= htmlspecialchars($student['email']); ?></p>
                        <p><b>Phone:</b> <?= htmlspecialchars($student['phone']); ?></p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include("../../includes/footer.php"); ?>