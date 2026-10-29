<?php

namespace Tests\Feature\Http\Task;

use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UncompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_uncomplete_task(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => TaskStatus::Completed,
        ]);

        $response = $this->actingAs($user)->post(route('tasks.uncomplete', $task));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::InProgress,
        ]);
    }

    public function test_user_is_redirected_to_index_with_success_message_after_uncompleting_from_index_page(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => TaskStatus::Completed,
        ]);

        $response = $this->actingAs($user)
            ->from(route('tasks.index'))
            ->post(route('tasks.uncomplete', $task));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tasks.index'));
        $response->assertSessionHas('success', 'タスクを実施中に戻しました');
    }

    public function test_user_is_redirected_to_detail_page_with_success_message_after_uncompleting_from_show_page(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => TaskStatus::Completed,
        ]);

        $response = $this->actingAs($user)
            ->from(route('tasks.show', $task))
            ->post(route('tasks.uncomplete', $task));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tasks.show', $task));
        $response->assertSessionHas('success', 'タスクを実施中に戻しました');
    }

    public function test_guest_is_redirected_to_login_when_uncompleting(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => TaskStatus::Completed,
        ]);

        $response = $this->post(route('tasks.uncomplete', $task));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::Completed,
        ]);
    }

    public function test_uncomplete_returns_403_when_uncompleting_another_users_task(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => TaskStatus::Completed,
        ]);

        $response = $this->actingAs($otherUser)->post(route('tasks.uncomplete', $task));

        $response->assertForbidden();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::Completed,
        ]);
    }

    public function test_uncomplete_returns_404_when_task_is_not_registered(): void
    {
        $user = User::factory()->create();

        $task = Task::factory()->create(['user_id' => $user->id]);

        $task->delete();

        $response = $this->actingAs($user)->post(route('tasks.uncomplete', $task));

        $response->assertNotFound();
    }
}
