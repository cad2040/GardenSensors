<?php

namespace GardenSensors\Services;

use GardenSensors\Core\Database;

/**
 * EC trend-based nutrient proxy (not isolated nitrate).
 */
class EcNutrientService
{
    public const STATE_UNDERFED = 'underfed';
    public const STATE_BALANCED = 'balanced';
    public const STATE_OVERFED = 'overfed';

    private const TREND_SLOPE_THRESHOLD = -0.05;
    private const MIN_TREND_POINTS = 3;

    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function availableEc(float $soilEc, float $baselineWaterEc): float
    {
        return max(0.0, round($soilEc - $baselineWaterEc, 3));
    }

    /**
     * @return array<string, float> date Y-m-d => mean soil EC
     */
    public function rollingDailyMeans(int $sensorId, int $days = 7): array
    {
        $rows = $this->db->query(
            "SELECT DATE(created_at) AS day, AVG(value) AS mean_val
             FROM readings
             WHERE sensor_id = ?
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [$sensorId, $days]
        );

        $means = [];
        foreach ($rows as $row) {
            $means[$row['day']] = round((float)$row['mean_val'], 3);
        }
        return $means;
    }

    /**
     * Linear regression slope (mS/cm per day) on ordered daily means.
     */
    public function trendSlope(array $dailyMeans): ?float
    {
        if (count($dailyMeans) < self::MIN_TREND_POINTS) {
            return null;
        }

        $xs = [];
        $ys = [];
        $i = 0;
        foreach ($dailyMeans as $mean) {
            $xs[] = $i++;
            $ys[] = (float)$mean;
        }

        $n = count($xs);
        $sumX = array_sum($xs);
        $sumY = array_sum($ys);
        $sumXY = 0;
        $sumXX = 0;
        for ($j = 0; $j < $n; $j++) {
            $sumXY += $xs[$j] * $ys[$j];
            $sumXX += $xs[$j] * $xs[$j];
        }
        $denom = ($n * $sumXX) - ($sumX * $sumX);
        if (abs($denom) < 1e-9) {
            return null;
        }

        return round((($n * $sumXY) - ($sumX * $sumY)) / $denom, 4);
    }

    /**
     * @param array{ec_underfed?:float,ec_balanced_min?:float,ec_balanced_max?:float,ec_overfed?:float} $plantZones
     */
    public function evaluateState(float $availableEc, ?float $slope, array $plantZones): string
    {
        $underfed = (float)($plantZones['ec_underfed'] ?? 0.80);
        $balancedMin = (float)($plantZones['ec_balanced_min'] ?? 1.00);
        $balancedMax = (float)($plantZones['ec_balanced_max'] ?? 1.60);
        $overfed = (float)($plantZones['ec_overfed'] ?? 2.00);

        if ($availableEc > $overfed) {
            return self::STATE_OVERFED;
        }

        $downwardTrend = $slope !== null && $slope < self::TREND_SLOPE_THRESHOLD;
        if ($availableEc < $underfed || $downwardTrend) {
            return self::STATE_UNDERFED;
        }

        if ($availableEc >= $balancedMin && $availableEc <= $balancedMax) {
            return self::STATE_BALANCED;
        }

        if ($availableEc < $balancedMin) {
            return self::STATE_UNDERFED;
        }

        return self::STATE_BALANCED;
    }

    public function alertMessage(string $state, string $plantName, bool $trendDriven = false): string
    {
        switch ($state) {
            case self::STATE_UNDERFED:
                if ($trendDriven) {
                    return "EC trend is dropping for {$plantName}. Time to add nitrogen-heavy fertilizer.";
                }
                return "Available nutrient EC is low for {$plantName}. Consider adding fertilizer.";
            case self::STATE_OVERFED:
                return "EC is too high for {$plantName}. Flush soil with plain water to reduce salt buildup.";
            default:
                return "Nutrient EC is in a healthy range for {$plantName}.";
        }
    }

    /**
     * Full evaluation for one EC sensor linked to a plant.
     *
     * @return array<string, mixed>
     */
    public function evaluateSensor(
        int $sensorId,
        float $latestSoilEc,
        float $baselineWaterEc,
        array $plantZones
    ): array {
        $available = $this->availableEc($latestSoilEc, $baselineWaterEc);
        $daily = $this->rollingDailyMeans($sensorId);
        $availableDaily = [];
        foreach ($daily as $day => $soilMean) {
            $availableDaily[$day] = $this->availableEc($soilMean, $baselineWaterEc);
        }
        $slope = $this->trendSlope($availableDaily);
        $state = $this->evaluateState($available, $slope, $plantZones);
        $trendDriven = $state === self::STATE_UNDERFED
            && $slope !== null
            && $slope < self::TREND_SLOPE_THRESHOLD
            && $available >= (float)($plantZones['ec_underfed'] ?? 0.80);

        return [
            'soil_ec' => round($latestSoilEc, 2),
            'available_ec' => round($available, 2),
            'slope' => $slope,
            'state' => $state,
            'trend_driven' => $trendDriven,
            'daily_means' => $availableDaily,
        ];
    }

    public function dashboardStatusFromState(string $state): string
    {
        switch ($state) {
            case self::STATE_UNDERFED:
                return 'low';
            case self::STATE_OVERFED:
                return 'high';
            default:
                return 'ideal';
        }
    }
}
