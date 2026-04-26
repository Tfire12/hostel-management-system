<?php
session_start();
include("../../config/database.php");

// Auth check
if (!isset($_SESSION['user']) || $_SESSION['role'] != "student") {
    header("Location: ../../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// SETTINGS
$academic_year = "2025/2026";
$amount = 500000;
$expiry_days = 30;

// 1. CHECK EXISTING ACTIVE INVOICE
$check = $conn->query("
    SELECT * FROM invoices 
    WHERE student_id=$student_id 
    AND academic_year='$academic_year'
    AND status IN ('PENDING','PARTIAL')
");

if ($check->num_rows > 0) {
    $_SESSION['error'] = "You already have an active invoice for this academic year.";
    header("Location: my_payments.php");
    exit();
}

// 2. GENERATE INVOICE NUMBER
$invoice_no = "INV-" . date("Ymd") . "-" . rand(1000,9999);

// 3. CREATE INVOICE (INITIAL)
$expires_at = date('Y-m-d H:i:s', strtotime("+$expiry_days days"));

$conn->query("
    INSERT INTO invoices (student_id, invoice_no, academic_year, amount, status, expires_at)
    VALUES ($student_id, '$invoice_no', '$academic_year', $amount, 'PENDING', '$expires_at')
");

$invoice_id = $conn->insert_id;

// 4. PREPARE GePG REQUEST (SIMULATION - XML STYLE)

$reference = $invoice_no;
$description = "Hostel Fee $academic_year";

// Normally unge-build XML kama hii (GePG style)
$xml = "
<gepgBill>
    <billHdr>
        <spCode>SP001</spCode>
        <RtrRespFlg>true</RtrRespFlg>
    </billHdr>
    <billTrxInf>
        <billId>$reference</billId>
        <spSysId>HMS</spSysId>
        <billAmt>$amount</billAmt>
        <miscAmt>0</miscAmt>
        <billDesc>$description</billDesc>
        <billGenDt>" . date("Y-m-d") . "</billGenDt>
        <billExpDt>" . date("Y-m-d", strtotime("+$expiry_days days")) . "</billExpDt>
        <pyrId>$student_id</pyrId>
        <pyrName>Student</pyrName>
    </billTrxInf>
</gepgBill>
";

// 5. SIMULATE API CALL (kwa sasa)
function generateControlNumber($invoice_id) {
    return "HN" . date("Y") . rand(100000,999999) . $invoice_id;
}

$control_number = generateControlNumber($invoice_id);

// 6. UPDATE INVOICE WITH CONTROL NUMBER
$conn->query("
    UPDATE invoices 
    SET control_number='$control_number' 
    WHERE invoice_id=$invoice_id
");

// SUCCESS MESSAGE
$_SESSION['success'] = "Invoice generated successfully!";

// REDIRECT
header("Location: my_payments.php");
exit();
?>