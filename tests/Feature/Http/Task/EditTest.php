<?php

namespace Tests\Feature\Http\Task;

use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_page_displays_original_task_information(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'name' => 'test category',
            'user_id' => $user->id,
        ]);

        $task = Task::factory()->create([
            'title' => 'テストタイトル',
            'description' => 'contents of the test task',
            'status' => TaskStatus::InProgress,
            'priority' => 1,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => '2026-10-26'
        ]);

        $response = $this->actingAs($user)->get(route('tasks.edit', $task));

        $response->assertOk();
        $response->assertViewIs('tasks.edit');
        $response->assertSee('テストタイトル', false);
        $response->assertSeeText('test category');
        $response->assertSeeText('低');
        $response->assertSeeText('contents of the test task');
    }

    public function test_edit_page_displays_authenticated_users_categories(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('tasks.edit', $task));

        $response->assertOk();
        $response->assertViewIs('tasks.edit');
        $response->assertViewHas('categories', function ($categories) use ($category) {
            return $categories->pluck('id')->all() === [
                $category->id,
            ];
        });
    }

    public function test_edit_page_does_not_display_other_users_categories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('tasks.edit', $task));

        $response->assertOk();
        $response->assertViewIs('tasks.edit');

        $response->assertViewHas('categories', function ($categories) use ($category) {
            return $categories->pluck('id')->all() === [
                $category->id,
            ];
        });

        $response->assertViewHas('categories', function ($categories) use ($otherCategory) {
            return $categories->pluck('id')->all() !== [
                $otherCategory->id,
            ];
        });
    }

    public function test_edit_page_can_be_displayed_without_categories(): void
    {
        $user = User::factory()->create();

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => null,
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('tasks.edit', $task));

        $response->assertOk();
        $response->assertViewIs('tasks.edit');
        $response->assertViewHas('categories', function ($categories) {
            return $categories->isEmpty();
        });
    }

    public function test_edit_page_displays_action_buttons(): void
    {
        $user = User::factory()->create();

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => null,
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('tasks.edit', $task));

        $response->assertOk();
        $response->assertViewIs('tasks.edit');

        $response->assertSeeText('更新');
        $response->assertSee(
            'action="'.route('tasks.update', $task).'"',
            false,
        );

        $response->assertSeeText('キャンセル');
        $response->assertSee(
            'href="'.route('tasks.show', $task).'"',
            false,
        );
    }

    public function test_guest_is_redirected_to_login_when_accessing_edit(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'updated_at' => now(),
        ]);

        $response = $this->get(route('tasks.edit', $task));

        $response->assertRedirect(route('login'));
    }

    public function test_edit_page_returns_403_when_accessing_another_users_task(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($otherUser)->get(route('tasks.edit', $task));

        $response->assertForbidden();
    }
}
