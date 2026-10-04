<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_lists_only_my_projects_with_task_counts(): void
    {
        Project::factory()->for($this->user)->has(Task::factory()->count(3))->create();
        Project::factory()->create(); // someone else's project

        $this->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tasks_count', 3)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_creates_a_project(): void
    {
        $this->postJson('/api/v1/projects', ['name' => 'Website revamp'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Website revamp');

        $this->assertDatabaseHas('projects', ['name' => 'Website revamp', 'user_id' => $this->user->id]);
    }

    public function test_project_name_is_required(): void
    {
        $this->postJson('/api/v1/projects', [])->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_updates_my_project(): void
    {
        $project = Project::factory()->for($this->user)->create();

        $this->patchJson("/api/v1/projects/{$project->id}", ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed');
    }

    public function test_cannot_view_update_or_delete_someone_elses_project(): void
    {
        $other = Project::factory()->create();

        $this->getJson("/api/v1/projects/{$other->id}")->assertForbidden();
        $this->patchJson("/api/v1/projects/{$other->id}", ['name' => 'x'])->assertForbidden();
        $this->deleteJson("/api/v1/projects/{$other->id}")->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $other->id]);
    }

    public function test_deleting_a_project_deletes_its_tasks(): void
    {
        $project = Project::factory()->for($this->user)->has(Task::factory()->count(2))->create();

        $this->deleteJson("/api/v1/projects/{$project->id}")->assertNoContent();

        $this->assertDatabaseCount('tasks', 0);
    }
}
