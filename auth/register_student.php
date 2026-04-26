<?php include("../config/database.php"); ?>
<?php include("../includes/header.php"); ?>

<div class="container">
    <div class="card">
        <div class="card-header bg-primary text-white">
            Student Registration
        </div>
        <div class="card-body">
            <form name="studentForm" method="POST" onsubmit="return validateForm()" action="">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Registration Number</label>
                    <input type="text" name="reg_number" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control">
                </div>
                <button type="submit" name="register" class="btn btn-success">Register</button><br>
                Already have an account?<a href="login.php" class="btn btn-link"> Login</a>
            </form>
        </div>
    </div>
</div>

<?php
if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $reg_number = $_POST['reg_number'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO Students (name, reg_number, email, phone, password)
            VALUES ('$name', '$reg_number', '$email', '$phone', '$password')";

    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert alert-success mt-3'>Student registered successfully!</div>";
    } else {
        echo "<div class='alert alert-danger mt-3'>Error: " . $conn->error . "</div>";
    }
}
?>


<script>
function validateForm() {
    var name = document.forms["studentForm"]["name"].value;
    var reg_number = document.forms["studentForm"]["reg_number"].value;
    var email = document.forms["studentForm"]["email"].value;
    var phone = document.forms["studentForm"]["phone"].value;
    var password = document.forms["studentForm"]["password"].value;

    if (name === "" || reg_number === "" || email === "" || phone === "" || password === "") {
        alert("All fields must be filled out");
        return false;
    }
    return true;
}

</script>


<?php include("../includes/footer.php"); ?>
