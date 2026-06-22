<?php
session_start();
include("../../config/database.php");

if (!isset($_SESSION['user']) || $_SESSION['role'] != "staff") {
    header("Location: ../../auth/login.php");
    exit();
}

$allocation_id = intval($_GET['id']);

$conn->query("
    UPDATE allocations 
    SET status='RELEASED', release_date=NOW()
    WHERE allocation_id=$allocation_id
");

$_SESSION['success'] = "Room released successfully";
header("Location: allocations.php");
exit();
