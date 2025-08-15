<?php
require_once '../config/database.php';
require_once 'includes/auth_check.php';

// Inisialisasi pesan session
if (!isset($_SESSION['message'])) {
    $_SESSION['message'] = [];
}

$database = new Database();
$db = $database->getConnection();

// Handle delete action
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        $db->beginTransaction();
        
        // Hapus kriteria produk terlebih dahulu
        $query = "DELETE FROM product_criteria WHERE product_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        // Kemudian hapus produk
        $query = "DELETE FROM products WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        $db->commit();
        
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Product deleted successfully'
        ];
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Error deleting product: ' . $e->getMessage()
        ];
    }
    
    header('Location: products.php');
    exit;
}

// Handle add product form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);
    
    // Validasi input
    $errors = [];
    if (empty($name)) $errors[] = 'Product name is required';
    if ($price <= 0) $errors[] = 'Price must be greater than 0';
    if (empty($description)) $errors[] = 'Description is required';
    if ($stock < 0) $errors[] = 'Stock cannot be negative';
    
    if (empty($errors)) {
        try {
            // Handle file upload
            $imageName = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../assets/images/products/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $imageExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $imageName = 'product_' . uniqid() . '.' . strtolower($imageExt);
                $targetPath = $uploadDir . $imageName;
                
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $fileType = mime_content_type($_FILES['image']['tmp_name']);
                
                if (!in_array($fileType, $allowedTypes)) {
                    throw new Exception('Only JPEG, PNG, and WEBP images are allowed.');
                }
                
                if ($_FILES['image']['size'] > 2097152) { // 2MB
                    throw new Exception('File size exceeds 2MB limit.');
                }
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    throw new Exception('Failed to upload image.');
                }
            }
            
            // Insert product into database
            $query = "INSERT INTO products (name, price, description, stock, image) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $price, $description, $stock, $imageName]);
            
            $productId = $db->lastInsertId();
            
            // Set default criteria values for the new product
            $query = "INSERT INTO product_criteria (product_id, criterion_id, value)
                     SELECT ?, id, 
                     CASE 
                         WHEN name = 'Harga' THEN ?
                         WHEN name = 'Kualitas' THEN 5.0
                         WHEN name = 'Daya Tahan' THEN 2.0
                         WHEN name = 'Popularitas' THEN 5.0
                         ELSE 5.0
                     END
                     FROM criteria";
            $stmt = $db->prepare($query);
            $stmt->execute([$productId, $price]);
            
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Product added successfully'
            ];
            
            header('Location: products.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Error adding product: ' . $e->getMessage()
            ];
        }
    } else {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => implode('<br>', $errors)
        ];
    }
}

// Get all products with their criteria count
try {
    $query = "SELECT p.*, 
                     (SELECT COUNT(*) FROM product_criteria pc WHERE pc.product_id = p.id) as criteria_count
              FROM products p
              ORDER BY p.created_at DESC";
    $stmt = $db->query($query);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => 'Error fetching products: ' . $e->getMessage()
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - TOPSIS & AHP Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
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

        .container {
            max-width: 1400px;
            padding: 2rem 1.5rem;
        }

        /* Header Styles */
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
        }

        .breadcrumb {
            font-size: 0.875rem;
            color: var(--gray);
            margin-top: 0.5rem;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        /* Button Styles */
        .btn {
            border-radius: var(--radius-sm);
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--secondary);
            border-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .btn-outline-secondary {
            border-color: var(--light-gray);
        }

        .btn-outline-secondary:hover {
            background-color: var(--light-gray);
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .card-header {
            background-color: var(--primary);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Form Styles */
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .form-control {
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem;
            border: 1px solid var(--light-gray);
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }

        textarea.form-control {
            min-height: 120px;
        }

        .input-group-text {
            background-color: var(--light-gray);
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: var(--radius-sm);
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.875rem;
        }

        .table thead th {
            background-color: var(--light-gray);
            color: var(--dark);
            padding: 1rem 1.25rem;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .table tbody td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: middle;
        }

        /* Product Image */
        .product-img-container {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border-radius: var(--radius-sm);
            overflow: hidden;
        }

        .product-img-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .no-image-icon {
            font-size: 1.5rem;
            color: #adb5bd;
        }

        /* Stock Indicators */
        .price-cell {
            font-weight: 600;
            color: #2b8a3e;
        }

        .stock-cell {
            font-weight: 500;
        }

        .stock-cell.low {
            color: #e03131;
        }

        .stock-cell.medium {
            color: #f08c00;
        }

        .stock-cell.high {
            color: #2b8a3e;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: var(--radius-sm);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
        }

        .btn-outline-danger {
            color: var(--danger);
            border-color: var(--danger);
        }

        .btn-outline-danger:hover {
            background-color: var(--danger);
            color: white;
        }

        .btn-outline-info {
            color: var(--info);
            border-color: var(--info);
        }

        .btn-outline-info:hover {
            background-color: var(--info);
            color: white;
        }

        .btn-outline-secondary {
            color: var(--gray);
            border-color: var(--light-gray);
        }

        .btn-outline-secondary:hover {
            background-color: var(--light-gray);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 0;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--gray);
            margin-bottom: 1rem;
        }

        /* Alert Styles */
        .alert {
            border-radius: var(--radius-sm);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border: none;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .btn-close {
            padding: 0.5rem;
        }

        /* Badge Styles */
        .badge {
            font-weight: 500;
            padding: 0.35rem 0.65rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .table tbody td {
                padding: 0.75rem;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container">
        <?php 
        // Perbaikan pengecekan pesan session
        if (isset($_SESSION['message']) && !empty($_SESSION['message']['text'])): 
        ?>
            <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show">
                <?= $_SESSION['message']['text'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php 
            // Reset pesan setelah ditampilkan
            $_SESSION['message'] = [
                'type' => '',
                'text' => ''
            ]; 
            ?>
        <?php endif; ?>
        
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-boxes me-2"></i>Product Management
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Products</li>
                        </ol>
                    </nav>
                </div>
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Product
                </a>
            </div>
        </div>
        
        <?php if (isset($_GET['action']) && $_GET['action'] == 'add'): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-plus-circle me-2"></i>Add New Product
                </div>
                <div class="card-body">
                    <form action="products.php" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="invalid-feedback">
                                    Please provide a product name.
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="price" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01" required>
                                    <div class="invalid-feedback">
                                        Please provide a valid price.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                <div class="invalid-feedback">
                                    Please provide a product description.
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                                <div class="invalid-feedback">
                                    Please provide stock quantity.
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp">
                                <small class="text-muted">Max size: 2MB (JPEG, PNG, WEBP)</small>
                            </div>
                            
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-save me-2"></i>Save Product
                                </button>
                                <a href="products.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>Product List
            </div>
            <div class="card-body">
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h5 class="text-muted mt-3">No products found</h5>
                        <a href="?action=add" class="btn btn-primary mt-3">
                            <i class="fas fa-plus me-2"></i>Add Your First Product
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th width="50">ID</th>
                                    <th width="80">Image</th>
                                    <th>Product</th>
                                    <th width="120">Price</th>
                                    <th width="100">Stock</th>
                                    <th width="100">Criteria</th>
                                    <th width="150">Created</th>
                                    <th width="220">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>#<?= $product['id'] ?></td>
                                    <td>
                                        <div class="product-img-container">
                                            <?php if (!empty($product['image'])): ?>
                                                <img src="../../assets/images/products/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="img-fluid">
                                            <?php else: ?>
                                                <i class="fas fa-image no-image-icon"></i>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                        <small class="text-muted text-truncate d-block" style="max-width: 300px;">
                                            <?= htmlspecialchars(substr($product['description'], 0, 60)) . (strlen($product['description']) > 60 ? '...' : '') ?>
                                        </small>
                                    </td>
                                    <td class="price-cell">Rp <?= number_format($product['price'], 0, ',', '.') ?></td>
                                    <td class="stock-cell <?= $product['stock'] > 20 ? 'high' : ($product['stock'] > 5 ? 'medium' : 'low') ?>">
                                        <?= $product['stock'] ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $product['criteria_count'] > 0 ? 'success' : 'warning' ?>">
                                            <?= $product['criteria_count'] ?> criteria
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= date('d M Y', strtotime($product['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="product_edit.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="products.php?delete=<?= $product['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                            <a href="product_criteria.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-info" title="Set Criteria">
                                                <i class="fas fa-sliders-h"></i>
                                            </a>
                                            <a href="product_view.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            
            var forms = document.querySelectorAll('.needs-validation')
            
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>