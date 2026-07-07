<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user()->load('workSchedule');

        abort_if($user->role !== 'employee', 403);

        $validated = $request->validate([
            'start_date' => ['nullable', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'before_or_equal:today'],
        ], [
            'start_date.before_or_equal' => 'Tanggal awal tidak boleh melebihi hari ini.',
            'end_date.before_or_equal' => 'Tanggal akhir tidak boleh melebihi hari ini.',
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh lebih kecil dari tanggal awal.',
        ]);

        if (! empty($validated['start_date']) && ! empty($validated['end_date'])) {
            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
            $endDate = Carbon::parse($validated['end_date'])->startOfDay();
        } elseif (! empty($validated['start_date'])) {
            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
            $endDate = Carbon::parse($validated['start_date'])->startOfDay();
        } else {
            $endDate = now()->startOfDay();
            $startDate = now()->copy()->subDays(29)->startOfDay();
        }

        $totalDays = $startDate->diffInDays($endDate) + 1;

        if ($totalDays > 90) {
            $startDate = $endDate->copy()->subDays(89);
            $totalDays = 90;
        }

        $attendances = Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('attendance_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->get()
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->attendance_date)->toDateString();
            });

        $items = collect();

        foreach (range(0, $totalDays - 1) as $dayOffset) {
            $currentDate = $endDate->copy()->subDays($dayOffset);
            $dateString = $currentDate->toDateString();
            $dayName = strtolower($currentDate->format('l'));

            $workDays = $user->workSchedule?->work_days ?? [];
            $isWorkDay = empty($workDays) || in_array($dayName, $workDays);

            $attendance = $attendances->get($dateString);

            if (! $isWorkDay) {
                $items->push([
                    'date' => $currentDate->translatedFormat('d F Y'),
                    'day' => $currentDate->translatedFormat('l'),
                    'status' => 'off',
                    'status_label' => 'Libur',
                    'check_in_at' => null,
                    'check_out_at' => null,
                    'late_minutes' => 0,
                    'early_checkout_minutes' => 0,
                ]);

                continue;
            }

            if ($attendance) {
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

                $items->push([
                    'date' => $currentDate->translatedFormat('d F Y'),
                    'day' => $currentDate->translatedFormat('l'),
                    'status' => $attendance->status,
                    'status_label' => $statusLabel,
                    'check_in_at' => $attendance->check_in_at?->format('H:i'),
                    'check_out_at' => $attendance->check_out_at?->format('H:i'),
                    'late_minutes' => $attendance->late_minutes,
                    'early_checkout_minutes' => $attendance->early_checkout_minutes ?? 0,
                ]);

                continue;
            }

            $status = 'absent';
            $statusLabel = 'Tidak Masuk';

            if ($currentDate->isToday()) {
                $isAfterScheduleEnd = false;

                if ($user->workSchedule) {
                    $scheduleEnd = Carbon::parse(
                        $dateString . ' ' . $user->workSchedule->end_time
                    );

                    $isAfterScheduleEnd = now()->greaterThan($scheduleEnd);
                }

                if (! $isAfterScheduleEnd) {
                    $status = 'not_checked_in';
                    $statusLabel = 'Belum Check-in';
                }
            }

            $items->push([
                'date' => $currentDate->translatedFormat('d F Y'),
                'day' => $currentDate->translatedFormat('l'),
                'status' => $status,
                'status_label' => $statusLabel,
                'check_in_at' => null,
                'check_out_at' => null,
                'late_minutes' => 0,
                'early_checkout_minutes' => 0,
            ]);
        }

        return Inertia::render('Employee/History', [
            'items' => $items,
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'today' => now()->toDateString(),
            ],
        ]);
    }
}