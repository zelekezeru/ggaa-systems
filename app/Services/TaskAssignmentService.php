<?php

namespace App\Services;

use App\Exceptions\CapacityThresholdExceededException;
use App\Models\Task;
use App\Models\User;

class TaskAssignmentService
{
    private const MAX_CAPACITY = 30;

    /**
     * Assign an employee to a task after validating their weighted capacity.
     *
     * @throws CapacityThresholdExceededException if adding this client's complexity
     *         would push the employee past the 30-point threshold.
     */
    public function assign(Task $task, User $employee): Task
    {
        // Load the client so we can read its complexity score.
        $task->loadMissing('client');

        $currentLoad    = $employee->getCurrentCapacityLoad();
        $requiredPoints = $task->client->complexity_score;

        if (($currentLoad + $requiredPoints) > self::MAX_CAPACITY) {
            throw new CapacityThresholdExceededException($currentLoad, $requiredPoints, self::MAX_CAPACITY);
        }

        $task->update([
            'assigned_user_id' => $employee->id,
            'status'           => 'To Do',
        ]);

        return $task->fresh(['client', 'template', 'assignedEmployee']);
    }

    /**
     * Remove an employee assignment, returning the task to the unassigned pool.
     */
    public function unassign(Task $task): Task
    {
        $task->update([
            'assigned_user_id' => null,
            'status'           => 'Waiting on Client',
        ]);

        return $task->fresh();
    }

    public function getMaxCapacity(): int
    {
        return self::MAX_CAPACITY;
    }
}
