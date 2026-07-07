<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $date = $request->input('date', now()->toDateString());
        $status = $request->input('status');

        $rows = User::query()
            ->with([
                'departmentRelation:id,name',
                'workSchedule:id,name,start_time,end_time',
                'attendances' => function ($query) use ($date) {
                    $query->whereDate('attendance_date', $date);
                },
            ])
            ->where('role', 'employee')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($employee) use ($date) {
                $attendance = $employee->attendances->first();

                $currentStatus = 'not_checked_in';
                $statusLabel = 'Belum Check-in';

                if ($attendance) {
                    $currentStatus = $attendance->status;

                    $statusLabel = match ($attendance->status) {
                        'present' => 'Hadir',
                        'late' => 'Telat',
                        'absent' => 'Tidak Masuk',
                        'sick' => 'Sakit',
                        'leave' => 'Izin',
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
                    $selectedDate = \Illuminate\Support\Carbon::parse($date)->startOfDay();
                    $today = now()->startOfDay();

                    $isPastDate = $selectedDate->lt($today);
                    $isTodayAfterScheduleEnd = false;

                    if ($selectedDate->isSameDay($today) && $employee->workSchedule) {
                        $scheduleEnd = \Illuminate\Support\Carbon::parse(
                            $date . ' ' . $employee->workSchedule->end_time
                        );

                        $isTodayAfterScheduleEnd = now()->greaterThan($scheduleEnd);
                    }

                    if ($isPastDate || $isTodayAfterScheduleEnd) {
                        $currentStatus = 'absent';
                        $statusLabel = 'Tidak Masuk';
                    }
                }

                return [
                    'employee_id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'position' => $employee->position,
                    'department' => $employee->departmentRelation?->name ?? '-',
                    'schedule' => $employee->workSchedule
                        ? $employee->workSchedule->name . ' (' . $employee->workSchedule->start_time . ' - ' . $employee->workSchedule->end_time . ')'
                        : '-',
                    'status' => $currentStatus,
                    'status_label' => $statusLabel,
                    'check_in_at' => $attendance?->check_in_at?->format('H:i'),
                    'check_out_at' => $attendance?->check_out_at?->format('H:i'),
                    'early_checkout_minutes' => $attendance?->early_checkout_minutes ?? 0,
                    'late_minutes' => $attendance?->late_minutes ?? 0,
                    'has_checked_out' => $attendance?->check_out_at !== null,
                ];
            });

        $stats = [
            'total' => $rows->count(),
            'present' => $rows->whereIn('status', ['present', 'late'])->count(),
            'late' => $rows->where('late_minutes', '>', 0)->count(),
            'not_checked_in' => $rows->where('status', 'not_checked_in')->count(),
            'checked_out' => $rows->where('has_checked_out', true)->count(),
            'absent' => $rows->where('status', 'absent')->count(),
            'leave' => $rows->where('status', 'leave')->count(),
            'sick' => $rows->where('status', 'sick')->count(),
            'wfh' => $rows->where('status', 'wfh')->count(),
            'wfc' => $rows->where('status', 'wfc')->count(),
        ];

        if ($status === 'present') {
            $rows = $rows
                ->filter(fn ($row) => in_array($row['status'], ['present', 'late']))
                ->values();
        }

        if ($status === 'late') {
            $rows = $rows
                ->filter(fn ($row) => $row['late_minutes'] > 0)
                ->values();
        }

        if ($status === 'checked_out') {
            $rows = $rows
                ->filter(fn ($row) => $row['has_checked_out'])
                ->values();
        }

        if ($status === 'not_checked_in') {
            $rows = $rows
                ->filter(fn ($row) => $row['status'] === 'not_checked_in')
                ->values();
        }

        if ($status === 'absent') {
            $rows = $rows
                ->filter(fn ($row) => $row['status'] === 'absent')
                ->values();
        }

        if (in_array($status, ['leave', 'sick', 'wfh', 'wfc'])) {
            $rows = $rows
                ->filter(fn ($row) => $row['status'] === $status)
                ->values();
        }

        return Inertia::render('Admin/Attendances/Index', [
            'rows' => $rows,
            'stats' => $stats,
            'filters' => [
                'date' => $date,
                'status' => $status,
            ],
        ]);
    }

    public function correct(Request $request, User $employee): RedirectResponse
    {
        abort_if($employee->role !== 'employee', 404);

        $validated = $request->validate(
            [
                'attendance_date' => ['required', 'date'],
                'status' => [
                    'required',
                    Rule::in([
                        'present',
                        'late',
                        'absent',
                        'leave',
                        'sick',
                        'wfh',
                        'wfc',
                    ]),
                ],
                'check_in_at' => ['nullable', 'date_format:H:i'],
                'check_out_at' => ['nullable', 'date_format:H:i'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            [
                'attendance_date.required' => 'Tanggal absensi wajib diisi.',
                'status.required' => 'Status absensi wajib dipilih.',
                'status.in' => 'Status absensi tidak valid.',
                'check_in_at.date_format' => 'Format jam check-in tidak valid.',
                'check_out_at.date_format' => 'Format jam check-out tidak valid.',
                'notes.max' => 'Catatan maksimal 1000 karakter.',
            ]
        );

        $date = Carbon::parse($validated['attendance_date'])->toDateString();
        $status = $validated['status'];

        $isPresenceStatus = in_array($status, ['present', 'late']);

        if ($isPresenceStatus && empty($validated['check_in_at'])) {
            return back()->withErrors([
                'check_in_at' => 'Jam check-in wajib diisi untuk status Hadir atau Telat.',
            ]);
        }

        $checkInAt = null;
        $checkOutAt = null;
        $lateMinutes = 0;
        $earlyCheckoutMinutes = 0;

        if ($isPresenceStatus) {
            $checkInAt = Carbon::parse($date . ' ' . $validated['check_in_at']);

            if (! empty($validated['check_out_at'])) {
                $checkOutAt = Carbon::parse($date . ' ' . $validated['check_out_at']);

                if ($checkOutAt->lessThanOrEqualTo($checkInAt)) {
                    return back()->withErrors([
                        'check_out_at' => 'Jam check-out harus setelah jam check-in.',
                    ]);
                }
            }

            if ($checkOutAt && $employee->workSchedule) {
                $scheduleEnd = Carbon::parse($date . ' ' . $employee->workSchedule->end_time);

                if ($checkOutAt->lessThan($scheduleEnd)) {
                    $earlyCheckoutMinutes = (int) ceil($checkOutAt->diffInSeconds($scheduleEnd) / 60);
                }
            }

           if ($isPresenceStatus && $employee->workSchedule) {
                $lateLimit = Carbon::parse($date . ' ' . $employee->workSchedule->start_time)
                    ->addMinutes($employee->workSchedule->late_tolerance_minutes);

                if ($checkInAt->greaterThan($lateLimit)) {
                    $lateMinutes = (int) ceil($lateLimit->diffInSeconds($checkInAt) / 60);
                    $status = 'late';
                } else {
                    $lateMinutes = 0;
                    $status = 'present';
                }
            }
        }

        Attendance::updateOrCreate(
            [
                'user_id' => $employee->id,
                'attendance_date' => $date,
            ],
            [
                'work_schedule_id' => $employee->work_schedule_id,
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'early_checkout_minutes' => $earlyCheckoutMinutes,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return back();
    }
}