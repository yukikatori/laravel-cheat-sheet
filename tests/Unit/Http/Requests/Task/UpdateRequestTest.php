<?php

namespace Tests\Unit\Http\Requests\Task;

use App\Http\Requests\Task\UpdateRequest;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('validPayloads')]
    public function test_validation_passes_with_valid_payload(array $overrides): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $request = new UpdateRequest;
        $request->setUserResolver(fn () => $user);

        $payload = array_merge([
            'title' => '更新後のタイトル',
            'description' => '更新後の説明',
            'priority' => 2,
            'category_id' => $category->id,
        ], $overrides);

        $validator = Validator::make(
            $payload,
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue($validator->passes());
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

        $request = new UpdateRequest;
        $request->setUserResolver(fn () => $user);

        $payload = array_merge([
            'title' => '更新後のタイトル',
            'description' => '更新後の説明',
            'priority' => 2,
            'category_id' => $category->id,
        ], $overrides);

        $validator = Validator::make(
            $payload,
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has($expectedErrorField));
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

    public function test_validation_fails_when_category_belongs_to_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->create(['user_id' => $otherUser->id]);

        $request = new UpdateRequest;
        $request->setUserResolver(fn () => $user);

        $payload = [
            'title' => '更新後のタイトル',
            'description' => '更新後の説明',
            'priority' => 2,
            'category_id' => $otherCategory->id,
        ];

        $validator = Validator::make(
            $payload,
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('category_id'));
    }
}
