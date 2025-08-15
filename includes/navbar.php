<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Style untuk navbar */
        .navbar {
            background-color: #2c3e50;
            padding: 0.8rem 1rem;
            transition: all 0.3s ease;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: #ecf0f1 !important;
        }
        
        .navbar-nav .nav-link {
            color: #ecf0f1 !important;
            padding: 0.5rem 1rem;
            margin: 0 0.2rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link:focus {
            background-color: #34495e;
            color: #fff !important;
        }
        
        .navbar-nav .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background-color: #3498db;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .navbar-nav .nav-link:hover::after,
        .navbar-nav .nav-link:focus::after {
            width: 70%;
        }
        
        .dropdown-menu {
            background-color: #2c3e50;
            border: none;
            border-radius: 0 0 8px 8px;
        }
        
        .dropdown-item {
            color: #ecf0f1 !important;
            padding: 0.5rem 1rem;
        }
        
        .dropdown-item:hover,
        .dropdown-item:focus {
            background-color: #34495e;
            color: #fff !important;
        }
        
        .dropdown-divider {
            border-color: #34495e;
        }
        
        .badge {
            font-weight: 500;
            padding: 0.25em 0.6em;
        }
        
        /* Animasi toggle button */
        .navbar-toggler {
            border: none;
            padding: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
            transition: all 0.3s ease;
        }
        
        /* Responsive behavior */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background-color: #2c3e50;
                padding: 1rem;
                border-radius: 0 0 8px 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            
            .navbar-nav .nav-link {
                margin: 0.2rem 0;
                padding: 0.8rem 1rem;
            }
            
            .dropdown-menu {
                background-color: #34495e;
                margin-left: 1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">TOPSIS & AHP Shop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="fas fa-shopping-cart me-1"></i>
                                Cart <span class="badge bg-primary" id="cart-count">
                                    <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : '0' ?>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i>
                                <?= htmlspecialchars($_SESSION['username']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="orders.php"><i class="fas fa-history me-2"></i>My Orders</a></li>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="admin/dashboard.php"><i class="fas fa-cog me-2"></i>Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i>Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- JavaScript untuk toggle navbar -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        // Fungsi untuk toggle navbar
        document.addEventListener('DOMContentLoaded', function() {
            const navbarToggler = document.querySelector('.navbar-toggler');
            const navbarCollapse = document.querySelector('.navbar-collapse');
            
            navbarToggler.addEventListener('click', function() {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', !isExpanded);
                navbarCollapse.classList.toggle('show');
            });
            
            // Tutup navbar saat klik di luar pada mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 991.98) {
                    const isClickInside = navbarToggler.contains(event.target) || 
                                         navbarCollapse.contains(event.target);
                    if (!isClickInside && navbarCollapse.classList.contains('show')) {
                        navbarToggler.setAttribute('aria-expanded', 'false');
                        navbarCollapse.classList.remove('show');
                    }
                }
            });
        });
    </script>
</body>
</html>