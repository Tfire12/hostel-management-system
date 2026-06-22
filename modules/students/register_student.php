<?php
session_start();

include("../../config/database.php");
include("../../config/app.php");

$success = "";
$error = "";


// HANDLE REGISTRATION

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {

    $name = trim($_POST['name']);
    $reg_number = trim($_POST['reg_number']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    // Server-side Validation
    if (
        empty($name) ||
        empty($reg_number) ||
        empty($email) ||
        empty($phone) ||
        empty($password)
    ) {

        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Invalid email address.";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {

        $error = "Phone number must contain 10 digits.";
    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters.";
    } else {

        // Check duplicate email or reg number
        $check = $conn->prepare("
            SELECT student_id
            FROM students
            WHERE email = ? OR reg_number = ?
        ");

        $check->bind_param("ss", $email, $reg_number);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $error = "Email or Registration Number already exists.";
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO students
                (name, reg_number, email, phone, password)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "sssss",
                $name,
                $reg_number,
                $email,
                $phone,
                $hashed_password
            );

            if ($stmt->execute()) {

                $_SESSION['success'] =
                    "Registration successful. Please login.";

                header("Location: " . BASE_URL . "auth/login.php");
                exit();
            } else {

                $error = "Registration failed. Try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

include("../../includes/header.php");
?>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7 col-lg-6">

            <div class="card shadow border-0">

                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">
                        <i class="fas fa-user-plus"></i>
                        Student Registration
                    </h4>
                </div>

                <div class="card-body">

                    <?php if (!empty($error)) { ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php } ?>

                    <form
                        method="POST"
                        id="studentForm"
                        onsubmit="return validateForm()">

                        <div class="mb-3">
                            <label class="form-label">
                                Full Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Registration Number
                            </label>

                            <input
                                type="text"
                                name="reg_number"
                                id="reg_number"
                                class="form-control"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Email Address
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Phone Number
                            </label>

                            <input
                                type="text"
                                name="phone"
                                id="phone"
                                class="form-control"
                                placeholder="07XXXXXXXX"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control"
                                required>

                            <small class="text-muted">
                                Minimum 8 characters.
                            </small>
                        </div>

                        <button
                            type="submit"
                            name="register"
                            class="btn btn-success w-100">

                            <i class="fas fa-user-check"></i>
                            Register
                        </button>

                    </form>

                    <div class="text-center mt-3">

                        Already have an account?

                        <a href="<?php echo BASE_URL; ?>auth/login.php">
                            Login
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<script>
    function validateForm() {

        let name =
            document.getElementById("name").value.trim();

        let reg =
            document.getElementById("reg_number").value.trim();

        let email =
            document.getElementById("email").value.trim();

        let phone =
            document.getElementById("phone").value.trim();

        let password =
            document.getElementById("password").value;

        if (
            name === "" ||
            reg === "" ||
            email === "" ||
            phone === "" ||
            password === ""
        ) {

            alert("All fields are required.");
            return false;
        }

        let emailPattern =
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailPattern.test(email)) {

            alert("Invalid email address.");
            return false;
        }

        let phonePattern =
            /^[0-9]{10}$/;

        if (!phonePattern.test(phone)) {

            alert("Phone number must contain 10 digits.");
            return false;
        }

        if (password.length < 8) {

            alert("Password must be at least 8 characters.");
            return false;
        }

        return true;
    }
</script>

<?php include("../../includes/footer.php"); ?>