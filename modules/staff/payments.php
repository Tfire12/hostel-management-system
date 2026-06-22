<?php
// modules/staff/payments.php
session_start();
include("../../config/database.php");
include_once("../../config/app.php");

// Security: only staff allowed
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'staff') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

// Helper: redirect back to invoices page (or referrer if available)
function redirect_back($path = 'modules/staff/invoices.php') {
    $ref = $_SERVER['HTTP_REFERER'] ?? null;
    if ($ref && strpos($ref, $_SERVER['HTTP_HOST']) !== false) {
        header("Location: $ref");
    } else {
        header("Location: " . BASE_URL . $path);
    }
    exit();
}

// Normalize POST values safely
$action = $_POST['action'] ?? null;

// ---------- PAY FROM INVOICE MODAL (preferred flow) ----------
if (isset($_POST['pay_invoice'])) {
    $invoice_id = intval($_POST['invoice_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);

    if ($invoice_id <= 0 || $amount <= 0) {
        $_SESSION['error'] = "Invalid invoice or amount.";
        redirect_back();
    }

    // Start transaction
    $conn->begin_transaction();
    try {
        // Lock invoice row for update to avoid race conditions
        $stmt = $conn->prepare("SELECT invoice_id, student_id, amount, paid_amount FROM invoices WHERE invoice_id = ? FOR UPDATE");
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $inv = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$inv) {
            throw new Exception("Invoice not found.");
        }

        $remaining = floatval($inv['amount']) - floatval($inv['paid_amount']);
        if ($amount > $remaining) {
            throw new Exception("Payment exceeds remaining balance ({$remaining}).");
        }

        // Insert payment
        $payment_status = 'CONFIRMED';
        $ins = $conn->prepare("INSERT INTO payments (student_id, amount, payment_date, payment_status, invoice_id) VALUES (?, ?, CURDATE(), ?, ?)");
        $ins->bind_param("idsi", $inv['student_id'], $amount, $payment_status, $invoice_id);
        if (!$ins->execute()) {
            $ins->close();
            throw new Exception("Failed to insert payment: " . $conn->error);
        }
        $ins->close();

        // Recalculate paid_amount (sum of payments) to be safe
        $sumStmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total_paid FROM payments WHERE invoice_id = ?");
        $sumStmt->bind_param("i", $invoice_id);
        $sumStmt->execute();
        $sumRes = $sumStmt->get_result()->fetch_assoc();
        $sumStmt->close();

        $new_paid = floatval($sumRes['total_paid']);
        $new_status = ($new_paid >= floatval($inv['amount'])) ? 'PAID' : (($new_paid > 0) ? 'PARTIAL' : 'PENDING');

        // Update invoice
        $upd = $conn->prepare("UPDATE invoices SET paid_amount = ?, status = ? WHERE invoice_id = ?");
        $upd->bind_param("dsi", $new_paid, $new_status, $invoice_id);
        if (!$upd->execute()) {
            $upd->close();
            throw new Exception("Failed to update invoice: " . $conn->error);
        }
        $upd->close();

        $conn->commit();
        $_SESSION['success'] = "Payment recorded successfully. Invoice status: $new_status.";
        redirect_back();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Payment failed: " . $e->getMessage();
        redirect_back();
    }
}

// ---------- ADD PAYMENT (manual add from payments page) ----------
if (isset($_POST['add_payment'])) {
    $invoice_id = intval($_POST['invoice_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_status = trim($_POST['payment_status'] ?? 'CONFIRMED');

    if ($invoice_id <= 0 || $amount <= 0) {
        $_SESSION['error'] = "Invalid invoice or amount.";
        redirect_back('modules/staff/payments.php');
    }

    $conn->begin_transaction();
    try {
        // fetch invoice
        $stmt = $conn->prepare("SELECT invoice_id, student_id, amount FROM invoices WHERE invoice_id = ? FOR UPDATE");
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $inv = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$inv) throw new Exception("Invoice not found.");

        // compute current total paid
        $sumStmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total_paid FROM payments WHERE invoice_id = ?");
        $sumStmt->bind_param("i", $invoice_id);
        $sumStmt->execute();
        $sumRes = $sumStmt->get_result()->fetch_assoc();
        $sumStmt->close();

        $current_paid = floatval($sumRes['total_paid']);
        $remaining = floatval($inv['amount']) - $current_paid;
        if ($amount > $remaining) throw new Exception("Amount exceeds remaining balance ($remaining).");

        // insert payment
        $ins = $conn->prepare("INSERT INTO payments (student_id, amount, payment_date, payment_status, invoice_id) VALUES (?, ?, CURDATE(), ?, ?)");
        $ins->bind_param("idsi", $inv['student_id'], $amount, $payment_status, $invoice_id);
        if (!$ins->execute()) {
            $ins->close();
            throw new Exception("Failed to insert payment: " . $conn->error);
        }
        $ins->close();

        // update invoice paid_amount and status
        $new_paid = $current_paid + $amount;
        $new_status = ($new_paid >= floatval($inv['amount'])) ? 'PAID' : 'PARTIAL';
        $upd = $conn->prepare("UPDATE invoices SET paid_amount = ?, status = ? WHERE invoice_id = ?");
        $upd->bind_param("dsi", $new_paid, $new_status, $invoice_id);
        if (!$upd->execute()) {
            $upd->close();
            throw new Exception("Failed to update invoice: " . $conn->error);
        }
        $upd->close();

        $conn->commit();
        $_SESSION['success'] = "Payment added successfully. Invoice status: $new_status.";
        header("Location: " . BASE_URL . "modules/staff/payments.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Add payment failed: " . $e->getMessage();
        header("Location: " . BASE_URL . "modules/staff/payments.php");
        exit();
    }
}

// ---------- UPDATE PAYMENT ----------
if (isset($_POST['update_payment'])) {
    $payment_id = intval($_POST['payment_id'] ?? 0);
    $new_amount = floatval($_POST['amount'] ?? 0);
    $new_status = trim($_POST['payment_status'] ?? 'CONFIRMED');

    if ($payment_id <= 0 || $new_amount <= 0) {
        $_SESSION['error'] = "Invalid payment or amount.";
        redirect_back('modules/staff/payments.php');
    }

    $conn->begin_transaction();
    try {
        // fetch payment and invoice
        $stmt = $conn->prepare("SELECT p.payment_id, p.amount AS old_amount, p.invoice_id, i.amount AS invoice_amount FROM payments p JOIN invoices i ON p.invoice_id = i.invoice_id WHERE p.payment_id = ? FOR UPDATE");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) throw new Exception("Payment not found.");

        $invoice_id = intval($row['invoice_id']);
        $old_amount = floatval($row['old_amount']);
        $invoice_amount = floatval($row['invoice_amount']);

        // compute total paid excluding this payment
        $sumStmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total_paid FROM payments WHERE invoice_id = ? AND payment_id != ?");
        $sumStmt->bind_param("ii", $invoice_id, $payment_id);
        $sumStmt->execute();
        $sumRes = $sumStmt->get_result()->fetch_assoc();
        $sumStmt->close();

        $paid_excl = floatval($sumRes['total_paid']);
        $new_total_paid = $paid_excl + $new_amount;

        if ($new_total_paid > $invoice_amount) throw new Exception("New amount causes invoice overpayment.");

        // update payment
        $u = $conn->prepare("UPDATE payments SET amount = ?, payment_status = ? WHERE payment_id = ?");
        $u->bind_param("dsi", $new_amount, $new_status, $payment_id);
        if (!$u->execute()) {
            $u->close();
            throw new Exception("Failed to update payment: " . $conn->error);
        }
        $u->close();

        // update invoice
        $invoice_status = ($new_total_paid >= $invoice_amount) ? 'PAID' : (($new_total_paid > 0) ? 'PARTIAL' : 'PENDING');
        $v = $conn->prepare("UPDATE invoices SET paid_amount = ?, status = ? WHERE invoice_id = ?");
        $v->bind_param("dsi", $new_total_paid, $invoice_status, $invoice_id);
        if (!$v->execute()) {
            $v->close();
            throw new Exception("Failed to update invoice: " . $conn->error);
        }
        $v->close();

        $conn->commit();
        $_SESSION['success'] = "Payment updated. Invoice status: $invoice_status.";
        header("Location: " . BASE_URL . "modules/staff/payments.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Update failed: " . $e->getMessage();
        header("Location: " . BASE_URL . "modules/staff/payments.php");
        exit();
    }
}

// ---------- DELETE PAYMENT ----------
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($del_id <= 0) {
        $_SESSION['error'] = "Invalid payment id.";
        redirect_back('modules/staff/payments.php');
    }

    $conn->begin_transaction();
    try {
        // fetch payment
        $stmt = $conn->prepare("SELECT payment_id, amount, invoice_id FROM payments WHERE payment_id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $p = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$p) throw new Exception("Payment not found.");

        $invoice_id = intval($p['invoice_id']);

        // delete payment
        $d = $conn->prepare("DELETE FROM payments WHERE payment_id = ?");
        $d->bind_param("i", $del_id);
        if (!$d->execute()) {
            $d->close();
            throw new Exception("Failed to delete payment: " . $conn->error);
        }
        $d->close();

        // recalc invoice paid_amount
        $sumStmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS total_paid FROM payments WHERE invoice_id = ?");
        $sumStmt->bind_param("i", $invoice_id);
        $sumStmt->execute();
        $sumRes = $sumStmt->get_result()->fetch_assoc();
        $sumStmt->close();

        $new_paid = floatval($sumRes['total_paid']);

        // fetch invoice amount to determine status
        $invStmt = $conn->prepare("SELECT amount FROM invoices WHERE invoice_id = ?");
        $invStmt->bind_param("i", $invoice_id);
        $invStmt->execute();
        $invRow = $invStmt->get_result()->fetch_assoc();
        $invStmt->close();

        $invoice_amount = floatval($invRow['amount'] ?? 0);
        $new_status = ($new_paid >= $invoice_amount && $invoice_amount > 0) ? 'PAID' : (($new_paid > 0) ? 'PARTIAL' : 'PENDING');

        $u = $conn->prepare("UPDATE invoices SET paid_amount = ?, status = ? WHERE invoice_id = ?");
        $u->bind_param("dsi", $new_paid, $new_status, $invoice_id);
        if (!$u->execute()) {
            $u->close();
            throw new Exception("Failed to update invoice after delete: " . $conn->error);
        }
        $u->close();

        $conn->commit();
        $_SESSION['success'] = "Payment deleted. Invoice status: $new_status.";
        redirect_back('modules/staff/payments.php');
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Delete failed: " . $e->getMessage();
        redirect_back('modules/staff/payments.php');
    }
}

// If no action matched, redirect to payments page
header("Location: " . BASE_URL . "modules/staff/invoices.php");
exit();
