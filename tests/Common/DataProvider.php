<?php

// Requestのバリデーション失敗を確認するためのテスト
class IndexRequestTest extends TestCase
{
    #[DataProvider('invalidFilterPayloads')]
    public function test_validation_fails(array $params, string $expectedErrorField): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->getJson(route('qa-board.index', $params));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidFilterPayloads(): array
    {
        return [
            'keyword 101 文字で 422' => [['keyword' => str_repeat('a', 101)], 'keyword'],
            'status 不正値で 422' => [['status' => 'unknown'], 'status'],
            'certification_id 不正 ulid で 422' => [['certification_id' => 'not-ulid'], 'category_id'],
        ];
    }

    // 作成系、$payloadで入力するデータを管理
    #[DataProvider('invalidPayloads')]
    public function test_validation_fails2(array $overrides, string $expectedErrorField): void
    {
        $student = User::factory()->student()->create();
        $certification = Certification::factory()->published()->create();

        $payload = array_merge([
            'certification_id' => $certification->id,
            'title' => 'test',
            'body' => 'test',
        ], $overrides);

        $response = $this->actingAs($student)->postJson(route('qa-board.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads2(): array
    {
        return [
            'certification_id 未指定で 422' => [['certification_id' => ''], 'certification_id'],
            'certification_id 不正 ulid で 422' => [['certification_id' => 'not-ulid'], 'certification_id'],
            'certification_id 存在しない ulid で 422' => [['certification_id' => (string) Str::ulid()], 'certification_id'],
            'title 未指定で 422' => [['title' => ''], 'title'],
            'title 201 文字で 422' => [['title' => str_repeat('a', 201)], 'title'],
            'body 未指定で 422' => [['body' => ''], 'body'],
            'body 5001 文字で 422' => [['body' => str_repeat('a', 5001)], 'body'],
            'title 全角スペースで 422' => [['title' => '全角　スペース'], 'title'],
            'body 全角スペースで 422' => [['body' => '全角　スペース'], 'body'],
        ];
    }
}

