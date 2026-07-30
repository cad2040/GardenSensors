<?php

namespace GardenSensors\Tests\Services;

use GardenSensors\Services\EcNutrientService;
use GardenSensors\Tests\TestCase;

class EcNutrientServiceTest extends TestCase
{
    private EcNutrientService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EcNutrientService($this->db);
    }

    public function testAvailableEcSubtractsBaseline(): void
    {
        $this->assertSame(1.2, $this->service->availableEc(1.5, 0.3));
        $this->assertSame(0.0, $this->service->availableEc(0.2, 0.3));
    }

    public function testEvaluateStateUnderfedOverfedBalanced(): void
    {
        $zones = [
            'ec_underfed' => 0.8,
            'ec_balanced_min' => 1.0,
            'ec_balanced_max' => 1.6,
            'ec_overfed' => 2.0,
        ];
        $this->assertSame(
            EcNutrientService::STATE_BALANCED,
            $this->service->evaluateState(1.3, null, $zones)
        );
        $this->assertSame(
            EcNutrientService::STATE_UNDERFED,
            $this->service->evaluateState(0.5, null, $zones)
        );
        $this->assertSame(
            EcNutrientService::STATE_OVERFED,
            $this->service->evaluateState(2.5, null, $zones)
        );
        $this->assertSame(
            EcNutrientService::STATE_UNDERFED,
            $this->service->evaluateState(1.2, -0.1, $zones)
        );
    }

    public function testTrendSlopeOnDailyMeans(): void
    {
        $slope = $this->service->trendSlope([
            '2026-01-01' => 1.5,
            '2026-01-02' => 1.3,
            '2026-01-03' => 1.1,
        ]);
        $this->assertNotNull($slope);
        $this->assertLessThan(0, $slope);
    }

    public function testAlertMessages(): void
    {
        $this->assertStringContainsString(
            'fertilizer',
            $this->service->alertMessage(EcNutrientService::STATE_UNDERFED, 'Tomato', true)
        );
        $this->assertStringContainsString(
            'Flush',
            $this->service->alertMessage(EcNutrientService::STATE_OVERFED, 'Tomato')
        );
    }
}
