<?php
require_once '../config/database.php';
require_once 'includes/auth_check.php';

$database = new Database();
$db = $database->getConnection();

if (isset($_GET['id']) && isset($_GET['role'])) {
    $user_id = $_GET['id'];
    $new_role = $_GET['role'];
    
    // Validasi role
    if (!in_array($new_role, ['admin', 'customer'])) {
        $_SESSION['error'] = "Invalid role specified";
        header('Location: users.php');
        exit;
    }
    
    // Update role
    $query = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$new_role, $user_id])) {
        $_SESSION['message'] = "User role updated successfully";
        
        // Jika mengubah role sendiri, update session
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['role'] = $new_role;
        }
    } else {
        $_SESSION['error'] = "Failed to update user role";
    }
}

header('Location: users.php');
exit;
?>