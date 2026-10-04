<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->project = Project::factory()->for($user)->create();
    }

    public function test_creates_a_task_with_defaults(): void
    {
        $this->postJson("/api/v1/projects/{$this->project->id}/tasks", ['title' => 'Write tests'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Write tests')
            ->assertJsonPath('data.status', 'todo')
            ->assertJsonPath('data.priority', 'medium');
    }

    public function test_rejects_invalid_status_and_past_due_date(): void
    {
        $this->postJson("/api/v1/projects/{$this->project->id}/tasks", [
            'title' => 'X',
            'status' => 'archived',
            'due_date' => now()->subDay()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['status', 'due_date']);
    }

    public function test_filters_tasks_by_status(): void
    {
        Task::factory()->for($this->project)->count(2)->create(['status' => TaskStatus::Done]);
        Task::factory()->for($this->project)->count(3)->create(['status' => TaskStatus::Todo]);

        $this->getJson("/api/v1/projects/{$this->project->id}/tasks?status=done")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_overdue_filter_excludes_done_tasks(): void
    {
        Task::factory()->for($this->project)->create(['due_date' => now()->subDays(2), 'status' => TaskStatus::Todo]);
        Task::factory()->for($this->project)->create(['due_date' => now()->subDays(2), 'status' => TaskStatus::Done]);
        Task::factory()->for($this->project)->create(['due_date' => now()->addDays(2), 'status' => TaskStatus::Todo]);

        $this->getJson("/api/v1/projects/{$this->project->id}/tasks?overdue=1")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_sorts_by_due_date_and_ignores_unknown_sort_columns(): void
    {
        Task::factory()->for($this->project)->create(['title' => 'Later', 'due_date' => now()->addDays(5)]);
        Task::factory()->for($this->project)->create(['title' => 'Sooner', 'due_date' => now()->addDay()]);

        $this->getJson("/api/v1/projects/{$this->project->id}/tasks?sort=due_date")
            ->assertJsonPath('data.0.title', 'Sooner');

        $this->getJson("/api/v1/projects/{$this->project->id}/tasks?sort=password")->assertOk();
    }

    public function test_updates_task_status(): void
    {
        $task = Task::factory()->for($this->project)->create(['status' => TaskStatus::Todo]);

        $this->patchJson("/api/v1/projects/{$this->project->id}/tasks/{$task->id}", ['status' => 'done'])
            ->assertOk()
            ->assertJsonPath('data.status', 'done');
    }

    public function test_task_from_another_project_returns_404(): void
    {
        $foreignTask = Task::factory()->create(); // belongs to a different project

        $this->getJson("/api/v1/projects/{$this->project->id}/tasks/{$foreignTask->id}")->assertNotFound();
    }

    public function test_cannot_add_tasks_to_someone_elses_project(): void
    {
        $other = Project::factory()->create();

        $this->postJson("/api/v1/projects/{$other->id}/tasks", ['title' => 'Sneaky'])->assertForbidden();
    }
}
