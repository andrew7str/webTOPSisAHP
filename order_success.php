<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['checkout_success']) || !isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

unset($_SESSION['checkout_success']);

$database = new Database();
$db = $database->getConnection();

// Get transaction details
$query = "SELECT t.*, u.username, u.email 
          FROM transactions t 
          JOIN users u ON t.user_id = u.id 
          WHERE t.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_GET['id']]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

// Get transaction items
$query = "SELECT td.*, p.name, p.image 
          FROM transaction_details td 
          JOIN products p ON td.product_id = p.id 
          WHERE td.transaction_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_GET['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="mb-0">Order Placed Successfully!</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    Thank you for your order! Your order number is <strong>#<?= $transaction['id'] ?></strong>.
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h4>Order Summary</h4>
                        <p><strong>Order Date:</strong> <?= date('d M Y H:i', strtotime($transaction['created_at'])) ?></p>
                        <p><strong>Status:</strong> <span class="badge bg-warning"><?= ucfirst($transaction['status']) ?></span></p>
                        <p><strong>Total Amount:</strong> Rp <?= number_format($transaction['total'], 0, ',', '.') ?></p>
                    </div>
                    <div class="col-md-6">
                        <h4>Customer Information</h4>
                        <p><strong>Name:</strong> <?= $transaction['username'] ?></p>
                        <p><strong>Email:</strong> <?= $transaction['email'] ?></p>
                    </div>
                </div>
                
                <h4 class="mt-4">Order Items</h4>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="assets/images/<?= $item['image'] ?>" alt="<?= $item['name'] ?>" width="50" class="me-3">
                                    <?= $item['name'] ?>
                                </div>
                            </td>
                            <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td>Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Grand Total</strong></td>
                            <td><strong>Rp <?= number_format($transaction['total'], 0, ',', '.') ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
                    <a href="orders.php" class="btn btn-primary">View All Orders</a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>