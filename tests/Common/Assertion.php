<?php

// 含んでいることの確認
$this->assertTrue($meetings->contains('id', $enrollment1Meeting->id));
$this->assertFalse($meetings->contains('id', $enrollment2Meeting->id));

// true or false
$policy = new QaThreadPolicy;

$this->assertTrue($policy->view($admin, $thread));
$this->assertTrue($policy->view($coach, $thread));
$this->assertTrue($policy->view($student, $thread));

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

$this->assertDatabaseMissing('qa_threads', [
    'user_id' => $coach->id,
    'certification_id' => $certification->id,
    'title' => 'test',
    'body' => 'content',
]);

// nullでないことの確認
$this->assertNotNull($meeting->fresh()->canceled_at);

// レスポンスのステータスコードが 200〜299 の成功系であること
$response->assertSuccessful();

// レスポンスが 403 Forbidden を返していること
$response->assertForbidden();

// レスポンスのHTTPステータスが 404 Not Found であることを確認する
$response->assertNotFound();

// リダイレクトの確認
$response->assertRedirect(route('qa-board.show', $thread));

// バリデーションエラーの確認
$response->assertJsonValidationErrors('certification_id');

// ビューへ渡されたデータに特定の値が含まれているか
// $response->assertViewHas('key', 'value');　keyはviewに渡されている変数
$response->assertOk();
$response->assertViewIs('meeting.index');
$response->assertViewHas('meetings', fn ($meetings) => $meetings->contains('id', $own->id)
    && ! $meetings->contains('id', $other->id));

// ビューへ渡されたデータに特定の値が含まれているか（単体）
$response->assertViewHas('thread', fn ($thread) => 
    $thread->id === $targetThread->id
);

// セッションエラーの確認
$response->assertSessionHas('error', '回答がついているスレッドは削除できません。');
$response->assertSessionHas('success', '面談パックを削除しました。');

// データの個数の確認
$this->assertDatabaseCount('meeting_packs', 0);