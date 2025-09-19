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

// Ambil riwayat pesanan user
$sql_orders = "
    SELECT 
        o.id, 
        o.created_at AS order_date, 
        o.order_status, 
        SUM(oi.quantity * oi.unit_price) AS total_harga
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.nis = ?
    GROUP BY o.id, o.created_at, o.order_status
    ORDER BY o.created_at DESC
";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("s", $nis);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();
$orders = $result_orders->fetch_all(MYSQLI_ASSOC);

function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - BiCanteen</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/history.css"> <!-- CSS khusus history -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <h1>BiCanteen.</h1>
            </div>
            <div class="header-icons">
                <a href="cart.php" class="icon-btn cart-icon" style="text-decoration:none; position:relative;">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>
                <button class="icon-btn"><i class="fas fa-calendar"></i></button>
                <a href="akun.php" class="icon-btn"><i class="fas fa-user-cog"></i></a>
                <a href="logout.php" class="icon-btn" style="background:#e53e3e;">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Konten Utama -->
<main class="main-content">
    <div class="container">
        <h2 class="section-title">Riwayat Pesanan</h2>

        <?php if (!empty($orders)): ?>
            <div class="history-list">
                <?php foreach ($orders as $order): ?>
                    <div class="history-card">
                        <div class="history-header">
                            <span class="order-id">#<?php echo $order['id']; ?></span>
                            <span class="order-date"><?php echo date("d M Y H:i", strtotime($order['order_date'])); ?></span>
                        </div>
                        <div class="history-body">
                            <p><strong>Status:</strong> <?php echo htmlspecialchars($order['order_status']); ?></p>
                            <p><strong>Total:</strong> <?php echo formatPrice($order['total_harga']); ?></p>
                        </div>
                        <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="detail-btn">
                            Lihat Detail
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Tidak ada riwayat pesanan.</p>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
