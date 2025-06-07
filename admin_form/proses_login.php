<?php
session_start();
include "koneksi.php";

$username = $_POST['username'];
$password = $_POST['password'];

$query = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
$result = mysqli_query($koneksi, $query);

if (!$result) {
    die("Query error: " . mysqli_error($koneksi));
}

$data = mysqli_fetch_array($result);

if ($data) {
    $_SESSION['username'] = $username;
    header("Location: admin.php");
    exit();
} else {
    header("Location: login.php?error=Username atau password salah");
    exit();
}
