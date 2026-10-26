<?php

namespace Tests\Unit\Models;

use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task モデルのリレーション・Cast を検証する Unit テスト。
 */
class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_relation_returns_assigned_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        $target = $task->category;

        $this->assertTrue($target->is($category));
    }

    public function test_task_does_not_return_unrelated_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $otherCategory = Category::factory()->create(['user_id' => $user->id]);
        
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);
        
        $otherTask = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $otherCategory->id
        ]);

        $target = $task->category;

        $this->assertTrue($target->is($category));
        $this->assertFalse($target->is($otherCategory));
    }

    public function test_task_relation_returns_assigned_user(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        $target = $task->user;

        $this->assertTrue($target->is($user));
    }

    public function test_task_does_not_return_unrelated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);
        
        $otherTask = Task::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id
        ]);

        $target = $task->user;

        $this->assertTrue($target->is($user));
        $this->assertFalse($target->is($otherUser));
    }

    public function test_task_without_category_returns_null(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => null,
        ]);

        $target = $task->category;

        $this->assertNull($target);
    }

    public function test_status_cast_converts_to_enum(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->in_progress()->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        $task->refresh();

        $this->assertInstanceOf(TaskStatus::class, $task->status);
        $this->assertSame(TaskStatus::InProgress, $task->status);
    }
}
