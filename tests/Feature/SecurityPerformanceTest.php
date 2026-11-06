<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Security;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_lockout_ip_block_headers_and_admin_page(): void
    {
        $u = User::factory()->create(['password' => bcrypt('right-pass')]);
        for ($i = 0; $i < 5; $i++) $this->post('/login', ['email' => $u->email, 'password' => 'wrong']);
        $this->post('/login', ['email' => $u->email, 'password' => 'right-pass'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(5, DB::table('security_events')->where('type', 'login_failed')->count());
        $this->assertTrue(DB::table('security_events')->where('type', 'lockout')->exists());

        $this->assertTrue(Security::ipIn('198.51.100.25', ['198.51.100.0/24']));
        $this->assertFalse(Security::ipIn('198.51.101.25', ['198.51.100.0/24']));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.security'))->assertOk()->assertSee('Security score')->assertSee('Optimise database')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('X-Content-Type-Options', 'nosniff');
        // Cannot lock yourself out
        $this->actingAs($admin)->put(route('admin.security.save'), ['login_max' => 5, 'login_decay' => 15, 'ban_after' => 3, 'ban_minutes' => 60, 'blocked_ips' => '127.0.0.1'])->assertSessionHas('error');
        $this->actingAs($admin)->put(route('admin.security.save'), ['login_max' => 5, 'login_decay' => 15, 'ban_after' => 3, 'ban_minutes' => 60, 'blocked_ips' => "203.0.113.9\nnot-an-ip", 'headers' => 1, 'block_bad_bots' => 1])->assertSessionHas('success');
        $this->assertSame(['203.0.113.9'], Security::settings()['blocked_ips']);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])->get('/')->assertForbidden();
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_USER_AGENT' => 'sqlmap/1.7'])->get('/')->assertForbidden();
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_USER_AGENT' => 'Mozilla/5.0'])->get('/')->assertOk();

        foreach (['views', 'db', 'logs', 'prune', 'events'] as $a) {
            $this->actingAs($admin)->post(route('admin.security.action'), ['action' => $a])->assertSessionHas('success');
        }
    }
}
