<?php
session_start();
require_once "includes/koneksi.php";
require_once "vendor/autoload.php"; // Midtrans PHP SDK

if (!isset($_SESSION['nis'])) {
    header("Location: login.php");
    exit;
}

$nis = $_SESSION['nis'];

// Ambil data cart
$sql = "
    SELECT ci.menu_id, m.nama, ci.qty, ci.price_at_added 
    FROM cart c
    JOIN cart_items ci ON c.id = ci.cart_id
    JOIN menu m ON ci.menu_id = m.id
    WHERE c.nis = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nis);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total += $row['qty'] * $row['price_at_added'];
}

// Buat order di DB
$order_code = "ORD-" . time();
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO orders (order_code, nis, penjual_id, total, payment_status, order_status) VALUES (?, ?, ?, ?, 'PENDING', 'MENUNGGU_PEMBAYARAN')");
    $penjual_id = 1; // contoh default penjual, bisa diatur sesuai kebutuhan
    $stmt->bind_param("sssd", $order_code, $nis, $penjual_id, $total);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // Simpan item
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, menu_id, nama_menu, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $it) {
        $stmt_item->bind_param("iisid", $order_id, $it['menu_id'], $it['nama'], $it['qty'], $it['price_at_added']);
        $stmt_item->execute();
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    die("Gagal membuat order: " . $e->getMessage());
}

// Midtrans config
\Midtrans\Config::$serverKey = "Mid-server-SAYi1hM4Ku8MKt78hN4cFxaj";
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// Buat transaksi ke Midtrans
$params = [
    'transaction_details' => [
        'order_id' => $order_code,
        'gross_amount' => $total,
    ],
    'customer_details' => [
        'first_name' => $nis,
        'email' => $nis . "@bicanteen.local", // contoh aja
    ],
];

$snapToken = \Midtrans\Snap::getSnapToken($params);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Checkout BiCanteen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #f7f7fa;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
        }
        .container {
            max-width: 480px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(76,81,191,0.08);
            padding: 32px 24px;
        }
        .section-title {
            color: #4c51bf;
            font-size: 1.5rem;
            margin-bottom: 24px;
            font-weight: bold;
        }
        .order-summary {
            margin-bottom: 24px;
        }
        .order-summary ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .order-summary li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 1rem;
        }
        .order-summary li:last-child {
            border-bottom: none;
        }
        .total-label {
            color: #4c51bf;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .pay-btn {
            width: 100%;
            padding: 14px 0;
            background: linear-gradient(135deg,#4c51bf 0%,#553c9a 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 18px;
            transition: background 0.3s;
        }
        .pay-btn:hover {
            background: linear-gradient(135deg,#553c9a 0%,#4c51bf 100%);
        }
        @media (max-width: 600px) {
            .container {
                max-width: 100vw;
                margin: 0;
                border-radius: 0;
                box-shadow: none;
                padding: 18px 8px;
            }
            .section-title {
                font-size: 1.2rem;
            }
            .pay-btn {
                font-size: 1rem;
                padding: 12px 0;
            }
        }
    </style>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="Mid-client-PTSXG8_Dl0X607u3"></script>
</head>
<body>
    <div class="container">
        <h2 class="section-title"><i class="fas fa-credit-card"></i> Checkout</h2>
        <div class="order-summary">
            <ul>
                <?php foreach ($items as $item): ?>
                    <li>
                        <span><?= htmlspecialchars($item['nama']) ?> x <?= $item['qty'] ?></span>
                        <span>Rp <?= number_format($item['qty'] * $item['price_at_added'], 0, ',', '.') ?></span>
                    </li>
                <?php endforeach; ?>
                <li>
                    <span class="total-label">Total</span>
                    <span class="total-label">Rp <?= number_format($total, 0, ',', '.') ?></span>
                </li>
            </ul>
        </div>
        <button id="pay-button" class="pay-btn"><i class="fas fa-money-bill-wave"></i> Bayar Sekarang</button>
    </div>
    <script>
    document.getElementById('pay-button').onclick = function(){
        snap.pay('<?= $snapToken ?>', {
            onSuccess: function(result){
                window.location.href = "thanks.php?status=success&order_id=<?= $order_id ?>";
            },
            onPending: function(result){
                window.location.href = "thanks.php?status=pending&order_id=<?= $order_id ?>";
            },
            onError: function(result){
                alert("Pembayaran gagal!");
            }
        });
    };
    </script>
</body>
</html>