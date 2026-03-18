<?php

namespace Modules\Aichat\Tests\Feature;

use App\User;
use App\Http\Middleware\AdminSidebarMenu;
use App\Http\Middleware\Language;
use App\Http\Middleware\SetSessionData;
use App\Http\Middleware\Timezone;
use Modules\Aichat\Utils\ChatProductQuoteWizardUtil;
use Modules\Aichat\Utils\ChatUtil;
use Tests\TestCase;

class ChatQuoteWizardResolutionControllerTest extends TestCase
{
    public function test_contacts_route_returns_403_without_quote_wizard_permission()
    {
        $this->withoutMiddleware([SetSessionData::class, Language::class, Timezone::class, AdminSidebarMenu::class]);
        $this->actingAs($this->makeUser(false, true));

        $response = $this->withSession(['user.business_id' => 44])
            ->get('/aichat/chat/conversations/00000000-0000-0000-0000-000000000111/quote-wizard/contacts?q=Acme');

        $response->assertStatus(403);
    }

    public function test_contacts_route_uses_session_business_scope_for_search()
    {
        $this->withoutMiddleware([SetSessionData::class, Language::class, Timezone::class, AdminSidebarMenu::class]);
        $this->actingAs($this->makeUser(true, true));

        $chatUtil = \Mockery::mock(ChatUtil::class);
        $chatUtil->shouldReceive('isChatEnabled')->once()->with(44)->andReturn(true);
        $this->app->instance(ChatUtil::class, $chatUtil);

        $wizardUtil = \Mockery::mock(ChatProductQuoteWizardUtil::class);
        $wizardUtil->shouldReceive('searchContacts')
            ->once()
            ->with(44, 'Acme', null)
            ->andReturn([
                ['id' => 5, 'label' => 'Acme - Alice'],
            ]);
        $this->app->instance(ChatProductQuoteWizardUtil::class, $wizardUtil);

        $response = $this->withSession(['user.business_id' => 44])
            ->get('/aichat/chat/conversations/00000000-0000-0000-0000-000000000111/quote-wizard/contacts?q=Acme');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    ['id' => 5, 'label' => 'Acme - Alice'],
                ],
            ]);
    }

    protected function makeUser(bool $canUseQuoteWizard, bool $canViewChat): User
    {
        return new class($canUseQuoteWizard, $canViewChat) extends User
        {
            protected bool $canUseQuoteWizard = false;

            protected bool $canViewChat = false;

            public function __construct(bool $canUseQuoteWizard = false, bool $canViewChat = false)
            {
                parent::__construct();
                $this->id = 1;
                $this->business_id = 44;
                $this->canUseQuoteWizard = $canUseQuoteWizard;
                $this->canViewChat = $canViewChat;
            }

            public function hasRole($roles, ?string $guard = null): bool
            {
                return false;
            }

            public function hasPermissionTo($permission, $guardName = null): bool
            {
                return $permission === 'aichat.quote_wizard.use'
                    ? $this->canUseQuoteWizard
                    : false;
            }

            public function checkPermissionTo($permission, $guardName = null): bool
            {
                return $this->hasPermissionTo($permission, $guardName);
            }

            public function can($ability, $arguments = [])
            {
                if ($ability === 'aichat.quote_wizard.use') {
                    return $this->canUseQuoteWizard;
                }

                if ($ability === 'aichat.chat.view') {
                    return $this->canViewChat;
                }

                return false;
            }
        };
    }
}
