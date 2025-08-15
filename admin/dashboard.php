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
    'revenue' => $db->query("SELECT SUM(total) FROM transactions WHERE status = 'completed'")->fetchColumn() ?? 0,
    'pending_orders' => $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'")->fetchColumn()
];

// Ambil transaksi terbaru
$recentTransactions = $db->query("
    SELECT t.id, u.username, t.total, t.status, t.created_at 
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Ambil produk terlaris
$topProducts = $db->query("
    SELECT p.name, p.image, SUM(td.quantity) as total_sold
    FROM transaction_details td
    JOIN products p ON td.product_id = p.id
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Data untuk grafik
$salesData = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m-%d') AS date,
        COUNT(*) AS order_count,
        SUM(total) AS total_revenue
    FROM transactions
    WHERE created_at >= CURDATE() - INTERVAL 7 DAY
    GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d')
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Format data untuk Chart.js
$chartLabels = [];
$chartOrders = [];
$chartRevenue = [];

foreach ($salesData as $data) {
    $chartLabels[] = date('d M', strtotime($data['date']));
    $chartOrders[] = $data['order_count'];
    $chartRevenue[] = $data['total_revenue'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TOPSIS & AHP Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #eef2ff;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #e63946;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* Dashboard Layout */
        .dashboard-container {
            max-width: 1800px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header Styles */
        .dashboard-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-light);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            opacity: 0;
            transition: var(--transition);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            background-color: var(--primary-light);
            color: var(--primary);
        }

        .stat-info h3 {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-info span {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            display: block;
        }

        /* Dashboard Content Layout */
        .dashboard-content {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
        }

        @media (max-width: 1200px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
        }

        /* Cards */
        .card {
            background: var(--white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            border: none;
            margin-bottom: 2rem;
            overflow: hidden;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            background: var(--white);
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header .action {
            font-size: 0.875rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .card-header .action:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Chart Container */
        .chart-container {
            height: 320px;
            position: relative;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .table th {
            background-color: #f8fafc;
            padding: 0.875rem 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--light-gray);
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: middle;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover td {
            background-color: #f8fafc;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .status-badge i {
            font-size: 0.625rem;
        }

        .status-pending {
            background-color: #ffeed9;
            color: #e67700;
        }

        .status-completed {
            background-color: #d3f9d8;
            color: #2b8a3e;
        }

        .status-processing {
            background-color: #d0ebff;
            color: #1971c2;
        }

        /* Top Products List */
        .top-products-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .top-product-item {
            display: flex;
            align-items: center;
            padding: 0.875rem 0;
            border-bottom: 1px solid var(--light-gray);
            transition: var(--transition);
        }

        .top-product-item:last-child {
            border-bottom: none;
        }

        .top-product-item:hover {
            background-color: #f8fafc;
            border-radius: var(--radius-sm);
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        .product-image {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            margin-right: 1rem;
            border: 1px solid var(--light-gray);
        }

        .product-info {
            flex: 1;
            min-width: 0;
        }

        .product-name {
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-sales {
            font-size: 0.75rem;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .product-sales i {
            font-size: 0.625rem;
            color: var(--primary);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .btn-action {
            display: flex;
            align-items: center;
            padding: 0.875rem 1rem;
            background-color: var(--white);
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--dark);
            transition: var(--transition);
            border: 1px solid var(--light-gray);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .btn-action:hover {
            background-color: var(--primary);
            color: var(--white);
            transform: translateX(5px);
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .btn-action i {
            font-size: 1rem;
            margin-right: 0.75rem;
            width: 24px;
            text-align: center;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            animation: fadeIn 0.5s ease-out forwards;
        }

        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                border: 1px solid var(--light-gray);
                border-radius: var(--radius-sm);
            }
        }

        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            :root {
                --light: #1e293b;
                --dark: #f8fafc;
                --gray: #94a3b8;
                --light-gray: #334155;
                --white: #0f172a;
            }
            
            body {
                background-color: #020617;
                color: #e2e8f0;
            }
            
            .card, .stat-card {
                background-color: #1e293b;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            }
            
            .table th {
                background-color: #1e293b;
                color: #94a3b8;
            }
            
            .table tr:hover td {
                background-color: #334155;
            }
            
            .btn-action {
                background-color: #1e293b;
                border-color: #334155;
                color: #e2e8f0;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <!-- Header Dashboard -->
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
            <div class="user-info">
                <img src="https://ui-avatars.com/api/?name=Admin&background=4361ee&color=fff" alt="User" class="user-avatar">
                <span>Admin</span>
            </div>
        </div>

        <!-- Statistik Utama -->
        <div class="stats-grid">
            <div class="stat-card" style="border-left-color: #4361ee;">
                <div class="stat-icon" style="background-color: #eef2ff; color: #4361ee;">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Produk</h3>
                    <span><?= $stats['products'] ?></span>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> 12% from last month
                    </div>
                </div>
            </div>

            <div class="stat-card" style="border-left-color: #06b6d4;">
                <div class="stat-icon" style="background-color: #ecfeff; color: #06b6d4;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Pengguna</h3>
                    <span><?= $stats['users'] ?></span>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> 5% from last month
                    </div>
                </div>
            </div>

            <div class="stat-card" style="border-left-color: #0ea5e9;">
                <div class="stat-icon" style="background-color: #f0f9ff; color: #0ea5e9;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Transaksi</h3>
                    <span><?= $stats['transactions'] ?></span>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> 8% from last month
                    </div>
                </div>
            </div>

            <div class="stat-card" style="border-left-color: #8b5cf6;">
                <div class="stat-icon" style="background-color: #f5f3ff; color: #8b5cf6;">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Pendapatan</h3>
                    <span>Rp <?= number_format($stats['revenue'], 0, ',', '.') ?></span>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> 15% from last month
                    </div>
                </div>
            </div>
            
            <div class="stat-card" style="border-left-color: #ec4899;">
                <div class="stat-icon" style="background-color: #fdf2f8; color: #ec4899;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Pesanan Pending</h3>
                    <span><?= $stats['pending_orders'] ?></span>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-down"></i> 3% from last month
                    </div>
                </div>
            </div>
        </div>

        <!-- Dua Kolom Utama -->
        <div class="dashboard-content">
            <!-- Kolom Kiri -->
            <div class="main-content">
                <!-- Grafik Penjualan -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line me-2"></i>Statistik Penjualan 7 Hari Terakhir</h3>
                        <a href="transactions.php" class="action">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Transaksi Terbaru -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-clock me-2"></i>Transaksi Terbaru</h3>
                        <a href="transactions.php" class="action">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Pelanggan</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTransactions as $transaction): ?>
                                    <tr>
                                        <td>#<?= $transaction['id'] ?></td>
                                        <td><?= htmlspecialchars($transaction['username']) ?></td>
                                        <td>Rp <?= number_format($transaction['total'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $transaction['status'] ?>">
                                                <i class="fas fa-<?= $transaction['status'] == 'completed' ? 'check-circle' : ($transaction['status'] == 'pending' ? 'clock' : 'sync-alt') ?>"></i>
                                                <?= ucfirst($transaction['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($transaction['created_at'])) ?></td>
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
                        <h3><i class="fas fa-star me-2"></i>Produk Terlaris</h3>
                        <a href="products.php" class="action">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <ul class="top-products-list">
                            <?php foreach ($topProducts as $product): ?>
                            <li class="top-product-item">
                                <img src="../../assets/images/<?= htmlspecialchars($product['image']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>" 
                                     class="product-image">
                                <div class="product-info">
                                    <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                    <div class="product-sales">
                                        <i class="fas fa-shopping-bag"></i>
                                        <?= $product['total_sold'] ?> terjual
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt me-2"></i>Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="products.php?action=add" class="btn-action">
                                <i class="fas fa-plus"></i>
                                <span>Tambah Produk</span>
                            </a>
                            <a href="products.php" class="btn-action">
                                <i class="fas fa-boxes"></i>
                                <span>Kelola Produk</span>
                            </a>
                            <a href="users.php" class="btn-action">
                                <i class="fas fa-users"></i>
                                <span>Kelola Pengguna</span>
                            </a>
                            <a href="criteria_weights.php" class="btn-action">
                                <i class="fas fa-weight"></i>
                                <span>Atur Bobot</span>
                            </a>
                            <a href="transactions.php" class="btn-action">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <span>Lihat Transaksi</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Grafik Penjualan
            const ctx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chartLabels) ?>,
                    datasets: [
                        {
                            label: 'Jumlah Pesanan',
                            data: <?= json_encode($chartOrders) ?>,
                            backgroundColor: '#3b82f6',
                            borderColor: '#3b82f6',
                            borderWidth: 1,
                            borderRadius: 6,
                            order: 2
                        },
                        {
                            label: 'Total Pendapatan (Rp)',
                            data: <?= json_encode($chartRevenue) ?>,
                            type: 'line',
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            borderWidth: 3,
                            pointRadius: 5,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#8b5cf6',
                            pointBorderWidth: 2,
                            fill: true,
                            order: 1,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label.includes('Pendapatan')) {
                                        return label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                                    }
                                    return label + ': ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Jumlah Pesanan'
                            },
                            grid: {
                                drawOnChartArea: false,
                            }
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Total Pendapatan (Rp)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'Rp' + value.toLocaleString('id-ID');
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            });
            
            // Update stats every 30 seconds
            setInterval(updateDashboardStats, 30000);
            
            function updateDashboardStats() {
                fetch('api/get_dashboard_stats.php')
                    .then(response => response.json())
                    .then(data => {
                        // Update the stats cards here
                        console.log('Stats updated:', data);
                    });
            }
        });
    </script>
</body>
</html>