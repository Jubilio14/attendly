<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'IT',
            'HR',
            'Finance',
            'Marketing',
            'Operational',
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['name' => $department],
                ['is_active' => true]
            );
        }

        $regularSchedule = WorkSchedule::updateOrCreate(
            ['name' => 'Regular Office'],
            [
                'start_time' => '08:00',
                'end_time' => '17:00',
                'late_tolerance_minutes' => 15,
                'work_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $itDepartment = Department::where('name', 'IT')->first();
        $hrDepartment = Department::where('name', 'HR')->first();

        User::where('email', 'admin@attendly.test')->update([
            'department_id' => $hrDepartment?->id,
            'work_schedule_id' => $regularSchedule->id,
        ]);

        User::where('email', 'employee@attendly.test')->update([
            'department_id' => $itDepartment?->id,
            'work_schedule_id' => $regularSchedule->id,
        ]);
    }
}