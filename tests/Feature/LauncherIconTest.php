<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LauncherIconTest extends TestCase
{
    use RefreshDatabase;

    public function test_preset_and_validated_upload(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $w = Widget::create(['user_id' => $user->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true]);
        $save = fn (array $data) => $this->actingAs($user)->withoutMiddleware(\App\Http\Middleware\EnsureSubscription::class)
            ->put(route('widgets.update', $w), ['name' => 'Shop'] + $data);

        // Preset
        $save(['launcher_icon' => 'calendar'])->assertSessionHasNoErrors();
        $this->assertSame('svg', \App\Support\LauncherIcons::forWidget($w->fresh())['type']);

        // Rejected: wrong type, not square, too small, too big
        $save(['launcher_icon' => 'custom', 'launcher_icon_file' => UploadedFile::fake()->create('icon.gif', 10, 'image/gif')])->assertSessionHasErrors('launcher_icon_file');
        $save(['launcher_icon' => 'custom', 'launcher_icon_file' => UploadedFile::fake()->image('wide.png', 300, 200)])->assertSessionHasErrors('launcher_icon_file');
        $save(['launcher_icon' => 'custom', 'launcher_icon_file' => UploadedFile::fake()->image('tiny.png', 32, 32)])->assertSessionHasErrors('launcher_icon_file');
        $save(['launcher_icon' => 'custom', 'launcher_icon_file' => UploadedFile::fake()->image('huge.jpg', 800, 800)])->assertSessionHasErrors('launcher_icon_file');

        // Accepted: square PNG within limits
        $save(['launcher_icon' => 'custom', 'launcher_icon_file' => UploadedFile::fake()->image('icon.png', 256, 256)])->assertSessionHasNoErrors();
        $w->refresh();
        $this->assertSame('custom', $w->launcher_icon);
        Storage::disk('public')->assertExists($w->launcher_icon_path);
        $this->assertStringEndsWith($w->launcher_icon_path, \App\Support\LauncherIcons::forWidget($w)['url']);

        // Back to a preset removes the uploaded file
        $old = $w->launcher_icon_path;
        $save(['launcher_icon' => 'bubble'])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($old);
    }
}
