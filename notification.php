<?php
require_once "includes/koneksi.php";
require_once "vendor/autoload.php";

\Midtrans\Config::$serverKey = "Mid-server-SAYi1hM4Ku8MKt78hN4cFxaj";
\Midtrans\Config::$isProduction = false;

$notif = new \Midtrans\Notification();

$order_code = $notif->order_id;
$transaction = $notif->transaction_status;
$type = $notif->payment_type;
$fraud = $notif->fraud_status;
$midtrans_id = $notif->transaction_id;

$status = "PENDING";
if ($transaction == 'capture' || $transaction == 'settlement') {
    $status = "LUNAS";
} elseif ($transaction == 'cancel' || $transaction == 'deny' || $transaction == 'expire') {
    $status = "GAGAL";
}

$stmt = $conn->prepare("UPDATE orders SET payment_status=?, order_status=? WHERE order_code=?");
$stmt->bind_param("sss", $status, $status, $order_code);
$stmt->execute();

// Simpan ke payments
$stmt2 = $conn->prepare("INSERT INTO payments (order_id, midtrans_transaction_id, midtrans_payment_type, amount, status, payment_data) VALUES (
    (SELECT id FROM orders WHERE order_code=?), ?, ?, ?, ?, ?
)");
$data_json = json_encode($notif);
$stmt2->bind_param("ssssss", $order_code, $midtrans_id, $type, $notif->gross_amount, $status, $data_json);
$stmt2->execute();
