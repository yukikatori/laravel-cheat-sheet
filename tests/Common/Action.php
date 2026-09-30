<?php

// あるユーザーでログインした状態でアクセス
$response = $this->actingAs($student)->get(route('qa-board.index'));

// Filter条件追加
$response = $this->actingAs($student)->get(route('qa-board.index', [
    'keyword' => 'test',
    'status' => QaThreadStatus::Solved->value,
    'certification_id' => $certification->id,
]));

// Actionの直接実行
$result = app(IndexAction::class)($student, 'upcoming');

// Serviceの直接実行
$this->assertSame(2, app(MeetingQuotaService::class)->remaining($student));