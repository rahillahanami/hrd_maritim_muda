<?php

namespace App\Filament\Resources\SalaryResource\Pages;

use App\Filament\Resources\SalaryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PerformanceResult;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\Leave;
use App\Models\Attendance;
use Carbon\Carbon;

class CreateSalary extends CreateRecord
{
    protected static string $resource = SalaryResource::class;

    public static function calculateLeaveDeduction($employeeId, $period)
    {
        $carbonDate = Carbon::now();
        if (isset($data['period'])) {
            $englishPeriod = convertIndonesianMonthToEnglish($data['period']);
            $carbonDate = \Carbon\Carbon::parse($englishPeriod);
            // kalau mau simpan sebagai tanggal, bisa pakai:
            $data['period_date'] = $carbonDate->format('Y-m-d');
        }   

        $start = Carbon::parse($carbonDate)->startOfMonth(); // 2025-06-01
        $end = Carbon::parse($carbonDate)->endOfMonth(); // 2025-06-30

        // Cari cuti approved dan unpaid yang overlap dengan periode
        $leaves = Leave::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('leave_type', 'unpaid') // hanya cuti tanpa bayar yang kena potongan
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            })
            ->get();

        $totalLeaveDays = 0;

        foreach ($leaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd = Carbon::parse($leave->end_date);

            // Batasi tanggal cuti supaya masuk periode evaluasi
            if ($leaveStart < $start) {
                $leaveStart = $start;
            }
            if ($leaveEnd > $end) {
                $leaveEnd = $end;
            }

            $totalLeaveDays += $leaveEnd->diffInDays($leaveStart) + 1; // +1 supaya hari awal dihitung
        }

        // Ambil gaji pokok karyawan
        $employee = Employee::find($employeeId);
        $baseSalary = $employee?->base_salary ?? 0;

        $workDays = 22; // total hari kerja per bulan, bisa disesuaikan

        // Hitung potongan cuti tanpa bayar per hari
        $deduction = ($baseSalary / $workDays) * $totalLeaveDays;

        return round($deduction, 2);
    }

    public static function calculateLateDeduction($employeeId, $period, $baseSalary)
    {
        $carbonDate = Carbon::now();
        $periodEnglish = convertIndonesianMonthToEnglish($period);
        if (isset($period)) {
            $carbonDate = Carbon::parse($periodEnglish);
        }

        $start = $carbonDate->copy()->startOfMonth(); // Awal bulan
        $end = $carbonDate->copy()->endOfMonth(); // Akhir bulan

        // Ambil data keterlambatan dari tabel attendances
        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$start, $end])
            ->get();

        $totalLateMinutes = $attendances->sum('late_minutes'); // Total menit keterlambatan


        // Ambil gaji pokok karyawan
        $employee = Employee::find($employeeId);

        $workDays = 22; // Total hari kerja per bulan, bisa disesuaikan
        $workHoursPerDay = 8; // Jam kerja per hari
        $workMinutesPerMonth = $workDays * $workHoursPerDay * 60; // Total menit kerja per bulan

        // Hitung gaji per menit
        $salaryPerMinute = $baseSalary / $workMinutesPerMonth;

        // Hitung potongan keterlambatan
        $deduction = $salaryPerMinute * $totalLateMinutes;
        return round($deduction, 2);
    }

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $result = PerformanceResult::where('employee_id', $data['employee_id'])
            ->where('evaluation_id', $data['evaluation_id'])
            ->first();

        $score = $result?->score ?? 0;
        $bonus = $score * 0.1 * $data['base_salary'];
        $potongan = self::calculateLateDeduction($data['employee_id'], Evaluation::find($data['evaluation_id'])->period, $data['base_salary']);
        $data['performance_score'] = $score;
        $data['bonus'] = $bonus;
        $data['potongan'] = $potongan;
        $data['final_salary'] = $data['base_salary'] + $bonus - $potongan;

        return $data;
    }
}
