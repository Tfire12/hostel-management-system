<?php
session_start();
include("../../config/database.php");
include("../../includes/header.php");
include("../../includes/sidebar.php");

// Auth check
if (!isset($_SESSION['user']) || $_SESSION['role'] != "student") {
    header("Location: ../../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// 🔷 FETCH INVOICES + CALCULATED PAYMENTS
$invoices = $conn->query("
SELECT 
    i.*,
    IFNULL(SUM(p.amount),0) as total_paid
FROM invoices i
LEFT JOIN payments p ON p.invoice_id = i.invoice_id
WHERE i.student_id = $student_id
GROUP BY i.invoice_id
ORDER BY i.academic_year DESC, i.created_at DESC
");

// 🔷 CURRENT ACTIVE (NOT PAID + NOT EXPIRED)
$current = $conn->query("
SELECT 
    i.*,
    IFNULL(SUM(p.amount),0) as total_paid
FROM invoices i
LEFT JOIN payments p ON p.invoice_id = i.invoice_id
WHERE i.student_id=$student_id 
AND i.status IN ('PENDING','PARTIAL')
GROUP BY i.invoice_id
ORDER BY i.created_at DESC 
LIMIT 1
")->fetch_assoc();
?>

<div class="col-md-9 col-lg-10">
    <div class="container-fluid mt-4">

        <h3><i class="fas fa-credit-card"></i> My Payments</h3>
        <p class="text-muted">Manage your invoices and payments</p>
        <hr>

        <div class="alert alert-info">
            <strong>NOTE:</strong> Use the control number below to complete your payment.
        </div>

        <a href="generate_invoice.php" class="btn btn-primary mb-3">
            + Generate Control Number
        </a>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])) { ?>
            <div class="alert alert-success auto-dismiss">
                <?php echo $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php } ?>

        <?php if (isset($_SESSION['error'])) { ?>
            <div class="alert alert-danger auto-dismiss">
                <?php echo $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php } ?>

        <!-- CURRENT INVOICE -->
        <?php if ($current) {
            $balance = $current['amount'] - $current['total_paid'];
            $progress = ($current['amount'] > 0) ? ($current['total_paid'] / $current['amount']) * 100 : 0;
        ?>
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    Current Active Invoice
                </div>

                <div class="card-body text-center">

                    <h6>Control Number</h6>
                    <h3 class="fw-bold"><?php echo $current['control_number']; ?></h3>

                    <button onclick="copyCN('<?php echo $current['control_number']; ?>')"
                        class="btn btn-sm btn-outline-dark mb-3">
                        Copy
                    </button>

                    <p>Amount: <strong class="text-primary">
                            TZS <?php echo number_format($current['amount'], 2); ?>
                        </strong></p>

                    <p>Paid: <strong class="text-success">
                            TZS <?php echo number_format($current['total_paid'], 2); ?>
                        </strong></p>

                    <p>Balance: <strong class="text-danger">
                            TZS <?php echo number_format($balance, 2); ?>
                        </strong></p>

                    <!-- Progress bar -->
                    <div class="progress mt-3" style="height:10px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%"></div>
                    </div>

                    <p class="mt-2 small text-muted">
                        <?php echo round($progress); ?>% Paid
                    </p>

                    <p>Expires: <span class="text-danger">
                            <?php echo $current['expires_at']; ?>
                        </span></p>

                </div>
            </div>
        <?php } ?>

        <!-- 🔷 ALL INVOICES -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-secondary text-white">
                Invoice History
            </div>

            <div class="card-body">

                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice</th>
                            <th>Control No</th>
                            <th>Year</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $i = 1;
                        while ($row = $invoices->fetch_assoc()) {

                            $balance = $row['amount'] - $row['total_paid'];
                        ?>
                            <tr>

                                <td><?php echo $i++; ?></td>

                                <td><strong><?php echo $row['invoice_no']; ?></strong></td>

                                <td>
                                    <span class="badge bg-dark"><?php echo $row['control_number']; ?></span><br>
                                    <button onclick="copyCN('<?php echo $row['control_number']; ?>')"
                                        class="btn btn-sm btn-outline-secondary mt-1">
                                        Copy
                                    </button>
                                </td>

                                <td><?php echo $row['academic_year']; ?></td>

                                <td class="text-primary">
                                    <?php echo number_format($row['amount'], 2); ?>
                                </td>

                                <td class="text-success">
                                    <?php echo number_format($row['total_paid'], 2); ?>
                                </td>

                                <td class="<?php echo ($balance == 0) ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo number_format($balance, 2); ?>
                                </td>

                                <td>
                                    <?php
                                    if ($balance == 0) {
                                        echo "<span class='badge bg-success'>Paid</span>";
                                    } elseif ($row['total_paid'] > 0) {
                                        echo "<span class='badge bg-warning text-dark'>Partial</span>";
                                    } else {
                                        echo "<span class='badge bg-secondary'>Pending</span>";
                                    }
                                    ?>
                                </td>

                            </tr>
                        <?php } ?>
                    </tbody>

                </table>

            </div>
        </div>

    </div>
</div>

<!-- JS -->
<script>
    function copyCN(text) {
        navigator.clipboard.writeText(text);
    }

    // auto dismiss
    setTimeout(() => {
        document.querySelectorAll(".auto-dismiss").forEach(el => {
            el.style.opacity = "0";
            setTimeout(() => el.remove(), 500);
        });
    }, 3000);
</script>

<?php include("../../includes/footer.php"); ?>