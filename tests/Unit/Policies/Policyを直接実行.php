<?php

// $policy = new MeetingPolicy;のように呼び出す

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Meeting;
use App\Models\User;
use App\Policies\MeetingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MeetingPolicy の判定を検証する Unit テスト。
 * view は当事者のみ / create は student のみ / cancel は予約状態 + 当事者 / upsertMemo は担当 coach のみ。
 */
class MeetingPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_only_for_admin_or_party(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();
        $otherCoach = User::factory()->coach()->create();
        $meeting = Meeting::factory()->forCoach($coach)->forStudent($student)->reserved()->create();
        $policy = new MeetingPolicy;

        $this->assertTrue($policy->view($admin, $meeting));
        $this->assertTrue($policy->view($coach, $meeting));
        $this->assertTrue($policy->view($student, $meeting));
        $this->assertFalse($policy->view($otherCoach, $meeting), '他コーチは view 不可');
    }
}