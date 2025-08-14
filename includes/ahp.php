<?php
class AHP {
    private $criteria;
    private $pairwiseMatrix;
    private $normalizedMatrix;
    private $weights;
    private $consistencyRatio;
    
    public function __construct($criteria) {
        $this->criteria = $criteria;
        $this->initializePairwiseMatrix();
    }
    
    private function initializePairwiseMatrix() {
        $n = count($this->criteria);
        $this->pairwiseMatrix = array_fill(0, $n, array_fill(0, $n, 1));
    }
    
    public function setPairwiseComparison($i, $j, $value) {
        $this->pairwiseMatrix[$i][$j] = $value;
        $this->pairwiseMatrix[$j][$i] = 1 / $value;
    }
    
    public function calculateWeights() {
        $this->normalizeMatrix();
        $this->calculateWeightVector();
        $this->checkConsistency();
        
        return [
            'weights' => $this->weights,
            'consistency_ratio' => $this->consistencyRatio
        ];
    }
    
    private function normalizeMatrix() {
        $n = count($this->pairwiseMatrix);
        $this->normalizedMatrix = array_fill(0, $n, array_fill(0, $n, 0));
        
        $columnSums = array_fill(0, $n, 0);
        for ($j = 0; $j < $n; $j++) {
            for ($i = 0; $i < $n; $i++) {
                $columnSums[$j] += $this->pairwiseMatrix[$i][$j];
            }
        }
        
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $this->normalizedMatrix[$i][$j] = $this->pairwiseMatrix[$i][$j] / $columnSums[$j];
            }
        }
    }
    
    private function calculateWeightVector() {
        $n = count($this->normalizedMatrix);
        $this->weights = array_fill(0, $n, 0);
        
        for ($i = 0; $i < $n; $i++) {
            $this->weights[$i] = array_sum($this->normalizedMatrix[$i]) / $n;
        }
    }
    
    private function checkConsistency() {
        $n = count($this->pairwiseMatrix);
        $ri = [0, 0, 0.58, 0.9, 1.12, 1.24, 1.32, 1.41, 1.45, 1.49];
        
        $lambdaMax = 0;
        for ($i = 0; $i < $n; $i++) {
            $sum = 0;
            for ($j = 0; $j < $n; $j++) {
                $sum += $this->pairwiseMatrix[$i][$j] * $this->weights[$j];
            }
            $lambdaMax += $sum / $this->weights[$i];
        }
        $lambdaMax /= $n;
        
        $ci = ($lambdaMax - $n) / ($n - 1);
        $this->consistencyRatio = $ci / $ri[$n - 1];
    }
    
    public static function getComparisonScale() {
        return [
            1 => "Equal importance",
            3 => "Moderate importance",
            5 => "Strong importance",
            7 => "Very strong importance",
            9 => "Extreme importance",
            2 => "Between equal and moderate",
            4 => "Between moderate and strong",
            6 => "Between strong and very strong",
            8 => "Between very strong and extreme"
        ];
    }
}
?>