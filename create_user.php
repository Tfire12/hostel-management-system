<?php
include('config/database.php');

$password = password_hash("123456", PASSWORD_DEFAULT);

$query = "INSERT INTO users (full_name, email, password, role)
VALUES ('Admin User', 'admin@gmail.com', '$password', 'admin')";

if(mysqli_query($conn, $query)){
    echo "User created";
} else {
    echo "Error: " . mysqli_error($conn);
}