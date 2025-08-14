<?php
// Pastikan session selalu dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: /topsis_ahp_shop/login.php");
    exit();
}

// Redirect jika bukan admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: /topsis_ahp_shop/index.php");
    exit();
}
?>