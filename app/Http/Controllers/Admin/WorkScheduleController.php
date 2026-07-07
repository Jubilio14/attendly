<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkScheduleController extends Controller
{
    public function index(): Response
    {
        $workSchedules = WorkSchedule::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'start_time' => \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H:i'),
                    'end_time' => \Illuminate\Support\Carbon::parse($schedule->end_time)->format('H:i'),
                    'late_tolerance_minutes' => $schedule->late_tolerance_minutes,
                    'work_days' => $schedule->work_days ?? [],
                    'work_days_label' => $this->workDaysLabel($schedule->work_days ?? []),
                    'is_default' => $schedule->is_default,
                    'is_active' => $schedule->is_active,
                ];
            });

        return Inertia::render('Admin/WorkSchedules/Index', [
            'workSchedules' => $workSchedules,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:255', 'unique:work_schedules,name'],
                'start_time' => ['required', 'date_format:H:i'],
                'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
                'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
                'work_days' => ['required', 'array', 'min:1'],
                'work_days.*' => [
                    Rule::in([
                        'monday',
                        'tuesday',
                        'wednesday',
                        'thursday',
                        'friday',
                        'saturday',
                        'sunday',
                    ]),
                ],
                'is_default' => ['boolean'],
                'is_active' => ['boolean'],
            ],
            [
                'name.required' => 'Nama jadwal wajib diisi.',
                'name.unique' => 'Nama jadwal sudah digunakan.',
                'start_time.required' => 'Jam masuk wajib diisi.',
                'start_time.date_format' => 'Format jam masuk tidak valid.',
                'end_time.required' => 'Jam pulang wajib diisi.',
                'end_time.date_format' => 'Format jam pulang tidak valid.',
                'end_time.after' => 'Jam pulang harus setelah jam masuk.',
                'late_tolerance_minutes.required' => 'Toleransi telat wajib diisi.',
                'late_tolerance_minutes.integer' => 'Toleransi telat harus berupa angka.',
                'work_days.required' => 'Hari kerja wajib dipilih.',
                'work_days.min' => 'Pilih minimal 1 hari kerja.',
            ]
        );

        if ($validated['is_default'] ?? false) {
            WorkSchedule::query()->update(['is_default' => false]);
        }

        WorkSchedule::create([
            'name' => $validated['name'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'late_tolerance_minutes' => $validated['late_tolerance_minutes'],
            'work_days' => $validated['work_days'],
            'is_default' => $validated['is_default'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back();
    }

    public function update(Request $request, WorkSchedule $workSchedule): RedirectResponse
    {
        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('work_schedules', 'name')->ignore($workSchedule->id),
                ],
                'start_time' => ['required', 'date_format:H:i'],
                'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
                'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
                'work_days' => ['required', 'array', 'min:1'],
                'work_days.*' => [
                    Rule::in([
                        'monday',
                        'tuesday',
                        'wednesday',
                        'thursday',
                        'friday',
                        'saturday',
                        'sunday',
                    ]),
                ],
                'is_default' => ['boolean'],
                'is_active' => ['boolean'],
            ],
            [
                'name.required' => 'Nama jadwal wajib diisi.',
                'name.unique' => 'Nama jadwal sudah digunakan.',
                'start_time.required' => 'Jam masuk wajib diisi.',
                'end_time.required' => 'Jam pulang wajib diisi.',
                'end_time.after' => 'Jam pulang harus setelah jam masuk.',
                'late_tolerance_minutes.required' => 'Toleransi telat wajib diisi.',
                'work_days.required' => 'Hari kerja wajib dipilih.',
                'work_days.min' => 'Pilih minimal 1 hari kerja.',
            ]
        );

        if ($validated['is_default'] ?? false) {
            WorkSchedule::query()
                ->where('id', '!=', $workSchedule->id)
                ->update(['is_default' => false]);
        }

        $wantsToDeactivate = ! ($validated['is_active'] ?? false);

        if ($wantsToDeactivate) {
            $hasEmployees = $workSchedule->users()
                ->where('role', 'employee')
                ->exists();

            if ($hasEmployees) {
                return back()->withErrors([
                    'work_schedule' => 'Jadwal kerja tidak bisa dinonaktifkan karena masih digunakan oleh karyawan.',
                ]);
            }
        }

        $workSchedule->update([
            'name' => $validated['name'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'late_tolerance_minutes' => $validated['late_tolerance_minutes'],
            'work_days' => $validated['work_days'],
            'is_default' => $validated['is_default'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back();
    }

    private function workDaysLabel(array $workDays): string
    {
        $labels = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
        ];

        return collect($workDays)
            ->map(fn ($day) => $labels[$day] ?? $day)
            ->join(', ');
    }
}