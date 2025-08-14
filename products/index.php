<?php
require_once '../config/database.php';
require_once '../includes/topsis.php';

$database = new Database();
$db = $database->getConnection();

// Ambil data produk
$query = "SELECT * FROM products";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil kriteria dan bobot
$query = "SELECT * FROM criteria";
$stmt = $db->prepare($query);
$stmt->execute();
$criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil nilai produk terhadap kriteria
$productCriteria = [];
foreach ($products as $product) {
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

// Bobot kriteria (dalam contoh ini diambil dari database)
$weights = array_column($criteria, 'weight');

// Jalankan TOPSIS
$topsis = new TOPSIS($productCriteria, $criteria, $weights);
$recommendations = $topsis->calculate();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Recommendations</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Product Recommendations</h2>
        
        <div class="row">
            <?php foreach ($recommendations as $item): 
                $product = $item['alternative']['product'];
                $score = $item['score'];
            ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <img src="../assets/images/<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['name'] ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= $product['name'] ?></h5>
                        <p class="card-text"><?= substr($product['description'], 0, 100) ?>...</p>
                        <p class="text-success">Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
                        <p class="text-info">Recommendation Score: <?= number_format($score, 4) ?></p>
                        <a href="#" class="btn btn-primary">Add to Cart</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>