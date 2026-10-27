<?php

namespace Tests\Feature\Seeder;

use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\TaskSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_can_create_tasks_for_each_user(): void
    {
        $users = User::factory()->count(2)->create();

        $this->seed(CategorySeeder::class);
        $this->seed(TaskSeeder::class);

        $this->assertDatabaseCount('tasks', 6);

        foreach ($users as $user) {
            $this->assertSame(
                ['タスク1', 'タスク2', 'タスク3'],
                $user->tasks()->orderBy('title')->pluck('title')->all(),
            );
        }
    }

    public function test_repeated_seeding_creates_each_task_only_once(): void
    {
        $users = User::factory()->count(2)->create();

        $this->seed(CategorySeeder::class);
        $this->seed(TaskSeeder::class);
        $this->seed(TaskSeeder::class);

        $this->assertDatabaseCount('tasks', 6);

        foreach ($users as $user) {
            $this->assertSame(
                ['タスク1', 'タスク2', 'タスク3'],
                $user->tasks()->orderBy('title')->pluck('title')->all(),
            );
        }
    }

    public function test_seeded_tasks_belong_to_their_users_categories(): void
    {
        $users = User::factory()->count(2)->create();

        $this->seed(CategorySeeder::class);
        $this->seed(TaskSeeder::class);

        $this->assertDatabaseCount('tasks', 6);

        foreach ($users as $user) {
            $categoryIds = $user->categories()->pluck('id')->all();
            $tasks = $user->tasks()->get();

            $this->assertCount(3, $tasks);

            foreach ($tasks as $task) {
                $this->assertNotNull($task->category_id);
                $this->assertContains($task->category_id, $categoryIds);
            }
        }
    }
}
