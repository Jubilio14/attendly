<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Employee\AttendanceController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Employee\AttendanceRequestController as EmployeeAttendanceRequestController;
use App\Http\Controllers\Admin\AttendanceRequestController as AdminAttendanceRequestController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\WorkScheduleController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Employee\ProfileController as EmployeeProfileController;
use App\Http\Controllers\Employee\AttendanceHistoryController;


Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    $user = Auth::user();

    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('employee.home');
})->name('home');

Route::middleware(['auth', 'verified', 'active.user'])->group(function () {
    Route::get('dashboard', function () {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('employee.home');
    })->name('dashboard');

    Route::get('/employee/home', [AttendanceController::class, 'home'])
        ->name('employee.home');

    Route::get('/employee/history', [AttendanceHistoryController::class, 'index'])
        ->name('employee.history');

    Route::get('/employee/requests', [EmployeeAttendanceRequestController::class, 'index'])
        ->name('employee.requests.index');

    Route::get('/employee/profile', [EmployeeProfileController::class, 'index'])
        ->name('employee.profile');

    Route::patch('/employee/profile/phone', [EmployeeProfileController::class, 'updatePhone'])
        ->name('employee.profile.phone');

    Route::patch('/employee/profile/password', [EmployeeProfileController::class, 'updatePassword'])
        ->name('employee.profile.password');

    Route::post('/employee/attendance/check-in', [AttendanceController::class, 'checkIn'])
        ->name('employee.attendance.check-in');

    Route::patch('/employee/attendance/check-out', [AttendanceController::class, 'checkOut'])
        ->name('employee.attendance.check-out');

    Route::post('/employee/requests', [EmployeeAttendanceRequestController::class, 'store'])
        ->name('employee.requests.store');

    Route::middleware(['admin.user'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('/employees', [EmployeeController::class, 'index'])
                ->name('employees.index');

            Route::post('/employees', [EmployeeController::class, 'store'])
                ->name('employees.store');

            Route::put('/employees/{employee}', [EmployeeController::class, 'update'])
                ->name('employees.update');

            Route::patch('/employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])
                ->name('employees.toggle-status');

            Route::patch('/employees/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])
                ->name('employees.reset-password');

            Route::patch('/attendances/{employee}/correction', [AdminAttendanceController::class, 'correct'])
                ->name('attendances.correct');

            Route::get('/attendances', [AdminAttendanceController::class, 'index'])
                ->name('attendances.index');    

            Route::get('/requests', [AdminAttendanceRequestController::class, 'index'])
                ->name('requests.index');

            Route::patch('/requests/{attendanceRequest}/approve', [AdminAttendanceRequestController::class, 'approve'])
                ->name('requests.approve');

            Route::patch('/requests/{attendanceRequest}/reject', [AdminAttendanceRequestController::class, 'reject'])
                ->name('requests.reject');

            Route::get('/reports/export', [ReportController::class, 'export'])
                ->name('reports.export');

            Route::get('/reports/{employee}/detail/export', [ReportController::class, 'exportDetail'])
                ->name('reports.detail.export');

            Route::get('/reports/{employee}/detail', [ReportController::class, 'detail'])
                ->name('reports.detail');

            Route::get('/reports', [ReportController::class, 'index'])
                ->name('reports.index');

            Route::get('/work-schedules', [WorkScheduleController::class, 'index'])
                ->name('work-schedules.index');

            Route::post('/work-schedules', [WorkScheduleController::class, 'store'])
                ->name('work-schedules.store');

            Route::put('/work-schedules/{workSchedule}', [WorkScheduleController::class, 'update'])
                ->name('work-schedules.update');

            Route::get('/departments', [DepartmentController::class, 'index'])
                ->name('departments.index');

            Route::post('/departments', [DepartmentController::class, 'store'])
                ->name('departments.store');

            Route::put('/departments/{department}', [DepartmentController::class, 'update'])
                ->name('departments.update');
        });
});

require __DIR__.'/settings.php';