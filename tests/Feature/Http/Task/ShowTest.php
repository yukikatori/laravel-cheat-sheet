<?php

namespace Tests\Feature\Http\Task;

use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_can_show_content_of_task(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'name' => 'test category',
            'user_id' => $user->id,
        ]);

        $task = Task::factory()->create([
            'title' => 'test',
            'description' => 'contents of the test task',
            'status' => TaskStatus::InProgress,
            'priority' => 1,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => '2026-10-26'
        ]);

        $response = $this->actingAs($user)->get(route('tasks.show', $task));

        $response->assertOk();
        $response->assertViewIs('tasks.show');
        $response->assertSeeText('test');
        $response->assertSeeText('test category');
        $response->assertSeeText('低');
        $response->assertSeeText('実施中');
        $response->assertSeeText('contents of the test task');
        $response->assertSeeText('2026年10月26日');
    }

    public function test_page_can_show_content_of_uncategorized_task(): void
    {
        $user = User::factory()->create();

        $task = Task::factory()->create([
            'title' => 'test',
            'description' => 'contents of the test task',
            'status' => TaskStatus::InProgress,
            'priority' => 1,
            'user_id' => $user->id,
            'category_id' => null,
            'created_at' => '2026-10-26'
        ]);

        $response = $this->actingAs($user)->get(route('tasks.show', $task));

        $response->assertOk();
        $response->assertViewIs('tasks.show');
        $response->assertSeeText('test');
        $response->assertSeeText('未分類');
        $response->assertSeeText('低');
        $response->assertSeeText('実施中');
        $response->assertSeeText('contents of the test task');
        $response->assertSeeText('2026年10月26日');
    }

    public function test_page_can_display_return_button_to_index(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'name' => 'test category',
            'user_id' => $user->id,
        ]);

        $task = Task::factory()->create([
            'title' => 'test',
            'description' => 'contents of the test task',
            'status' => TaskStatus::InProgress,
            'priority' => 1,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => '2026-10-26'
        ]);

        $response = $this->actingAs($user)->get(route('tasks.show', $task));

        $response->assertOk();
        $response->assertViewIs('tasks.show');
        $response->assertSeeText('一覧に戻る');
        $response->assertSee(
            'href="'.route('tasks.index').'"',
            false
        );
    }

    public function test_page_can_display_task_status_change_button_with_in_progress_task(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'name' => 'test category',
            'user_id' => $user->id,
        ]);

        $task = Task::factory()->create([
            'title' => 'test',
            'description' => 'contents of the test task',
            'status' => TaskStatus::InProgress,
            'priority' => 1,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => '2026-10-26'
        ]);

        $response = $this->actingAs($user)->get(route('tasks.show', $task));

        $response->assertOk();
        $response->assertViewIs('tasks.show');
        $response->assertSeeText('タスク終了');
        $response->assertSee(
            'action="'.route('tasks.complete', $task).'"',
            false
        );
    }

    public function test_page_can_display_task_status_change_button_with_completed_task(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'name' => 'test category',
            'user_id' => $user->id,
        ]);

        $task = Task::factory()->create([
            'title' => 'test',
            'description' => 'contents of the test task',
            'status' => TaskStatus::Completed,
            'priority' => 1,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => '2026-10-26'
        ]);

        $response = $this->actingAs($user)->get(route('tasks.show', $task));

        $response->assertOk();
        $response->assertViewIs('tasks.show');
        $response->assertSeeText('実施中に戻す');
        $response->assertSee(
            'action="'.route('tasks.uncomplete', $task).'"',
            false
        );
    }

    public function test_guest_is_redirected_to_login_when_accessing_show(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get(route('tasks.show', $task));

        $response->assertRedirect(route('login'));
    }

    public function test_show_returns_403_when_accessing_another_users_task(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($otherUser)->get(route('tasks.show', $task));

        $response->assertForbidden();
    }

    public function test_show_returns_404_when_task_is_not_registered(): void
    {
        $user = User::factory()->create();

        $task = Task::factory()->create(['user_id' => $user->id]);
        $taskId = $task->id;
        $task->delete();

        $response = $this->actingAs($user)
            ->get(route('tasks.show', $taskId));

        $response->assertNotFound();
    }
}
