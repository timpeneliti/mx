<?php
// delete.php

// Koneksi ke database
$db_host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "ping";

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ambil ID dari URL
$id = $_GET['id'];

// Hapus data dari database
$delete_query = "DELETE FROM domains WHERE id = $id";
$conn->query($delete_query);

// Redirect kembali ke halaman utama
header("Location: index.php");
exit();
?>
