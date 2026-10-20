<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebhookSecurityTest extends TestCase
{
    /**
     * Test Xendit webhook rejects unauthorized requests without token.
     */
    public function test_xendit_webhook_rejects_missing_external_id(): void
    {
        $response = $this->postJson('/api/webhook/xendit', [
            'id' => 'xendit_invoice_123',
            'status' => 'PAID',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid request',
        ]);
    }

    public function test_duitku_webhook_rejects_missing_order_id(): void
    {
        $response = $this->postJson('/api/webhook/duitku', [
            'resultCode' => '00',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid request',
        ]);
    }

    public function test_midtrans_webhook_rejects_missing_order_id(): void
    {
        $response = $this->postJson('/api/webhook/midtrans', [
            'transaction_status' => 'settlement',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid request',
        ]);
    }

    public function test_tripay_webhook_rejects_missing_payload(): void
    {
        $response = $this->postJson('/api/webhook/tripay', []);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid request',
        ]);
    }
}
