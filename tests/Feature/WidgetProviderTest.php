<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_saves_selected_provider_and_model(): void
    {
        $this->withoutVite()->withoutMiddleware([\App\Http\Middleware\CheckSubscription::class]);
        $user   = User::factory()->create();
        $widget = Widget::create(['user_id' => $user->id, 'name' => 'Site bot']);

        $this->actingAs($user)->get(route('widgets.edit', $widget))->assertOk()->assertSee('OpenRouter');

        $this->actingAs($user)->put(route('widgets.update', $widget), [
            'name'                => 'Site bot',
            'ai_provider'         => 'openrouter',
            'ai_model_openrouter' => 'deepseek/deepseek-chat',
            'ai_model_openai'     => 'gpt-4o', // hidden select for another provider: must be ignored
        ])->assertSessionHasNoErrors();

        $widget->refresh();
        $this->assertSame('openrouter', $widget->ai_provider);
        $this->assertSame('deepseek/deepseek-chat', $widget->ai_model);
    }
}
