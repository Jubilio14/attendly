<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeMonthlyAttendanceDetailExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        private array $employee,
        private Collection $dailyReports,
        private array $summary,
        private string $periodLabel
    ) {}

    public function title(): string
    {
        return 'Detail Laporan';
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Hari',
            'Status',
            'Check-in',
            'Check-out',
            'Telat',
            'Catatan',
        ];
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['Nama', $this->employee['name']];
        $rows[] = ['Email', $this->employee['email']];
        $rows[] = ['Jabatan', $this->employee['position'] ?? '-'];
        $rows[] = ['Departemen', $this->employee['department']];
        $rows[] = ['Jadwal', $this->employee['schedule']];
        $rows[] = ['Periode', $this->periodLabel];
        $rows[] = [];

        $rows[] = ['Ringkasan'];
        $rows[] = ['Hari Kerja', $this->summary['work_days']];
        $rows[] = ['Hadir', $this->summary['present']];
        $rows[] = ['Telat', $this->summary['late']];
        $rows[] = ['Belum Check-in', $this->summary['not_checked_in']];
        $rows[] = ['Tidak Masuk', $this->summary['absent']];
        $rows[] = ['Izin', $this->summary['leave']];
        $rows[] = ['Sakit', $this->summary['sick']];
        $rows[] = ['WFH', $this->summary['wfh']];
        $rows[] = ['WFC', $this->summary['wfc']];
        $rows[] = ['Libur', $this->summary['off']];
        $rows[] = ['Total Telat (Menit)', $this->summary['total_late_minutes']];
        $rows[] = [];

        $rows[] = $this->headings();

        foreach ($this->dailyReports as $dailyReport) {
            $rows[] = [
                $dailyReport['date'],
                $dailyReport['day'],
                $dailyReport['status_label'],
                $dailyReport['check_in_at'],
                $dailyReport['check_out_at'],
                $dailyReport['late_minutes'] > 0 ? $dailyReport['late_minutes'] . ' menit' : '-',
                $dailyReport['notes'],
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],
            8 => [
                'font' => [
                    'bold' => true,
                ],
            ],
            21 => [
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }
}