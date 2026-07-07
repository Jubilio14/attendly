<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;


class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $departmentId = $request->input('department_id');
        $status = $request->input('status');

        $employees = User::query()
            ->with([
                'departmentRelation:id,name',
                'workSchedule:id,name,start_time,end_time',
            ])
            ->where('role', 'employee')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->orWhere('phone', 'ilike', "%{$search}%")
                        ->orWhere('position', 'ilike', "%{$search}%");
                });
            })
            ->when($departmentId, function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->when($status === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($status === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->latest()
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'position' => $employee->position,
                    'department_id' => $employee->department_id,
                    'work_schedule_id' => $employee->work_schedule_id,
                    'department' => $employee->departmentRelation?->name ?? '-',
                    'work_schedule' => $employee->workSchedule
                        ? $employee->workSchedule->name . ' (' . $employee->workSchedule->start_time . ' - ' . $employee->workSchedule->end_time . ')'
                        : '-',
                    'is_active' => $employee->is_active,
                ];
            });

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $workSchedules = WorkSchedule::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'start_time', 'end_time']);

        return Inertia::render('Admin/Employees/Index', [
            'employees' => $employees,
            'departments' => $departments,
            'workSchedules' => $workSchedules,
            'filters' => [
                'search' => $search,
                'department_id' => $departmentId,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')],
                'position' => ['nullable', 'string', 'max:100'],
                'department_id' => ['nullable', 'exists:departments,id'],
                'work_schedule_id' => ['nullable', 'exists:work_schedules,id'],
            ],
            [
                'name.required' => 'Nama karyawan wajib diisi.',
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.unique' => 'Email sudah digunakan.',
                'password.required' => 'Password wajib diisi.',
                'password.min' => 'Password minimal 8 karakter.',
                'password.confirmed' => 'Konfirmasi password tidak sesuai.',
                'phone.unique' => 'No HP sudah digunakan.',
                'department_id.exists' => 'Departemen tidak valid.',
                'work_schedule_id.exists' => 'Jadwal kerja tidak valid.',
            ]
        );
        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'employee',
            'phone' => $validated['phone'] ?? null,
            'position' => $validated['position'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'work_schedule_id' => $validated['work_schedule_id'] ?? null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        abort_if($employee->role !== 'employee', 404);

        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($employee->id),
                ],
                'phone' => [
                    'nullable',
                    'string',
                    'max:30',
                    Rule::unique('users', 'phone')->ignore($employee->id),
                ],
                'position' => ['nullable', 'string', 'max:100'],
                'department_id' => ['nullable', 'exists:departments,id'],
                'work_schedule_id' => ['nullable', 'exists:work_schedules,id'],
            ],
            [
                'name.required' => 'Nama karyawan wajib diisi.',
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.unique' => 'Email sudah digunakan.',
                'phone.unique' => 'No HP sudah digunakan.',
                'department_id.exists' => 'Departemen tidak valid.',
                'work_schedule_id.exists' => 'Jadwal kerja tidak valid.',
            ]
        );

        $employee->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'position' => $validated['position'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'work_schedule_id' => $validated['work_schedule_id'] ?? null,
        ]);

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function toggleStatus(User $employee): RedirectResponse
    {
        abort_if($employee->role !== 'employee', 404);

        $employee->update([
            'is_active' => ! $employee->is_active,
        ]);

        $message = $employee->is_active
            ? 'Karyawan berhasil diaktifkan.'
            : 'Karyawan berhasil dinonaktifkan.';

        return redirect()
            ->route('admin.employees.index')
            ->with('success', $message);
    }

    public function resetPassword(Request $request, User $employee): RedirectResponse
    {
        abort_if($employee->role !== 'employee', 404);

        $validated = $request->validate(
            [
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ],
            [
                'password.required' => 'Password baru wajib diisi.',
                'password.min' => 'Password minimal 8 karakter.',
                'password.confirmed' => 'Konfirmasi password tidak sesuai.',
            ]
        );

        $employee->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Password karyawan berhasil direset.');
    }
}