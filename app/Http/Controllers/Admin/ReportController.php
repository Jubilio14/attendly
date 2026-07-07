<?php

namespace App\Http\Controllers\Admin;

use App\Exports\MonthlyAttendanceReportExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exports\EmployeeMonthlyAttendanceDetailExport;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $month = $request->input('month', now()->format('Y-m'));
        $search = $request->input('search');

        $data = $this->buildMonthlyReport($month, $search);

        return Inertia::render('Admin/Reports/Index', [
            'reports' => $data['reports'],
            'totals' => $data['totals'],
            'filters' => [
                'month' => $month,
                'search' => $search,
            ],
            'periodLabel' => $data['periodLabel'],
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $month = $request->input('month', now()->format('Y-m'));
        $search = $request->input('search');

        $data = $this->buildMonthlyReport($month, $search);

        $fileName = 'laporan-attendance-' . $month . '.xlsx';

        return Excel::download(
            new MonthlyAttendanceReportExport(
                $data['reports'],
                $data['periodLabel']
            ),
            $fileName
        );
    }

    public function detail(Request $request, User $employee): Response
    {
        abort_if($employee->role !== 'employee', 404);

        $month = $request->input('month', now()->format('Y-m'));

        $data = $this->buildEmployeeMonthlyDetail($employee, $month);

        return Inertia::render('Admin/Reports/Detail', [
            'employee' => $data['employee'],
            'dailyReports' => $data['dailyReports'],
            'summary' => $data['summary'],
            'filters' => [
                'month' => $month,
            ],
            'periodLabel' => $data['periodLabel'],
        ]);
    }

    public function exportDetail(Request $request, User $employee): BinaryFileResponse
    {
        abort_if($employee->role !== 'employee', 404);

        $month = $request->input('month', now()->format('Y-m'));

        $data = $this->buildEmployeeMonthlyDetail($employee, $month);

        $employeeName = Str::slug($employee->name);

        $fileName = 'detail-laporan-' . $employeeName . '-' . $month . '.xlsx';

        return Excel::download(
            new EmployeeMonthlyAttendanceDetailExport(
                $data['employee'],
                $data['dailyReports'],
                $data['summary'],
                $data['periodLabel']
            ),
            $fileName
        );
    }

    private function buildMonthlyReport(string $month, ?string $search = null): array
    {
        $startDate = Carbon::parse($month . '-01')->startOfMonth()->startOfDay();
        $endDate = Carbon::parse($month . '-01')->endOfMonth()->startOfDay();
        $today = now()->startOfDay();

        $employees = User::query()
            ->with([
                'departmentRelation:id,name',
                'workSchedule:id,name,start_time,end_time,work_days',
            ])
            ->where('role', 'employee')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->orWhere('position', 'ilike', "%{$search}%")
                        ->orWhereHas('departmentRelation', function ($query) use ($search) {
                            $query->where('name', 'ilike', "%{$search}%");
                        });
                });
            })
            ->orderBy('name')
            ->get();

        $attendances = Attendance::query()
            ->whereIn('user_id', $employees->pluck('id'))
            ->whereBetween('attendance_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->get()
            ->groupBy('user_id');

        $reports = $employees->map(function ($employee) use ($startDate, $endDate, $today, $attendances) {
            $employeeAttendances = $attendances
                ->get($employee->id, collect())
                ->keyBy(fn ($attendance) => $attendance->attendance_date->toDateString());

            $summary = [
                'work_days' => 0,
                'present' => 0,
                'late' => 0,
                'not_checked_in' => 0,
                'absent' => 0,
                'leave' => 0,
                'sick' => 0,
                'wfh' => 0,
                'wfc' => 0,
                'total_late_minutes' => 0,
                'early_checkout_count' => 0,
                'total_early_checkout_minutes' => 0,
            ];

            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                $dateString = $currentDate->toDateString();
                $dayName = strtolower($currentDate->format('l'));

                $workDays = $employee->workSchedule?->work_days ?? [];
                $isWorkDay = empty($workDays) || in_array($dayName, $workDays);

                if (! $isWorkDay) {
                    $currentDate->addDay();
                    continue;
                }

                $isFutureDate = $currentDate->copy()->startOfDay()->gt($today);

                if ($isFutureDate) {
                    $currentDate->addDay();
                    continue;
                }

                $summary['work_days']++;

                $attendance = $employeeAttendances->get($dateString);

                if ($attendance) {
                    if (in_array($attendance->status, ['present', 'late'])) {
                        $summary['present']++;
                    }

                    if ($attendance->late_minutes > 0 || $attendance->status === 'late') {
                        $summary['late']++;
                        $summary['total_late_minutes'] += $attendance->late_minutes;
                    }

                    $earlyCheckoutMinutes = $attendance->early_checkout_minutes ?? 0;

                    if ($earlyCheckoutMinutes > 0) {
                        $summary['early_checkout_count']++;
                        $summary['total_early_checkout_minutes'] += $earlyCheckoutMinutes;
                    }

                    if (in_array($attendance->status, ['absent', 'leave', 'sick', 'wfh', 'wfc'])) {
                        $summary[$attendance->status]++;
                    }

                    $currentDate->addDay();
                    continue;
                }

                if ($currentDate->isToday()) {
                    $isAfterScheduleEnd = false;

                    if ($employee->workSchedule) {
                        $scheduleEnd = Carbon::parse(
                            $dateString . ' ' . $employee->workSchedule->end_time
                        );

                        $isAfterScheduleEnd = now()->greaterThan($scheduleEnd);
                    }

                    if ($isAfterScheduleEnd) {
                        $summary['absent']++;
                    } else {
                        $summary['not_checked_in']++;
                    }
                } else {
                    $summary['absent']++;
                }

                $currentDate->addDay();
            }

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'position' => $employee->position,
                'department' => $employee->departmentRelation?->name ?? '-',
                'schedule' => $employee->workSchedule?->name ?? '-',
                'is_active' => $employee->is_active,
                'summary' => $summary,
            ];
        });

        return [
            'reports' => $reports,
            'totals' => $this->buildTotals($reports),
            'periodLabel' => $startDate->translatedFormat('F Y'),
        ];
    }
    
    private function buildEmployeeMonthlyDetail(User $employee, string $month): array
    {
        $employee->load([
            'departmentRelation:id,name',
            'workSchedule:id,name,start_time,end_time,work_days',
        ]);

        $startDate = Carbon::parse($month . '-01')->startOfMonth()->startOfDay();
        $endDate = Carbon::parse($month . '-01')->endOfMonth()->startOfDay();
        $today = now()->startOfDay();

        $attendances = Attendance::query()
            ->where('user_id', $employee->id)
            ->whereBetween('attendance_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->get()
            ->keyBy(fn ($attendance) => $attendance->attendance_date->toDateString());

        $summary = [
            'work_days' => 0,
            'present' => 0,
            'late' => 0,
            'not_checked_in' => 0,
            'absent' => 0,
            'leave' => 0,
            'sick' => 0,
            'wfh' => 0,
            'wfc' => 0,
            'off' => 0,
            'total_late_minutes' => 0,
            'early_checkout_count' => 0,
            'total_early_checkout_minutes' => 0,
        ];

        $dailyReports = collect();

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->toDateString();

            $isFutureDate = $currentDate->copy()->startOfDay()->gt($today);

            if ($isFutureDate) {
                $currentDate->addDay();
                continue;
            }

            $dayName = strtolower($currentDate->format('l'));

            $workDays = $employee->workSchedule?->work_days ?? [];
            $isWorkDay = empty($workDays) || in_array($dayName, $workDays);

            $attendance = $attendances->get($dateString);

            if (! $isWorkDay) {
                $summary['off']++;

                $dailyReports->push([
                    'date' => $currentDate->translatedFormat('d F Y'),
                    'day' => $currentDate->translatedFormat('l'),
                    'status' => 'off',
                    'status_label' => 'Libur',
                    'check_in_at' => '-',
                    'check_out_at' => '-',
                    'late_minutes' => 0,
                    'early_checkout_minutes' => 0,
                    'notes' => '-',
                ]);

                $currentDate->addDay();
                continue;
            }

            $summary['work_days']++;

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

                if (in_array($attendance->status, ['present', 'late'])) {
                    $summary['present']++;
                }

                if ($attendance->late_minutes > 0 || $attendance->status === 'late') {
                    $summary['late']++;
                    $summary['total_late_minutes'] += $attendance->late_minutes;
                }

                $earlyCheckoutMinutes = $attendance->early_checkout_minutes ?? 0;

                if ($earlyCheckoutMinutes > 0) {
                    $summary['early_checkout_count']++;
                    $summary['total_early_checkout_minutes'] += $earlyCheckoutMinutes;
                }

                if (in_array($attendance->status, ['absent', 'leave', 'sick', 'wfh', 'wfc'])) {
                    $summary[$attendance->status]++;
                }

                $dailyReports->push([
                    'date' => $currentDate->translatedFormat('d F Y'),
                    'day' => $currentDate->translatedFormat('l'),
                    'status' => $attendance->status,
                    'status_label' => $statusLabel,
                    'check_in_at' => $attendance->check_in_at?->format('H:i') ?? '-',
                    'check_out_at' => $attendance->check_out_at?->format('H:i') ?? '-',
                    'late_minutes' => $attendance->late_minutes,
                    'early_checkout_minutes' => $earlyCheckoutMinutes,
                    'notes' => $attendance->notes ?? '-',
                ]);

                $currentDate->addDay();
                continue;
            }

            $status = 'absent';
            $statusLabel = 'Tidak Masuk';

            if ($currentDate->isToday()) {
                $isAfterScheduleEnd = false;

                if ($employee->workSchedule) {
                    $scheduleEnd = Carbon::parse(
                        $dateString . ' ' . $employee->workSchedule->end_time
                    );

                    $isAfterScheduleEnd = now()->greaterThan($scheduleEnd);
                }

                if (! $isAfterScheduleEnd) {
                    $status = 'not_checked_in';
                    $statusLabel = 'Belum Check-in';
                }
            }

            $summary[$status]++;

            $dailyReports->push([
                'date' => $currentDate->translatedFormat('d F Y'),
                'day' => $currentDate->translatedFormat('l'),
                'status' => $status,
                'status_label' => $statusLabel,
                'check_in_at' => '-',
                'check_out_at' => '-',
                'late_minutes' => 0,
                'early_checkout_minutes' => 0,
                'notes' => '-',
            ]);

            $currentDate->addDay();
        }

        return [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'position' => $employee->position,
                'department' => $employee->departmentRelation?->name ?? '-',
                'schedule' => $employee->workSchedule?->name ?? '-',
                'is_active' => $employee->is_active,
            ],
            'dailyReports' => $dailyReports,
            'summary' => $summary,
            'periodLabel' => $startDate->translatedFormat('F Y'),
        ];
    }

    private function buildTotals(Collection $reports): array
    {
        return [
            'employees' => $reports->count(),
            'work_days' => $reports->sum('summary.work_days'),
            'present' => $reports->sum('summary.present'),
            'late' => $reports->sum('summary.late'),
            'not_checked_in' => $reports->sum('summary.not_checked_in'),
            'absent' => $reports->sum('summary.absent'),
            'leave' => $reports->sum('summary.leave'),
            'sick' => $reports->sum('summary.sick'),
            'wfh' => $reports->sum('summary.wfh'),
            'wfc' => $reports->sum('summary.wfc'),
            'total_late_minutes' => $reports->sum('summary.total_late_minutes'),
            'early_checkout_count' => $reports->sum('summary.early_checkout_count'),
            'total_early_checkout_minutes' => $reports->sum('summary.total_early_checkout_minutes'),
        ];
    }
}