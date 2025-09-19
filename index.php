<?php 
session_start();
// Konfigurasi koneksi ke database Aiven
require_once "includes/koneksi.php";

// Koneksi
$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Cek apakah user sudah login
$isLoggedIn = isset($_SESSION['nis']);

// Hitung jumlah item di keranjang (kalau user login)
$cartCount = 0;
if ($isLoggedIn) {
    $nis = $_SESSION['nis'];
    $sql_cart = "
        SELECT SUM(ci.qty) as total_qty 
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
}

// Ambil data penjual (6 penjual terbaru)
$sellers = [];
$sql = "SELECT id, nama, deskripsi, foto_path FROM penjual ORDER BY created_at LIMIT 6";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $sellers = $result->fetch_all(MYSQLI_ASSOC);
}

// Ambil data menu rekomendasi
$products = [];
$sql = "
    SELECT m.id, m.nama, m.harga, m.foto_path, SUM(oi.quantity) AS total_dibeli
    FROM order_items oi
    JOIN menu m ON oi.menu_id = m.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.order_status != 'DIBATALKAN'
    GROUP BY m.id, m.nama, m.harga, m.foto_path
    ORDER BY total_dibeli DESC
    LIMIT 6
";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $products = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $sql = "SELECT id, nama, harga, foto_path FROM menu ORDER BY RAND() LIMIT 6";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Fungsi format harga
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiCanteen - Homepage</title>
    <link rel="stylesheet" href="css/style.css">
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
                    <!-- Tombol Keranjang -->
                <a href="cart.php" class="icon-btn cart-icon" style="text-decoration:none; position:relative;">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Tombol History -->
                <a href="history.php" class="icon-btn" style="text-decoration:none;">
                    <i class="fas fa-history"></i>
                </a>

                    <!-- Tombol Login / Akun -->
                    <?php if ($isLoggedIn): ?>
                        <a href="akun.php" class="icon-btn" style="text-decoration:none;">
                            <i class="fas fa-user-cog"></i>
                        </a>
                        <a href="logout.php" class="icon-btn" style="text-decoration:none;background-color:#e53e3e;">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="icon-btn" style="text-decoration:none;">
                            <i class="fas fa-user"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">

            <!-- Sellers Section -->
            <section class="sellers-section">
                <h2 class="section-title">Penjual</h2>
                <div class="sellers-grid">
                    <?php if (!empty($sellers)): ?>
                        <?php foreach ($sellers as $seller): ?>
                            <a href="menu.php?id=<?php echo $seller['id']; ?>" class="seller-card">
                                <div class="seller-avatar">
                                    <img src="<?php echo htmlspecialchars($seller['foto_path'] ?: 'img/avatar.png'); ?>" 
                                         alt="<?php echo htmlspecialchars($seller['nama']); ?>" 
                                         class="avatar-img">
                                </div>
                                <h3 class="seller-name"><?php echo htmlspecialchars($seller['nama']); ?></h3>
                                <p class="seller-desc"><?php echo htmlspecialchars($seller['deskripsi']); ?></p>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Tidak ada data penjual.</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Recommendations Section -->
            <section class="recommendations-section">
                <h2 class="section-title">Rekomendasi</h2>
                <div class="products-grid">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image-container">
                                    <img src="<?php echo htmlspecialchars($product['foto_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['nama']); ?>" 
                                         class="product-image">
                                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-shopping-bag"></i>
                                    </button>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo htmlspecialchars($product['nama']); ?></h3>
                                    <p class="product-price"><?php echo formatPrice($product['harga']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Tidak ada rekomendasi produk.</p>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </main>
    <script src="includes/script.js"></script>
</body>
</html>
