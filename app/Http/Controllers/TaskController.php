<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Task::class);
        $tasks = Task::with('creator', 'assignedTo')->get();
        return response()->json(['tasks' => $tasks]);
    }

    public function store(Request $request): Response
    {
        $this->authorize('create', Task::class);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:pending,in_progress,completed',
            'priority' => 'in:low,medium,high',
            'due_date' => 'nullable|datetime',
            'assigned_to' => 'nullable|exists:users,id',
        ]);
        
        $validated['created_by'] = auth()->id();
        $task = Task::create($validated);
        return response()->json(['task' => $task], 201);
    }

    public function show(Task $task): Response
    {
        $this->authorize('view', $task);
        $task->load('creator', 'assignedTo');
        return response()->json(['task' => $task]);
    }

    public function update(Request $request, Task $task): Response
    {
        $this->authorize('update', $task);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:pending,in_progress,completed',
            'priority' => 'in:low,medium,high',
            'due_date' => 'nullable|datetime',
            'assigned_to' => 'nullable|exists:users,id',
        ]);
        
        $task->update($validated);
        return response()->json(['task' => $task]);
    }

    public function destroy(Task $task): Response
    {
        $this->authorize('delete', $task);
        $task->delete();
        return response()->json(['message' => 'Task deleted successfully']);
    }
}
