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
}

