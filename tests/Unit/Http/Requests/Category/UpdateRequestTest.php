<?php

namespace Tests\Unit\Http\Requests\Category;

use App\Http\Requests\Category\UpdateRequest;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
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

        $request = new UpdateRequest;
        $request->setUserResolver(fn () => $user);

        $payload = array_merge([
            'name' => '更新後のカテゴリ名',
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
            'name 1文字' => [['name' => 'あ']],
            'name 254文字' => [['name' => str_repeat('あ', 254)]],
            'name 255文字' => [['name' => str_repeat('あ', 255)]],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        $user = User::factory()->create();

        $request = new UpdateRequest;
        $request->setUserResolver(fn () => $user);

        $payload = array_merge([
            'name' => '更新後のカテゴリ名',
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
            'name 空文字' => [['name' => ''], 'name'],
            'name 256 文字' => [['name' => str_repeat('a', 256)], 'name'],
            'name 不正値' => [['name' => 123], 'name'],
        ];
    }

    public function test_validation_passes_when_category_name_is_unchanged(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => '既存のカテゴリ名',
        ]);

        $request = new UpdateRequest;
        $request->setUserResolver(fn () => $user);

        $route = new Route('PUT', 'categories/{category}', []);
        $route->bind($request);
        $route->setParameter('category', $category);

        $request->setRouteResolver(fn () => $route);

        $payload = [
            'name' => '既存のカテゴリ名',
        ];

        $validator = Validator::make(
            $payload,
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_when_category_name_exists_for_another_category_of_same_user(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => '更新対象のカテゴリ名',
        ]);
        $otherCategory = Category::factory()->create([
            'user_id' => $user->id,
            'name' => '既存のカテゴリ名',
        ]);

        $request = new UpdateRequest;
        $request->setUserResolver(fn () => $user);

        $route = new Route('PUT', 'categories/{category}', []);
        $route->bind($request);
        $route->setParameter('category', $category);

        $request->setRouteResolver(fn () => $route);

        $validator = Validator::make(
            ['name' => $otherCategory->name],
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('Unique', $validator->failed()['name']);
        $this->assertSame(
            'このカテゴリ名はすでに登録されています。',
            $validator->errors()->first('name'),
        );
    }

    public function test_validation_passes_when_category_name_exists_for_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $otherUser->id,
            'name' => '既存のカテゴリ名',
        ]);

        $request = new UpdateRequest;
        $request->setUserResolver(fn () => $user);

        $payload = [
            'name' => '既存のカテゴリ名',
        ];

        $validator = Validator::make(
            $payload,
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue($validator->passes());
    }
}
