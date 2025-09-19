<?php
session_start();
header('Content-Type: application/json');
require_once "includes/koneksi.php";

if (!isset($_SESSION['nis'])) {
    echo json_encode(["success" => false, "message" => "Silakan login"]);
    exit;
}

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Koneksi gagal"]);
    exit;
}

$item_id = intval($_POST['item_id']);
$action = $_POST['action'];

$sql = "SELECT ci.id, ci.qty, ci.price_at_added, c.nis 
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.id
        WHERE ci.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Item tidak ditemukan"]);
    exit;
}

$item = $result->fetch_assoc();
if ($item['nis'] !== $_SESSION['nis']) {
    echo json_encode(["success" => false, "message" => "Tidak boleh mengubah item milik user lain"]);
    exit;
}

$new_qty = $item['qty'];
if ($action === "plus") {
    $new_qty++;
} elseif ($action === "minus" && $new_qty > 1) {
    $new_qty--;
}

// Update qty
$sql = "UPDATE cart_items SET qty = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $new_qty, $item_id);
$stmt->execute();

// Hitung subtotal baru & total keranjang
$new_subtotal = number_format($new_qty * $item['price_at_added'], 0, ',', '.');

$sql = "SELECT SUM(ci.qty * ci.price_at_added) AS total
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.id
        WHERE c.nis = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['nis']);
$stmt->execute();
$total_result = $stmt->get_result();
$total_row = $total_result->fetch_assoc();
$new_total = number_format($total_row['total'], 0, ',', '.');

echo json_encode([
    "success" => true,
    "new_qty" => $new_qty,
    "new_subtotal" => $new_subtotal,
    "new_total" => $new_total
]);
