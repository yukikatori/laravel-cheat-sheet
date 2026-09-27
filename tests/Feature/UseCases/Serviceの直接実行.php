<?php

/**
 * Serviceの直接実行
 * 
 * app(MeetingQuotaService::class)->remaining($student)
 * MeetingQuotaServiceを呼び出し、remaining()メソッドを実行
 */

// 参考コード
// Link : https://github.com/yukikatori/Certify-LMS
// tests/Feature/UseCases/Meeting/StoreAction

use App\Enums\MeetingQuotaTransactionType;
use App\Enums\MeetingStatus;
use App\Models\Certification;
use App\Models\CoachAvailability;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\MeetingQuotaService;
use App\UseCases\Meeting\StoreAction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumes_meeting_quota(): void
    {
        $student = User::factory()->student()->inProgress()->create(['max_meetings' => 3]);
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->inProgress()->create([
            'meeting_url' => 'https://meet.example.com/coach-room',
        ]);
        $certification = Certification::factory()->published()->create();

        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'unassigned_at' => null,
        ]);

        CoachAvailability::factory()
            ->forCoach($coach)
            ->onDay(Carbon::MONDAY)
            ->timeRange('09:00:00', '18:00:00')
            ->create();

        $enrollment = Enrollment::factory()->for($student, 'user')->for($certification)->learning()->create();
        $scheduledAt = now()->startOfDay()->next(Carbon::MONDAY)->setTime(10, 0); // 次の月曜 10:00(未来)

        $meeting = app(StoreAction::class)($student, $enrollment, [
            'scheduled_at' => $scheduledAt->toDateTimeString(),
            'topic' => '学習計画について相談したい',
        ]);

        $this->assertSame(2, app(MeetingQuotaService::class)->remaining($student));

        $this->assertDatabaseHas('meeting_quota_transactions', [
            'user_id' => $student->id,
            'type' => MeetingQuotaTransactionType::Consumed->value,
            'amount' => -1,
            'related_meeting_id' => $meeting->id,
        ]);
    }
}