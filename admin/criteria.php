<?php
require_once '../config/database.php';
require_once 'includes/auth_check.php';

$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_criteria'])) {
        $name = $_POST['name'];
        $weight = $_POST['weight'];
        $type = $_POST['type'];
        
        $query = "INSERT INTO criteria (name, weight, type) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $weight, $type]);
        
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Criteria added successfully'
        ];
        header('Location: criteria.php');
        exit;
    }
    
    if (isset($_POST['update_criteria'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $weight = $_POST['weight'];
        $type = $_POST['type'];
        
        $query = "UPDATE criteria SET name = ?, weight = ?, type = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $weight, $type, $id]);
        
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Criteria updated successfully'
        ];
        header('Location: criteria.php');
        exit;
    }
    
    if (isset($_GET['delete'])) {
        $id = $_GET['delete'];
        $query = "DELETE FROM criteria WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Criteria deleted successfully'
        ];
        header('Location: criteria.php');
        exit;
    }
}

// Get all criteria
$query = "SELECT * FROM criteria ORDER BY weight DESC";
$stmt = $db->query($query);
$criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total weight for validation
$totalWeight = array_sum(array_column($criteria, 'weight'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Criteria - TOPSIS & AHP Shop</title>
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
        
        .badge-benefit {
            background-color: #d3f9d8;
            color: var(--success);
        }
        
        .badge-cost {
            background-color: #ffd8d8;
            color: var(--danger);
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
        }
        
        .form-control {
            border-radius: 6px;
            padding: 0.75rem 1rem;
        }
        
        .total-weight {
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background-color: var(--primary-light);
            color: var(--primary);
            display: inline-block;
            margin-top: 1rem;
        }
        
        .total-weight.warning {
            background-color: #fff3bf;
            color: var(--warning);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .modal-content {
            border: none;
            border-radius: 10px;
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
                <h3><i class="fas fa-list-alt"></i> Manage Criteria</h3>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCriteriaModal">
                    <i class="fas fa-plus"></i> Add Criteria
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Criteria Name</th>
                                <th>Weight</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($criteria)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                                        <h5 class="text-muted">No criteria found</h5>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($criteria as $index => $c): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($c['name']) ?></td>
                                    <td><?= $c['weight'] ?></td>
                                    <td>
                                        <span class="badge <?= $c['type'] === 'benefit' ? 'badge-benefit' : 'badge-cost' ?>">
                                            <?= ucfirst($c['type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editCriteriaModal"
                                                    data-id="<?= $c['id'] ?>"
                                                    data-name="<?= htmlspecialchars($c['name']) ?>"
                                                    data-weight="<?= $c['weight'] ?>"
                                                    data-type="<?= $c['type'] ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <a href="criteria.php?delete=<?= $c['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this criteria?')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="total-weight <?= $totalWeight != 1 ? 'warning' : '' ?>">
                    Total Weight: <?= number_format($totalWeight, 2) ?>
                    <?php if ($totalWeight != 1): ?>
                        <i class="fas fa-exclamation-triangle ms-2"></i>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Criteria Modal -->
    <div class="modal fade" id="addCriteriaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Criteria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Criteria Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="weight" class="form-label">Weight (0-1)</label>
                            <input type="number" class="form-control" id="weight" name="weight" 
                                   step="0.01" min="0" max="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="benefit">Benefit</option>
                                <option value="cost">Cost</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_criteria" class="btn btn-primary">Save Criteria</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Criteria Modal -->
    <div class="modal fade" id="editCriteriaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Criteria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Criteria Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_weight" class="form-label">Weight (0-1)</label>
                            <input type="number" class="form-control" id="edit_weight" name="weight" 
                                   step="0.01" min="0" max="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_type" class="form-label">Type</label>
                            <select class="form-select" id="edit_type" name="type" required>
                                <option value="benefit">Benefit</option>
                                <option value="cost">Cost</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_criteria" class="btn btn-primary">Update Criteria</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit modal data
        document.getElementById('editCriteriaModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modal = this;
            
            modal.querySelector('#edit_id').value = button.getAttribute('data-id');
            modal.querySelector('#edit_name').value = button.getAttribute('data-name');
            modal.querySelector('#edit_weight').value = button.getAttribute('data-weight');
            modal.querySelector('#edit_type').value = button.getAttribute('data-type');
        });
        
        // Validate weight input
        document.querySelectorAll('input[name="weight"]').forEach(input => {
            input.addEventListener('change', function() {
                if (parseFloat(this.value) > 1) {
                    this.value = 1;
                } else if (parseFloat(this.value) < 0) {
                    this.value = 0;
                }
            });
        });
    </script>
</body>
</html>