<?php

namespace Tests\Feature\Http\Task;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_task(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $newCategory = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'title' => '修正前タスク',
            'description' => '修正前タスク内容',
            'priority' => 1,
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $updateData = [
            'title' => '修正後タスク',
            'description' => '修正後タスク内容',
            'priority' => 2,
            'category_id' => $newCategory->id,
        ];

        $response = $this->actingAs($user)->patch(route('tasks.update', $task), $updateData);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'user_id' => $user->id,
            'title' => $updateData['title'],
            'description' => $updateData['description'],
            'priority' => $updateData['priority'],
            'category_id' => $updateData['category_id'],
        ]);
    }

    public function test_user_can_update_task_without_description_and_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'title' => '修正前タスク',
            'description' => '修正前タスク内容',
            'priority' => 1,
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $updateData = [
            'title' => '修正後タスク',
            'description' => null,
            'priority' => 2,
            'category_id' => null,
        ];

        $response = $this->actingAs($user)->patch(route('tasks.update', $task), $updateData);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'user_id' => $user->id,
            'title' => $updateData['title'],
            'description' => null,
            'priority' => $updateData['priority'],
            'category_id' => null,
        ]);
    }

    public function test_user_is_redirected_to_task_detail_with_success_message_after_updating(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $newCategory = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'title' => '修正前タスク',
            'description' => '修正前タスク内容',
            'priority' => 1,
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $updateData = [
            'title' => '修正後タスク',
            'description' => '修正後タスク内容',
            'priority' => 2,
            'category_id' => $newCategory->id,
        ];

        $response = $this->actingAs($user)->patch(route('tasks.update', $task), $updateData);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tasks.show', $task));
        $response->assertSessionHas('success', 'タスクを更新しました');
    }

    public function test_guest_cannot_update_task(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $newCategory = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'title' => '修正前タスク',
            'description' => '修正前タスク内容',
            'priority' => 1,
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $updateData = [
            'title' => '修正後タスク',
            'description' => '修正後タスク内容',
            'priority' => 2,
            'category_id' => $newCategory->id,
        ];

        $response = $this->patch(route('tasks.update', $task), $updateData);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('tasks', 1);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'user_id' => $user->id,
            'title' => '修正前タスク',
            'description' => '修正前タスク内容',
            'priority' => 1,
            'category_id' => $category->id,
            'status' => $task->status->value,
        ]);
    }

    public function test_user_cannot_update_another_users_task(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $otherUser->id]);
        $newCategory = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'title' => '修正前タスク',
            'description' => '修正前タスク内容',
            'priority' => 1,
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
        ]);

        $updateData = [
            'title' => '修正後タスク',
            'description' => '修正後タスク内容',
            'priority' => 2,
            'category_id' => $newCategory->id,
        ];

        $response = $this->actingAs($user)->patch(route('tasks.update', $task), $updateData);

        $response->assertForbidden();
        $this->assertDatabaseCount('tasks', 1);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'user_id' => $otherUser->id,
            'title' => '修正前タスク',
            'description' => '修正前タスク内容',
            'priority' => 1,
            'category_id' => $category->id,
            'status' => $task->status->value,
        ]);
    }
}
