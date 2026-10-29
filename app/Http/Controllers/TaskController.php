<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\Task\StoreRequest;
use App\Http\Requests\Task\UpdateRequest;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * タスク用コントローラ。CRUDおよび状態遷移を提供する。
 */
class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $tasks = $request->user()
            ->tasks()
            ->with('category')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return view('tasks.index', [
            'tasks' => $tasks,
        ]);
    }

    public function show(Task $task): View
    {
        $this->authorize('view', $task);

        $task->load('category');

        return view('tasks.show', [
            'task' => $task,
        ]);
    }

    public function create(Request $request): View
    {
        $categories = $request
            ->user()
            ->categories()
            ->orderBy('id', 'asc')
            ->get();

        return view('tasks.create', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => TaskStatus::InProgress,
            'priority' => $validated['priority'],
            'user_id' => $request->user()->id,
            'category_id' => $validated['category_id'] ?? null,
        ]);

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'タスクを登録しました');
    }

    public function edit(Task $task, Request $request): View
    {
        $this->authorize('update', $task);

        $categories = $request
            ->user()
            ->categories()
            ->orderBy('id', 'asc')
            ->get();

        return view('tasks.edit', [
            'task' => $task,
            'categories' => $categories,
        ]);
    }

    public function update(Task $task, UpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $task->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'category_id' => $validated['category_id'] ?? null,
        ]);

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'タスクを更新しました');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('success', 'タスクを削除しました');
    }

    public function complete(Task $task): RedirectResponse
    {
        $this->authorize('complete', $task);

        $task->update([
            'status' => TaskStatus::Completed,
        ]);

        return redirect()
            ->back()
            ->with('success', 'タスクを終了しました');
    }

    public function uncomplete(Task $task): RedirectResponse
    {
        $this->authorize('uncomplete', $task);

        $task->update([
            'status' => TaskStatus::InProgress,
        ]);

        return redirect()
            ->back()
            ->with('success', 'タスクを実施中に戻しました');
    }
}
