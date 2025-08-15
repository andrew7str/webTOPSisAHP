<?php
require_once '../config/database.php';
require_once 'includes/auth_check.php';

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        // Check if username or email already exists
        $query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Username or email already exists'
            ];
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $query = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$username, $email, $hashed_password, $role]);
            
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'User added successfully'
            ];
            header('Location: users.php');
            exit;
        }
    } else {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => implode('<br>', $errors)
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User - TOPSIS & AHP Shop</title>
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
        
        .password-strength {
            height: 4px;
            background-color: #e9ecef;
            margin-top: 0.5rem;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            background-color: var(--danger);
            transition: width 0.3s ease;
        }
        
        .password-hint {
            font-size: 0.75rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }
        
        .btn {
            border-radius: 6px;
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        
        .user-avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            display: block;
            border: 3px solid var(--light-gray);
            background-color: #f8f9fa;
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
                <h3><i class="fas fa-user-plus"></i> Add New User</h3>
                <a href="users.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            </div>
            <div class="card-body">
                <form method="POST" id="userForm">
                    <div class="text-center mb-4">
                        <img src="https://ui-avatars.com/api/?name=New+User&background=random&color=fff&size=200" 
                             alt="New User" 
                             class="user-avatar-preview"
                             id="avatarPreview">
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <div class="password-hint">
                            Password must be at least 6 characters
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="text-danger small mt-1" id="passwordMatchMessage"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="customer" selected>Customer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add User
                        </button>
                        <a href="users.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update avatar preview when username changes
        document.getElementById('username').addEventListener('input', function() {
            const username = this.value.trim() || 'New User';
            document.getElementById('avatarPreview').src = 
                `https://ui-avatars.com/api/?name=${encodeURIComponent(username)}&background=random&color=fff&size=200`;
        });
        
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            let strength = 0;
            
            // Length check
            if (password.length > 5) strength += 25;
            if (password.length > 8) strength += 25;
            
            // Complexity checks
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            
            // Update strength bar
            strengthBar.style.width = strength + '%';
            
            // Update color based on strength
            if (strength < 50) {
                strengthBar.style.backgroundColor = 'var(--danger)';
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = 'var(--warning)';
            } else {
                strengthBar.style.backgroundColor = 'var(--success)';
            }
        });
        
        // Password match validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const messageElement = document.getElementById('passwordMatchMessage');
            
            if (confirmPassword && password !== confirmPassword) {
                messageElement.textContent = 'Passwords do not match';
            } else {
                messageElement.textContent = '';
            }
        });
        
        // Form validation before submission
        document.getElementById('userForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                document.getElementById('confirm_password').focus();
            }
        });
    </script>
</body>
</html>