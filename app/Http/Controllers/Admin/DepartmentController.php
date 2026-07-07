<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $departments = Department::query()
            ->withCount([
                'users as employees_count' => function ($query) {
                    $query->where('role', 'employee');
                },
            ])
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%");
            })
            ->when($status === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($status === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->orderBy('name')
            ->get()
            ->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'is_active' => $department->is_active,
                    'employees_count' => $department->employees_count,
                ];
            });

        return Inertia::render('Admin/Departments/Index', [
            'departments' => $departments,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
                'is_active' => ['boolean'],
            ],
            [
                'name.required' => 'Nama departemen wajib diisi.',
                'name.unique' => 'Nama departemen sudah digunakan.',
            ]
        );

        Department::create([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back();
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('departments', 'name')->ignore($department->id),
                ],
                'is_active' => ['boolean'],
            ],
            [
                'name.required' => 'Nama departemen wajib diisi.',
                'name.unique' => 'Nama departemen sudah digunakan.',
            ]
        );

        $wantsToDeactivate = ! ($validated['is_active'] ?? false);

        if ($wantsToDeactivate) {
            $hasEmployees = $department->users()
                ->where('role', 'employee')
                ->exists();

            if ($hasEmployees) {
                return back()->withErrors([
                    'department' => 'Departemen tidak bisa dinonaktifkan karena masih memiliki karyawan.',
                ]);
            }
        }

        $department->update([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back();
    }
}