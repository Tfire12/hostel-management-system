<?php
// session_start();
include("../../config/database.php");
include("../../includes/header.php");
include("../../includes/admin_sidebar.php");

// Security check
if (!isset($_SESSION['user']) || $_SESSION['role'] != "admin") {
    header("Location: ../../auth/login.php");
    exit();
}

// Handle add room
if (isset($_POST['add_room'])) {
    $room_number = trim($_POST['room_number']);
    $capacity = intval($_POST['capacity']);

    // Check duplicate room number
    $check = $conn->query("SELECT * FROM rooms WHERE room_number='$room_number'");
    if ($check->num_rows > 0) {
        $error = "Room number already exists!";
    } else {
        $sql = "INSERT INTO rooms (room_number, capacity) VALUES ('$room_number', '$capacity')";
        if ($conn->query($sql) === TRUE) {
            $success = "Room added successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Handle update room
if (isset($_POST['update_room'])) {
    $id = intval($_POST['room_id']);
    $room_number = trim($_POST['room_number']);
    $capacity = intval($_POST['capacity']);

    // Check duplicate room number (excluding current room)
    $check = $conn->query("SELECT * FROM rooms WHERE room_number='$room_number' AND room_id!=$id");
    if ($check->num_rows > 0) {
        $error = "Room number already exists!";
    } else {
        $sql = "UPDATE rooms SET room_number='$room_number', capacity='$capacity' WHERE room_id=$id";
        if ($conn->query($sql) === TRUE) {
            $success = "Room updated successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM rooms WHERE room_id=$id");
    header("Location: manage_rooms.php");
    exit();
}

// Fetch all rooms
$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_id DESC");
?>

<div class="col-md-9 col-lg-10">
    <div class="container-fluid mt-4">
        <h3><i class="fas fa-door-open"></i> Manage Rooms</h3>
        <p class="text-muted">View, add, edit, and delete rooms</p>
        <hr>

        <!-- Alerts -->
        <?php if (isset($success)) { ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" id="alertBox">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>
        <?php if (isset($error)) { ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" id="alertBox">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php } ?>

        <!-- Add Room Button -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addRoomModal">
            <i class="fas fa-plus"></i> Add Room
        </button>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-info text-white">
                <i class="fas fa-list"></i> Rooms List
            </div>
            <div class="card-body">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Room Number</th>
                            <th>Capacity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1;
                        while ($row = $rooms->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['capacity']); ?></td>
                                <td>
                                    <!-- Edit Button triggers modal -->
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editRoomModal<?php echo $row['room_id']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="manage_rooms.php?delete=<?php echo $row['room_id']; ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this room?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>

                            <!-- Edit Room Modal -->
                            <div class="modal fade" id="editRoomModal<?php echo $row['room_id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header bg-warning">
                                                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Room</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="room_id" value="<?php echo $row['room_id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Room Number</label>
                                                    <input type="text" name="room_number" class="form-control" value="<?php echo htmlspecialchars($row['room_number']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Capacity</label>
                                                    <input type="number" name="capacity" class="form-control" value="<?php echo htmlspecialchars($row['capacity']); ?>" min="1" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" name="update_room" class="btn btn-success"><i class="fas fa-save"></i> Save</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addRoomModalLabel"><i class="fas fa-door-open"></i> Add New Room</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Room Number</label>
                        <input type="text" name="room_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" name="capacity" class="form-control" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_room" class="btn btn-success"><i class="fas fa-save"></i> Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php include("../../includes/footer.php"); ?>