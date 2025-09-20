<?php
session_start();
require_once "includes/koneksi.php";

if (!isset($_SESSION['nis'])) {
    header("Location: login.php");
    exit;
}

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

$nis = $_SESSION['nis'];

$sql = "
    SELECT ci.id, m.nama, m.foto_path, ci.qty, ci.price_at_added, (ci.qty * ci.price_at_added) AS subtotal
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
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
        $total += $row['subtotal'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keranjang Saya - BiCanteen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- Tambahkan ini -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
         @media (max-width: 600px) {
            .container {
                padding: 10px;
            }
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .products-grid {
                display: flex;
                flex-direction: column;
                gap: 16px;
            }
            .product-card {
                flex-direction: column;
                width: 100%;
                max-width: 100%;
                margin: 0;
                box-sizing: border-box;
            }
            .product-image-container {
                width: 100%;
                text-align: center;
            }
            .product-image {
                max-width: 90vw;
                height: auto;
            }
            .qty-control {
                gap: 6px;
            }
            .checkout-button, .back-btn {
                width: 100%;
                font-size: 1rem;
                padding: 12px 0;
            }
            .section-title {
                font-size: 1.3rem;
            }
        }
        .qty-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .qty-btn {
            background: #4c51bf;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        .qty-value {
            min-width: 30px;
            text-align: center;
            font-weight: bold;
        }
        .animate {
            transform: scale(1.2);
            transition: transform 0.2s ease;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            padding: 10px 16px;
            background: linear-gradient(135deg,#553c9a 0%,#4c51bf 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
        }
        .delete-btn {
            background: #e53e3e;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .delete-btn:hover {
            background: #c53030;
        }
        .checkout-button {
            margin-top: 20px;
            padding: 12px 20px;
            background: linear-gradient(135deg,#4c51bf 0%,#553c9a 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .checkout-button:hover:not(:disabled) {
            background: linear-gradient(135deg,#553c9a 0%,#4c51bf 100%);
        }

        .checkout-button:disabled {
            background: #ccc;
            color: #666;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>BiCanteen.</h1>
                </div>
                <div class="header-icons">
                <a href="index.php" class="icon-btn"><i class="fas fa-arrow-left"></i></a>    
                    <!-- Tombol Keranjang -->
                <a href="cart.php" class="icon-btn cart-icon" style="text-decoration:none; position:relative;">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>

                    <button class="icon-btn"><i class="fas fa-calendar"></i></button>

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

<main class="main-content">
    <div class="container">

        <h2 class="section-title">Keranjang Saya</h2>

        <?php if (!empty($items)): ?>
            <div class="products-grid" id="cart-items">
                <?php foreach ($items as $item): ?>
                    <div class="product-card" id="item-<?php echo $item['id']; ?>">
                        <div class="product-image-container">
                            <img src="<?php echo htmlspecialchars($item['foto_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['nama']); ?>" 
                                 class="product-image">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($item['nama']); ?></h3>
                            <p>Harga: Rp <?php echo number_format($item['price_at_added'], 0, ',', '.'); ?></p>
                            <div class="qty-control">
                                <button class="qty-btn" onclick="updateQty(<?php echo $item['id']; ?>, 'minus')">-</button>
                                <span class="qty-value" id="qty-<?php echo $item['id']; ?>"><?php echo $item['qty']; ?></span>
                                <button class="qty-btn" onclick="updateQty(<?php echo $item['id']; ?>, 'plus')">+</button>
                            </div>
                            <p><strong>Subtotal: Rp <span id="subtotal-<?php echo $item['id']; ?>"><?php echo number_format($item['subtotal'], 0, ',', '.'); ?></span></strong></p>
                            
                            <!-- Tombol Hapus -->
                            <button class="delete-btn" onclick="deleteItem(<?php echo $item['id']; ?>)">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <h3 style="margin-top:20px;color:#4c51bf;">
                Total: Rp <span id="total-cart"><?php echo number_format($total, 0, ',', '.'); ?></span>
            </h3>
            <button id="checkout-btn" class="checkout-button" onclick="window.location.href='checkout.php'">Checkout</button>
        <?php else: ?>
            <p>Tidak ada item di keranjang.</p>
            <button id="checkout-btn" class="checkout-button" disabled>Checkout</button>
        <?php endif; ?>
    </div>
</main>

<script>
function goBack() {
    window.history.back();
}

function updateQty(itemId, action) {
    fetch("update_cart.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "item_id=" + itemId + "&action=" + action
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            let qtyEl = document.getElementById("qty-" + itemId);
            qtyEl.innerText = data.new_qty;
            qtyEl.classList.add("animate");
            setTimeout(() => qtyEl.classList.remove("animate"), 300);

            document.getElementById("subtotal-" + itemId).innerText = data.new_subtotal;
            document.getElementById("total-cart").innerText = data.new_total;

            // Jika qty 0, hapus item dari DOM
            if (data.new_qty === 0) {
                document.getElementById("item-" + itemId).remove();
            }

            // Disable tombol checkout kalau semua item habis
            checkCartEmpty();
        } else {
            alert(data.message);
        }
    })
    .catch(err => console.error(err));
}

function deleteItem(itemId) {
    if (confirm("Apakah Anda yakin ingin menghapus item ini dari keranjang?")) {
        fetch("delete_cart_item.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "item_id=" + itemId
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("item-" + itemId).remove();
                document.getElementById("total-cart").innerText = data.new_total;

                // Disable tombol checkout kalau semua item habis
                checkCartEmpty();
            } else {
                alert(data.message);
            }
        })
        .catch(err => console.error(err));
    }
}

function checkCartEmpty() {
    let cartItems = document.querySelectorAll("#cart-items .product-card");
    let checkoutBtn = document.getElementById("checkout-btn");
    if (cartItems.length === 0) {
        checkoutBtn.disabled = true;
    }
}
</script>
</body>
</html>
