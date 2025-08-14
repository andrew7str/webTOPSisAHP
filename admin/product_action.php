<?php
require_once '../config/database.php';
require_once 'includes/auth_check.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../../assets/images/";
            $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $image = uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $image;
            
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check === false) {
                $_SESSION['error'] = "File is not an image.";
                header('Location: products.php?action=add');
                exit;
            }
            
            if ($_FILES["image"]["size"] > 2000000) {
                $_SESSION['error'] = "Sorry, your file is too large.";
                header('Location: products.php?action=add');
                exit;
            }
            
            if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                $_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                header('Location: products.php?action=add');
                exit;
            }
            
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $_SESSION['error'] = "Sorry, there was an error uploading your file.";
                header('Location: products.php?action=add');
                exit;
            }
        }
        
        $query = "INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$name, $description, $price, $stock, $image])) {
            $_SESSION['message'] = "Product added successfully";
        } else {
            $_SESSION['error'] = "Failed to add product";
        }
        
        header('Location: products.php');
        exit;
    }
}
?>