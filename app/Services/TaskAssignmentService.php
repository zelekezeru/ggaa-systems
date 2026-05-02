<?php

namespace App\Services;

use App\Exceptions\CapacityThresholdExceededException;
use App\Models\Branch;
use App\Models\Task;
use App\Models\User;
use App\Notifications\CapacityWarningNotification;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Support\Facades\Notification;

class TaskAssignmentService
{
    public function __construct(private readonly ?CapacityMonitor $capacityMonitor = null)
    {
    }

    /**
     * Assign an employee to a task after validating their weighted capacity.
     *
     * @throws CapacityThresholdExceededException if adding this client's complexity
     *         would push the employee past the configured workforce threshold.
     */
    public function assign(Task $task, User $employee): Task
    {
        $task->loadMissing('client');

        $currentLoad    = $employee->getCurrentCapacityLoad();
        $requiredPoints = $task->client->complexity_score;
        $maxCapacity    = $this->getMaxCapacity();

        if (($currentLoad + $requiredPoints) > $maxCapacity) {
            throw new CapacityThresholdExceededException($currentLoad, $requiredPoints, $maxCapacity);
        }

        $task->update([
            'assigned_user_id' => $employee->id,
            'status'           => 'To Do',
        ]);

        $employee->notify(new TaskAssignedNotification($task->fresh(['client', 'template'])));

        // After assignment, check whether the team is now running hot and notify managers.
        $this->capacityMonitor?->checkBranchAndNotify($employee->branch);

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
        return (int) config('workforce.max_capacity');
    }
}
