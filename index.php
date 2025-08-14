<?php
require_once 'config/database.php';
require_once 'includes/topsis.php';

// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $database = new Database();
    $db = $database->getConnection();

    // Query produk baru dengan pengecekan kolom
    $query = "SHOW COLUMNS FROM products LIKE 'created_at'";
    $stmt = $db->query($query);
    $created_at_exists = ($stmt->rowCount() > 0);

    if ($created_at_exists) {
        $query = "SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC LIMIT 8";
    } else {
        $query = "SELECT * FROM products WHERE stock > 0 ORDER BY id DESC LIMIT 8";
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $new_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query untuk TOPSIS
    if ($created_at_exists) {
        $query = "SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC";
    } else {
        $query = "SELECT * FROM products WHERE stock > 0 ORDER BY id DESC";
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil kriteria dan bobot
    $query = "SELECT * FROM criteria";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $productCriteria = [];
    foreach ($all_products as $product) {
        $query = "SELECT criterion_id, value FROM product_criteria WHERE product_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$product['id']]);
        $values = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $criteriaValues = [];
        foreach ($values as $value) {
            $criteriaValues[$value['criterion_id']] = $value['value'];
        }
        
        $productCriteria[] = [
            'product' => $product,
            'criteria_values' => $criteriaValues
        ];
    }

    $weights = array_column($criteria, 'weight');
    $recommendations = [];

    if (!empty($criteria) && !empty($productCriteria)) {
        $topsis = new TOPSIS($productCriteria, $criteria, $weights);
        $recommendations = $topsis->calculate();
        $recommendations = array_slice($recommendations, 0, 4);
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die("Application error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - TOPSIS & AHP Shop</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/topsis.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-5">
        <!-- Hero Section -->
        <div class="p-5 mb-4 bg-light rounded-3">
            <div class="container-fluid py-5">
                <h1 class="display-5 fw-bold">Welcome to Our Shop</h1>
                <p class="col-md-8 fs-4">Find the best products selected using TOPSIS and AHP methods.</p>
                <a href="products.php" class="btn btn-primary btn-lg">Browse Products</a>
            </div>
        </div>
        
        <?php if (!empty($recommendations)): ?>
        <h2 class="mb-4">Recommended For You</h2>
        <div class="row mb-5">
            <?php foreach ($recommendations as $item): 
                $product = $item['alternative']['product'];
                $score = $item['score'];
            ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100 recommendation-card">
                    <img src="assets/images/<?= htmlspecialchars($product['image'] ?? 'default.jpg') ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                        <p class="card-text"><?= substr(htmlspecialchars($product['description']), 0, 50) ?>...</p>
                        <p class="text-success">Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
                        <div class="progress mb-2 score-progress">
                            <div class="progress-bar bg-info" 
                                 style="width: <?= $score*100 ?>%" 
                                 aria-valuenow="<?= $score*100 ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?= number_format($score*100, 1) ?>%
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="product_detail.php?id=<?= $product['id'] ?>" 
                           class="btn btn-sm btn-primary">Detail</a>
                        <a href="cart_action.php?action=add&id=<?= $product['id'] ?>" 
                           class="btn btn-sm btn-success">Add to Cart</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <h2 class="mb-4">New Arrivals</h2>
        <div class="row">
            <?php foreach ($new_products as $product): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="assets/images/<?= htmlspecialchars($product['image'] ?? 'default.jpg') ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                        <p class="card-text"><?= substr(htmlspecialchars($product['description']), 0, 50) ?>...</p>
                        <p class="text-success">Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="product_detail.php?id=<?= $product['id'] ?>" 
                           class="btn btn-sm btn-primary">Detail</a>
                        <a href="cart_action.php?action=add&id=<?= $product['id'] ?>" 
                           class="btn btn-sm btn-success">Add to Cart</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>