<?php

namespace Tests\Unit\Http\Requests\Task;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreRequestTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('validPayloads')]
    public function test_validation_passes_with_valid_payload(array $overrides): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $payload = array_merge([
            'title' => 'test',
            'description' => 'test',
            'priority' => 1,
            'category_id' => $category->id,
        ], $overrides);

        $response = $this->actingAs($user)->postJson(route('tasks.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => $payload['title'],
            'description' => $payload['description'],
            'priority' => $payload['priority'],
            'category_id' => $payload['category_id'],
        ]);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function validPayloads(): array
    {
        return [
            '基本の入力' => [[]],
            'title 1文字' => [['title' => 'あ']],
            'title 254文字' => [['title' => str_repeat('あ', 254)]],
            'title 255文字' => [['title' => str_repeat('あ', 255)]],
            'description null' => [['description' => null]],
            'description 改行・絵文字あり' => [
                ['description' => "買い物する\n牛乳を買う🥛"],
            ],
            'priority 1' => [['priority' => 1]],
            'priority 2' => [['priority' => 2]],
            'priority 3' => [['priority' => 3]],
            'category_id null' => [['category_id' => null]],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $payload = array_merge([
            'title' => 'test',
            'description' => 'test',
            'priority' => 1,
            'category_id' => $category->id,
        ], $overrides);

        $response = $this->actingAs($user)->postJson(route('tasks.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
    }

    public function test_validation_fails_when_category_belongs_to_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->postJson(route('tasks.store'), [
            'title' => 'test',
            'description' => 'test',
            'priority' => 1,
            'category_id' => $otherCategory->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('category_id');
        $this->assertDatabaseCount('tasks', 0);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'title 空文字で 422' => [['title' => ''], 'title'],
            'title 256 文字で 422' => [['title' => str_repeat('a', 256)], 'title'],
            'title 不正値で 422' => [['title' => 123], 'title'],
            'description 不正値で 422' => [['description' => 123], 'description'],
            'priority 空文字で 422' => [['priority' => ''], 'priority'],
            'priority 整数以外で 422' => [['priority' => 'not-integer'], 'priority'],
            'priority 不正値で 422' => [['priority' => 5], 'priority'],
            'category_id 整数以外で 422' => [['category_id' => 'not-integer'], 'category_id'],
            'category_id 不正値で 422' => [['category_id' => 999], 'category_id'],
        ];
    }
}
