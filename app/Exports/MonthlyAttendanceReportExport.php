<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlyAttendanceReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        private Collection $reports,
        private string $periodLabel
    ) {}

    public function title(): string
    {
        return 'Laporan ' . $this->periodLabel;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'Email',
            'Jabatan',
            'Departemen',
            'Jadwal',
            'Hari Kerja',
            'Hadir',
            'Telat',
            'Belum Check-in',
            'Tidak Masuk',
            'Izin',
            'Sakit',
            'WFH',
            'WFC',
            'Total Telat (Menit)',
            'Status Karyawan',
        ];
    }

    public function array(): array
    {
        return $this->reports
            ->values()
            ->map(function ($report, $index) {
                return [
                    $index + 1,
                    $report['name'],
                    $report['email'],
                    $report['position'] ?? '-',
                    $report['department'],
                    $report['schedule'],
                    $report['summary']['work_days'],
                    $report['summary']['present'],
                    $report['summary']['late'],
                    $report['summary']['not_checked_in'],
                    $report['summary']['absent'],
                    $report['summary']['leave'],
                    $report['summary']['sick'],
                    $report['summary']['wfh'],
                    $report['summary']['wfc'],
                    $report['summary']['total_late_minutes'],
                    $report['is_active'] ? 'Aktif' : 'Nonaktif',
                ];
            })
            ->toArray();
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }
}