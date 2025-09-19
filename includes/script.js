function addToCart(menuId) {
    fetch("add_to_cart.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "menu_id=" + menuId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Menu berhasil ditambahkan ke keranjang!");
        } else if (data.login_required) {
            if (confirm("Anda harus login terlebih dahulu untuk menambahkan ke keranjang. Login sekarang?")) {
                window.location.href = "login.php";
            }
        } else {
            alert(data.message);
        }
    })
    .catch(err => console.error("Error:", err));
}
