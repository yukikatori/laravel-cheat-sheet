<?php

namespace Tests\Feature\Http\Task;

use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_tasks_in_updated_at_descending_order(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $newTask = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'updated_at' => now(),
        ]);

        $oldTask = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'updated_at' => now()->subday(),
        ]);

        $response = $this->actingAs($user)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertViewIs('tasks.index');
        $response->assertViewHas('tasks', function ($tasks) use ($newTask, $oldTask) {
            return $tasks->pluck('id')->all() === [
                $newTask->id,
                $oldTask->id,
            ];
        });
    }

    public function test_index_lists_tasks_in_id_descending_order_when_updated_at_is_equal(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $updatedAt = now()->subDay();

        $task1 = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'updated_at' => $updatedAt,
        ]);

        $task2 = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'updated_at' => $updatedAt,
        ]);

        $response = $this->actingAs($user)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertViewIs('tasks.index');
        $response->assertViewHas('tasks', function ($tasks) use ($task1, $task2) {
            return $tasks->pluck('id')->all() === [
                $task2->id,
                $task1->id,
            ];
        });
    }

    public function test_index_lists_only_authenticated_users_tasks(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $task1 = Task::factory()->create([
            'user_id' => $user1->id,
            'category_id' => null,
        ]);

        $task2 = Task::factory()->create([
            'user_id' => $user2->id,
            'category_id' => null,
        ]);

        $response = $this->actingAs($user1)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertViewIs('tasks.index');
        $response->assertViewHas('tasks', function ($tasks) use ($task1) {
            return $tasks->pluck('id')->all() === [
                $task1->id,
            ];
        });
    }

    public function test_index_displays_category_and_priority(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'priority' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertViewIs('tasks.index');
        $response->assertSeeText($category->name);
        $response->assertSeeText('低');
    }

    public function test_index_displays_status_and_matching_action(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task1 = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => TaskStatus::InProgress->value,
        ]);

        $task2 = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => TaskStatus::Completed->value,
        ]);

        $response = $this->actingAs($user)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertViewIs('tasks.index');
        $response->assertSeeText('実施中');
        $response->assertSeeText('タスク終了');
        $response->assertSeeText('完了');
        $response->assertSeeText('実施中に戻す');
    }

    public function test_index_displays_uncategorized_when_category_is_not_assigned(): void
    {
        $user = User::factory()->create();

        Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => null,
        ]);

        $response = $this->actingAs($user)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertViewIs('tasks.index');
        $response->assertSeeText('未分類');
    }

    public function test_guest_is_redirected_to_login_when_accessing_index(): void
    {
        $response = $this->get(route('tasks.index'));

        $response->assertRedirect(route('login'));
    }
}
