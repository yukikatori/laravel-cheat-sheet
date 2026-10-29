<?php

namespace Tests\Feature\Http\Task;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_task(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->delete(route('tasks.destroy', $task));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_user_is_redirected_to_index_with_success_message_after_delete(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->delete(route('tasks.destroy', $task));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tasks.index'));
        $response->assertSessionHas('success', 'タスクを削除しました');
    }

    public function test_guest_is_redirected_to_login_when_delete(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->delete(route('tasks.destroy', $task));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_destroy_returns_403_when_deleting_another_users_task(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($otherUser)->delete(route('tasks.destroy', $task));

        $response->assertForbidden();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_destroy_returns_404_when_task_is_not_registered(): void
    {
        $user = User::factory()->create();

        $task = Task::factory()->create(['user_id' => $user->id]);

        $task->delete();

        $response = $this->actingAs($user)
            ->delete(route('tasks.destroy', $task));

        $response->assertNotFound();
    }
}
