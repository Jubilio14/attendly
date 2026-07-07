<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $today = now()->toDateString();

        $rows = User::query()
            ->with([
                'departmentRelation:id,name',
                'workSchedule:id,name,start_time,end_time',
                'attendances' => function ($query) use ($today) {
                    $query->whereDate('attendance_date', $today);
                },
            ])
            ->where('role', 'employee')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($employee) use ($today) {
                $attendance = $employee->attendances->first();

                $status = 'not_checked_in';
                $statusLabel = 'Belum Check-in';

                if ($attendance) {
                    $status = $attendance->status;

                    $statusLabel = match ($attendance->status) {
                        'present' => 'Hadir',
                        'late' => 'Telat',
                        'absent' => 'Tidak Masuk',
                        'leave' => 'Izin',
                        'sick' => 'Sakit',
                        'wfh' => 'WFH',
                        'wfc' => 'WFC',
                        default => 'Belum Check-in',
                    };

                    if ($attendance->check_out_at && in_array($attendance->status, ['present', 'late'])) {
                        $statusLabel = $attendance->status === 'late'
                            ? 'Telat - Check-out'
                            : 'Hadir - Check-out';
                    }
                }

                if (! $attendance) {
                    $isTodayAfterScheduleEnd = false;

                    if ($employee->workSchedule) {
                        $scheduleEnd = Carbon::parse(
                            $today . ' ' . $employee->workSchedule->end_time
                        );

                        $isTodayAfterScheduleEnd = now()->greaterThan($scheduleEnd);
                    }

                    if ($isTodayAfterScheduleEnd) {
                        $status = 'absent';
                        $statusLabel = 'Tidak Masuk';
                    }
                }

                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'position' => $employee->position,
                    'department' => $employee->departmentRelation?->name ?? '-',
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'check_in_at' => $attendance?->check_in_at?->format('H:i'),
                    'check_out_at' => $attendance?->check_out_at?->format('H:i'),
                    'late_minutes' => $attendance?->late_minutes ?? 0,
                    'has_checked_out' => $attendance?->check_out_at !== null,
                ];
            });

        $pendingRequests = AttendanceRequest::query()
            ->with(['user:id,name,position,department_id', 'user.departmentRelation:id,name'])
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'employee_name' => $request->user?->name ?? '-',
                    'position' => $request->user?->position,
                    'department' => $request->user?->departmentRelation?->name ?? '-',
                    'type_label' => match ($request->type) {
                        'leave' => 'Izin',
                        'sick' => 'Sakit',
                        'wfh' => 'WFH',
                        'wfc' => 'WFC',
                        default => '-',
                    },
                    'date_range' => $request->start_date->format('d M Y') . ' - ' . $request->end_date->format('d M Y'),
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_employees' => $rows->count(),
                'present_today' => $rows->whereIn('status', ['present', 'late'])->count(),
                'late_today' => $rows->where('late_minutes', '>', 0)->count(),
                'not_checked_in' => $rows->where('status', 'not_checked_in')->count(),
                'checked_out' => $rows->where('has_checked_out', true)->count(),
                'absent' => $rows->where('status', 'absent')->count(),
                'leave' => $rows->where('status', 'leave')->count(),
                'sick' => $rows->where('status', 'sick')->count(),
                'wfh' => $rows->where('status', 'wfh')->count(),
                'wfc' => $rows->where('status', 'wfc')->count(),
                'pending_requests' => AttendanceRequest::where('status', 'pending')->count(),
            ],
            'lateEmployees' => $rows
                ->where('late_minutes', '>', 0)
                ->values()
                ->take(5),
            'notCheckedInEmployees' => $rows
                ->where('status', 'not_checked_in')
                ->values()
                ->take(5),
            'pendingRequests' => $pendingRequests,
            'today' => now()->translatedFormat('l, d F Y'),
        ]);
    }
}