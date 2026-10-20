<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Olt;
use Illuminate\Support\Facades\Crypt;

class OltCredentialEncryptionTest extends TestCase
{
    /**
     * Test that new credentials set on Olt model are encrypted at rest.
     */
    public function test_olt_credentials_are_encrypted_on_assignment(): void
    {
        $olt = new Olt();
        $plainPassword = 'super-secret-password-123';
        $olt->telnet_password = $plainPassword;

        // Raw attribute stored internally should NOT be plaintext
        $rawAttributes = $olt->getAttributes();
        $this->assertNotEquals($plainPassword, $rawAttributes['telnet_password']);

        // The raw attribute should be a valid encrypted string
        $decrypted = Crypt::decryptString($rawAttributes['telnet_password']);
        $this->assertEquals($plainPassword, $decrypted);

        // Accessor should return decrypted value transparently
        $this->assertEquals($plainPassword, $olt->telnet_password);
    }

    /**
     * Test backward compatibility: existing legacy plaintext passwords do not crash decryption.
     */
    public function test_legacy_plaintext_credentials_fall_back_safely(): void
    {
        $olt = new Olt();
        // Simulate legacy unencrypted database row directly into attributes
        $legacyPlaintext = 'public123';
        $olt->setRawAttributes([
            'snmp_community' => $legacyPlaintext,
            'ssh_password' => 'zte_old_pass',
        ]);

        // Accessor must return the plaintext directly without throwing DecryptException
        $this->assertEquals($legacyPlaintext, $olt->snmp_community);
        $this->assertEquals('zte_old_pass', $olt->ssh_password);
    }
}
