<?php

namespace Tests\Unit;

use App\Services\PaginationService;
use App\Services\SecurityService;
use App\Services\ValidationService;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    public function test_security_hash_and_check(): void
    {
        $hash = SecurityService::hash('secreto');
        $this->assertNotSame('secreto', $hash);
        $this->assertTrue(SecurityService::check('secreto', $hash));
        $this->assertFalse(SecurityService::check('otro', $hash));
    }

    public function test_security_token_length(): void
    {
        $this->assertSame(64, strlen(SecurityService::token()));
        $this->assertSame(16, strlen(SecurityService::token(8)));
    }

    public function test_validator_required_and_email(): void
    {
        $v = validator(['email' => 'no-es-email'], ['email' => 'required|email']);
        $this->assertTrue($v->fails());
        $this->assertNotNull($v->firstError('email'));

        $ok = validator(['email' => 'a@b.com'], ['email' => 'required|email']);
        $this->assertTrue($ok->passes());
        $this->assertSame(['email' => 'a@b.com'], $ok->validated());
    }

    public function test_validator_min_and_confirmed(): void
    {
        $v = validator([
            'password' => 'abc',
            'password_confirmation' => 'xyz',
        ], [
            'password' => 'required|min:8|confirmed',
        ]);

        $this->assertTrue($v->fails());
        $this->assertCount(2, $v->flattenErrors());
    }

    public function test_validator_nullable_skips_other_rules(): void
    {
        $v = validator(['phone' => ''], ['phone' => 'nullable|numeric']);
        $this->assertTrue($v->passes());
    }

    public function test_pagination_over_array(): void
    {
        $items = range(1, 25);
        $page = PaginationService::paginate($items, 10, 2);

        $this->assertSame([11, 12, 13, 14, 15, 16, 17, 18, 19, 20], $page['data']);
        $this->assertSame(25, $page['total']);
        $this->assertSame(3, $page['last_page']);
        $this->assertSame(2, $page['current_page']);
        $this->assertSame(11, $page['from']);
        $this->assertSame(20, $page['to']);
        $this->assertTrue($page['has_more']);
    }

    public function test_validation_service_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ValidationService::validate([], ['name' => 'required']);
    }
}
