<?php

namespace Tests\Feature;

use App\Models\ChatFlow;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class VoiceCanvasTest extends TestCase
{
    use RefreshDatabase;

    private function widget(bool $voice = true): Widget
    {
        $u = $this->subscribed(User::factory()->create());

        return Widget::create(['user_id' => $u->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true, 'voice_input_enabled' => $voice]);
    }

    public function test_recorded_voice_is_transcribed_with_whisper(): void
    {
        $w = $this->widget();
        config(['ai.groq.key' => null, 'ai.openai.key' => null]);
        $this->getJson("/api/widget/{$w->uuid}/config")->assertJsonPath('widget.voice_upload', false);
        $audio = UploadedFile::fake()->create('speech.webm', 20, 'audio/webm');
        $this->post("/api/widget/{$w->uuid}/transcribe", ['audio' => $audio], ['Accept' => 'application/json'])->assertStatus(503);

        config(['ai.groq.key' => 'gsk_test']);
        Http::fake(['api.groq.com/*' => Http::response(['text' => ' Do you ship to Ireland? '])]);
        $this->getJson("/api/widget/{$w->uuid}/config")->assertJsonPath('widget.voice_upload', true);
        $this->post("/api/widget/{$w->uuid}/transcribe", ['audio' => UploadedFile::fake()->createWithContent('speech.webm', 'OggS-fake-audio-bytes'), 'lang' => 'en'], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('text', 'Do you ship to Ireland?');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'audio/transcriptions'));

        $off = $this->widget(false);
        $this->post("/api/widget/{$off->uuid}/transcribe", ['audio' => UploadedFile::fake()->create('s.webm', 5)], ['Accept' => 'application/json'])->assertForbidden();
    }

    public function test_canvas_positions_are_saved_and_end_step_stops_the_flow(): void
    {
        $w = $this->widget();
        $this->actingAs($w->user)->post(route('widgets.flows.save', $w), [
            'name' => 'Q', 'trigger_keywords' => 'quote', 'match_mode' => 'any', 'is_active' => '1',
            'steps' => [['type' => 'message', 'text' => 'Hi', 'x' => 40, 'y' => 30], ['type' => 'end', 'x' => 300, 'y' => 150], ['type' => 'message', 'text' => 'never', 'x' => 40, 'y' => 270]],
        ])->assertSessionHasNoErrors();
        $f = ChatFlow::sole();
        $this->assertSame([300, 150], [(int) $f->steps[1]['x'], (int) $f->steps[1]['y']]);
        $this->assertSame('Hi', app(\App\Services\FlowEngine::class)->run($f, 0, [])['reply']);
    }
}
