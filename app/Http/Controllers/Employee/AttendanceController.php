<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\AttendanceRequest;

class AttendanceController extends Controller
{
    public function home(Request $request): Response
    {
        $user = $request->user()->load('workSchedule');

        abort_if($user->role !== 'employee', 403);

        $today = now()->toDateString();

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        return Inertia::render('Employee/Home', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'position' => $user->position,
            ],
            'schedule' => $user->workSchedule ? [
                'name' => $user->workSchedule->name,
                'start_time' => Carbon::parse($user->workSchedule->start_time)->format('H:i'),
                'end_time' => Carbon::parse($user->workSchedule->end_time)->format('H:i'),
                'late_tolerance_minutes' => $user->workSchedule->late_tolerance_minutes,
            ] : null,
            'attendance' => $this->formatAttendance($attendance),
            'today' => now()->translatedFormat('l, d F Y'),
        ]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $user = $request->user()->load('workSchedule');

        abort_if($user->role !== 'employee', 403);

        if (! $user->workSchedule) {
            return back()->withErrors([
                'attendance' => 'Jadwal kerja belum diatur. Silakan hubungi admin.',
            ]);
        }

        $today = now()->toDateString();

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($attendance && $attendance->check_in_at) {
            return back()->withErrors([
                'attendance' => 'Kamu sudah check-in hari ini.',
            ]);
        }

        $now = now();

        $scheduleStart = Carbon::parse($today . ' ' . $user->workSchedule->start_time);
        $lateLimit = $scheduleStart->copy()->addMinutes($user->workSchedule->late_tolerance_minutes);

        $isLate = $now->greaterThan($lateLimit);

        $lateMinutes = $isLate
            ? (int) ceil($lateLimit->diffInSeconds($now) / 60)
            : 0;

        Attendance::updateOrCreate(
            [
                'user_id' => $user->id,
                'attendance_date' => $today,
            ],
            [
                'work_schedule_id' => $user->work_schedule_id,
                'check_in_at' => $now,
                'status' => $isLate ? 'late' : 'present',
                'late_minutes' => $lateMinutes,
            ]
        );

        return back();
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $user = $request->user()->load('workSchedule');

        abort_if($user->role !== 'employee', 403);

        $today = now()->toDateString();

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->where('attendance_date', $today)
            ->first();

        if (! $attendance || ! $attendance->check_in_at) {
            return back()->withErrors([
                'attendance' => 'Anda belum check-in hari ini.',
            ]);
        }

        if ($attendance->check_out_at) {
            return back()->withErrors([
                'attendance' => 'Anda sudah check-out hari ini.',
            ]);
        }

        $checkOutAt = now();
        $earlyCheckoutMinutes = 0;

        if ($user->workSchedule) {
            $scheduleEnd = Carbon::parse($today . ' ' . $user->workSchedule->end_time);

            if ($checkOutAt->lessThan($scheduleEnd)) {
                $earlyCheckoutMinutes = (int) ceil($checkOutAt->diffInSeconds($scheduleEnd) / 60);
            }
        }

        $attendance->update([
            'check_out_at' => $checkOutAt,
            'early_checkout_minutes' => $earlyCheckoutMinutes,
        ]);

        return back();
    }

    private function formatAttendance(?Attendance $attendance): array
    {
        if (! $attendance) {
            return [
                'status' => 'not_checked_in',
                'status_label' => 'Belum Check-in',
                'check_in_at' => null,
                'check_out_at' => null,
                'late_minutes' => 0,
                'early_checkout_minutes' => 0,
                'can_check_in' => true,
                'can_check_out' => false,
            ];
        }

        $statusLabel = match ($attendance->status) {
            'present' => 'Checked In',
            'late' => 'Checked In - Telat',
            'absent' => 'Tidak Masuk',
            'leave' => 'Izin',
            'sick' => 'Sakit',
            'wfh' => 'WFH',
            'wfc' => 'WFC',
            default => 'Belum Check-in',
        };

        if ($attendance->check_out_at && in_array($attendance->status, ['present', 'late'])) {
            $statusLabel = ($attendance->early_checkout_minutes ?? 0) > 0
                ? 'Pulang Cepat'
                : 'Checked Out';
        }

        $isRequestStatus = in_array($attendance->status, [
            'absent',
            'leave',
            'sick',
            'wfh',
            'wfc',
        ]);

        return [
            'status' => $attendance->status,
            'status_label' => $statusLabel,
            'check_in_at' => $attendance->check_in_at?->format('H:i'),
            'check_out_at' => $attendance->check_out_at?->format('H:i'),
            'late_minutes' => $attendance->late_minutes,
            'early_checkout_minutes' => $attendance->early_checkout_minutes ?? 0,
            'can_check_in' => ! $attendance->check_in_at && ! $isRequestStatus,
            'can_check_out' => $attendance->check_in_at && ! $attendance->check_out_at && ! $isRequestStatus,
        ];
    }

    private function buildRecentAttendances($user)
    {
        return collect(range(0, 6))->map(function ($day) use ($user) {
            $date = now()->subDays($day)->startOfDay();
            $dateString = $date->toDateString();

            $attendance = Attendance::query()
                ->where('user_id', $user->id)
                ->whereDate('attendance_date', $dateString)
                ->first();

            $dayName = strtolower($date->format('l'));

            $workDays = $user->workSchedule?->work_days ?? [];

            $isWorkDay = empty($workDays) || in_array($dayName, $workDays);

            $status = 'not_checked_in';
            $statusLabel = 'Belum Check-in';

            if (! $isWorkDay && ! $attendance) {
                $status = 'off';
                $statusLabel = 'Libur';
            }

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

            if (! $attendance && $isWorkDay) {
                $isPastDate = $date->lt(now()->startOfDay());

                $isTodayAfterScheduleEnd = false;

                if ($date->isToday() && $user->workSchedule) {
                    $scheduleEnd = Carbon::parse(
                        $dateString . ' ' . $user->workSchedule->end_time
                    );

                    $isTodayAfterScheduleEnd = now()->greaterThan($scheduleEnd);
                }

                if ($isPastDate || $isTodayAfterScheduleEnd) {
                    $status = 'absent';
                    $statusLabel = 'Tidak Masuk';
                }
            }

            return [
                'date' => $date->translatedFormat('d M Y'),
                'day' => $date->translatedFormat('l'),
                'status' => $status,
                'status_label' => $statusLabel,
                'check_in_at' => $attendance?->check_in_at?->format('H:i'),
                'check_out_at' => $attendance?->check_out_at?->format('H:i'),
                'late_minutes' => $attendance?->late_minutes ?? 0,
            ];
        });
    }
}