<?php
require_once '../config/database.php';
require_once 'includes/auth_check.php';

$database = new Database();
$db = $database->getConnection();

// Get user ID from URL
$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => 'User ID not specified'
    ];
    header('Location: users.php');
    exit;
}

// Fetch user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['message'] = [
        'type' => 'danger',
        'text' => 'User not found'
    ];
    header('Location: users.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    
    // Basic validation
    if (empty($username) || empty($email)) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Username and email are required'
        ];
    } else {
        // Update user data
        $query = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $email, $role, $user_id]);
        
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'User updated successfully'
        ];
        header('Location: users.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - TOPSIS & AHP Shop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #eef2ff;
            --danger: #e63946;
            --success: #2b8a3e;
            --warning: #e67700;
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
        
        .card-body {
            padding: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .form-control {
            border-radius: 6px;
            padding: 0.75rem 1rem;
            border: 1px solid var(--light-gray);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .btn {
            border-radius: 6px;
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 3px solid var(--light-gray);
        }
        
        .role-badge {
            padding: 0.5rem 0.75rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.875rem;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .badge-admin {
            background-color: #d3f9d8;
            color: var(--success);
        }
        
        .badge-customer {
            background-color: #d0ebff;
            color: #1971c2;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
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
                <h3><i class="fas fa-user-edit"></i> Edit User</h3>
                <a href="users.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&background=random&color=fff&size=128" 
                         alt="<?= htmlspecialchars($user['username']) ?>" 
                         class="user-avatar">
                    <div class="role-badge <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-customer' ?>">
                        <?= ucfirst($user['role']) ?> User
                    </div>
                </div>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Account Created</label>
                        <div class="form-control-plaintext">
                            <?= date('F j, Y', strtotime($user['created_at'])) ?>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="users.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <a href="user_delete.php?id=<?= $user['id'] ?>" 
                               class="btn btn-outline-danger ms-auto"
                               onclick="return confirm('Are you sure you want to delete this user?')">
                                <i class="fas fa-trash-alt"></i> Delete User
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Confirm before leaving page with unsaved changes
        const form = document.querySelector('form');
        let isFormDirty = false;
        
        form.querySelectorAll('input, select').forEach(element => {
            element.addEventListener('input', () => {
                isFormDirty = true;
            });
        });
        
        window.addEventListener('beforeunload', (e) => {
            if (isFormDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        // Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>