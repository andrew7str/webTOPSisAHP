<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get user orders
$query = "SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <h2>My Orders</h2>
        
        <?php if (empty($orders)): ?>
            <div class="alert alert-info mt-4">You haven't placed any orders yet. <a href="products.php">Browse products</a></div>
        <?php else: ?>
            <div class="table-responsive mt-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= $order['id'] ?></td>
                            <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                            <td>Rp <?= number_format($order['total'], 0, ',', '.') ?></td>
                            <td>
                                <?php 
                                $badge_class = '';
                                switch ($order['status']) {
                                    case 'pending': $badge_class = 'bg-warning'; break;
                                    case 'completed': $badge_class = 'bg-success'; break;
                                    case 'cancelled': $badge_class = 'bg-danger'; break;
                                    default: $badge_class = 'bg-secondary';
                                }
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= ucfirst($order['status']) ?></span>
                            </td>
                            <td>
                                <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>