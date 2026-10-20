<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GenieAcsService;
use Illuminate\Support\Facades\Http;

class GenieAcsHuaweiRemediationTest extends TestCase
{
    /**
     * Test that fixHuaweiServiceList constructs appropriate TR-069 SetParameterValues payload.
     */
    public function test_fix_huawei_service_list_payload_construction(): void
    {
        $deviceId = '00259E-HG8245H-HWTC12345678';

        // Mock GenieACS NBI responses
        Http::fake([
            // Mock getBrandByDeviceId / devices query
            '*/devices?query=*' => Http::response([
                [
                    '_id' => $deviceId,
                    'InternetGatewayDevice' => [
                        'X_HW_Security' => [],
                        'WANDevice' => [
                            '1' => [
                                'WANConnectionDevice' => [
                                    '1' => [
                                        'WANPPPConnection' => [
                                            '1' => [
                                                'Name' => ['_value' => 'PPPoE_WAN'],
                                                'Username' => ['_value' => 'user_test'],
                                                'X_HW_SERVICELIST' => ['_value' => 'TR069_INTERNET'], // Buggy dual state
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),

            // Mock tasks endpoint
            '*/tasks*' => Http::response(['_id' => 'mock_task_123'], 200),
        ]);

        $service = new GenieAcsService();
        $result = $service->fixHuaweiServiceList($deviceId, 120);

        $this->assertTrue($result['success']);
        $this->assertEquals(120, $result['inform_interval']);
        $this->assertEquals(1, $result['fixed_wans']);

        // Verify that HTTP post to tasks included the corrected parameters
        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/tasks')) {
                return false;
            }
            $body = $request->data();
            $paramValues = $body['parameterValues'] ?? [];

            // Check PeriodicInformInterval is 120
            $informIntervalCorrect = false;
            $serviceListCorrect = false;

            foreach ($paramValues as [$key, $val, $type]) {
                if ($key === 'InternetGatewayDevice.ManagementServer.PeriodicInformInterval' && $val === 120) {
                    $informIntervalCorrect = true;
                }
                if (str_ends_with($key, '.X_HW_SERVICELIST') && $val === 'INTERNET') {
                    $serviceListCorrect = true;
                }
            }

            return $informIntervalCorrect && $serviceListCorrect;
        });
    }
}
