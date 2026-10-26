<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Userの初期データ
 * 動作確認のため規定ユーザーを準備する。
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate([
            'email' => 'userA@example.com',
        ], [
            'name' => 'userA',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        User::firstOrCreate([
            'email' => 'userB@example.com',
        ], [
            'name' => 'userB',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }
}
