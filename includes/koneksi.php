<?php
    $host = "canteen-db-noobfanny53-2ee5.j.aivencloud.com";
    $port = 28611;
    $user = "avnadmin";
    $pass = "AVNS_-p-TtR7tTo6O92PsXU4"; // ganti dengan password dari Aiven
    $dbname = "defaultdb";

// Koneksi
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
?>