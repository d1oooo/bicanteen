<?php
session_start();
header('Content-Type: application/json');
require_once "includes/koneksi.php";

if (!isset($_SESSION['nis'])) {
    echo json_encode(["success" => false, "message" => "Anda harus login terlebih dahulu."]);
    exit;
}

if (!isset($_POST['item_id'])) {
    echo json_encode(["success" => false, "message" => "Item tidak ditemukan."]);
    exit;
}

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Koneksi database gagal."]);
    exit;
}

$nis = $_SESSION['nis'];
$item_id = intval($_POST['item_id']);

// Hapus item dari cart_items (pastikan hanya cart user ini)
$sql = "
    DELETE ci FROM cart_items ci
    JOIN cart c ON ci.cart_id = c.id
    WHERE ci.id = ? AND c.nis = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $item_id, $nis);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // Hitung ulang total setelah penghapusan
    $sql_total = "
        SELECT SUM(ci.qty * ci.price_at_added) AS total
        FROM cart c
        JOIN cart_items ci ON c.id = ci.cart_id
        WHERE c.nis = ?
    ";
    $stmt_total = $conn->prepare($sql_total);
    $stmt_total->bind_param("s", $nis);
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $row_total = $result_total->fetch_assoc();

    $new_total = $row_total['total'] ?? 0;

    echo json_encode([
        "success" => true,
        "new_total" => number_format($new_total, 0, ',', '.')
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Gagal menghapus item."]);
}

$stmt->close();
$conn->close();
