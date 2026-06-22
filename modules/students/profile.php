<?php
session_start();
include("../../config/database.php");
include("../../config/app.php"); // for BASE_URL
include("../../includes/header.php");
include("../../includes/sidebar.php");

// Hakikisha ni mwanafunzi aliye login
if (!isset($_SESSION['user']) || $_SESSION['role'] != "student") {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch student data safely
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Handle profile update
if (isset($_POST['update_profile'])) {

    $email = trim($_POST['email']);
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
    $password_input = $_POST['password'];

    $errors = [];

    // Email validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Phone validation
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match('/^[0-9]{9,12}$/', $phone)) {
        $errors[] = "Phone must be 9–12 digits";
    }

    // Password validation
    if (!empty($password_input) && strlen($password_input) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }

    // Check duplicate email
    $stmt = $conn->prepare("SELECT student_id FROM students WHERE email=? AND student_id != ?");
    $stmt->bind_param("si", $email, $student_id);
    $stmt->execute();
    $check = $stmt->get_result();

    if ($check->num_rows > 0) {
        $errors[] = "Email already exists";
    }

    // If no errors
    if (empty($errors)) {

        $password = !empty($password_input)
            ? password_hash($password_input, PASSWORD_DEFAULT)
            : $student['password'];

        $stmt = $conn->prepare("UPDATE students SET email=?, phone=?, password=? WHERE student_id=?");
        $stmt->bind_param("sssi", $email, $phone, $password, $student_id);

        if ($stmt->execute()) {
            $success = "Profile updated successfully!";

            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM students WHERE student_id=?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();

        } else {
            $error = "Database error occurred";
        }

    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<div class="col-md-9 col-lg-10">
    <div class="container-fluid mt-4" >
        <h3><i class="fas fa-user"></i> My Profile</h3>
        <p class="text-muted">View and update your personal information</p>
        <hr>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-edit"></i> Update Profile
            </div>
            <div class="card-body">

                <?php if(isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" onsubmit="return validateProfileForm()">

                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" class="form-control" value="<?php echo $student['name']; ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label>Registration Number</label>
                        <input type="text" class="form-control" value="<?php echo $student['reg_number']; ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?php echo htmlspecialchars($student['phone']); ?>"
                               pattern="[0-9]{10}" required maxlength="10" minlength="10">
                        <small class="text-muted">10 digits only</small>
                    </div>

                    <div class="mb-3">
                        <label>New Password</label>
                        <input type="password" name="password" class="form-control" minlength="6">
                        <small class="text-muted">Leave blank to keep current password</small>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Changes
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
function validateProfileForm() {
    let phone = document.querySelector("input[name='phone']").value;

    if (!/^[0-9]{10}$/.test(phone)) {
        alert("Phone must be 10 digits only");
        return false;
    }
    return true;
}
</script>

<?php include("../../includes/footer.php"); ?>