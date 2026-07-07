<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->input('status');

        $requests = AttendanceRequest::query()
            ->with([
                'user:id,name,email,position,department_id,work_schedule_id',
                'user.departmentRelation:id,name',
                'reviewer:id,name',
            ])
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->get()
            ->map(function ($attendanceRequest) {
                return [
                    'id' => $attendanceRequest->id,
                    'employee_name' => $attendanceRequest->user?->name ?? '-',
                    'employee_email' => $attendanceRequest->user?->email ?? '-',
                    'position' => $attendanceRequest->user?->position,
                    'department' => $attendanceRequest->user?->departmentRelation?->name ?? '-',
                    'type' => $attendanceRequest->type,
                    'type_label' => $this->typeLabel($attendanceRequest->type),
                    'start_date' => $attendanceRequest->start_date->format('d M Y'),
                    'end_date' => $attendanceRequest->end_date->format('d M Y'),
                    'reason' => $attendanceRequest->reason,
                    'status' => $attendanceRequest->status,
                    'status_label' => $this->statusLabel($attendanceRequest->status),
                    'reviewer_name' => $attendanceRequest->reviewer?->name,
                    'reviewed_at' => $attendanceRequest->reviewed_at?->format('d M Y H:i'),
                    'rejection_reason' => $attendanceRequest->rejection_reason,
                ];
            });

        $stats = [
            'total' => AttendanceRequest::count(),
            'pending' => AttendanceRequest::where('status', 'pending')->count(),
            'approved' => AttendanceRequest::where('status', 'approved')->count(),
            'rejected' => AttendanceRequest::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Admin/Requests/Index', [
            'requests' => $requests,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    public function approve(AttendanceRequest $attendanceRequest): RedirectResponse
    {
        if ($attendanceRequest->status !== 'pending') {
            return back()->withErrors([
                'request' => 'Pengajuan ini sudah diproses.',
            ]);
        }

        $user = $attendanceRequest->user;

        if (! $user) {
            return back()->withErrors([
                'request' => 'Data karyawan tidak ditemukan.',
            ]);
        }

        $attendanceRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        $currentDate = Carbon::parse($attendanceRequest->start_date);
        $endDate = Carbon::parse($attendanceRequest->end_date);

        while ($currentDate->lte($endDate)) {
            Attendance::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'attendance_date' => $currentDate->toDateString(),
                ],
                [
                    'work_schedule_id' => $user->work_schedule_id,
                    'status' => $attendanceRequest->type,
                    'late_minutes' => 0,
                    'notes' => 'Approved request: ' . $this->typeLabel($attendanceRequest->type),
                ]
            );

            $currentDate->addDay();
        }

        return back();
    }

    public function reject(Request $request, AttendanceRequest $attendanceRequest): RedirectResponse
    {
        if ($attendanceRequest->status !== 'pending') {
            return back()->withErrors([
                'request' => 'Pengajuan ini sudah diproses.',
            ]);
        }

        $validated = $request->validate(
            [
                'rejection_reason' => ['required', 'string', 'min:5', 'max:1000'],
            ],
            [
                'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
                'rejection_reason.min' => 'Alasan penolakan minimal 5 karakter.',
                'rejection_reason.max' => 'Alasan penolakan maksimal 1000 karakter.',
            ]
        );

        $attendanceRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back();
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'leave' => 'Izin',
            'sick' => 'Sakit',
            'wfh' => 'WFH',
            'wfc' => 'WFC',
            default => '-',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => '-',
        };
    }
}