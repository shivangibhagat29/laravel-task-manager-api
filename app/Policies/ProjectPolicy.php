<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Users can only see and change their own projects (and tasks inside them).
 * Auto-discovered by Laravel because of the naming convention.
 */
class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $this->owns($user, $project);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->owns($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->owns($user, $project);
    }

    private function owns(User $user, Project $project): bool
    {
        // Cast: some DB drivers return ids as strings.
        return (int) $project->user_id === (int) $user->id;
    }
}
