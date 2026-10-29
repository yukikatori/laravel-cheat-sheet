<?php

namespace Tests\Feature\Http\Task;

use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_task(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $data = [
            'title' => 'test',
            'description' => 'test',
            'priority' => 1,
            'category_id' => $category->id,
        ];

        $response = $this->actingAs($user)->post(route('tasks.store'), $data);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => TaskStatus::InProgress,
            'priority' => $data['priority'],
            'category_id' => $data['category_id'],
        ]);
    }

    public function test_user_can_store_task_without_description_and_category(): void
    {
        $user = User::factory()->create();

        $data = [
            'title' => 'test',
            'description' => null,
            'priority' => 1,
            'category_id' => null,
        ];

        $response = $this->actingAs($user)->post(route('tasks.store'), $data);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => $data['title'],
            'description' => null,
            'status' => TaskStatus::InProgress,
            'priority' => $data['priority'],
            'category_id' => null,
        ]);
    }

    public function test_user_is_redirected_to_task_detail_with_success_message_after_storing(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $data = [
            'title' => 'test',
            'description' => 'test',
            'priority' => 1,
            'category_id' => $category->id,
        ];

        $response = $this->actingAs($user)->post(route('tasks.store'), $data);

        $task = Task::query()->first();

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tasks.show', $task));
        $response->assertSessionHas('success', 'タスクを登録しました');
    }

    public function test_guest_cannot_store_task(): void
    {
        $data = [
            'title' => 'test',
            'description' => null,
            'priority' => 1,
            'category_id' => null,
        ];

        $response = $this->post(route('tasks.store'), $data);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('tasks', 0);
    }
}
