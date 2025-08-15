<?php
require_once '../config/database.php';
require_once 'includes/auth_check.php';

$database = new Database();
$db = $database->getConnection();

// Handle role change
if (isset($_GET['change_role'])) {
    $user_id = $_GET['id'];
    $new_role = $_GET['role'];
    
    // Prevent changing own role
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'You cannot change your own role!'
        ];
        header('Location: users.php');
        exit;
    }
    
    $query = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$new_role, $user_id]);
    
    $_SESSION['message'] = [
        'type' => 'success',
        'text' => 'User role updated successfully'
    ];
    header('Location: users.php');
    exit;
}

// Get all users
$query = "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC";
$stmt = $db->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - TOPSIS & AHP Shop</title>
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
        
        .badge-admin {
            background-color: #d3f9d8;
            color: var(--success);
        }
        
        .badge-customer {
            background-color: #d0ebff;
            color: #1971c2;
        }
        
        .role-toggle {
            display: flex;
            border-radius: 50px;
            overflow: hidden;
            border: 1px solid var(--light-gray);
            background-color: white;
        }
        
        .role-toggle-btn {
            padding: 0.375rem 0.75rem;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .role-toggle-btn.active {
            background-color: var(--primary);
            color: white;
        }
        
        .role-toggle-btn:first-child {
            border-right: 1px solid var(--light-gray);
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
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 0.75rem;
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-sm {
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
                <h3><i class="fas fa-users"></i> Manage Users</h3>
                <a href="user_add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add User
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                                        <h5 class="text-muted">No users found</h5>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $index => $user): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&background=random&color=fff" 
                                                 alt="<?= htmlspecialchars($user['username']) ?>" 
                                                 class="user-avatar">
                                            <?= htmlspecialchars($user['username']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-customer' ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                        
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <div class="role-toggle mt-2">
                                                <a href="users.php?change_role&id=<?= $user['id'] ?>&role=admin" 
                                                   class="role-toggle-btn <?= $user['role'] == 'admin' ? 'active' : '' ?>">
                                                    Admin
                                                </a>
                                                <a href="users.php?change_role&id=<?= $user['id'] ?>&role=customer" 
                                                   class="role-toggle-btn <?= $user['role'] == 'customer' ? 'active' : '' ?>">
                                                    Customer
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="user_edit.php?id=<?= $user['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="user_delete.php?id=<?= $user['id'] ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </a>
                                            <?php endif; ?>
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
        // Confirm before changing role
        document.querySelectorAll('.role-toggle-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to change this user\'s role?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>