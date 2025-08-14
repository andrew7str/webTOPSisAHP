<?php
class TOPSIS {
    private $alternatives;
    private $criteria;
    private $weights;
    private $decisionMatrix;
    
    public function __construct($alternatives, $criteria, $weights) {
        $this->alternatives = $alternatives;
        $this->criteria = $criteria;
        $this->weights = $weights;
        $this->initializeDecisionMatrix();
    }
    
    private function initializeDecisionMatrix() {
        foreach ($this->alternatives as $alt) {
            $row = [];
            foreach ($this->criteria as $criterion) {
                $row[] = $alt['criteria_values'][$criterion['id']];
            }
            $this->decisionMatrix[] = $row;
        }
    }
    
    public function calculate() {
        $normalizedMatrix = $this->normalizeMatrix();
        $weightedMatrix = $this->applyWeights($normalizedMatrix);
        $idealSolutions = $this->determineIdealSolutions($weightedMatrix);
        $distances = $this->calculateDistances($weightedMatrix, $idealSolutions);
        $preferenceValues = $this->calculatePreferenceValues($distances);
        
        return $this->rankAlternatives($preferenceValues);
    }
    
    private function normalizeMatrix() {
        $normalized = [];
        $columns = count($this->criteria);
        
        for ($j = 0; $j < $columns; $j++) {
            $column = array_column($this->decisionMatrix, $j);
            $sumSquares = array_sum(array_map(function($x) { return $x * $x; }, $column));
            $sqrtSum = sqrt($sumSquares);
            
            for ($i = 0; $i < count($this->decisionMatrix); $i++) {
                $normalized[$i][$j] = $sqrtSum != 0 ? $this->decisionMatrix[$i][$j] / $sqrtSum : 0;
            }
        }
        
        return $normalized;
    }
    
    private function applyWeights($matrix) {
        $weighted = [];
        for ($i = 0; $i < count($matrix); $i++) {
            for ($j = 0; $j < count($matrix[$i]); $j++) {
                $weighted[$i][$j] = $matrix[$i][$j] * $this->weights[$j];
            }
        }
        return $weighted;
    }
    
    private function determineIdealSolutions($matrix) {
        $positiveIdeal = [];
        $negativeIdeal = [];
        $columns = count($this->criteria);
        
        for ($j = 0; $j < $columns; $j++) {
            $column = array_column($matrix, $j);
            if ($this->criteria[$j]['type'] == 'benefit') {
                $positiveIdeal[$j] = max($column);
                $negativeIdeal[$j] = min($column);
            } else {
                $positiveIdeal[$j] = min($column);
                $negativeIdeal[$j] = max($column);
            }
        }
        
        return ['positive' => $positiveIdeal, 'negative' => $negativeIdeal];
    }
    
    private function calculateDistances($matrix, $idealSolutions) {
        $distances = [];
        for ($i = 0; $i < count($matrix); $i++) {
            $positiveSum = 0;
            $negativeSum = 0;
            
            for ($j = 0; $j < count($matrix[$i]); $j++) {
                $positiveSum += pow($matrix[$i][$j] - $idealSolutions['positive'][$j], 2);
                $negativeSum += pow($matrix[$i][$j] - $idealSolutions['negative'][$j], 2);
            }
            
            $distances[$i] = [
                'positive' => sqrt($positiveSum),
                'negative' => sqrt($negativeSum)
            ];
        }
        
        return $distances;
    }
    
    private function calculatePreferenceValues($distances) {
        $preferences = [];
        foreach ($distances as $i => $d) {
            $preferences[$i] = $d['negative'] / ($d['positive'] + $d['negative']);
        }
        return $preferences;
    }
    
    private function rankAlternatives($preferences) {
        $ranked = [];
        foreach ($preferences as $i => $value) {
            $ranked[] = [
                'alternative' => $this->alternatives[$i],
                'score' => $value
            ];
        }
        
        usort($ranked, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $ranked;
    }
}
?>