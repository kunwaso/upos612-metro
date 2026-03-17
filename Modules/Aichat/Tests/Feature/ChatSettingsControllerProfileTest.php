<?php

namespace Modules\Aichat\Tests\Feature;

use App\User;
use Modules\Aichat\Entities\ChatSetting;
use Modules\Aichat\Http\Controllers\ChatSettingsController;
use Modules\Aichat\Http\Requests\Chat\UpdateChatBusinessSettingsRequest;
use Modules\Aichat\Http\Requests\Chat\UpdateUserChatProfileRequest;
use Modules\Aichat\Utils\ChatUtil;
use Tests\TestCase;

class ChatSettingsControllerProfileTest extends TestCase
{
    public function test_update_profile_uses_session_business_and_auth_user_scope()
    {
        $this->be($this->makeUser(true));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(UpdateUserChatProfileRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldReceive('validated')->andReturn([
            'display_name' => 'Ray',
            'timezone' => 'Asia/Bangkok',
            'concerns_topics' => 'Margin',
            'preferences' => 'Concise',
        ]);
        $request->shouldReceive('expectsJson')->andReturn(false);
        $request->shouldReceive('ajax')->andReturn(false);

        $profile = new class
        {
            public int $id = 12;

            public function fill(array $attributes)
            {
                return $this;
            }

            public function save(): bool
            {
                return true;
            }
        };

        $calledBusinessId = null;
        $calledUserId = null;

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('getOrCreateUserChatProfile')
            ->once()
            ->withArgs(function ($businessId, $userId) use (&$calledBusinessId, &$calledUserId) {
                $calledBusinessId = $businessId;
                $calledUserId = $userId;

                return true;
            })
            ->andReturn($profile);
        $chatUtil->shouldReceive('audit')->zeroOrMoreTimes();

        $controller = new ChatSettingsController($chatUtil);
        $response = $controller->updateProfile($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(44, (int) $calledBusinessId);
        $this->assertSame(1, (int) $calledUserId);
    }

    public function test_update_profile_returns_unauthorized_when_user_lacks_permission()
    {
        $this->be($this->makeUser(false));

        $request = \Mockery::mock(UpdateUserChatProfileRequest::class);

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldNotReceive('getOrCreateUserChatProfile');
        $chatUtil->shouldNotReceive('audit');

        $controller = new ChatSettingsController($chatUtil);
        $response = $controller->updateProfile($request);

        $payload = $response->getData(true);
        $this->assertFalse($payload['success']);
    }

    public function test_update_business_passes_reasoning_rules_to_chat_util()
    {
        $this->be($this->makeUser(true));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(UpdateChatBusinessSettingsRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldReceive('validated')->once()->andReturn([
            'enabled' => true,
            'reasoning_rules' => 'Start with one summary sentence, then answer.',
        ]);
        $request->shouldReceive('expectsJson')->andReturn(false);
        $request->shouldReceive('ajax')->andReturn(false);

        $settings = new ChatSetting();
        $settings->id = 8;

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('updateBusinessSettings')
            ->once()
            ->with(44, \Mockery::on(function ($payload) {
                return isset($payload['reasoning_rules'])
                    && $payload['reasoning_rules'] === 'Start with one summary sentence, then answer.';
            }))
            ->andReturn($settings);
        $chatUtil->shouldReceive('audit')->once();

        $controller = new ChatSettingsController($chatUtil);
        $response = $controller->updateBusiness($request);

        $this->assertSame(302, $response->getStatusCode());
    }

    protected function makeUser(bool $canSettings): User
    {
        return new class($canSettings) extends User
        {
            protected bool $canSettings;

            public function __construct(bool $canSettings)
            {
                parent::__construct();
                $this->id = 1;
                $this->business_id = 44;
                $this->canSettings = $canSettings;
            }

            public function can($ability, $arguments = [])
            {
                if ($ability === 'aichat.chat.settings') {
                    return $this->canSettings;
                }

                return false;
            }
        };
    }
}
