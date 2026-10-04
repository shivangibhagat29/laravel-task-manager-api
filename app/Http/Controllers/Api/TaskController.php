<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Nested resource: /api/projects/{project}/tasks/{task}
 * Routes are ->scoped(), so a task from another project returns 404.
 */
class TaskController extends Controller
{
    private const SORTABLE = ['due_date', 'priority', 'created_at', 'title'];

    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        Gate::authorize('view', $project);

        // ?sort=-due_date  (minus = descending). Whitelisted to avoid SQL injection.
        $sort = $request->query('sort', '-created_at');
        $column = ltrim($sort, '-');
        $column = in_array($column, self::SORTABLE, true) ? $column : 'created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $tasks = $project->tasks()
            ->status($request->query('status'))
            ->overdue($request->boolean('overdue'))
            ->orderBy($column, $direction)
            ->paginate(min($request->integer('per_page', 20), 100)) // cap page size
            ->withQueryString();

        return TaskResource::collection($tasks);
    }

    public function store(TaskRequest $request, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $task = $project->tasks()->create($request->validated());

        return (new TaskResource($task->refresh()))->response()->setStatusCode(201);
    }

    public function show(Project $project, Task $task): TaskResource
    {
        Gate::authorize('view', $project);

        return new TaskResource($task);
    }

    public function update(TaskRequest $request, Project $project, Task $task): TaskResource
    {
        Gate::authorize('update', $project);

        $task->update($request->validated());

        return new TaskResource($task);
    }

    public function destroy(Project $project, Task $task): Response
    {
        Gate::authorize('update', $project);

        $task->delete();

        return response()->noContent();
    }
}
