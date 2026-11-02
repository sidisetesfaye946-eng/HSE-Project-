<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HseOfficerMaterialRequestController;
use App\Http\Controllers\MaterialAssignmentController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialHistoryController;
use App\Http\Controllers\MaterialQuantityController;
use App\Http\Controllers\MaterialReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SiteOfficerEmployeeAssignmentController;
use App\Http\Controllers\SiteOfficerEmployeeReportController;
use App\Http\Controllers\SiteOfficerMaterialReportController;
use App\Http\Controllers\SiteOfficerMaterialRequestController;
use App\Http\Controllers\SiteOfficerProjectController;
use App\Http\Controllers\SiteOfficerProjectEmployeeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('role:Admin')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
    });

    Route::middleware('role:Admin|HSE Head Office|HSE Officer|HSE Site Officer')->group(function () {
        Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
        Route::get('employees/import', [EmployeeController::class, 'importForm'])->name('employees.import');
        Route::get('employees/import/template', [EmployeeController::class, 'downloadTemplate'])->name('employees.import.template');
        Route::post('employees/import', [EmployeeController::class, 'importStore'])->name('employees.import.store');
    });

    Route::middleware('role:Admin|HSE Head Office|HSE Officer|HSE Site Officer')->group(function () {
        Route::resource('employees', EmployeeController::class);
    });

    Route::middleware('role:Admin|HSE Officer')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('projects', ProjectController::class);
    });

    Route::get('materials', [MaterialController::class, 'index'])->name('materials.index');

    Route::middleware('role:Admin|HSE Officer')->group(function () {
        Route::get('materials/import', [MaterialController::class, 'importForm'])->name('materials.import');
        Route::get('materials/import/template', [MaterialController::class, 'downloadTemplate'])->name('materials.import.template');
        Route::post('materials/import', [MaterialController::class, 'importStore'])->name('materials.import.store');

        Route::get('material-histories', [MaterialHistoryController::class, 'index'])->name('material-histories.index');
        Route::get('material-histories/export', [MaterialHistoryController::class, 'export'])->name('material-histories.export');
    });

    Route::middleware('role:Admin|HSE Head Office|HSE Officer')->group(function () {
        Route::get('materials/create', [MaterialController::class, 'create'])->name('materials.create');
        Route::post('materials', [MaterialController::class, 'store'])->name('materials.store');
        Route::get('materials/{material}/edit', [MaterialController::class, 'edit'])->name('materials.edit');
        Route::put('materials/{material}', [MaterialController::class, 'update'])->name('materials.update');
        Route::delete('materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');

        Route::get('material-assignments/create', [MaterialAssignmentController::class, 'create'])->name('material-assignments.create');
        Route::post('material-assignments', [MaterialAssignmentController::class, 'store'])->name('material-assignments.store');

        Route::get('material-quantities', [MaterialQuantityController::class, 'index'])->name('material-quantities.index');
        Route::get('material-quantities/{material}/edit', [MaterialQuantityController::class, 'edit'])->name('material-quantities.edit');
        Route::patch('material-quantities/{material}', [MaterialQuantityController::class, 'update'])->name('material-quantities.update');

        Route::get('material-reports/balance/export', [MaterialReportController::class, 'exportBalance'])->name('material-reports.balance.export');
        Route::get('material-reports/balance', [MaterialReportController::class, 'balance'])->name('material-reports.balance');
        Route::get('material-reports/head-office/export', [MaterialReportController::class, 'exportHeadOffice'])->name('material-reports.head-office.export');
        Route::get('material-reports/head-office', [MaterialReportController::class, 'headOfficeReport'])->name('material-reports.head-office');
        Route::get('material-reports/site/export', [MaterialReportController::class, 'exportSite'])->name('material-reports.site.export');
        Route::get('material-reports/site', [MaterialReportController::class, 'siteReport'])->name('material-reports.site');
        Route::get('material-reports/inventory/export', [MaterialReportController::class, 'exportInventory'])->name('material-reports.inventory.export');
        Route::get('material-reports/inventory', [MaterialReportController::class, 'inventory'])->name('material-reports.inventory');
        Route::get('material-reports/{project}/export', [MaterialReportController::class, 'exportProject'])->name('material-reports.project.export');
        Route::get('material-reports', [MaterialReportController::class, 'index'])->name('material-reports.index');
        Route::get('material-reports/{project}', [MaterialReportController::class, 'show'])->name('material-reports.show');
    });

    Route::middleware('role:HSE Site Officer|HSE Officer')->prefix('site-officer')->name('site-officer.')->group(function () {
        Route::get('projects', [SiteOfficerProjectController::class, 'index'])->name('projects.index');
        Route::get('projects/{project}', [SiteOfficerProjectController::class, 'show'])->name('projects.show');
        Route::get('projects/{project}/employees', [SiteOfficerProjectEmployeeController::class, 'index'])->name('projects.employees.index');
        Route::post('projects/{project}/employees', [SiteOfficerProjectEmployeeController::class, 'sync'])->name('projects.employees.sync');
        Route::delete('projects/{project}/employees/{employee}', [SiteOfficerProjectEmployeeController::class, 'destroy'])->name('projects.employees.destroy');

        Route::get('material-reports', [SiteOfficerMaterialReportController::class, 'index'])->name('material-reports.index');
        Route::get('reports/employees/export', [SiteOfficerEmployeeReportController::class, 'export'])->name('reports.employees.export');
        Route::get('reports/employees', [SiteOfficerEmployeeReportController::class, 'index'])->name('reports.employees');

        Route::get('employee-assignments/export', [SiteOfficerEmployeeAssignmentController::class, 'export'])->name('employee-assignments.export');
        Route::get('employee-assignments', [SiteOfficerEmployeeAssignmentController::class, 'index'])->name('employee-assignments.index');
        Route::get('employee-assignments/create', [SiteOfficerEmployeeAssignmentController::class, 'create'])->name('employee-assignments.create');
        Route::post('employee-assignments', [SiteOfficerEmployeeAssignmentController::class, 'store'])->name('employee-assignments.store');
        Route::get('employees/{employee}/assignment-history', [SiteOfficerEmployeeAssignmentController::class, 'employeeHistory'])->name('employees.assignment-history');

        Route::get('material-requests', [SiteOfficerMaterialRequestController::class, 'index'])->name('material-requests.index');
        Route::get('material-requests/create', [SiteOfficerMaterialRequestController::class, 'create'])->name('material-requests.create');
        Route::get('material-requests/import/template', [SiteOfficerMaterialRequestController::class, 'downloadTemplate'])->name('material-requests.import.template');
        Route::post('material-requests/import', [SiteOfficerMaterialRequestController::class, 'import'])->name('material-requests.import');
        Route::post('material-requests', [SiteOfficerMaterialRequestController::class, 'store'])->name('material-requests.store');
        Route::get('material-requests/{materialRequest}', [SiteOfficerMaterialRequestController::class, 'show'])->name('material-requests.show');
    });

    Route::middleware('role:Admin|HSE Officer')->prefix('hse-officer')->name('hse-officer.')->group(function () {
        Route::get('material-requests', [HseOfficerMaterialRequestController::class, 'index'])->name('material-requests.index');
        Route::get('material-requests/{materialRequest}', [HseOfficerMaterialRequestController::class, 'show'])->name('material-requests.show');
        Route::post('material-requests/{materialRequest}/approve', [HseOfficerMaterialRequestController::class, 'approve'])->name('material-requests.approve');
        Route::post('material-requests/{materialRequest}/reject', [HseOfficerMaterialRequestController::class, 'reject'])->name('material-requests.reject');
        Route::post('material-requests/{materialRequest}/decide-items', [HseOfficerMaterialRequestController::class, 'decideItems'])->name('material-requests.decide-items');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});

require __DIR__.'/auth.php';
