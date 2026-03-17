<?php

namespace Modules\Aichat\Tests\Feature;

use App\User;
use Modules\Aichat\Http\Controllers\ChatSettingsController;
use Modules\Aichat\Http\Requests\Chat\StoreTelegramBotRequest;
use Modules\Aichat\Utils\ChatUtil;
use Modules\Aichat\Utils\TelegramApiUtil;
use Tests\TestCase;

class ChatSettingsTelegramControllerTest extends TestCase
{
    public function test_store_telegram_bot_with_invalid_token_does_not_activate_bot()
    {
        $this->be($this->makeUser(true));

        $session = \Mockery::mock();
        $session->shouldReceive('get')->once()->with('user.business_id')->andReturn(44);

        $request = \Mockery::mock(StoreTelegramBotRequest::class);
        $request->shouldReceive('session')->andReturn($session);
        $request->shouldReceive('validated')->once()->andReturn([
            'bot_token' => 'invalid-token',
        ]);

        $telegramApi = \Mockery::mock(TelegramApiUtil::class);
        $telegramApi->shouldReceive('getMe')->once()->with('invalid-token')->andThrow(new \RuntimeException('Invalid token'));

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('getTelegramBotForBusiness')->once()->with(44)->andReturn(null);
        $chatUtil->shouldNotReceive('saveTelegramBot');
        $chatUtil->shouldNotReceive('deleteTelegramBot');

        $controller = new ChatSettingsController($chatUtil);
        $response = $controller->storeTelegramBot($request, $telegramApi);

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
