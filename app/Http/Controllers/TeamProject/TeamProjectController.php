<?php

namespace App\Http\Controllers\TeamProject;

use App\Exceptions\CapacityThresholdExceededException;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Message;
use App\Models\StaffUser;
use App\Models\TeamProject;
use App\Models\User;
use App\Services\TeamProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TeamProjectController extends Controller
{
    public function __construct(private readonly TeamProjectService $service)
    {
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->can('view team projects') || $user->hasRole('Client'), 403);

        $projects = TeamProject::query()
            ->when($user->hasRole('Client'), fn($q) => $q->where('client_id', $user->client_id))
            ->with(['client:id,company_name', 'branch:id,name', 'teamLeader:id,name', 'activeMembers.user:id,name'])
            ->withCount(['todos', 'activeMembers'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->orderByRaw("FIELD(status, 'in_progress','planning','in_review','completed','cancelled')")
            ->orderByDesc('due_date')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total'       => TeamProject::count(),
            'active'      => TeamProject::whereIn('status', TeamProject::ACTIVE_STATUSES)->count(),
            'completed'   => TeamProject::where('status', 'completed')->count(),
            'overdue'     => TeamProject::whereIn('status', TeamProject::ACTIVE_STATUSES)
                                ->whereDate('due_date', '<', now())->count(),
        ];

        return Inertia::render('TeamProjects/Index', [
            'projects' => $projects,
            'stats'    => $stats,
            'filters'  => $request->only('status', 'search'),
            'canManage' => $user->can('manage team projects'),
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        abort_unless($user->can('manage team projects'), 403);

        return Inertia::render('TeamProjects/Create', [
            'branches'      => Branch::where('is_active', true)->orderBy('name', 'asc')->get(['id', 'name']),
            'clients'       => Client::query()->orderBy('company_name', 'asc')->get(['id', 'company_name', 'branch_id']),
            'staffOptions'  => $this->staffOptions(),
            'maxCapacity'   => $this->service->getMaxCapacity(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->can('manage team projects'), 403);

        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'client_id'         => 'nullable|exists:clients,id',
            'branch_id'         => 'required|exists:branches,id',
            'team_leader_id'    => 'required|exists:users,id',
            'priority'          => 'required|in:low,normal,high,urgent',
            'start_date'        => 'nullable|date',
            'due_date'          => 'required|date|after_or_equal:today',
            'complexity_score'  => 'required|integer|min:1|max:10',
            'members'           => 'nullable|array',
            'members.*.user_id' => 'required|exists:users,id',
            'members.*.complexity_share' => 'required|integer|min:1|max:10',
        ]);

        try {
            $project = DB::transaction(function () use ($validated, $user) {
                $project = TeamProject::create([
                    'title'            => $validated['title'],
                    'description'      => $validated['description'] ?? null,
                    'client_id'        => $validated['client_id'] ?? null,
                    'branch_id'        => $validated['branch_id'],
                    'team_leader_id'   => $validated['team_leader_id'],
                    'created_by'       => $user->id,
                    'priority'         => $validated['priority'],
                    'start_date'       => $validated['start_date'] ?? now()->toDateString(),
                    'due_date'         => $validated['due_date'],
                    'complexity_score' => $validated['complexity_score'],
                    'status'           => 'planning',
                ]);

                // Add the leader as a member (their share defaults to project complexity).
                $leader = User::findOrFail($validated['team_leader_id']);
                $this->service->addMember($project, $leader, 'leader', (int) $validated['complexity_score']);

                foreach ($validated['members'] ?? [] as $member) {
                    if ((int) $member['user_id'] === (int) $validated['team_leader_id']) {
                        continue;
                    }
                    $staffMember = User::findOrFail($member['user_id']);
                    $this->service->addMember(
                        $project,
                        $staffMember,
                        'member',
                        (int) $member['complexity_share'],
                    );
                }

                return $project;
            });
        } catch (CapacityThresholdExceededException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('team-projects.show', $project->id)
            ->with('success', 'Team project created.');
    }

    public function show(TeamProject $teamProject)
    {
        $user = Auth::user();

        $teamProject->load([
            'client:id,company_name,tin_number,sector,logo_path,status', 'branch', 'teamLeader',
            'creator:id,name',
            'activeMembers.user:id,name,email,profile_photo_path',
            'todos.assignee:id,name',
            'todos.creator:id,name',
            'files.uploader:id,name',
            'messages.sender:id,name,profile_photo_path',
        ]);

        $userId = $user->id;
        $isMember = $teamProject->activeMembers->contains(fn ($m) => $m->user_id === $userId);
        $isLeader = $teamProject->team_leader_id === $userId;
        $isClient = $user->hasRole('Client') && $teamProject->client_id === $user->client_id;
        $canManage = $user->can('manage team projects') || $isLeader;

        abort_unless($user->can('view team projects') || $isMember || $isLeader || $isClient, 403);

        // Ledgers attached to this team project — visible to members or admins
        $ledgers = \App\Models\MonthlyLedger::where('team_project_id', $teamProject->id)
            ->orWhere(fn ($q) => $teamProject->client_id ? $q->where('client_id', $teamProject->client_id)->whereNull('team_project_id') : null)
            ->with(['client:id,company_name', 'submittedBy:id,name', 'verifiedBy:id,name'])
            ->orderByDesc('eth_year')
            ->get()
            ->map(fn ($l) => [
                'id'          => $l->id,
                'client'      => $l->client?->company_name,
                'eth_year'    => $l->eth_year,
                'eth_month'   => $l->eth_month,
                'status'      => $l->status,
                'net_profit'  => $l->net_profit,
                'submitted_by'=> $l->submittedBy?->name,
                'verified_by' => $l->verifiedBy?->name,
            ]);

        return Inertia::render('TeamProjects/Show', [
            'project'     => $teamProject,
            'isMember'    => $isMember,
            'isLeader'    => $isLeader,
            'isClient'    => $isClient,
            'canManage'   => $canManage,
            'staffOptions' => $canManage ? $this->staffOptions() : [],
            'ledgers'     => $ledgers,
        ]);
    }

    public function edit(TeamProject $teamProject)
    {
        $user = Auth::user();
        abort_unless($user->can('manage team projects') || $teamProject->team_leader_id === $user->id, 403);

        $teamProject->load(['activeMembers.user:id,name']);

        return Inertia::render('TeamProjects/Edit', [
            'project'       => $teamProject,
            'branches'      => Branch::where('is_active', true)->orderBy('name', 'asc')->get(['id', 'name']),
            'clients'       => Client::query()->orderBy('company_name', 'asc')->get(['id', 'company_name', 'branch_id']),
            'staffOptions'  => $this->staffOptions(),
            'maxCapacity'   => $this->service->getMaxCapacity(),
        ]);
    }

    public function update(Request $request, TeamProject $teamProject)
    {
        $user = Auth::user();
        $isLeader = $teamProject->team_leader_id === $user->id;
        abort_unless($user->can('manage team projects') || $isLeader, 403);

        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'client_id'         => 'nullable|exists:clients,id',
            'branch_id'         => 'required|exists:branches,id',
            'team_leader_id'    => 'required|exists:users,id',
            'priority'          => 'required|in:low,normal,high,urgent',
            'start_date'        => 'nullable|date',
            'due_date'          => 'required|date',
            'complexity_score'  => 'required|integer|min:1|max:10',
        ]);

        try {
            DB::transaction(function () use ($validated, $teamProject) {
                // If the leader changed, use the service to handle demotion/promotion.
                if ((int) $validated['team_leader_id'] !== (int) $teamProject->team_leader_id) {
                    $this->service->changeLeader($teamProject, User::findOrFail($validated['team_leader_id']));
                }

                $teamProject->update($validated);
            });
        } catch (CapacityThresholdExceededException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('team-projects.show', $teamProject->id)
            ->with('success', 'Project updated.');
    }

    public function destroy(TeamProject $teamProject)
    {
        $user = Auth::user();
        abort_unless($user->can('manage team projects'), 403);

        $teamProject->delete();

        return redirect()->route('team-projects.index')->with('success', 'Project deleted.');
    }

    public function transition(Request $request, TeamProject $teamProject)
    {
        $user = Auth::user();
        $isLeader = $teamProject->team_leader_id === $user->id;
        abort_unless($user->can('manage team projects') || $isLeader, 403);

        $validated = $request->validate([
            'status' => 'required|in:planning,in_progress,in_review,completed,cancelled',
        ]);

        $this->service->transition($teamProject, $validated['status']);

        return back()->with('success', 'Project status updated.');
    }

    public function addMember(Request $request, TeamProject $teamProject)
    {
        $user = Auth::user();
        $isLeader = $teamProject->team_leader_id === $user->id;
        abort_unless($user->can('manage team projects') || $isLeader, 403);

        $validated = $request->validate([
            'user_id'          => 'required|exists:users,id',
            'complexity_share' => 'required|integer|min:1|max:10',
            'role_in_team'     => 'nullable|in:leader,member',
        ]);

        try {
            $this->service->addMember(
                $teamProject,
                User::findOrFail($validated['user_id']),
                $validated['role_in_team'] ?? 'member',
                (int) $validated['complexity_share'],
            );
        } catch (CapacityThresholdExceededException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Member added to project.');
    }

    public function removeMember(TeamProject $teamProject, User $user)
    {
        $authUser = Auth::user();
        $isLeader = $teamProject->team_leader_id === $authUser->id;
        abort_unless($authUser->can('manage team projects') || $isLeader, 403);

        // Refuse to remove the leader directly — must transfer leadership first.
        if ($teamProject->team_leader_id === $user->id) {
            return back()->with('error', 'Transfer leadership before removing the team leader.');
        }

        $this->service->removeMember($teamProject, $user);

        return back()->with('success', 'Member removed.');
    }

    public function changeLeader(Request $request, TeamProject $teamProject)
    {
        $user = Auth::user();
        abort_unless($user->can('manage team projects'), 403);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $this->service->changeLeader($teamProject, User::findOrFail($validated['user_id']));
        } catch (CapacityThresholdExceededException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Team leader updated.');
    }

    /**
     * Project-scoped client communication.
     * Only the team leader (or Super Admin / Branch Manager) may post —
     * this enforces the rule that client comms route through the leader
     * within the team-project context.
     */
    public function sendClientMessage(Request $request, TeamProject $teamProject)
    {
        abort_unless($teamProject->client_id, 422, 'This project has no client.');

        $user = Auth::user();
        $isLeader = $teamProject->team_leader_id === $user->id;
        $isClient = $user->hasRole('Client') && $teamProject->client_id === $user->client_id;
        abort_unless(
            $isLeader || $user->hasRole('Super Admin') || $user->hasRole('Branch Manager') || $isClient,
            403,
            'Unauthorized to post in this thread.'
        );

        $request->validate([
            'body'       => 'required_without:attachment|nullable|string|max:5000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('firm_files/outbound', 'local');
        }

        Message::create([
            'client_id'         => $teamProject->client_id,
            'sender_id'         => $user->id,
            'body'              => $request->input('body'),
            'attachment_path'   => $attachmentPath,
            'is_read_by_client' => false,
        ]);

        return back()->with('success', 'Message sent to client.');
    }

    public function clientThread(TeamProject $teamProject)
    {
        abort_unless($teamProject->client_id, 422, 'This project has no client.');
        $this->authorizeMember($teamProject);

        $messages = Message::with('sender:id,name')
            ->where('client_id', $teamProject->client_id)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'id'         => $m->id,
                'body'       => $m->body,
                'sender'     => $m->sender ? ['id' => $m->sender->id, 'name' => $m->sender->name] : null,
                'attachment' => $m->attachment_path ? route('messages.attachment', $m->id) : null,
                'created_at' => $m->created_at->toISOString(),
            ]);

        return response()->json(['messages' => $messages]);
    }

    private function authorizeMember(TeamProject $teamProject): void
    {
        $user = Auth::user();

        // Admin, Manager, or Team Leader
        if ($user->can('manage team projects') || $teamProject->team_leader_id === $user->id) {
            return;
        }

        // Assigned Client
        if ($user->hasRole('Client') && $teamProject->client_id === $user->client_id) {
            return;
        }

        // Active Team Member
        $isMember = $teamProject->activeMembers()->where('user_id', $user->id)->exists();
        abort_unless($isMember, 403);
    }

    private function staffOptions(): \Illuminate\Support\Collection
    {
        return User::query()
            ->whereHas('staffProfile', fn ($q) => $q->where('is_active', true))
            ->with('staffProfile:id,user_id,position,position_title')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'email', 'branch_id'])
            ->map(function (User $u) {
                return [
                    'id'                 => $u->id,
                    'name'               => $u->name,
                    'email'              => $u->email,
                    'branch_id'          => $u->branch_id,
                    'position'           => $u->staffProfile?->position,
                    'position_label'     => StaffUser::POSITIONS[$u->staffProfile?->position] ?? null,
                    'capacity_load'      => $u->getCurrentCapacityLoad(),
                    'remaining_capacity' => $u->remaining_capacity,
                ];
            });
    }
}
