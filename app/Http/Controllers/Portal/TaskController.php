<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'pending');

        $tasks = Task::with(['company', 'contact', 'followup'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->orderByRaw("CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC")
            ->paginate(20);

        return view('portal.tasks.index', compact('tasks', 'status'));
    }

    public function complete(Task $task): RedirectResponse
    {
        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        if ($task->followup) {
            $task->followup->update([
                'status' => 'executed',
                'executed_at' => now(),
            ]);
        }

        return back()->with('success', 'Tarefa marcada como concluída!');
    }
}
