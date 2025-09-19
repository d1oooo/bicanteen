<?php
session_start();
require_once "includes/koneksi.php";

// Koneksi DB
$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Cek login
if (!isset($_SESSION['nis'])) {
    header("Location: login.php");
    exit;
}
$nis = $_SESSION['nis'];

// Ambil ID order dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: history.php");
    exit;
}
$order_id = intval($_GET['id']);

// Ambil jumlah keranjang
$cartCount = 0;
$sql_cart = "
    SELECT SUM(ci.qty) AS total_qty
    FROM cart c
    JOIN cart_items ci ON c.id = ci.cart_id
    WHERE c.nis = ?
";
$stmt = $conn->prepare($sql_cart);
$stmt->bind_param("s", $nis);
$stmt->execute();
$result_cart = $stmt->get_result();
$row = $result_cart->fetch_assoc();
$cartCount = $row['total_qty'] ?? 0;

// Ambil detail order
$sql_order = "
    SELECT 
        o.id, 
        o.order_code,
        o.created_at,
        o.order_status,
        o.payment_status,
        o.payment_method,
        o.total
    FROM orders o
    WHERE o.id = ? AND o.nis = ?
    LIMIT 1
";
$stmt_order = $conn->prepare($sql_order);
$stmt_order->bind_param("is", $order_id, $nis);
$stmt_order->execute();
$result_order = $stmt_order->get_result();
$order = $result_order->fetch_assoc();

if (!$order) {
    echo "Pesanan tidak ditemukan.";
    exit;
}

// Ambil item dari order_items
$sql_items = "
    SELECT 
        oi.nama_menu, 
        oi.quantity, 
        oi.unit_price, 
        oi.subtotal
    FROM order_items oi
    WHERE oi.order_id = ?
";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
$items = $result_items->fetch_all(MYSQLI_ASSOC);

function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

// Mapping status pembayaran -> class CSS
function getStatusClass($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'paid':
            return 'status-paid';
        case 'pending':
            return 'status-pending';
        case 'canceled':
        case 'failed':
            return 'status-canceled';
        default:
            return '';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?php echo $order['id']; ?> - BiCanteen</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: #f4f6fb;
            color: #333;
        }
        .header {
            background: linear-gradient(135deg, #4f46e5, #3b82f6);
            padding: 15px 0;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header .container {
            width: 90%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo h1 {
            margin: 0;
            font-size: 22px;
        }
        .header-icons {
            display: flex;
            gap: 12px;
        }
        .icon-btn {
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            padding: 8px 10px;
            color: white;
            text-decoration: none;
            transition: 0.3s;
        }
        .icon-btn:hover {
            background: rgba(255,255,255,0.35);
        }
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 50%;
        }
        .main-content {
            width: 90%;
            margin: 20px auto;
        }
        .section-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #1e293b;
        }
        .detail-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            transition: transform 0.2s;
        }
        .detail-card:hover {
            transform: translateY(-2px);
        }
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .detail-header h3 {
            margin: 0;
        }
        .order-status {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            text-transform: capitalize;
        }
        .status-paid {
            background: #dcfce7;
            color: #16a34a;
        }
        .status-pending {
            background: #fef9c3;
            color: #ca8a04;
        }
        .status-canceled {
            background: #fee2e2;
            color: #dc2626;
        }
        .order-items {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .order-items th {
            background: #f1f5f9;
            text-align: left;
            padding: 12px;
            font-size: 14px;
            text-transform: uppercase;
            color: #475569;
        }
        .order-items td {
            padding: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 15px;
        }
        .order-items tr:hover {
            background: #f9fafb;
        }
        /* Tombol Back */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #4f46e5, #3b82f6);
            color: white;
            font-size: 15px;
            font-weight: 600;
            border-radius: 10px;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.25s ease;
        }
        .btn-back:hover {
            background: linear-gradient(135deg, #4338ca, #2563eb);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }
        .btn-back i {
            transition: transform 0.25s ease;
        }
        .btn-back:hover i {
            transform: translateX(-4px);
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="container">
        <div class="logo">
            <h1>BiCanteen.</h1>
        </div>
        <div class="header-icons">
            <a href="cart.php" class="icon-btn cart-icon" style="position:relative;">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="history.php" class="icon-btn"><i class="fas fa-calendar"></i></a>
            <a href="akun.php" class="icon-btn"><i class="fas fa-user-cog"></i></a>
            <a href="logout.php" class="icon-btn" style="background:#ef4444;">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</header>

<!-- Konten Utama -->
<main class="main-content">
    <h2 class="section-title">Detail Pesanan</h2>

    <div class="detail-card">
        <div class="detail-header">
            <h3>Pesanan #<?php echo $order['order_code']; ?></h3>
            <span class="order-status <?php echo getStatusClass($order['payment_status']); ?>">
                <?php echo htmlspecialchars($order['payment_status']); ?>
            </span>
        </div>
        <p><strong>Tanggal:</strong> <?php echo date("d M Y H:i", strtotime($order['created_at'])); ?></p>
        <p><strong>Status Pesanan:</strong> <?php echo htmlspecialchars($order['order_status']); ?></p>
        <p><strong>Metode Pembayaran:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
        <p><strong>Total:</strong> <?php echo formatPrice($order['total']); ?></p>
    </div>

    <h3 class="section-title">Item Pesanan</h3>
    <table class="order-items">
        <thead>
            <tr>
                <th>Menu</th>
                <th>Qty</th>
                <th>Harga Satuan</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['nama_menu']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo formatPrice($item['unit_price']); ?></td>
                    <td><?php echo formatPrice($item['subtotal']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="history.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
    </a>
</main>

</body>
</html>
