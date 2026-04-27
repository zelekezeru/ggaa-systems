<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\Employee\WorkspaceController;
use App\Http\Controllers\StaffController;
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
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Smart redirect: send each role to their own portal
Route::get('/dashboard', function () {
    /** @var \App\Models\User $user */
    $user = Auth::user();
    if ($user->hasRole('Employee'))
        return redirect()->route('employee.workspace');
    if ($user->hasRole('Client'))
        return redirect()->route('client.dashboard');
    if ($user->hasRole('Finance Admin'))
        return redirect()->route('finance.billing');
    return Inertia::render('SuperAdmin/Dashboard'); // Super Admin / Branch Manager fallback
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// --- CLIENT PORTAL ---
Route::middleware(['auth', 'role:Client'])->group(function () {
    Route::get('/portal/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');
    Route::get('/portal/tasks/{task}', [ClientPortalController::class, 'showTask'])->name('client.tasks.show');
    Route::post('/portal/messages', [MessageController::class, 'storeFromClient'])->name('client.messages.store');
});

// --- SUPER ADMIN ---
Route::middleware(['auth', 'role:Super Admin|Branch Manager'])->group(function () {
    Route::get('/super-admin/dashboard', [SuperAdminDashboardController::class, 'index'])->name('super-admin.dashboard');
    Route::get('/super-admin/branches', [SuperAdminController::class, 'branches'])->name('super-admin.branches');
    Route::get('/super-admin/staff', [StaffController::class, 'index'])->name('super-admin.staff');
    Route::get('/super-admin/clients', [AdminClientController::class, 'index'])->name('super-admin.clients');
    Route::get('/super-admin/reports', [SuperAdminController::class, 'reports'])->name('super-admin.reports');

    // Branch CRUD operations
    Route::post('/super-admin/branches', [BranchController::class, 'store'])->name('admin.branches.store');
    Route::put('/super-admin/branches/{branch}', [BranchController::class, 'update'])->name('admin.branches.update');
    Route::delete('/super-admin/branches/{branch}', [BranchController::class, 'destroy'])->name('admin.branches.destroy');

    // Staff CRUD operations
    Route::post('/super-admin/staff', [StaffController::class, 'store'])->name('admin.staff.store');
    Route::put('/super-admin/staff/{staff}', [StaffController::class, 'update'])->name('admin.staff.update');
    Route::delete('/super-admin/staff/{staff}', [StaffController::class, 'destroy'])->name('admin.staff.destroy');
    Route::post('/super-admin/staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('admin.staff.reset-password');

    // Client CRUD operations
    Route::post('/super-admin/clients', [AdminClientController::class, 'store'])->name('admin.clients.store');
    Route::put('/super-admin/clients/{client}', [AdminClientController::class, 'update'])->name('admin.clients.update');
    Route::delete('/super-admin/clients/{client}', [AdminClientController::class, 'destroy'])->name('admin.clients.destroy');

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

    // Task Management
    Route::get('/super-admin/tasks', [TaskManagementController::class, 'index'])->name('super-admin.tasks.index');
    Route::post('/super-admin/tasks', [TaskManagementController::class, 'store'])->name('super-admin.tasks.store');
    Route::put('/super-admin/tasks/{task}', [TaskManagementController::class, 'update'])->name('super-admin.tasks.update');
    Route::delete('/super-admin/tasks/{task}', [TaskManagementController::class, 'destroy'])->name('super-admin.tasks.destroy');

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
});

// --- FINANCE ADMIN ---
Route::middleware(['auth', 'role:Finance Admin'])->group(function () {
    Route::get('/finance/billing', [BillingController::class, 'index'])->name('finance.billing');
    Route::post('/finance/clients/{client}/reminders', [BillingController::class, 'sendReminder'])->name('finance.reminders.send');
});

// --- EMPLOYEE PORTAL ---
Route::middleware(['auth', 'role:Employee'])->group(function () {
    Route::get('/workspace', [WorkspaceController::class, 'index'])->name('employee.workspace');
    Route::patch('/workspace/tasks/{task}/status', [WorkspaceController::class, 'updateStatus'])->name('employee.tasks.status');
    Route::post('/workspace/tasks/{task}/details', [WorkspaceController::class, 'updateDetails'])->name('employee.tasks.details');
    Route::post('/workspace/tasks/{task}/upload', [TaskController::class, 'uploadReport'])->name('employee.tasks.upload');
    Route::get('/daily-tasks', [WorkspaceController::class, 'dailyTasks'])->name('employee.daily-tasks.index');
    Route::post('/daily-tasks', [WorkspaceController::class, 'storeDailyTask'])->name('employee.daily-tasks.store');
    Route::patch('/daily-tasks/{dailyTask}/status', [WorkspaceController::class, 'updateDailyTaskStatus'])->name('employee.daily-tasks.status');
    Route::get('/performance', [WorkspaceController::class, 'performance'])->name('employee.performance');
});

// --- INTERNAL FIRM ROUTES (Employees & Managers) ---
Route::middleware(['auth', 'role:Employee|Branch Manager|Super Admin'])->group(function () {
    Route::post('/firm/clients/{client_id}/messages', [MessageController::class, 'storeFromEmployee'])->name('employee.messages.store');
});

require __DIR__ . '/auth.php';
