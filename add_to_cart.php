<?php
session_start();
header('Content-Type: application/json');
require_once "includes/koneksi.php";

if (!isset($_SESSION['nis'])) {
    echo json_encode(["success" => false, "login_required" => true]);
    exit;
}

if (!isset($_POST['menu_id'])) {
    echo json_encode(["success" => false, "message" => "Menu tidak ditemukan."]);
    exit;
}

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Koneksi database gagal."]);
    exit;
}

$nis = $_SESSION['nis'];
$menu_id = intval($_POST['menu_id']);

// Pastikan cart ada untuk user ini
$sql_cart = "SELECT id FROM cart WHERE nis = ?";
$stmt_cart = $conn->prepare($sql_cart);
$stmt_cart->bind_param("s", $nis);
$stmt_cart->execute();
$result_cart = $stmt_cart->get_result();

if ($result_cart->num_rows > 0) {
    $cart = $result_cart->fetch_assoc();
    $cart_id = $cart['id'];
} else {
    // buat cart baru
    $sql_new_cart = "INSERT INTO cart (nis) VALUES (?)";
    $stmt_new_cart = $conn->prepare($sql_new_cart);
    $stmt_new_cart->bind_param("s", $nis);
    $stmt_new_cart->execute();
    $cart_id = $stmt_new_cart->insert_id;
}

// cek apakah menu sudah ada di cart
$sql_check = "SELECT id, qty FROM cart_items WHERE cart_id = ? AND menu_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $cart_id, $menu_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // jika ada, update qty
    $row = $result_check->fetch_assoc();
    $new_qty = $row['qty'] + 1;
    $sql_update = "UPDATE cart_items SET qty = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ii", $new_qty, $row['id']);
    $stmt_update->execute();
} else {
    // ambil harga menu saat ini
    $sql_menu = "SELECT harga FROM menu WHERE id = ?";
    $stmt_menu = $conn->prepare($sql_menu);
    $stmt_menu->bind_param("i", $menu_id);
    $stmt_menu->execute();
    $result_menu = $stmt_menu->get_result();
    $menu = $result_menu->fetch_assoc();

    // insert ke cart_items
    $sql_insert = "INSERT INTO cart_items (cart_id, menu_id, qty, price_at_added) VALUES (?, ?, 1, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iid", $cart_id, $menu_id, $menu['harga']);
    $stmt_insert->execute();
}

echo json_encode(["success" => true, "message" => "Berhasil menambahkan ke keranjang."]);
$conn->close();
