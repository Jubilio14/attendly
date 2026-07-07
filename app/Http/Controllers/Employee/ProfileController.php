<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user()->load([
            'departmentRelation:id,name',
            'workSchedule:id,name,start_time,end_time,late_tolerance_minutes,work_days',
        ]);

        abort_if($user->role !== 'employee', 403);

        return Inertia::render('Employee/Profile', [
            'employee' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'position' => $user->position,
                'department' => $user->departmentRelation?->name ?? '-',
                'schedule' => $user->workSchedule ? [
                    'name' => $user->workSchedule->name,
                    'start_time' => $user->workSchedule->start_time,
                    'end_time' => $user->workSchedule->end_time,
                    'late_tolerance_minutes' => $user->workSchedule->late_tolerance_minutes,
                    'work_days' => $user->workSchedule->work_days ?? [],
                ] : null,
            ],
        ]);
    }

    public function updatePhone(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user->role !== 'employee', 403);

        $validated = $request->validate(
            [
                'phone' => ['nullable', 'string', 'max:20'],
            ],
            [
                'phone.max' => 'Nomor HP maksimal 20 karakter.',
            ]
        );

        $user->update([
            'phone' => $validated['phone'],
        ]);

        return back();
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user->role !== 'employee', 403);

        $validated = $request->validate(
            [
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ],
            [
                'current_password.required' => 'Password lama wajib diisi.',
                'current_password.current_password' => 'Password lama tidak sesuai.',
                'password.required' => 'Password baru wajib diisi.',
                'password.min' => 'Password baru minimal 8 karakter.',
                'password.confirmed' => 'Konfirmasi password tidak sama.',
            ]
        );

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back();
    }
}