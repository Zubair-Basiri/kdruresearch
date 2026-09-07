<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResearchForecastingService
{
    protected int $minHistoricalYears = 3;
    protected int $defaultHorizon = 3;
    protected int $maxHorizon = 5;

    public function forecast(array $historicalData, int $forecastHorizon = 3, float $confidenceLevel = 0.95): array
    {
        ksort($historicalData);

        $years = array_keys($historicalData);
        $values = array_values($historicalData);

        // Cast years to integers and values to floats
        $years = array_map('intval', $years);
        $values = array_map('floatval', $values);

        $n = count($years);

        if ($n < $this->minHistoricalYears) {
            return [
                'success' => false,
                'message' => "Insufficient historical data. At least {$this->minHistoricalYears} years are required.",
                'method' => 'none',
            ];
        }

        $result = $this->linearRegression($years, $values);

        if (!$result['success']) {
            return $result;
        }

        $lastYear = end($years);
        $forecastYears = range($lastYear + 1, $lastYear + $forecastHorizon);

        $forecastValues = [];
        $intervals = [];

        foreach ($forecastYears as $year) {
            $predicted = $result['slope'] * $year + $result['intercept'];
            $forecastValues[] = [
                'year' => $year,
                'value' => round($predicted, 0),
            ];

            $interval = $this->predictionInterval($predicted, $result['residual_std_error'], $confidenceLevel);
            $intervals[] = [
                'year' => $year,
                'lower' => round($interval['lower'], 0),
                'upper' => round($interval['upper'], 0),
            ];
        }

        return [
            'success' => true,
            'method' => 'linear_regression',
            'historical' => $historicalData,
            'forecast' => $forecastValues,
            'intervals' => $intervals,
            'slope' => $result['slope'],
            'intercept' => $result['intercept'],
            'r_squared' => $result['r_squared'],
            'residual_std_error' => $result['residual_std_error'],
            'horizon' => $forecastHorizon,
        ];
    }

    protected function linearRegression(array $x, array $y): array
    {
        $n = count($x);
        if ($n < 2) {
            return ['success' => false, 'message' => 'At least 2 data points required for regression.'];
        }

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }

        $denominator = ($n * $sumX2 - $sumX * $sumX);
        if ($denominator == 0) {
            return ['success' => false, 'message' => 'Denominator is zero.'];
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $intercept = ($sumY - $slope * $sumX) / $n;

        $meanY = $sumY / $n;
        $ssTot = 0;
        $ssRes = 0;
        for ($i = 0; $i < $n; $i++) {
            $predicted = $slope * $x[$i] + $intercept;
            $ssTot += pow($y[$i] - $meanY, 2);
            $ssRes += pow($y[$i] - $predicted, 2);
        }
        $rSquared = $ssTot > 0 ? 1 - ($ssRes / $ssTot) : 0;
        $residualStdError = ($n - 2) > 0 ? sqrt($ssRes / ($n - 2)) : 0;

        return [
            'success' => true,
            'slope' => $slope,
            'intercept' => $intercept,
            'r_squared' => $rSquared,
            'residual_std_error' => $residualStdError,
        ];
    }

    protected function predictionInterval($predicted, $residualStdError, $confidenceLevel = 0.95): array
    {
        $z = $this->zScore($confidenceLevel);
        $margin = $z * $residualStdError;
        return [
            'lower' => $predicted - $margin,
            'upper' => $predicted + $margin,
        ];
    }

    protected function zScore($confidenceLevel): float
    {
        $map = [
            0.80 => 1.28,
            0.85 => 1.44,
            0.90 => 1.645,
            0.95 => 1.96,
            0.98 => 2.33,
            0.99 => 2.576,
        ];
        $closest = null;
        $closestDiff = INF;
        foreach ($map as $level => $z) {
            $diff = abs($confidenceLevel - $level);
            if ($diff < $closestDiff) {
                $closestDiff = $diff;
                $closest = $z;
            }
        }
        return $closest ?? 1.96;
    }

    public function getReliability($rSquared, $n): string
    {
        if ($n < $this->minHistoricalYears) {
            return 'insufficient_data';
        }
        if ($rSquared > 0.7 && $n >= 5) {
            return 'high';
        } elseif ($rSquared > 0.4 && $n >= 4) {
            return 'moderate';
        } else {
            return 'low';
        }
    }

    public function validateHorizon($horizon): int
    {
        $horizon = (int) $horizon;
        if ($horizon < 1) $horizon = 1;
        if ($horizon > $this->maxHorizon) $horizon = $this->maxHorizon;
        return $horizon;
    }
}