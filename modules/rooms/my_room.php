<?php
session_start();
include("../../config/database.php");
include("../../includes/header.php");
include("../../includes/sidebar.php");

// Hakikisha ni mwanafunzi aliye login
if (!isset($_SESSION['user']) || $_SESSION['role'] != "student") {
    header("Location: ../../auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch room allocation
$allocation = $conn->query("SELECT Rooms.room_number, Rooms.room_type, Rooms.capacity 
                            FROM allocations 
                            JOIN Rooms ON allocations.room_id = Rooms.room_id 
                            WHERE allocations.student_id=$student_id")->fetch_assoc();
?>

<div class="col-md-9 col-lg-10">
    <div class="container-fluid mt-4">
        <h3><i class="fas fa-door-open"></i> My Room</h3>
        <p class="text-muted">View your room details and request changes</p>
        <hr>

        <!-- Room Details -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-info text-white">
                <i class="fas fa-bed"></i> Room Details
            </div>
            <div class="card-body">
                <?php if($allocation) { ?>
                    <p><strong>Room Number:</strong> <?php echo $allocation['room_number']; ?></p>
                    <p><strong>Room Type:</strong> <?php echo $allocation['room_type']; ?></p>
                    <p><strong>Capacity:</strong> <?php echo $allocation['capacity']; ?> students</p>
                <?php } else { ?>
                    <div class="alert alert-warning">You have not been allocated a room yet.</div>
                <?php } ?>
            </div>
        </div>

        <!-- Request Room Change -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-secondary text-white">
                <i class="fas fa-exchange-alt"></i> Request Room Change
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Reason for Change</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <button type="submit" name="request_change" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                </form>
            </div>
        </div>

        <?php
        // Handle room change request
        if (isset($_POST['request_change'])) {
            $reason = $_POST['reason'];
            $date = date("Y-m-d");

            $sql = "INSERT INTO complaints (student_id, issue, description, complaint_date) 
                    VALUES ('$student_id', 'Room Change Request', '$reason', '$date')";
            if ($conn->query($sql) === TRUE) {
                echo "<div class='alert alert-success mt-3'>Room change request submitted successfully!</div>";
            } else {
                echo "<div class='alert alert-danger mt-3'>Error: " . $conn->error . "</div>";
            }
        }
        ?>
    </div>
</div>

<?php include("../../includes/footer.php"); ?>
