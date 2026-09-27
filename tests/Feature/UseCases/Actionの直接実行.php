<?php

/**
 * Actionの直接実行
 * 
 * 以下と同義である。
 * $action = app(IndexAction::class);
 * $result = $action($student, 'upcoming');
 */

// 参考コード
// Link : https://github.com/yukikatori/Certify-LMS
// tests/Feature/UseCases/Meeting/IndexAction

namespace Tests\Feature\UseCases\Meeting;

use App\Models\Meeting;
use App\Models\User;
use App\UseCases\Meeting\IndexAction;
use App\UseCases\Meeting\IndexAsCoachAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_viewers_meetings(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();
        $coach = User::factory()->coach()->create();
        $own = Meeting::factory()->reserved()->forCoach($coach)->forStudent($student)->create([
            'scheduled_at' => now()->addDays(3)->startOfHour(),
        ]);
        $other = Meeting::factory()->reserved()->forCoach($coach)->forStudent($otherStudent)->create([
            'scheduled_at' => now()->addDays(4)->startOfHour(),
        ]);

        $result = app(IndexAction::class)($student, 'upcoming');

        $meetings = $result['meetings'];

        $this->assertTrue($meetings->contains('id', $own->id));
        $this->assertFalse($meetings->contains('id', $other->id));
    }

    // 呼び出したActionの引数のバリエーション
    public function test_lists_only_viewers_meetings2(): void
    {
        $coach = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();
        $own = Meeting::factory()->reserved()->forCoach($coach)->forStudent($student)->create([
            'scheduled_at' => now()->addDays(3)->startOfHour(),
        ]);
        $other = Meeting::factory()->reserved()->forCoach($otherCoach)->forStudent($student)->create([
            'scheduled_at' => now()->addDays(4)->startOfHour(),
        ]);

        $meetings = app(IndexAsCoachAction::class)(
            viewer: $coach,
            filter: 'upcoming',
            studentId: null,
            enrollmentId: null,
        );

        $this->assertTrue($meetings->contains('id', $own->id));
        $this->assertFalse($meetings->contains('id', $other->id));
    }
}