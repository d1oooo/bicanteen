<?php
// Konfigurasi koneksi ke database Aiven
require_once "includes/koneksi.php";

$conn = new mysqli($host, $user, $pass, $dbname, $port);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

session_start();

$warning = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nis = trim($_POST["nis"]);
    $password = trim($_POST["password"]);

    if (!empty($nis) && !empty($password)) {
        $sql = "SELECT nis, nama, password_hash FROM siswa WHERE nis = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nis);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if ($password === $user['password_hash']) { 
                // NOTE: Untuk keamanan, sebaiknya password_hash disimpan dengan hashing (password_hash PHP)
                $_SESSION['nis'] = $user['nis'];
                $_SESSION['nama'] = $user['nama'];
                header("Location: index.php");
                exit();
            } else {
                $warning = "Password salah.";
            }
        } else {
            $warning = "NIS tidak ditemukan di database.";
        }
    } else {
        $warning = "Mohon isi semua field.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BiCanteen</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>BiCanteen.</h1>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <section class="login-section" style="max-width:400px;margin:0 auto;background:white;padding:30px;border-radius:16px;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                <h2 class="section-title" style="text-align:center;">Login Siswa</h2>

                <?php if (!empty($warning)): ?>
                    <p style="color:red;text-align:center;margin-bottom:10px;"><?php echo htmlspecialchars($warning); ?></p>
                <?php endif; ?>

                <form method="POST">
                    <div style="margin-bottom:15px;">
                        <label for="nis" style="display:block;margin-bottom:5px;font-weight:600;">NIS</label>
                        <input type="text" id="nis" name="nis" required class="search-input" style="border:1px solid #ccc;border-radius:8px;padding:10px;width:100%;">
                    </div>
                    <div style="margin-bottom:20px;">
                        <label for="password" style="display:block;margin-bottom:5px;font-weight:600;">Password</label>
                        <input type="password" id="password" name="password" required class="search-input" style="border:1px solid #ccc;border-radius:8px;padding:10px;width:100%;">
                    </div>
                    <button type="submit" style="width:100%;padding:12px;background:linear-gradient(135deg,#4c51bf 0%,#553c9a 100%);color:white;border:none;border-radius:8px;cursor:pointer;font-weight:bold;">Login</button>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
