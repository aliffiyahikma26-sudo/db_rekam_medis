<?php
$host = "localhost";
$user = "aliffiya_hikma";
$pass = "smkn3tbn";
$database = "db_rekam_medis";

$koneksi = mysqli_connect($host, $user, $pass, $database);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, "utf8mb4");
?>
