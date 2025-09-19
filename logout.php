<?php
session_start();

// Hapus semua session
$_SESSION = [];
session_destroy();

// Redirect ke halaman login (atau index jika lebih cocok)
header("Location: login.php");
exit;
