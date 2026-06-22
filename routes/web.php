<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\Employee\WorkspaceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SuperAdmin\AvailabilityController;
use App\Http\Controllers\SuperAdmin\BranchOverviewController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\RoleManagementController;
use App\Http\Controllers\SuperAdmin\DailyTaskController;
use App\Http\Controllers\SuperAdmin\TaskManagementController;
use App\Http\Controllers\SuperAdmin\TaskTemplateController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\Finance\BillingController;
use App\Http\Controllers\AdminClientController;
use App\Http\Controllers\ServiceTypeController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\ClientPortalController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\MonthlyLedgerController;
use App\Http\Controllers\Finance\LedgerProgressController;
use App\Http\Controllers\Finance\InvoiceController as FinanceInvoiceController;
use App\Http\Controllers\Client\LedgerController as ClientLedgerController;
use App\Http\Controllers\Client\InvoiceController as ClientInvoiceController;
use App\Http\Controllers\TeamProject\TeamProjectController;
use App\Http\Controllers\TeamProject\TeamProjectFileController;
use App\Http\Controllers\TeamProject\TeamProjectMessageController;
use App\Http\Controllers\TeamProject\TeamProjectTodoController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        // Framework/PHP versions deliberately omitted — exposing them to
        // unauthenticated visitors only aids version-specific fingerprinting.
    ]);
});

// Client portal landing and login alias
Route::get('/portal', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->hasRole('Client')) {
            return redirect()->route('client.dashboard');
        }

        return redirect()->route('dashboard');
    }

    return Inertia::render('Portal/Landing');
})->name('portal.landing');

Route::get('/portal/login', function () {
    return redirect()->route('login');
})->name('portal.login');

// Smart redirect: send each role to their own portal
Route::get('/dashboard', function () {
    /** @var \App\Models\User $user */
    $user = Auth::user();
    return redirect()->route($user->homeRoute());
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications (all roles)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/inbox', [NotificationController::class, 'inbox'])->name('notifications.inbox');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Task comments — visibility piggy-backs on the Task global scope.
    Route::get('/tasks/{task}/comments', [TaskCommentController::class, 'index'])->name('tasks.comments.index');
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('tasks.comments.store');
    Route::delete('/tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('tasks.comments.destroy');
    Route::get('/tasks/{task}/comments/{comment}/attachment', [TaskCommentController::class, 'downloadAttachment'])->name('tasks.comments.attachment');

    // Secure Task report downloads
    Route::get('/tasks/{task}/documents/download', [TaskController::class, 'downloadDocument'])->name('tasks.documents.download');

    // Private-disk attachment streams (auth-checked inside the controllers).
    Route::get('/messages/{message}/attachment', [MessageController::class, 'downloadAttachment'])->name('messages.attachment');
    Route::get('/invoice-payments/{payment}/receipt', [BillingController::class, 'downloadReceipt'])->name('invoice-payments.receipt');
});

// --- CLIENT PORTAL ---
Route::middleware(['auth', 'role:Client'])->group(function () {
    Route::get('/portal/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');
    Route::get('/portal/tasks/{task}', [ClientPortalController::class, 'showTask'])->name('client.tasks.show');
    Route::post('/portal/tasks/{task}/upload', [ClientPortalController::class, 'uploadFiles'])->name('client.tasks.upload');
    Route::get('/portal/messages', [MessageController::class, 'indexForClient'])->name('client.messages.index');
    Route::post('/portal/messages', [MessageController::class, 'storeFromClient'])->name('client.messages.store');
});

// --- SUPER ADMIN ---
Route::middleware(['auth', 'role:Super Admin|Branch Manager|Operation Manager'])->group(function () {
    Route::get('/super-admin/dashboard', [SuperAdminDashboardController::class, 'index'])->name('super-admin.dashboard');
    Route::get('/super-admin/branches', [SuperAdminController::class, 'branches'])->name('super-admin.branches');
    Route::get('/super-admin/branches/{branch}', [BranchOverviewController::class, 'show'])->name('super-admin.branches.show');
    Route::get('/super-admin/staff', [StaffController::class, 'index'])->name('super-admin.staff');
    Route::get('/super-admin/clients', [AdminClientController::class, 'index'])->name('super-admin.clients');
    Route::get('/super-admin/reports', [SuperAdminController::class, 'reports'])->name('super-admin.reports');
    Route::get('/super-admin/availability', [AvailabilityController::class, 'index'])->name('super-admin.availability');

    // Branch CRUD operations
    Route::post('/super-admin/branches', [BranchController::class, 'store'])->name('admin.branches.store');
    Route::put('/super-admin/branches/{branch}', [BranchController::class, 'update'])->name('admin.branches.update');
    Route::delete('/super-admin/branches/{branch}', [BranchController::class, 'destroy'])->name('admin.branches.destroy');

    // Staff CRUD operations
    Route::post('/super-admin/staff', [StaffController::class, 'store'])->name('admin.staff.store');
    Route::put('/super-admin/staff/{staff}', [StaffController::class, 'update'])->name('admin.staff.update');
    Route::delete('/super-admin/staff/{staff}', [StaffController::class, 'destroy'])->name('admin.staff.destroy');
    Route::post('/super-admin/staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('admin.staff.reset-password');
    Route::post('/super-admin/staff/send-email', [StaffController::class, 'sendBulkEmail'])->name('admin.staff.send-email');

    Route::get('/super-admin/documents', [\App\Http\Controllers\SuperAdmin\DocumentManagementController::class, 'index'])->name('super-admin.documents');
    Route::post('/super-admin/documents', [\App\Http\Controllers\SuperAdmin\DocumentManagementController::class, 'storeDocument'])->name('admin.documents.store');
    Route::post('/super-admin/documents/place', [\App\Http\Controllers\SuperAdmin\DocumentManagementController::class, 'placeDocument'])->name('admin.documents.place');
    Route::post('/super-admin/documents/{document}/update', [\App\Http\Controllers\SuperAdmin\DocumentManagementController::class, 'updateDocument'])->name('admin.documents.update');
    Route::post('/super-admin/documents/{document}/retrieve', [\App\Http\Controllers\SuperAdmin\DocumentManagementController::class, 'retrieveDocument'])->name('admin.documents.retrieve');
    Route::post('/super-admin/documents/types', [\App\Http\Controllers\SuperAdmin\DocumentManagementController::class, 'storeType'])->name('admin.documents.types.store');
    Route::post('/super-admin/shelves', [\App\Http\Controllers\SuperAdmin\DocumentManagementController::class, 'storeShelf'])->name('admin.shelves.store');
    Route::post('/super-admin/shelves/{shelf}/update', [\App\Http\Controllers\SuperAdmin\DocumentManagementController::class, 'updateShelf'])->name('admin.shelves.update');

    // Client CRUD operations
    Route::get('/super-admin/clients/{client}', [\App\Http\Controllers\AdminClientController::class, 'show'])->name('super-admin.clients.show');
    Route::post('/super-admin/clients', [AdminClientController::class, 'store'])->name('admin.clients.store');
    Route::put('/super-admin/clients/{client}', [AdminClientController::class, 'update'])->name('admin.clients.update');
    Route::delete('/super-admin/clients/{client}', [AdminClientController::class, 'destroy'])->name('admin.clients.destroy');
    Route::post('/super-admin/clients/{client}/reset-password', [AdminClientController::class, 'resetPassword'])->name('admin.clients.reset-password');
    Route::post('/super-admin/clients/{client}/reveal-etrade', [AdminClientController::class, 'revealEtradePassword'])->name('admin.clients.reveal-etrade');
    Route::post('/super-admin/legal-structures', [AdminClientController::class, 'storeLegalStructure'])->name('admin.legal-structures.store');

    // Staff show page
    Route::get('/super-admin/staff/{staff}', [\App\Http\Controllers\StaffController::class, 'show'])->name('super-admin.staff.show');

    // Service Type CRUD
    Route::get('/super-admin/service-types', [ServiceTypeController::class, 'index'])->name('super-admin.service-types');
    Route::post('/super-admin/service-types', [ServiceTypeController::class, 'store'])->name('admin.service-types.store');
    Route::put('/super-admin/service-types/{serviceType}', [ServiceTypeController::class, 'update'])->name('admin.service-types.update');
    Route::delete('/super-admin/service-types/{serviceType}', [ServiceTypeController::class, 'destroy'])->name('admin.service-types.destroy');

    // Role & Permission Management
    Route::get('/super-admin/roles', [RoleManagementController::class, 'index'])->name('super-admin.roles.index');
    Route::post('/super-admin/roles', [RoleManagementController::class, 'store'])->name('super-admin.roles.store');
    Route::put('/super-admin/roles/{role}', [RoleManagementController::class, 'update'])->name('super-admin.roles.update');
    Route::delete('/super-admin/roles/{role}', [RoleManagementController::class, 'destroy'])->name('super-admin.roles.destroy');

    // Permission Management
    Route::post('/super-admin/permissions', [RoleManagementController::class, 'storePermission'])->name('super-admin.permissions.store');
    Route::put('/super-admin/permissions/{permission}', [RoleManagementController::class, 'updatePermission'])->name('super-admin.permissions.update');
    Route::delete('/super-admin/permissions/{permission}', [RoleManagementController::class, 'destroyPermission'])->name('super-admin.permissions.destroy');

    // User Role Assignment & Removal
    Route::put('/super-admin/users/{user}/roles', [RoleManagementController::class, 'updateUserRoles'])->name('super-admin.users.roles');
    Route::delete('/super-admin/users/{user}', [RoleManagementController::class, 'destroyUser'])->name('super-admin.users.destroy');

    // Task Management
    Route::get('/super-admin/tasks', [TaskManagementController::class, 'index'])->name('super-admin.tasks.index');
    Route::post('/super-admin/tasks', [TaskManagementController::class, 'store'])->name('super-admin.tasks.store');
    Route::put('/super-admin/tasks/{task}', [TaskManagementController::class, 'update'])->name('super-admin.tasks.update');
    Route::delete('/super-admin/tasks/{task}', [TaskManagementController::class, 'destroy'])->name('super-admin.tasks.destroy');

    // Staff Performance Evaluations
    Route::get('/super-admin/evaluations', [EvaluationController::class, 'index'])->name('super-admin.evaluations.index');
    Route::get('/super-admin/evaluations/staff/{staff}', [EvaluationController::class, 'show'])->name('super-admin.evaluations.show');
    Route::post('/super-admin/evaluations/{evaluation}/scores', [EvaluationController::class, 'saveScores'])->name('super-admin.evaluations.scores');
    Route::post('/super-admin/evaluations/{evaluation}/finalize', [EvaluationController::class, 'finalize'])->name('super-admin.evaluations.finalize');
    Route::post('/super-admin/evaluations/{evaluation}/reopen', [EvaluationController::class, 'reopen'])->name('super-admin.evaluations.reopen');
    Route::post('/super-admin/evaluations/staff/{staff}/metrics', [EvaluationController::class, 'attachMetric'])->name('super-admin.evaluations.metrics.attach');
    Route::put('/super-admin/evaluations/staff/{staff}/metrics/{assignment}', [EvaluationController::class, 'updateMetricWeight'])->name('super-admin.evaluations.metrics.update');
    Route::delete('/super-admin/evaluations/staff/{staff}/metrics/{assignment}', [EvaluationController::class, 'detachMetric'])->name('super-admin.evaluations.metrics.detach');
    Route::post('/super-admin/evaluation-metrics', [EvaluationController::class, 'storeMetric'])->name('super-admin.evaluation-metrics.store');

    // Task Types (Templates)
    Route::get('/super-admin/task-types', [TaskTemplateController::class, 'index'])->name('super-admin.task-types.index');
    Route::post('/super-admin/task-types', [TaskTemplateController::class, 'store'])->name('super-admin.task-types.store');
    Route::put('/super-admin/task-types/{taskTemplate}', [TaskTemplateController::class, 'update'])->name('super-admin.task-types.update');
    Route::delete('/super-admin/task-types/{taskTemplate}', [TaskTemplateController::class, 'destroy'])->name('super-admin.task-types.destroy');

    // Task assignment
    Route::post('/tasks/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign');
    Route::post('/tasks/{task}/unassign', [TaskController::class, 'unassign'])->name('tasks.unassign');

    // Daily Task Management
    Route::get('/super-admin/daily-tasks', [DailyTaskController::class, 'index'])->name('admin.daily-tasks.index');
    Route::post('/super-admin/daily-tasks', [DailyTaskController::class, 'store'])->name('admin.daily-tasks.store');
    Route::patch('/super-admin/daily-tasks/{dailyTask}/status', [DailyTaskController::class, 'updateStatus'])->name('admin.daily-tasks.status');
    Route::delete('/super-admin/daily-tasks/{dailyTask}', [DailyTaskController::class, 'destroy'])->name('admin.daily-tasks.destroy');

    // Team Project management (admin/manager only — create/delete/leader changes)
    Route::get('/team-projects/create', [TeamProjectController::class, 'create'])->name('team-projects.create');
    Route::post('/team-projects', [TeamProjectController::class, 'store'])->name('team-projects.store');
    Route::delete('/team-projects/{teamProject}', [TeamProjectController::class, 'destroy'])->name('team-projects.destroy');
    Route::post('/team-projects/{teamProject}/leader', [TeamProjectController::class, 'changeLeader'])->name('team-projects.change-leader');
});

// --- TEAM PROJECTS (any active staff or client member) ---
Route::middleware(['auth'])->group(function () {
    Route::get('/team-projects', [TeamProjectController::class, 'index'])->name('team-projects.index');
    Route::get('/team-projects/{teamProject}', [TeamProjectController::class, 'show'])->name('team-projects.show');
    Route::get('/team-projects/{teamProject}/edit', [TeamProjectController::class, 'edit'])->name('team-projects.edit');
    Route::put('/team-projects/{teamProject}', [TeamProjectController::class, 'update'])->name('team-projects.update');
    Route::post('/team-projects/{teamProject}/transition', [TeamProjectController::class, 'transition'])->name('team-projects.transition');

    // Members (leader or admin)
    Route::post('/team-projects/{teamProject}/members', [TeamProjectController::class, 'addMember'])->name('team-projects.members.add');
    Route::delete('/team-projects/{teamProject}/members/{user}', [TeamProjectController::class, 'removeMember'])->name('team-projects.members.remove');

    // Plan / todolist
    Route::post('/team-projects/{teamProject}/todos', [TeamProjectTodoController::class, 'store'])->name('team-projects.todos.store');
    Route::patch('/team-projects/{teamProject}/todos/{todo}', [TeamProjectTodoController::class, 'update'])->name('team-projects.todos.update');
    Route::delete('/team-projects/{teamProject}/todos/{todo}', [TeamProjectTodoController::class, 'destroy'])->name('team-projects.todos.destroy');

    // Shared files
    Route::post('/team-projects/{teamProject}/files', [TeamProjectFileController::class, 'store'])->name('team-projects.files.store');
    Route::get('/team-projects/{teamProject}/files/{file}/download', [TeamProjectFileController::class, 'download'])->name('team-projects.files.download');
    Route::delete('/team-projects/{teamProject}/files/{file}', [TeamProjectFileController::class, 'destroy'])->name('team-projects.files.destroy');

    // Internal team chat
    Route::post('/team-projects/{teamProject}/messages', [TeamProjectMessageController::class, 'store'])->name('team-projects.messages.store');
    Route::get('/team-projects/{teamProject}/messages/{message}/attachment', [TeamProjectMessageController::class, 'downloadAttachment'])->name('team-projects.messages.attachment');

    // Project-scoped client thread (leader-gated for posting)
    Route::get('/team-projects/{teamProject}/client-thread', [TeamProjectController::class, 'clientThread'])->name('team-projects.client-thread');
    Route::post('/team-projects/{teamProject}/client-message', [TeamProjectController::class, 'sendClientMessage'])->name('team-projects.client-message');
});

// --- CLIENT TEAM PROJECTS ---
Route::prefix('client')->middleware(['auth', 'role:Client'])->group(function () {
    Route::get('/team-projects', [TeamProjectController::class, 'index'])->name('client.team-projects.index');
    Route::get('/team-projects/{teamProject}', [TeamProjectController::class, 'show'])->name('client.team-projects.show');
    Route::get('/team-projects/{teamProject}/client-thread', [TeamProjectController::class, 'clientThread'])->name('client.team-projects.client-thread');
});

// --- EMPLOYEE TEAM PROJECTS ---
Route::prefix('employee')->middleware(['auth', 'can:view team projects'])->group(function () {
    Route::get('/team-projects', [TeamProjectController::class, 'index'])->name('employee.team-projects.index');
    Route::get('/team-projects/{teamProject}', [TeamProjectController::class, 'show'])->name('employee.team-projects.show');
    Route::get('/team-projects/{teamProject}/edit', [TeamProjectController::class, 'edit'])->name('employee.team-projects.edit');
    Route::put('/team-projects/{teamProject}', [TeamProjectController::class, 'update'])->name('employee.team-projects.update');
    Route::post('/team-projects/{teamProject}/transition', [TeamProjectController::class, 'transition'])->name('employee.team-projects.transition');
    Route::post('/team-projects/{teamProject}/members', [TeamProjectController::class, 'addMember'])->name('employee.team-projects.members.add');
    Route::delete('/team-projects/{teamProject}/members/{user}', [TeamProjectController::class, 'removeMember'])->name('employee.team-projects.members.remove');
    Route::post('/team-projects/{teamProject}/todos', [TeamProjectTodoController::class, 'store'])->name('employee.team-projects.todos.store');
    Route::patch('/team-projects/{teamProject}/todos/{todo}', [TeamProjectTodoController::class, 'update'])->name('employee.team-projects.todos.update');
    Route::delete('/team-projects/{teamProject}/todos/{todo}', [TeamProjectTodoController::class, 'destroy'])->name('employee.team-projects.todos.destroy');
    Route::post('/team-projects/{teamProject}/files', [TeamProjectFileController::class, 'store'])->name('employee.team-projects.files.store');
    Route::delete('/team-projects/{teamProject}/files/{file}', [TeamProjectFileController::class, 'destroy'])->name('employee.team-projects.files.destroy');
    Route::post('/team-projects/{teamProject}/messages', [TeamProjectMessageController::class, 'store'])->name('employee.team-projects.messages.store');
});

// --- FINANCIAL LEDGER (permission-gated) ---
Route::middleware(['auth'])->group(function () {
    Route::get('/ledger', [MonthlyLedgerController::class, 'index'])
        ->middleware('can:view ledger index')->name('ledger.index');
    Route::get('/ledger/clients/{client}', [MonthlyLedgerController::class, 'show'])
        ->middleware('can:enter ledger data')->name('ledger.show');
    Route::post('/ledger/clients/{client}/entries', [MonthlyLedgerController::class, 'store'])
        ->middleware('can:enter ledger data')->name('ledger.store');
    Route::put('/ledger/entries/{ledger}', [MonthlyLedgerController::class, 'update'])
        ->middleware('can:enter ledger data')->name('ledger.update');
    Route::post('/ledger/entries/{ledger}/verify', [MonthlyLedgerController::class, 'verify'])
        ->middleware('can:verify ledger')->name('ledger.verify');
    Route::post('/ledger/clients/{client}/bank-accounts', [MonthlyLedgerController::class, 'storeBankAccount'])
        ->middleware('can:enter ledger data')->name('ledger.bank-accounts.store');

    // Google Sheets: link a client's workbook and sync raw figures from it
    Route::put('/ledger/clients/{client}/sheet', [MonthlyLedgerController::class, 'linkSheet'])
        ->middleware('can:enter ledger data')->name('ledger.sheet.link');
    Route::post('/ledger/clients/{client}/sheet/sync', [MonthlyLedgerController::class, 'syncSheet'])
        ->middleware('can:enter ledger data')->name('ledger.sheet.sync');
    Route::get('/ledger/clients/{client}/sheet/template', [MonthlyLedgerController::class, 'downloadSheetTemplate'])
        ->middleware('can:enter ledger data')->name('ledger.sheet.template');
    Route::post('/ledger/clients/{client}/sheet/apply', [MonthlyLedgerController::class, 'applySheetTemplate'])
        ->middleware('can:enter ledger data')->name('ledger.sheet.apply');

    // Downloads (verified ledger only — enforced in controller via the model scope for Clients)
    Route::get('/ledger/entries/{ledger}/download/pdf', [MonthlyLedgerController::class, 'downloadPdf'])
        ->middleware('can:download ledger reports')->name('ledger.download.pdf');
    Route::get('/ledger/entries/{ledger}/download/xlsx', [MonthlyLedgerController::class, 'downloadXlsx'])
        ->middleware('can:download ledger reports')->name('ledger.download.xlsx');
});

// --- CLIENT PORTAL: Financial Ledger + Invoices + Payments ---
Route::prefix('portal')->middleware(['auth', 'role:Client'])->group(function () {
    Route::get('/financial-ledger', [ClientLedgerController::class, 'index'])
        ->middleware('can:view client ledger reports')->name('client.ledger.index');

    Route::get('/invoices', [ClientInvoiceController::class, 'index'])
        ->middleware('can:view own invoices')->name('client.invoices.index');
    Route::get('/invoices/{invoice}/download', [ClientInvoiceController::class, 'downloadPdf'])
        ->middleware('can:view own invoices')->name('client.invoices.download');
    Route::post('/invoices/{invoice}/submit-payment', [ClientInvoiceController::class, 'submitPayment'])
        ->middleware('can:submit invoice payment')->name('client.invoices.submit-payment');

    Route::get('/payments', [ClientInvoiceController::class, 'payments'])
        ->middleware('can:view own payments')->name('client.payments.index');
});

// --- FINANCE ADMIN ---
Route::middleware(['auth', 'role:Finance Admin|Super Admin'])->group(function () {
    Route::get('/finance/billing', [BillingController::class, 'index'])
        ->middleware('can:view finance billing')->name('finance.billing');
    Route::post('/finance/clients/{client}/reminders', [BillingController::class, 'sendReminder'])
        ->middleware('can:send payment reminders')->name('finance.reminders.send');
    Route::post('/finance/clients/{client}/record-payment', [BillingController::class, 'recordPayment'])
        ->middleware('can:record payments')->name('finance.payments.record');
    Route::post('/finance/payments/{payment}/approve', [BillingController::class, 'approvePayment'])
        ->middleware('can:approve payments')->name('finance.payments.approve');
    Route::post('/finance/payments/{payment}/reject', [BillingController::class, 'rejectPayment'])
        ->middleware('can:reject payments')->name('finance.payments.reject');
    Route::post('/finance/payments/{payment}/submit-draft', [BillingController::class, 'submitDraftPayment'])
        ->middleware('can:submit draft payments')->name('finance.payments.submit-draft');
    Route::post('/finance/expected-payments/generate', [BillingController::class, 'generateExpectedPayments'])
        ->middleware('can:generate expected payments')->name('finance.expected-payments.generate');

    // Ledger progress (read-only across all clients)
    Route::get('/finance/ledger-progress', [LedgerProgressController::class, 'index'])
        ->middleware('can:view ledger progress')->name('finance.ledger-progress');

    // Service invoicing (firm's billing of clients)
    Route::middleware('can:manage service invoices')->group(function () {
        Route::get('/finance/invoices',                       [FinanceInvoiceController::class, 'index'])->name('finance.invoices.index');
        Route::get('/finance/invoices/create',                [FinanceInvoiceController::class, 'create'])->name('finance.invoices.create');
        Route::match(['get', 'post'], '/finance/invoices/services-rendered', [FinanceInvoiceController::class, 'servicesRendered'])->name('finance.invoices.services-rendered');
        Route::post('/finance/invoices',                      [FinanceInvoiceController::class, 'store'])->name('finance.invoices.store');
        Route::get('/finance/invoices/{invoice}',             [FinanceInvoiceController::class, 'show'])->name('finance.invoices.show');
        Route::post('/finance/invoices/{invoice}/send',       [FinanceInvoiceController::class, 'send'])->name('finance.invoices.send');
        Route::post('/finance/invoices/{invoice}/cancel',     [FinanceInvoiceController::class, 'cancel'])->name('finance.invoices.cancel');
        Route::post('/finance/invoices/{invoice}/payments',   [FinanceInvoiceController::class, 'recordPayment'])->name('finance.invoices.payments');
        Route::post('/finance/invoice-payments/{payment}/approve', [FinanceInvoiceController::class, 'approvePayment'])->middleware('can:approve invoice payments')->name('finance.invoice-payments.approve');
        Route::post('/finance/invoice-payments/{payment}/reject',  [FinanceInvoiceController::class, 'rejectPayment'])->middleware('can:reject invoice payments')->name('finance.invoice-payments.reject');
        Route::get('/finance/invoices/{invoice}/download',    [FinanceInvoiceController::class, 'downloadPdf'])->name('finance.invoices.download');
        Route::delete('/finance/invoices/{invoice}',          [FinanceInvoiceController::class, 'destroy'])->name('finance.invoices.destroy');
    });
});

// --- EMPLOYEE PORTAL ---
Route::middleware(['auth', 'role:Employee|Team Leader'])->group(function () {
    Route::get('/workspace', [WorkspaceController::class, 'index'])->name('employee.workspace');
    Route::patch('/workspace/tasks/{task}/status', [WorkspaceController::class, 'updateStatus'])->name('employee.tasks.status');
    Route::post('/workspace/tasks/{task}/details', [WorkspaceController::class, 'updateDetails'])->name('employee.tasks.details');
    Route::post('/workspace/tasks/{task}/upload', [TaskController::class, 'uploadReport'])->name('employee.tasks.upload');
    Route::get('/daily-tasks', [WorkspaceController::class, 'dailyTasks'])->name('employee.daily-tasks.index');
    Route::post('/daily-tasks', [WorkspaceController::class, 'storeDailyTask'])->name('employee.daily-tasks.store');
    Route::patch('/daily-tasks/{dailyTask}/status', [WorkspaceController::class, 'updateDailyTaskStatus'])->name('employee.daily-tasks.status');
    Route::get('/performance', [WorkspaceController::class, 'performance'])->name('employee.performance');
    Route::get('/workspace/clients/{client}/messages', [MessageController::class, 'indexForEmployee'])->name('employee.client.messages');
});

// --- INTERNAL FIRM ROUTES (Employees & Managers) ---
Route::middleware(['auth', 'role:Employee|Branch Manager|Super Admin'])->group(function () {
    Route::post('/firm/clients/{client_id}/messages', [MessageController::class, 'storeFromEmployee'])->name('employee.messages.store');
});

// --- TRAINING PORTAL (Publicly Accessible) ---
Route::prefix('training')->group(function () {
    Route::get('/', [\App\Http\Controllers\TrainingController::class, 'index'])->name('training.index');
    Route::get('/employees', [\App\Http\Controllers\TrainingController::class, 'employees'])->name('training.employees');
    Route::get('/admins', [\App\Http\Controllers\TrainingController::class, 'admins'])->name('training.admins');
    Route::get('/finances', [\App\Http\Controllers\TrainingController::class, 'finances'])->name('training.finances');
});

require __DIR__ . '/auth.php';

