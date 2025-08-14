<?php
require_once '../../config/database.php';
require_once '../../includes/ahp.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Ambil kriteria
$query = "SELECT * FROM criteria ORDER BY id";
$stmt = $db->prepare($query);
$stmt->execute();
$criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ahp = new AHP($criteria);
$comparisonScale = AHP::getComparisonScale();

// Proses form jika ada submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $n = count($criteria);
    
    // Set pairwise comparisons
    for ($i = 0; $i < $n; $i++) {
        for ($j = $i + 1; $j < $n; $j++) {
            $key = "compare_{$i}_{$j}";
            if (isset($_POST[$key])) {
                $value = floatval($_POST[$key]);
                $ahp->setPairwiseComparison($i, $j, $value);
            }
        }
    }
    
    // Hitung bobot
    $result = $ahp->calculateWeights();
    $weights = $result['weights'];
    $cr = $result['consistency_ratio'];
    
    // Update bobot di database
    foreach ($criteria as $index => $criterion) {
        $query = "UPDATE criteria SET weight = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$weights[$index], $criterion['id']]);
    }
    
    $message = "Weights updated successfully! Consistency Ratio: " . number_format($cr, 4);
    if ($cr > 0.1) {
        $message .= " (Warning: CR > 0.1 - Comparisons may be inconsistent)";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criteria Weights with AHP</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Determine Criteria Weights using AHP</h2>
        
        <?php if (isset($message)): ?>
        <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>
        
        <form method="post">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Comparison</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < count($criteria); $i++): ?>
                        <?php for ($j = $i + 1; $j < count($criteria); $j++): ?>
                        <tr>
                            <td>
                                <?= $criteria[$i]['name'] ?> compared to <?= $criteria[$j]['name'] ?>
                            </td>
                            <td>
                                <select name="compare_<?= $i ?>_<?= $j ?>" class="form-control">
                                    <?php foreach ($comparisonScale as $value => $label): ?>
                                        <option value="<?= $value ?>"><?= $value ?> - <?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    <?php endfor; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Calculate Weights</button>
        </form>
        
        <h3 class="mt-5">Current Criteria Weights</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Criteria</th>
                    <th>Type</th>
                    <th>Weight</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($criteria as $criterion): ?>
                <tr>
                    <td><?= $criterion['name'] ?></td>
                    <td><?= $criterion['type'] ?></td>
                    <td><?= number_format($criterion['weight'], 4) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>