<?php
session_start();

// Session timeout (30 minutes)
$timeout = 1800;

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
}

$_SESSION['LAST_ACTIVITY'] = time();