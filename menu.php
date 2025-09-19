<?php
// Konfigurasi koneksi ke database Aiven
require_once "includes/koneksi.php";

// Ambil ID penjual
if (!isset($_GET['id'])) {
    die("Penjual tidak ditemukan.");
}
$penjual_id = intval($_GET['id']);

// Ambil data penjual
$sql = "SELECT id, nama, deskripsi, foto_path FROM penjual WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $penjual_id);
$stmt->execute();
$result = $stmt->get_result();
$seller = $result->fetch_assoc();

if (!$seller) {
    die("Penjual tidak ditemukan.");
}

// Ambil keyword search
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Ambil daftar menu penjual
$products = [];
if ($search !== "") {
    $sql = "SELECT id, nama, harga, foto_path 
            FROM menu 
            WHERE penjual_id = ? AND nama LIKE ?";
    $stmt = $conn->prepare($sql);
    $like = "%" . $search . "%";
    $stmt->bind_param("is", $penjual_id, $like);
} else {
    $sql = "SELECT id, nama, harga, foto_path 
            FROM menu 
            WHERE penjual_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $penjual_id);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $products = $result->fetch_all(MYSQLI_ASSOC);
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
    <title>Menu <?php echo htmlspecialchars($seller['nama']); ?> - BiCanteen</title>
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
                <div class="search-container">
                    <form action="menu.php" method="get" class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="hidden" name="id" value="<?php echo $penjual_id; ?>">
                        <input type="text" name="search" placeholder="Cari menu.." 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               class="search-input">
                    </form>
                </div>
                <div class="header-icons">
                    <a href="index.php" class="icon-btn"><i class="fas fa-arrow-left"></i></a>
                    <a href="cart.php" class="icon-btn" style="text-decoration:none;">
                        <i class="fas fa-lock"></i>
                    </a>
                    <button class="icon-btn"><i class="fas fa-user"></i></button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Info Penjual -->
            <section class="sellers-section">
                <div class="seller-card" style="max-width:400px; margin:auto;">
                    <div class="seller-avatar">
                        <img src="<?php echo htmlspecialchars($seller['foto_path'] ?: 'img/avatar.png'); ?>" 
                             alt="<?php echo htmlspecialchars($seller['nama']); ?>" 
                             class="avatar-img">
                    </div>
                    <h2 class="seller-name"><?php echo htmlspecialchars($seller['nama']); ?></h2>
                    <p class="seller-desc"><?php echo htmlspecialchars($seller['deskripsi']); ?></p>
                </div>
            </section>

            <!-- Menu Produk -->
            <section class="recommendations-section">
                <h2 class="section-title">Menu</h2>
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
                        <p>Tidak ada menu yang cocok untuk pencarian <b><?php echo htmlspecialchars($search); ?></b>.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
    <script src="includes/script.js"></script>
</body>
</html>
