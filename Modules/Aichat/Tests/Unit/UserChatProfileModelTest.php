<?php

namespace Modules\Aichat\Tests\Unit;

use Modules\Aichat\Entities\UserChatProfile;
use Tests\TestCase;

class UserChatProfileModelTest extends TestCase
{
    public function test_sensitive_profile_fields_are_encrypted_via_casts()
    {
        $profile = new UserChatProfile();
        $profile->concerns_topics = 'Focus on pricing and margin risk.';
        $profile->preferences = 'Use concise bullet answers.';

        $attributes = $profile->getAttributes();

        $this->assertArrayHasKey('concerns_topics', $attributes);
        $this->assertArrayHasKey('preferences', $attributes);
        $this->assertNotSame('Focus on pricing and margin risk.', $attributes['concerns_topics']);
        $this->assertNotSame('Use concise bullet answers.', $attributes['preferences']);
        $this->assertSame('Focus on pricing and margin risk.', $profile->concerns_topics);
        $this->assertSame('Use concise bullet answers.', $profile->preferences);
    }
}

