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

$nis = $_SESSION['nis'];

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

// Ambil data siswa
$sql = "SELECT nama, password_hash FROM siswa WHERE nis = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nis);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "Data akun tidak ditemukan!";
    exit;
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['nama']);
    $new_password = trim($_POST['password']);

    if (!empty($new_password)) {
        $sql_update = "UPDATE siswa SET nama = ?, password_hash = ? WHERE nis = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sss", $new_name, $new_password, $nis);
    } else {
        $sql_update = "UPDATE siswa SET nama = ? WHERE nis = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ss", $new_name, $nis);
    }

    $stmt_update->execute();
    header("Location: akun.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengaturan Akun - BiCanteen</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .account-container {
            max-width: 500px;
            margin: 40px auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        .account-container h2 {
            margin-bottom: 15px;
            color: #4c51bf;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            transition: 0.2s;
        }
        input:focus {
            border-color: #4c51bf;
            box-shadow: 0 0 4px rgba(76,81,191,0.3);
        }
        .btn-submit {
            display: inline-block;
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #4c51bf, #553c9a);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s ease;
            text-align: center;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #5a5fe0, #6b46c1);
        }
        .success-message {
            text-align: center;
            color: green;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            text-align: center;
            color: #4c51bf;
            font-weight: 600;
            text-decoration: none;
            width: 100%;
        }
        .back-link:hover {
            text-decoration: underline;
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

<main>
    <div class="account-container">
        <h2>Data Umum</h2>
        <?php if (isset($_GET['success'])): ?>
            <p class="success-message">✅ Data akun berhasil diperbarui!</p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" value="<?php echo htmlspecialchars($nis); ?>" disabled>
            </div>

            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="password" placeholder="Ganti password anda (biarkan kosong jika tidak mengganti)">
            </div>

            <button type="submit" class="btn-submit">Simpan</button>
        </form>
    </div>
</main>
</body>
</html>
