<?php

// Actionの直接実行
$result = app(IndexAction::class)($student, 'upcoming');

// Serviceの直接実行
$this->assertSame(2, app(MeetingQuotaService::class)->remaining($student));