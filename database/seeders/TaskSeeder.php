<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Taskの初期データ。
 * ユーザーそれぞれにカテゴリを追加したのち、タスクを投入する。
 */
class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        $addedTasks = [
            ['title' => 'タスク1', 'description' => 'タスク1の説明', 'status' => TaskStatus::InProgress->value, 'priority' => 1],
            ['title' => 'タスク2', 'description' => 'タスク2の説明', 'status' => TaskStatus::InProgress->value, 'priority' => 2],
            ['title' => 'タスク3', 'description' => 'タスク3の説明', 'status' => TaskStatus::Completed->value, 'priority' => 3],
        ];

        foreach ($users as $user) {
            foreach ($addedTasks as $task) {
                Task::firstOrCreate([
                    'title' => $task['title'],
                    'user_id' => $user->id,
                ], [
                    'description' => $task['description'],
                    'status' => $task['status'],
                    'priority' => $task['priority'],
                    'category_id' => $user->categories()->inRandomOrder()->value('id'),
                ]);
            }
        }
    }
}
