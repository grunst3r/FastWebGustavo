<?php

namespace Tests\Feature;

use App\Models\AuthToken;
use App\Models\User;
use App\Services\AuthTokenService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class AuthTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $schema = Capsule::schema();

        $schema->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        $schema->create('auth_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index();
            $table->string('token', 64)->unique();
            $table->boolean('remember')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    private function user(string $email = 'tester@example.com'): User
    {
        return User::create([
            'name' => 'Tester',
            'email' => $email,
            'password' => password_hash('secret', PASSWORD_DEFAULT),
        ]);
    }

    public function test_issue_stores_hash_and_returns_raw(): void
    {
        $issued = AuthTokenService::issue($this->user(), true);

        $this->assertSame(64, strlen($issued['raw']));

        $row = AuthToken::find($issued['token']->id);
        $this->assertNotNull($row);
        $this->assertSame(AuthTokenService::hash($issued['raw']), $row->token);
        $this->assertNotSame($issued['raw'], $row->token);
        $this->assertTrue($row->remember);
        $this->assertNotNull($row->expires_at);
    }

    public function test_find_returns_token_for_valid_raw(): void
    {
        $user = $this->user();
        $issued = AuthTokenService::issue($user, true);

        $found = AuthTokenService::find($issued['raw']);

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->user_id);
        $this->assertNull(AuthTokenService::find('no-existe'));
    }

    public function test_expired_token_is_not_found(): void
    {
        $issued = AuthTokenService::issue($this->user(), true);

        $issued['token']->expires_at = \Carbon\Carbon::now()->subDay();
        $issued['token']->save();

        $this->assertNull(AuthTokenService::find($issued['raw']));
    }

    public function test_session_token_has_no_expiry_and_is_found(): void
    {
        $issued = AuthTokenService::issue($this->user(), false);

        $this->assertNull($issued['token']->expires_at);
        $this->assertNotNull(AuthTokenService::find($issued['raw']));
    }

    public function test_revoke_removes_single_token(): void
    {
        $issued = AuthTokenService::issue($this->user(), true);

        AuthTokenService::revoke($issued['token']);

        $this->assertNull(AuthTokenService::find($issued['raw']));
    }

    public function test_revoke_user_expels_all_devices_only_of_that_user(): void
    {
        $user = $this->user();
        $a = AuthTokenService::issue($user, true);
        $b = AuthTokenService::issue($user, true);
        $other = AuthTokenService::issue($this->user('other@example.com'), true);

        $deleted = AuthTokenService::revokeUser($user->id);

        $this->assertSame(2, $deleted);
        $this->assertNull(AuthTokenService::find($a['raw']));
        $this->assertNull(AuthTokenService::find($b['raw']));
        $this->assertNotNull(AuthTokenService::find($other['raw']));
    }

    public function test_prune_expired_keeps_session_tokens(): void
    {
        $user = $this->user();
        $expired = AuthTokenService::issue($user, true);
        $session = AuthTokenService::issue($user, false);

        $expired['token']->expires_at = \Carbon\Carbon::now()->subDay();
        $expired['token']->save();

        $this->assertSame(1, AuthTokenService::pruneExpired());
        $this->assertNull(AuthTokenService::find($expired['raw']));
        $this->assertNotNull(AuthTokenService::find($session['raw']));
    }
}
