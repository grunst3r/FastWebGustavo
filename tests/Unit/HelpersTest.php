<?php

namespace Tests\Unit;

use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_base_path_points_to_project_root(): void
    {
        $this->assertDirectoryExists(base_path());
        $this->assertFileExists(base_path('composer.json'));
        $this->assertFileExists(base_path('app/Core/helpers.php'));
    }

    public function test_env_returns_default_for_missing_key(): void
    {
        $this->assertSame('fallback', env('THIS_KEY_DOES_NOT_EXIST', 'fallback'));
    }

    public function test_env_parses_booleans(): void
    {
        putenv('TEST_BOOL_TRUE=true');
        putenv('TEST_BOOL_FALSE=false');

        $this->assertTrue(env('TEST_BOOL_TRUE'));
        $this->assertFalse(env('TEST_BOOL_FALSE'));

        putenv('TEST_BOOL_TRUE');
        putenv('TEST_BOOL_FALSE');
    }

    public function test_config_reads_app_name(): void
    {
        $this->assertIsArray(config());
        $this->assertArrayHasKey('app', config());
        $this->assertArrayHasKey('name', config('app'));
        $this->assertSame('fallback', config('app.not_a_real_key', 'fallback'));
    }
}
