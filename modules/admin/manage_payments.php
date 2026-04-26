<?php
//session_start();
include("../../config/database.php");
include("../../includes/header.php");
include("../../includes/admin_sidebar.php");

// Security
if (!isset($_SESSION['user']) || $_SESSION['role'] != "admin") {
    header("Location: ../../auth/login.php");
    exit();
}

// Helper: Recalculate invoice from payments (SOURCE OF TRUTH)
function recalcInvoice($conn, $invoice_id)
{

    // total paid
    $res = $conn->query("
        SELECT IFNULL(SUM(amount),0) AS total_paid
        FROM payments
        WHERE invoice_id = $invoice_id
    ");

    $total_paid = 0;
    if ($res && $res->num_rows > 0) {
        $total_paid = $res->fetch_assoc()['total_paid'];
    }

    // invoice amount
    $invRes = $conn->query("SELECT amount FROM invoices WHERE invoice_id = $invoice_id");
    if (!$invRes || $invRes->num_rows == 0) return;

    $invoice = $invRes->fetch_assoc();
    $amount = $invoice['amount'];

    // status
    if ($total_paid == 0) {
        $status = 'PENDING';
    } elseif ($total_paid < $amount) {
        $status = 'PARTIAL';
    } else {
        $status = 'PAID';
    }

    // update
    $stmt = $conn->prepare("
        UPDATE invoices 
        SET paid_amount=?, status=? 
        WHERE invoice_id=?
    ");
    $stmt->bind_param("dsi", $total_paid, $status, $invoice_id);
    $stmt->execute();
}


// ADD PAYMENT
if (isset($_POST['add_payment'])) {

    $invoice_id = intval($_POST['invoice_id']);
    $amount = floatval($_POST['amount']);

    $conn->begin_transaction();

    try {

        // lock invoice
        $invRes = $conn->query("SELECT * FROM invoices WHERE invoice_id=$invoice_id FOR UPDATE");

        if (!$invRes || $invRes->num_rows == 0) {
            throw new Exception("Invoice not found!");
        }

        $invoice = $invRes->fetch_assoc();

        // get current total paid
        $res = $conn->query("
            SELECT IFNULL(SUM(amount),0) AS total_paid 
            FROM payments 
            WHERE invoice_id=$invoice_id
        ");
        $total_paid = $res->fetch_assoc()['total_paid'];

        // prevent overpayment
        if (($total_paid + $amount) > $invoice['amount']) {
            throw new Exception("Payment exceeds remaining balance!");
        }

        // insert payment
        $stmt = $conn->prepare("
            INSERT INTO payments (student_id, amount, payment_date, payment_status, invoice_id)
            VALUES (?, ?, CURDATE(), 'CONFIRMED', ?)
        ");
        $stmt->bind_param("idi", $invoice['student_id'], $amount, $invoice_id);
        $stmt->execute();

        // recalc invoice
        recalcInvoice($conn, $invoice_id);

        $conn->commit();
        $success = "Payment recorded successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}


// DELETE PAYMENT (NULL SAFE)
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $conn->begin_transaction();

    try {

        $result = $conn->query("SELECT invoice_id FROM payments WHERE payment_id=$id");

        if (!$result || $result->num_rows == 0) {
            throw new Exception("Payment not found!");
        }

        $row = $result->fetch_assoc();
        $invoice_id = $row['invoice_id'];

        // delete
        $conn->query("DELETE FROM payments WHERE payment_id=$id");

        // recalc
        recalcInvoice($conn, $invoice_id);

        $conn->commit();
        $success = "Payment deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}


// FETCH PAYMENTS (CORRECT BALANCE)
$payments = $conn->query("
SELECT 
    p.*, 
    i.invoice_no,
    i.amount AS invoice_amount,
    IFNULL(SUM(p2.amount),0) AS total_paid,
    s.name AS student_name
FROM payments p
LEFT JOIN invoices i ON p.invoice_id = i.invoice_id
LEFT JOIN students s ON p.student_id = s.student_id
LEFT JOIN payments p2 ON p2.invoice_id = i.invoice_id
GROUP BY p.payment_id
ORDER BY p.payment_id DESC
");
?>

<div class="col-md-9 col-lg-10">
    <div class="container-fluid mt-4">

        <h3><i class="fas fa-credit-card"></i> Manage Payments</h3>
        <hr>

        <!-- Alerts -->
        <?php if (isset($success)) { ?>
            <div class="alert alert-success auto-dismiss"><?php echo $success; ?></div>
        <?php } ?>

        <?php if (isset($error)) { ?>
            <div class="alert alert-danger auto-dismiss"><?php echo $error; ?></div>
        <?php } ?>

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
            + Add Payment
        </button>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-info text-white">Payments List</div>

            <div class="card-body">
                <table class="table table-striped table-hover">

                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Invoice</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $i = 1;
                        if ($payments): while ($row = $payments->fetch_assoc()) {

                                $balance = $row['invoice_amount'] - $row['total_paid'];
                        ?>
                                <tr>

                                    <td><?php echo $i++; ?></td>

                                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>

                                    <td><?php echo htmlspecialchars($row['invoice_no']); ?></td>

                                    <td class="text-success">
                                        <?php echo number_format($row['amount'], 2); ?>
                                    </td>

                                    <td class="<?php echo ($balance == 0) ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo number_format($balance, 2); ?>
                                    </td>

                                    <td><?php echo $row['payment_date']; ?></td>

                                    <td>
                                        <a href="?delete=<?php echo $row['payment_id']; ?>"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Delete this payment?');">
                                            Delete
                                        </a>
                                    </td>

                                </tr>
                        <?php }
                        endif; ?>
                    </tbody>

                </table>
            </div>
        </div>

    </div>
</div>


<!-- MODAL -->
<div class="modal fade" id="addPaymentModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <form method="POST">

                <div class="modal-header bg-primary text-white">
                    <h5>Add Payment</h5>
                </div>

                <div class="modal-body">

                    <input type="number" name="invoice_id" class="form-control mb-2" placeholder="Invoice ID" required>

                    <input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="add_payment" class="btn btn-success">Save</button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- AUTO ALERT -->
<script>
    setTimeout(() => {
        document.querySelectorAll(".auto-dismiss").forEach(el => {
            el.style.opacity = "0";
            setTimeout(() => el.remove(), 500);
        });
    }, 3000);
</script>

<?php include("../../includes/footer.php"); ?>