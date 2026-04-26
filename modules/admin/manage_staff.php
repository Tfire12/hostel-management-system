<?php
// session_start();
include("../../config/database.php");
include("../../includes/header.php");
include("../../includes/admin_sidebar.php");

// Security check: only admin can access
if (!isset($_SESSION['user']) || $_SESSION['role'] != "admin") {
  header("Location: ../../auth/login.php");
  exit();
}

// Handle add staff
if (isset($_POST['add_staff'])) {
  $name = trim($_POST['name']);
  $username = trim($_POST['username']);
  $role = $_POST['role'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  // Check duplicate username
  $check = $conn->query("SELECT * FROM staff WHERE username='$username'");
  if ($check->num_rows > 0) {
    $error = "Username already exists!";
  } else {
    $sql = "INSERT INTO staff (name, username, role, password) 
                VALUES ('$name', '$username', '$role', '$password')";
    if ($conn->query($sql) === TRUE) {
      $success = "Staff added successfully!";
    } else {
      $error = "Error: " . $conn->error;
    }
  }
}

// Handle update staff
if (isset($_POST['update_staff'])) {
  $id = intval($_POST['staff_id']);
  $name = trim($_POST['name']);
  $username = trim($_POST['username']);
  $role = $_POST['role'];

  // Check duplicate username (excluding current staff)
  $check = $conn->query("SELECT * FROM staff WHERE username='$username' AND staff_id!=$id");
  if ($check->num_rows > 0) {
    $error = "Username already exists!";
  } else {
    $sql = "UPDATE staff SET name='$name', username='$username', role='$role' WHERE staff_id=$id";
    if ($conn->query($sql) === TRUE) {
      $success = "Staff updated successfully!";
    } else {
      $error = "Error: " . $conn->error;
    }
  }
}

// Handle delete
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $conn->query("DELETE FROM staff WHERE staff_id=$id");
  header("Location: manage_staff.php");
  exit();
}

// Fetch all staff
$staff = $conn->query("SELECT * FROM staff ORDER BY staff_id DESC");
?>

<div class="col-md-9 col-lg-10">
  <div class="container-fluid mt-4">
    <h3><i class="fas fa-user-tie"></i> Manage Staff</h3>
    <p class="text-muted">View, add, edit, and delete staff accounts</p>
    <hr>

    <!-- Alerts -->
    <?php if (isset($success)) { ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert" id="alertBox">
        <?php echo $success; ?>
      </div>
    <?php } ?>
    <?php if (isset($error)) { ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert" id="alertBox">
        <?php echo $error; ?>
      </div>
    <?php } ?>

    <!-- Add Staff Button -->
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addStaffModal">
      <i class="fas fa-plus"></i> Add Staff
    </button>

    <div class="card shadow-sm border-0">
      <div class="card-header bg-success text-white">
        <i class="fas fa-list"></i> Staff List
      </div>
      <div class="card-body">
        <table class="table table-striped table-hover">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Username</th>
              <th>Role</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 1;
            while ($row = $staff->fetch_assoc()) { ?>
              <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><span class="badge bg-info"><?php echo htmlspecialchars($row['role']); ?></span></td>
                <td>
                  <!-- Edit Button triggers modal -->
                  <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editStaffModal<?php echo $row['staff_id']; ?>">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                  <a href="manage_staff.php?delete=<?php echo $row['staff_id']; ?>"
                    class="btn btn-sm btn-danger"
                    onclick="return confirm('Are you sure you want to delete this staff member?');">
                    <i class="fas fa-trash"></i> Delete
                  </a>
                </td>
              </tr>

              <!-- Edit Staff Modal -->
              <div class="modal fade" id="editStaffModal<?php echo $row['staff_id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form method="POST" action="">
                      <div class="modal-header bg-warning">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Staff</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <input type="hidden" name="staff_id" value="<?php echo $row['staff_id']; ?>">
                        <div class="mb-3">
                          <label class="form-label">Name</label>
                          <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Username</label>
                          <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($row['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Role</label>
                          <select name="role" class="form-select" required>
                            <option value="staff" <?php if ($row['role'] == "staff") echo "selected"; ?>>Staff</option>
                            <option value="admin" <?php if ($row['role'] == "admin") echo "selected"; ?>>Admin</option>
                          </select>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="submit" name="update_staff" class="btn btn-success"><i class="fas fa-save"></i> Save</button>
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

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addStaffModalLabel"><i class="fas fa-user-plus"></i> Add New Staff</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select" required>
              <option value="staff">Staff</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_staff" class="btn btn-primary"><i class="fas fa-plus"></i> Add Staff</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include("../../includes/footer.php"); ?>