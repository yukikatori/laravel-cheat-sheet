<?php

// 通知をテストするわけではないテストでは、以下を準備しテスト用の偽の通知にする。
// use Illuminate\Support\Facades\Notification;
// Notification::fake();

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\QaBoard;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 質問掲示板 StoreReplyRequest のバリデーション検証。
 */
class StoreReplyRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_payload(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $replyStudent = User::factory()->student()->create();
        $certification = Certification::factory()->published()->create();
        $thread = QaThread::factory()->forStudent($student)->forCertification($certification)->create();

        $response = $this->actingAs($replyStudent)->post(route('qa-board.replies.store', $thread), [
            'body' => 'reply',
        ]);

        $response->assertRedirect(route('qa_board.show', $thread));

        $this->assertDatabaseHas('qa-replies', [
            'qa_thread_id' => $thread->id,
            'user_id' => $replyStudent->id,
            'body' => 'reply',
        ]);
    }
}