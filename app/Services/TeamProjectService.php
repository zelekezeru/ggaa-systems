<?php

namespace App\Services;

use App\Exceptions\CapacityThresholdExceededException;
use App\Models\TeamProject;
use App\Models\TeamProjectMember;
use App\Models\User;
use App\Notifications\TeamProjectAssignedNotification;
use App\Notifications\TeamProjectStatusChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Centralises team-project lifecycle: capacity checks on member changes,
 * status transitions, and capacity release on completion / cancellation.
 */
class TeamProjectService
{
    public function getMaxCapacity(?User $user = null): int
    {
        if ($user && $user->staffProfile && !is_null($user->staffProfile->max_capacity)) {
            return (int) $user->staffProfile->max_capacity;
        }
        return (int) config('workforce.max_capacity');
    }

    /**
     * Add a staff user to a project with a complexity share. Validates that
     * the member still fits within their workforce capacity.
     */
    public function addMember(TeamProject $project, User $user, string $roleInTeam, int $complexityShare): TeamProjectMember
    {
        $existing = TeamProjectMember::where('team_project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && is_null($existing->left_at)) {
            return $existing;
        }

        $this->assertCanAccept($user, $complexityShare);

        if ($existing) {
            $existing->update([
                'role_in_team'     => $roleInTeam,
                'complexity_share' => $complexityShare,
                'joined_at'        => now(),
                'left_at'          => null,
            ]);

            $user->notify(new TeamProjectAssignedNotification($project, $roleInTeam));

            return $existing->fresh();
        }

        $member = TeamProjectMember::create([
            'team_project_id'  => $project->id,
            'user_id'          => $user->id,
            'role_in_team'     => $roleInTeam,
            'complexity_share' => $complexityShare,
            'joined_at'        => now(),
        ]);

        $user->notify(new TeamProjectAssignedNotification($project, $roleInTeam));

        return $member;
    }

    /**
     * Remove a member from the project (soft-leave; preserves history).
     * Their capacity share is released immediately.
     */
    public function removeMember(TeamProject $project, User $user): void
    {
        TeamProjectMember::where('team_project_id', $project->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);
    }

    /**
     * Promote a different active member to leader. The previous leader becomes
     * a regular member but stays on the team. team_leader_id is also updated.
     */
    public function changeLeader(TeamProject $project, User $newLeader): void
    {
        DB::transaction(function () use ($project, $newLeader) {
            // Demote the current leader (if still on the team).
            TeamProjectMember::where('team_project_id', $project->id)
                ->whereNull('left_at')
                ->where('role_in_team', 'leader')
                ->update(['role_in_team' => 'member']);

            // Ensure the new leader is on the team and marked as leader.
            $member = TeamProjectMember::firstOrNew([
                'team_project_id' => $project->id,
                'user_id'         => $newLeader->id,
            ]);

            if (! $member->exists) {
                $this->assertCanAccept($newLeader, max(1, (int) $project->complexity_score));
                $member->complexity_share = max(1, (int) $project->complexity_score);
                $member->joined_at        = now();
            }

            $member->role_in_team = 'leader';
            $member->left_at      = null;
            $member->save();

            $project->update(['team_leader_id' => $newLeader->id]);
        });
    }

    /**
     * Mark the project complete. Capacity is released because the global
     * scope on User::getCurrentCapacityLoad() only sums shares for projects
     * whose status is one of TeamProject::ACTIVE_STATUSES.
     */
    public function markCompleted(TeamProject $project): TeamProject
    {
        $project->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        $this->notifyMembers($project, 'completed');

        return $project->fresh();
    }

    public function markCancelled(TeamProject $project): TeamProject
    {
        $project->update([
            'status'       => 'cancelled',
            'completed_at' => now(),
        ]);

        $this->notifyMembers($project, 'cancelled');

        return $project->fresh();
    }

    public function transition(TeamProject $project, string $status): TeamProject
    {
        $allowed = ['planning', 'in_progress', 'in_review', 'completed', 'cancelled'];
        abort_unless(in_array($status, $allowed, true), 422, 'Invalid status.');

        if ($status === 'completed') {
            return $this->markCompleted($project);
        }
        if ($status === 'cancelled') {
            return $this->markCancelled($project);
        }

        $project->update([
            'status'       => $status,
            'completed_at' => null,
        ]);

        $this->notifyMembers($project, $status);

        return $project->fresh();
    }

    private function notifyMembers(TeamProject $project, string $status): void
    {
        $recipients = $project->activeMembers()->with('user')->get()
            ->pluck('user')
            ->filter();

        Notification::send($recipients, new TeamProjectStatusChangedNotification($project, $status));
    }

    private function assertCanAccept(User $user, int $additionalShare): void
    {
        $currentLoad = $user->getCurrentCapacityLoad();
        $max         = $this->getMaxCapacity($user);

        if (($currentLoad + $additionalShare) > $max) {
            throw new CapacityThresholdExceededException($currentLoad, $additionalShare, $max);
        }
    }
}
