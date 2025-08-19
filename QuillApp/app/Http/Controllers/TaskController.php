<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    // List tasks (with optional filters)
    public function index(Request $request)
    {
        $query = $request->user()->tasks();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('due')) {
            if ($request->due === 'today') {
                $query->whereDate('due_date', now()->toDateString());
            } elseif ($request->due === 'overdue') {
                $query->whereDate('due_date', '<', now()->toDateString())
                      ->where('status', 'pending');
            }
        }

        return response()->json($query->with('notes')->get());
    }

    // Store a new task
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'nullable|date',
        ]);

        $task = $request->user()->tasks()->create($validated);

        return response()->json($task, 201);
    }

    // Show one task
    public function show(Task $task)
    {
        $this->authorizeTask($task);

        return response()->json($task->load('notes'));
    }

    // Update a task
    public function update(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'due_date'    => 'sometimes|nullable|date',
            'status'      => 'sometimes|in:pending,done',
        ]);

        $task->update($validated);

        return response()->json($task);
    }

    // Delete a task
    public function destroy(Task $task)
    {
        $this->authorizeTask($task);

        $task->delete();

        return response()->json(['message' => 'Task deleted']);
    }

    // Helper: only owner can modify
    private function authorizeTask(Task $task)
    {
        if ($task->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
    }
}
