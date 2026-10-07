<?php

namespace Tests\Feature;

use GustRouter\Request;
use GustRouter\Router;
use Tests\TestCase;

class RoutesTest extends TestCase
{
    protected Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = new Router(new Request());

        $rutas = $this->router;
        foreach (['web.php', 'api.php', 'auth.php'] as $file) {
            require base_path('routes/' . $file);
        }
    }

    public function test_named_public_routes_resolve(): void
    {
        $this->assertSame('/', $this->path('home'));
        $this->assertSame('/login', $this->path('login'));
        $this->assertSame('/register', $this->path('register'));
        $this->assertSame('/logout', $this->path('logout'));
    }

    public function test_password_routes_resolve(): void
    {
        $this->assertSame('/password/request', $this->path('password.request'));
        $this->assertSame('/password/reset', $this->path('password.reset'));
    }

    public function test_dashboard_is_prefixed(): void
    {
        $this->assertSame('/dashboard', $this->path('dashboard'));
    }

    protected function path(string $name, array $params = []): string
    {
        return parse_url($this->router->url($name, $params), PHP_URL_PATH);
    }
}
