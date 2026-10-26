<?php

namespace Tests\Feature\Seeder;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_seeding_creates_each_user_only_once(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 2);

        foreach (['userA', 'userB'] as $name) {
            $user = User::where('email', $name.'@example.com')->sole();

            $this->assertSame($name, $user->name);
            $this->assertTrue(Hash::check('password', $user->password));
            $this->assertNotNull($user->email_verified_at);
        }
    }

    public function test_seeding_preserves_existing_user_and_creates_missing_user(): void
    {
        $existing = User::factory()->unverified()->create([
            'name' => 'Changed name',
            'email' => 'userA@example.com',
            'password' => Hash::make('changed-password'),
        ]);
        $attributes = $existing->fresh()->getAttributes();

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 2);
        $this->assertSame($attributes, $existing->fresh()->getAttributes());
        $this->assertDatabaseHas('users', [
            'name' => 'userB',
            'email' => 'userB@example.com',
        ]);
    }
}
