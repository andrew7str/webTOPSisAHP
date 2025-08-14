<?php
require_once '../config/database.php';
require_once 'includes/auth_check.php';

$database = new Database();
$db = $database->getConnection();

// Ambil data statistik
$stats = [
    'products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'transactions' => $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn(),
    'revenue' => $db->query("SELECT SUM(total) FROM transactions WHERE status = 'completed'")->fetchColumn() ?? 0
];

// Ambil transaksi terbaru
$recentTransactions = $db->query("
    SELECT t.id, u.username, t.total, t.created_at 
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Ambil produk terlaris
$topProducts = $db->query("
    SELECT p.name, SUM(td.quantity) as total_sold
    FROM transaction_details td
    JOIN products p ON td.product_id = p.id
    GROUP BY p.name
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Gunakan BASE_URL untuk path absolut -->
    <?php define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/topsis_ahp_shop'); ?>
    <link href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/dashboard.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="admin-container">
        <!-- Header Dashboard -->
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            <div class="breadcrumb">
                <a href="#">Home</a> / <span>Dashboard</span>
            </div>
        </div>

        <!-- Statistik Utama -->
        <div class="stats-grid">
            <div class="stat-card bg-primary">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Produk</h3>
                    <span><?= $stats['products'] ?></span>
                </div>
            </div>

            <div class="stat-card bg-success">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Pengguna</h3>
                    <span><?= $stats['users'] ?></span>
                </div>
            </div>

            <div class="stat-card bg-warning">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Transaksi</h3>
                    <span><?= $stats['transactions'] ?></span>
                </div>
            </div>

            <div class="stat-card bg-danger">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Pendapatan</h3>
                    <span>Rp <?= number_format($stats['revenue'], 0, ',', '.') ?></span>
                </div>
            </div>
        </div>

        <!-- Dua Kolom Utama -->
        <div class="dashboard-content">
            <!-- Kolom Kiri -->
            <div class="main-content">
                <!-- Grafik (Placeholder) -->
                <div class="card chart-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Statistik Penjualan</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-placeholder">
                            <p>Grafik akan ditampilkan di sini</p>
                            <img src="../../assets/images/chart-placeholder.png" alt="Chart Placeholder">
                        </div>
                    </div>
                </div>

                <!-- Transaksi Terbaru -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-clock"></i> Transaksi Terbaru</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Pelanggan</th>
                                        <th>Total</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTransactions as $transaction): ?>
                                    <tr>
                                        <td>#<?= $transaction['id'] ?></td>
                                        <td><?= $transaction['username'] ?></td>
                                        <td>Rp <?= number_format($transaction['total'], 0, ',', '.') ?></td>
                                        <td><?= date('d M Y', strtotime($transaction['created_at'])) ?></td>
                                        <td>
                                            <a href="transactions.php?view=<?= $transaction['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan -->
            <div class="sidebar">
                <!-- Produk Terlaris -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-star"></i> Produk Terlaris</h3>
                    </div>
                    <div class="card-body">
                        <ul class="top-products-list">
                            <?php foreach ($topProducts as $product): ?>
                            <li>
                                <span class="product-name"><?= $product['name'] ?></span>
                                <span class="sales-count"><?= $product['total_sold'] ?> terjual</span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card quick-actions">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <a href="products.php?action=add" class="btn btn-action btn-primary">
                            <i class="fas fa-plus"></i> Tambah Produk
                        </a>
                        <a href="products.php" class="btn btn-action btn-success">
                            <i class="fas fa-boxes"></i> Kelola Produk
                        </a>
                        <a href="users.php" class="btn btn-action btn-info">
                            <i class="fas fa-users"></i> Kelola Pengguna
                        </a>
                        <a href="criteria_weights.php" class="btn btn-action btn-warning">
                            <i class="fas fa-weight"></i> Atur Bobot
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/dashboard.js"></script>
</body>
</html>