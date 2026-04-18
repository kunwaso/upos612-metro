<?php

namespace Tests\Feature;

use App\Business;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Essentials\Http\Controllers\EssentialsSettingsController;
use App\Utils\ModuleUtil;
use Tests\TestCase;

class EssentialsSettingsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropIfExists('business');
        Schema::dropIfExists('businesses');
        Schema::create('business', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('essentials_settings')->nullable();
            $table->timestamps();
        });

        DB::table('business')->insert([
            'id' => 44,
            'essentials_settings' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_update_persists_transcript_translation_engine_in_business_essentials_settings()
    {
        $moduleUtil = \Mockery::mock(ModuleUtil::class);
        $moduleUtil->shouldReceive('is_admin')
            ->once()
            ->andReturn(false);

        $controller = new EssentialsSettingsController($moduleUtil);
        $user = $this->makeSuperadminUser();
        $this->be($user);

        $request = Request::create('/hrm/settings', 'POST', [
            'groq_api_key' => 'gsk-test',
            'transcript_translation_engine' => 'py_googletrans',
        ]);
        $request->setLaravelSession($this->makeSession([
            'user.business_id' => 44,
        ]));
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $this->app->instance('request', $request);

        $controller->update($request);

        $settings = json_decode((string) Business::query()->findOrFail(44)->essentials_settings, true);

        $this->assertIsArray($settings);
        $this->assertSame('py_googletrans', $settings['transcript_translation_engine'] ?? null);
        $this->assertSame('gsk-test', $settings['groq_api_key'] ?? null);
    }

    protected function makeSuperadminUser(): User
    {
        return new class extends User
        {
            public function can($ability, $arguments = [])
            {
                if ($ability === 'superadmin') {
                    return true;
                }

                return false;
            }
        };
    }

    protected function makeSession(array $data)
    {
        $session = $this->app['session']->driver();
        $session->start();

        foreach ($data as $key => $value) {
            $session->put($key, $value);
        }

        return $session;
    }
}
