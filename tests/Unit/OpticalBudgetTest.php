<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Odp;

class OpticalBudgetTest extends TestCase
{
    /**
     * Test equal splitter attenuation loss values.
     */
    public function test_equal_splitter_losses(): void
    {
        $loss1x2 = Odp::getSplitterLoss('1:2');
        $this->assertEquals(3.5, $loss1x2['branch']);
        $this->assertEquals(3.5, $loss1x2['main']);

        $loss1x4 = Odp::getSplitterLoss('1:4');
        $this->assertEquals(7.0, $loss1x4['branch']);

        $loss1x8 = Odp::getSplitterLoss('1:8');
        $this->assertEquals(10.5, $loss1x8['branch']);

        $loss1x16 = Odp::getSplitterLoss('1:16');
        $this->assertEquals(14.0, $loss1x16['branch']);

        $loss1x32 = Odp::getSplitterLoss('1:32');
        $this->assertEquals(17.5, $loss1x32['branch']);
    }

    /**
     * Test unequal (asymmetric) splitter attenuation loss values.
     */
    public function test_unequal_splitter_losses(): void
    {
        $loss7030 = Odp::getSplitterLoss('70:30');
        $this->assertEquals(1.5, $loss7030['main']);
        $this->assertEquals(5.2, $loss7030['branch']);

        $loss8020 = Odp::getSplitterLoss('80:20');
        $this->assertEquals(1.0, $loss8020['main']);
        $this->assertEquals(7.0, $loss8020['branch']);

        $loss9010 = Odp::getSplitterLoss('90:10');
        $this->assertEquals(0.5, $loss9010['main']);
        $this->assertEquals(10.0, $loss9010['branch']);
    }

    /**
     * Test optical power calculation with fiber distance and splitter.
     */
    public function test_calculate_optical_power_output(): void
    {
        $odp = new Odp([
            'input_power' => 3.0,          // +3 dBm from SFP
            'fiber_distance' => 2.0,       // 2 km
            'fiber_loss_per_km' => 0.35,   // standard 0.35 dB/km @ 1490nm
            'splitter_ratio' => '1:8',     // 10.5 dB loss
        ]);

        $result = $odp->calculateOpticalPower();

        // Fiber loss = 2 * 0.35 = 0.7 dB
        $this->assertEquals(0.7, $result['fiber_loss']);
        // Power after fiber = 3.0 - 0.7 = 2.3 dBm
        $this->assertEquals(2.3, $result['power_after_fiber']);
        // Output power at subscriber = 2.3 - 10.5 = -8.2 dBm
        $this->assertEquals(-8.2, $result['output_power']);
        // Total loss = 0.7 + 10.5 = 11.2 dB
        $this->assertEquals(11.2, $result['total_loss']);
        // Status should be ideal (-8.2 dBm is within normal operating window)
        $this->assertEquals('ideal', $result['status_power']);
        $this->assertFalse($result['is_critical']);
    }

    /**
     * Test warning and critical optical power thresholds.
     */
    public function test_optical_power_warning_and_critical_thresholds(): void
    {
        // Critical: low power (-28 dBm)
        $weakOdp = new Odp([
            'input_power' => 3.0,
            'fiber_distance' => 10.0,      // 10 km = 3.5 dB loss
            'fiber_loss_per_km' => 0.35,
            'splitter_ratio' => '1:64',    // 21.0 dB loss
            // Expected output: 3.0 - 3.5 - 21.0 = -21.5 dBm
        ]);
        $weakResult = $weakOdp->calculateOpticalPower();
        $this->assertEquals('ideal', $weakResult['status_power']);

        // Extreme cascade leading to critical low power
        $criticalOdp = new Odp([
            'input_power' => -10.0,        // Already weak cascade input
            'fiber_distance' => 5.0,       // 5 * 0.35 = 1.75 dB loss
            'fiber_loss_per_km' => 0.35,
            'splitter_ratio' => '1:32',    // 17.5 dB loss
            // Expected output: -10 - 1.75 - 17.5 = -29.25 dBm (< -27 dBm)
        ]);
        $criticalResult = $criticalOdp->calculateOpticalPower();
        $this->assertEquals('critical', $criticalResult['status_power']);
        $this->assertTrue($criticalResult['is_critical']);
    }
}
