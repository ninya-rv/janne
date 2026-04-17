<?php
session_start();
include "db.php";

/* Only admin can add users */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

$name = mysqli_real_escape_string($conn, $_POST['name']);
$email = mysqli_real_escape_string($conn, $_POST['email']);
$password = mysqli_real_escape_string($conn, $_POST['password']);
$role = mysqli_real_escape_string($conn, $_POST['role']);

/* Prevent creating another admin here */
if ($role === 'admin') {
    die("Admin account cannot be created here.");
}

/* Check duplicate email */
$check = "SELECT * FROM users WHERE email='$email'";
$result = mysqli_query($conn, $check);

if (mysqli_num_rows($result) > 0) {
    die("Email already exists.");
}

/* Admin-created accounts can be active immediately */
$sql = "INSERT INTO users(name, email, password, role, status)
        VALUES('$name', '$email', '$password', '$role', 'active')";

if (mysqli_query($conn, $sql)) {
    header("Location: ../../frontend/admin/users.php?success=1");
    exit;
} else {
    die("Failed to add user.");
}
?>