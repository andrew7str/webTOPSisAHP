<?php
require_once '../config/database.php';
require_once 'includes/auth_check.php';

$database = new Database();
$db = $database->getConnection();

// Handle status update
if (isset($_GET['update_status'])) {
    $transaction_id = $_GET['id'];
    $new_status = $_GET['status'];
    
    $query = "UPDATE transactions SET status = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$new_status, $transaction_id]);
    
    $_SESSION['message'] = [
        'type' => 'success',
        'text' => 'Transaction status updated successfully'
    ];
    header('Location: transactions.php');
    exit;
}

// Get all transactions with user data
$query = "SELECT t.id, u.username, t.total, t.status, t.created_at 
          FROM transactions t
          JOIN users u ON t.user_id = u.id
          ORDER BY t.created_at DESC";
$stmt = $db->query($query);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status counts for filter
$status_counts = [
    'all' => count($transactions),
    'pending' => 0,
    'processing' => 0,
    'completed' => 0,
    'cancelled' => 0
];

foreach ($transactions as $transaction) {
    $status_counts[$transaction['status']]++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management - TOPSIS & AHP Shop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #eef2ff;
            --danger: #e63946;
            --success: #2b8a3e;
            --warning: #e67700;
            --info: #1971c2;
            --light-gray: #e9ecef;
            --dark: #212529;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid var(--light-gray);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header h3 {
            margin: 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background-color: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
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
            background-color: #f8f9fa;
        }
        
        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        
        .badge-pending {
            background-color: #fff3bf;
            color: var(--warning);
        }
        
        .badge-processing {
            background-color: #d0ebff;
            color: var(--info);
        }
        
        .badge-completed {
            background-color: #d3f9d8;
            color: var(--success);
        }
        
        .badge-cancelled {
            background-color: #ffd8d8;
            color: var(--danger);
        }
        
        .status-dropdown .dropdown-menu {
            min-width: 10rem;
        }
        
        .status-dropdown .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            border-radius: 50px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid var(--light-gray);
            background-color: white;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-sm {
                width: 100%;
            }
            
            .filter-buttons {
                gap: 0.25rem;
            }
            
            .filter-btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.75rem;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .status-counts {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .status-counts span {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="main-content">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show">
                <?= $_SESSION['message']['text'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-file-invoice-dollar"></i> Transaction Management</h3>
                <div class="status-counts">
                    <span class="me-3 text-muted">
                        <i class="fas fa-chart-pie me-1"></i>
                        Total: <?= number_format($status_counts['all']) ?>
                    </span>
                    <span class="me-3 text-warning">
                        <i class="fas fa-clock me-1"></i>
                        Pending: <?= number_format($status_counts['pending']) ?>
                    </span>
                    <span class="me-3 text-info">
                        <i class="fas fa-sync-alt me-1"></i>
                        Processing: <?= number_format($status_counts['processing']) ?>
                    </span>
                    <span class="me-3 text-success">
                        <i class="fas fa-check-circle me-1"></i>
                        Completed: <?= number_format($status_counts['completed']) ?>
                    </span>
                    <span class="me-3 text-danger">
                        <i class="fas fa-times-circle me-1"></i>
                        Cancelled: <?= number_format($status_counts['cancelled']) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">
                        All <span class="badge bg-secondary ms-1"><?= $status_counts['all'] ?></span>
                    </button>
                    <button class="filter-btn" data-filter="pending">
                        Pending <span class="badge bg-warning ms-1"><?= $status_counts['pending'] ?></span>
                    </button>
                    <button class="filter-btn" data-filter="processing">
                        Processing <span class="badge bg-info ms-1"><?= $status_counts['processing'] ?></span>
                    </button>
                    <button class="filter-btn" data-filter="completed">
                        Completed <span class="badge bg-success ms-1"><?= $status_counts['completed'] ?></span>
                    </button>
                    <button class="filter-btn" data-filter="cancelled">
                        Cancelled <span class="badge bg-danger ms-1"><?= $status_counts['cancelled'] ?></span>
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                                        <h5 class="text-muted">No transactions found</h5>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                <tr data-status="<?= $transaction['status'] ?>">
                                    <td>#<?= $transaction['id'] ?></td>
                                    <td><?= htmlspecialchars($transaction['username']) ?></td>
                                    <td>Rp <?= number_format($transaction['total'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="badge badge-<?= $transaction['status'] ?>">
                                            <?= ucfirst($transaction['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($transaction['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="transaction_detail.php?id=<?= $transaction['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <div class="dropdown status-dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                        type="button" 
                                                        data-bs-toggle="dropdown">
                                                    <i class="fas fa-edit"></i> Status
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item <?= $transaction['status'] === 'pending' ? 'active' : '' ?>" 
                                                           href="transactions.php?update_status&id=<?= $transaction['id'] ?>&status=pending">
                                                            <i class="fas fa-clock text-warning"></i> Pending
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item <?= $transaction['status'] === 'processing' ? 'active' : '' ?>" 
                                                           href="transactions.php?update_status&id=<?= $transaction['id'] ?>&status=processing">
                                                            <i class="fas fa-sync-alt text-info"></i> Processing
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item <?= $transaction['status'] === 'completed' ? 'active' : '' ?>" 
                                                           href="transactions.php?update_status&id=<?= $transaction['id'] ?>&status=completed">
                                                            <i class="fas fa-check-circle text-success"></i> Completed
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item <?= $transaction['status'] === 'cancelled' ? 'active' : '' ?>" 
                                                           href="transactions.php?update_status&id=<?= $transaction['id'] ?>&status=cancelled">
                                                            <i class="fas fa-times-circle text-danger"></i> Cancelled
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter transactions by status
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                const rows = document.querySelectorAll('#transactionsTable tbody tr');
                
                rows.forEach(row => {
                    if (filter === 'all') {
                        row.style.display = '';
                    } else {
                        row.style.display = row.getAttribute('data-status') === filter ? '' : 'none';
                    }
                });
            });
        });
        
        // Confirm before changing status
        document.querySelectorAll('.status-dropdown .dropdown-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to change this transaction status?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>