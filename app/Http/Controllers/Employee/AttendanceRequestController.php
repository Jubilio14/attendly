<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_if($user->role !== 'employee', 403);

        $status = $request->input('status', 'all');

        if (! in_array($status, ['all', 'pending', 'approved', 'rejected'])) {
            $status = 'all';
        }

        $requests = $user->attendanceRequests()
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest('start_date')
            ->latest('id')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'type' => $request->type,
                    'type_label' => match ($request->type) {
                        'leave' => 'Izin',
                        'sick' => 'Sakit',
                        'wfh' => 'WFH',
                        'wfc' => 'WFC',
                        default => '-',
                    },
                    'date_range' => $request->start_date->format('d M Y') . ' - ' . $request->end_date->format('d M Y'),
                    'start_date' => $request->start_date->format('Y-m-d'),
                    'end_date' => $request->end_date->format('Y-m-d'),
                    'reason' => $request->reason,
                    'status' => $request->status,
                    'status_label' => match ($request->status) {
                        'pending' => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => '-',
                    },
                    'rejection_reason' => $request->rejection_reason,
                    'reviewed_at' => $request->reviewed_at?->format('d M Y H:i'),
                ];
            });

        return Inertia::render('Employee/Requests', [
            'requests' => $requests,
            'filters' => [
                'status' => $status,
                'today' => now()->toDateString(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user->role !== 'employee', 403);

        $validated = $request->validate(
            [
                'type' => ['required', Rule::in(['leave', 'sick', 'wfh', 'wfc'])],
                'start_date' => ['required', 'date', 'after_or_equal:today'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
                'reason' => ['required', 'string', 'min:5', 'max:1000'],
            ],
            [
                'type.required' => 'Jenis pengajuan wajib dipilih.',
                'type.in' => 'Jenis pengajuan tidak valid.',
                'start_date.required' => 'Tanggal mulai wajib diisi.',
                'start_date.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
                'end_date.required' => 'Tanggal selesai wajib diisi.',
                'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
                'reason.required' => 'Alasan wajib diisi.',
                'reason.min' => 'Alasan minimal 5 karakter.',
                'reason.max' => 'Alasan maksimal 1000 karakter.',
            ]
        );

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();

        if (
            $validated['type'] === 'leave' &&
            $startDate->isToday() &&
            now()->greaterThan(now()->setTime(12, 0))
        ) {
            return back()->withErrors([
                'start_date' => 'Izin untuk hari ini maksimal diajukan sebelum jam 12:00.',
            ]);
        }

        $hasOverlapRequest = AttendanceRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $validated['end_date'])
            ->whereDate('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($hasOverlapRequest) {
            return back()->withErrors([
                'start_date' => 'Kamu sudah memiliki pengajuan pada rentang tanggal tersebut.',
            ]);
        }

        AttendanceRequest::create([
            'user_id' => $user->id,
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return back();
    }
}