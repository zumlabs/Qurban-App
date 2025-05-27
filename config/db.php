<?php
$servername = "localhost"; // ganti dengan server MySQL Anda
$username = "root";        // ganti dengan username MySQL Anda
$password = "";            // ganti dengan password MySQL Anda jika ada
$dbname = "qurban"; // ganti dengan nama database Anda

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
