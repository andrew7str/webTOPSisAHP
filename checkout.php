<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get products in cart
$cart_products = [];
$total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    $query = "SELECT * FROM products WHERE id IN ($placeholders)";
    $stmt = $db->prepare($query);
    $stmt->execute($product_ids);
    $cart_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total
    foreach ($cart_products as $product) {
        $total += $product['price'] * $_SESSION['cart'][$product['id']];
    }
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($cart_products)) {
    try {
        $db->beginTransaction();
        
        // Create transaction
        $query = "INSERT INTO transactions (user_id, total, status) VALUES (?, ?, 'pending')";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id'], $total]);
        $transaction_id = $db->lastInsertId();
        
        // Add transaction details
        foreach ($cart_products as $product) {
            $quantity = $_SESSION['cart'][$product['id']];
            $query = "INSERT INTO transaction_details (transaction_id, product_id, quantity, price) 
                      VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$transaction_id, $product['id'], $quantity, $product['price']]);
            
            // Update product stock
            $query = "UPDATE products SET stock = stock - ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$quantity, $product['id']]);
        }
        
        $db->commit();
        
        // Clear cart
        unset($_SESSION['cart']);
        
        $_SESSION['checkout_success'] = true;
        header('Location: order_success.php?id=' . $transaction_id);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Checkout failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <h2>Checkout</h2>
        
        <?php if (empty($cart_products)): ?>
            <div class="alert alert-info mt-4">Your cart is empty. <a href="products.php">Browse products</a></div>
        <?php else: ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="row mt-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">Order Summary</div>
                        <div class="card-body">
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
                                    <?php foreach ($cart_products as $product): 
                                        $quantity = $_SESSION['cart'][$product['id']];
                                        $product_total = $product['price'] * $quantity;
                                    ?>
                                    <tr>
                                        <td><?= $product['name'] ?></td>
                                        <td>Rp <?= number_format($product['price'], 0, ',', '.') ?></td>
                                        <td><?= $quantity ?></td>
                                        <td>Rp <?= number_format($product_total, 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Grand Total</strong></td>
                                        <td><strong>Rp <?= number_format($total, 0, ',', '.') ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Shipping Information</div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" value="<?= $user['username'] ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?= $user['email'] ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Shipping Address</label>
                                    <textarea class="form-control" name="shipping_address" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <select class="form-select" name="payment_method" required>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="e_wallet">E-Wallet</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Place Order</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>