<?php

namespace Tests\Feature\Http\Task;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_displays_authenticated_users_categories(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('tasks.create'));

        $response->assertOk();
        $response->assertViewIs('tasks.create');
        $response->assertViewHas('categories', function ($categories) use ($category) {
            return $categories->pluck('id')->all() === [
                $category->id,
            ];
        });
    }

    public function test_create_page_does_not_display_other_users_categories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('tasks.create'));

        $response->assertOk();
        $response->assertViewIs('tasks.create');

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

    public function test_create_page_can_be_displayed_without_categories(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tasks.create'));

        $response->assertOk();
        $response->assertViewIs('tasks.create');
        $response->assertViewHas('categories', function ($categories) {
            return $categories->isEmpty();
        });
    }

    public function test_create_page_displays_action_buttons(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tasks.create'));

        $response->assertOk();
        $response->assertViewIs('tasks.create');

        $response->assertSeeText('登録');
        $response->assertSee(
            'action="'.route('tasks.store').'"',
            false,
        );

        $response->assertSeeText('キャンセル');
        $response->assertSee(
            'href="'.route('tasks.index').'"',
            false,
        );
    }

    public function test_guest_is_redirected_to_login_when_accessing_create(): void
    {
        $response = $this->get(route('tasks.create'));

        $response->assertRedirect(route('login'));
    }
}
