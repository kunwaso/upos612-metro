<?php

namespace Modules\Aichat\Tests\Unit;

use Modules\Aichat\Utils\ChatModelSerializer;
use Modules\Aichat\Utils\ChatSensitiveDataRedactor;
use Tests\TestCase;

class ChatSecurityComponentsTest extends TestCase
{
    public function test_redactor_masks_secret_keys_and_token_patterns(): void
    {
        $redactor = new ChatSensitiveDataRedactor();
        $input = [
            'password' => 'P@ssw0rd',
            'remember_token' => 'abc',
            'note' => 'Bearer eyJabc.def.ghi',
            'nested' => [
                'api_key' => 'sk-12345678901234567890',
                'label' => 'visible',
            ],
        ];

        $redacted = $redactor->redactArray($input);

        $this->assertSame('[redacted]', data_get($redacted, 'password'));
        $this->assertSame('[redacted]', data_get($redacted, 'remember_token'));
        $this->assertSame('[redacted]', data_get($redacted, 'nested.api_key'));
        $this->assertSame('visible', data_get($redacted, 'nested.label'));
        $this->assertStringNotContainsString('eyJabc.def.ghi', (string) data_get($redacted, 'note'));
    }

    public function test_model_serializer_applies_strict_allowlist_then_redacts(): void
    {
        config()->set('aichat.security.serializer.strict_allowlist', true);
        config()->set('aichat.security.serializer.allowlists.quote_wizard_contact', ['id', 'name', 'label', 'access_token']);

        $serializer = new ChatModelSerializer(new ChatSensitiveDataRedactor());
        $serialized = $serializer->serialize('quote_wizard_contact', [
            'id' => 9,
            'name' => 'Alice',
            'label' => 'Alice Co',
            'access_token' => 'Bearer abc123',
            'password' => 'must-not-pass',
            'extra_field' => 'drop-me',
        ]);

        $this->assertSame(9, data_get($serialized, 'id'));
        $this->assertSame('Alice', data_get($serialized, 'name'));
        $this->assertSame('Alice Co', data_get($serialized, 'label'));
        $this->assertSame('[redacted]', data_get($serialized, 'access_token'));
        $this->assertArrayNotHasKey('password', $serialized);
        $this->assertArrayNotHasKey('extra_field', $serialized);
    }
}

