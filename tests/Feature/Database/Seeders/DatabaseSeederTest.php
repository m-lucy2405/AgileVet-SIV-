<?php

namespace Tests\Feature\Database\Seeders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_seeding_creates_reference_data_without_demo_users(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed --force')
            ->assertExitCode(0);

        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('users', 0);
    }
}
