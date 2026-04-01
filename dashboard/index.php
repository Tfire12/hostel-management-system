<?php
include('../config/session.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>

<h3>Welcome <?php echo $_SESSION['user_name']; ?></h3>
<p>Role: <?php echo $_SESSION['user_role']; ?></p>

<a href="../auth/logout.php">Logout</a>

</body>
</html>