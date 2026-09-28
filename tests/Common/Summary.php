<?php

// 含んでいることの確認
$this->assertTrue($meetings->contains('id', $enrollment1Meeting->id));
$this->assertFalse($meetings->contains('id', $enrollment2Meeting->id));

// 同じことの確認、順番を確認することもできる
$this->assertSame(
    [
        $earlierMeeting->id,
        $laterMeeting->id,
    ],
    $meetings->pluck('id')->all(),
);

$this->assertSame($student->id, $meeting->student_id);
$this->assertSame($coach->id, $meeting->coach_id);

// ロードができているかの確認
$this->assertTrue($result->relationLoaded('enrollment'));
$this->assertTrue($result->enrollment->relationLoaded('certification'));
$this->assertTrue($result->relationLoaded('coach'));

// 例外の設置、StoreActionなどで使うときはActionの呼び出し直前に設置する
$this->expectException(InsufficientMeetingQuotaException::class);

// データベースに情報があるか
$this->assertDatabaseHas('meetings', [
    'id' => $meeting->id,
    'status' => MeetingStatus::Canceled->value,
    'canceled_by_user_id' => $student->id,
]);

// nullでないことの確認
$this->assertNotNull($meeting->fresh()->canceled_at);