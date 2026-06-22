<?php
include("../../config/database.php");
include("../../includes/header.php");
include("../../includes/admin_sidebar.php");

// 1. Security Check (Ulinzi)
if (!isset($_SESSION['user']) || $_SESSION['role'] != "admin") {
    header("Location: ../../auth/login.php");
    exit();
}

// 2. Fetch Totals for Summary Cards (Picha ya haraka ya dashboard)
$total_revenue = $conn->query("SELECT SUM(amount) as total FROM payments WHERE payment_status = 'CONFIRMED'")->fetch_assoc()['total'] ?? 0;
$pending_complaints = $conn->query("SELECT COUNT(*) as total FROM complaints WHERE status = 'OPEN'")->fetch_assoc()['total'] ?? 0;
$total_students = $conn->query("SELECT COUNT(*) as total FROM students")->fetch_assoc()['total'] ?? 0;

$report_data = [];
$type = "";

// 3. Handle Report Generation (Kwa kutumia Prepared Statements)
if (isset($_POST['generate_report'])) {
    $type = $_POST['report_type'];
    $from = $_POST['from_date'];
    $to = $_POST['to_date'];

    if ($type == "payments") {
        $sql = "SELECT p.payment_id, s.name as student, i.invoice_no, p.amount, p.payment_date, p.payment_status 
                FROM payments p 
                JOIN students s ON p.student_id = s.student_id 
                JOIN invoices i ON p.invoice_id = i.invoice_id 
                WHERE p.payment_date BETWEEN ? AND ? ORDER BY p.payment_date DESC";
    } elseif ($type == "complaints") {
        $sql = "SELECT c.complaint_id, s.name as student, c.issue, c.status, c.complaint_date 
                FROM complaints c 
                JOIN students s ON c.student_id = s.student_id 
                WHERE c.complaint_date BETWEEN ? AND ? ORDER BY c.complaint_date DESC";
    } elseif ($type == "allocations") {
        $sql = "SELECT a.allocation_id, s.name as student, r.room_number, a.allocation_date, a.status 
                FROM allocations a 
                JOIN students s ON a.student_id = s.student_id 
                JOIN rooms r ON a.room_id = r.room_id 
                WHERE a.allocation_date BETWEEN ? AND ? ORDER BY a.allocation_date DESC";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    $report_data = $stmt->get_result();
}
?>

<style>
.card {
    border: none;
    border-radius: 10px;
}

.bg-gradient-primary {
    background: linear-gradient(45deg, #4e73df, #224abe);
}

.table thead th {
    background-color: #f8f9fc;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e3e6f0;
}

.badge-soft-success {
    background-color: #e5f9f0;
    color: #00ab66;
}

.badge-soft-danger {
    background-color: #ffeef3;
    color: #ff5b5c;
}

@media print {
    .no-print {
        display: none;
    }

    .card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
</style>

<div class="col-md-9 col-lg-10 bg-light min-vh-100">
    <div class="container-fluid mt-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <div>
                <h3 class="fw-bold text-dark">System Reports</h3>
                <p class="text-muted">Analyze payments, complaints, and room allocations</p>
            </div>
            <button onclick="window.print()" class="btn btn-white shadow-sm border">
                <i class="fas fa-print text-primary me-2"></i> Print Report
            </button>
        </div>

        <!-- Summary Cards (Top UX) -->
        <div class="row mb-4 no-print">
            <div class="col-md-4">
                <div class="card shadow-sm p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-light p-3 me-3"><i class="fas fa-wallet text-success"></i></div>
                        <div>
                            <small class="text-muted d-block">Confirmed Revenue</small>
                            <span class="h5 fw-bold mb-0">TZS <?php echo number_format($total_revenue); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-light p-3 me-3"><i
                                class="fas fa-exclamation-circle text-danger"></i></div>
                        <div>
                            <small class="text-muted d-block">Open Complaints</small>
                            <span class="h5 fw-bold mb-0"><?php echo $pending_complaints; ?> Issues</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-light p-3 me-3"><i class="fas fa-users text-primary"></i></div>
                        <div>
                            <small class="text-muted d-block">Registered Students</small>
                            <span class="h5 fw-bold mb-0"><?php echo $total_students; ?> Students</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card shadow-sm mb-4 no-print">
            <div class="card-body">
                <form method="POST" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">Report Category</label>
                        <select name="report_type" class="form-select shadow-none border-primary" required>
                            <option value="payments" <?php echo ($type=='payments')?'selected':''; ?>>Financial:
                                Payments</option>
                            <option value="complaints" <?php echo ($type=='complaints')?'selected':''; ?>>Service:
                                Complaints</option>
                            <option value="allocations" <?php echo ($type=='allocations')?'selected':''; ?>>Logistics:
                                Allocations</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">From Date</label>
                        <input type="date" name="from_date" class="form-control" required
                            value="<?php echo $_POST['from_date'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-uppercase">To Date</label>
                        <input type="date" name="to_date" class="form-control" required
                            value="<?php echo $_POST['to_date'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" name="generate_report" class="btn btn-primary w-100 shadow-sm">
                            <i class="fas fa-filter me-2"></i> Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Results -->
        <?php if (isset($report_data)): ?>
        <div class="card shadow-sm mb-5 overflow-hidden">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary">
                    <i class="fas fa-table me-2"></i>
                    Showing Results for <?php echo ucfirst($type); ?>
                    (<?php echo $from ?? ''; ?> to <?php echo $to ?? ''; ?>)
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <?php if($type == 'payments'): ?>
                                <th class="ps-4">Ref</th>
                                <th>Student</th>
                                <th>Invoice</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <?php elseif($type == 'complaints'): ?>
                                <th class="ps-4">ID</th>
                                <th>Student</th>
                                <th>Issue</th>
                                <th>Status</th>
                                <th>Date</th>
                                <?php elseif($type == 'allocations'): ?>
                                <th class="ps-4">ID</th>
                                <th>Student</th>
                                <th>Room No</th>
                                <th>Date</th>
                                <th>Status</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($report_data && $report_data instanceof mysqli_result && $report_data->num_rows > 0): ?>
                            <?php while($row = $report_data->fetch_assoc()): ?>           <tr>
                                <?php if($type == 'payments'): ?>
                                <td class="ps-4">#<?php echo $row['payment_id']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['student']); ?></td>
                                <td><small class="text-muted"><?php echo $row['invoice_no']; ?></small></td>
                                <td class="fw-bold text-dark"><?php echo number_format($row['amount']); ?></td>
                                <td>
                                    <span
                                        class="badge <?php echo ($row['payment_status']=='CONFIRMED')?'badge-soft-success':'badge-soft-danger'; ?>">
                                        <?php echo $row['payment_status'] ?? 'PENDING'; ?>
                                    </span>
                                </td>
                                <td><?php echo $row['payment_date']; ?></td>

                                <?php elseif($type == 'complaints'): ?>
                                <td class="ps-4">#<?php echo $row['complaint_id']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['student']); ?></td>
                                <td><?php echo htmlspecialchars($row['issue']); ?></td>
                                <td>
                                    <span
                                        class="badge <?php echo ($row['status']=='RESOLVED')?'bg-success':'bg-warning'; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo $row['complaint_date']; ?></td>

                                <?php elseif($type == 'allocations'): ?>
                                <td class="ps-4">#<?php echo $row['allocation_id']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['student']); ?></td>
                                <td><span class="badge bg-info">Room <?php echo $row['room_number']; ?></span></td>
                                <td><?php echo $row['allocation_date']; ?></td>
                                <td><?php echo $row['status']; ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-3x d-block mb-3"></i>
                                    No records found for the selected criteria.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>