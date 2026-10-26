<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Categoryの初期データ。
 * UserSeederで作成したユーザーそれぞれにカテゴリを追加する。
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        $addedCategories = [
            'カテゴリ1',
            'カテゴリ2',
            'カテゴリ3',
        ];

        foreach ($users as $user) {
            foreach ($addedCategories as $category) {
                Category::firstOrCreate([
                    'name' => $category,
                    'user_id' => $user->id,
                ]);
            }
        }
    }
}
