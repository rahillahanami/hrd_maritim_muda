<?php

namespace App\Filament\Resources\SalaryResource\Pages;

use App\Filament\Resources\SalaryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PerformanceResult;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\Leave;
use Carbon\Carbon;

class CreateSalary extends CreateRecord
{
    protected static string $resource = SalaryResource::class;

    public static function calculateLeaveDeduction($employeeId, $period)
    {

        if (isset($data['period'])) {
            $englishPeriod = convertIndonesianMonthToEnglish($data['period']);
            $carbonDate = \Carbon\Carbon::parse($englishPeriod);
            // kalau mau simpan sebagai tanggal, bisa pakai:
            $data['period_date'] = $carbonDate->format('Y-m-d');
        }

        $start = Carbon::parse($period)->startOfMonth(); // 2025-06-01
        $end = Carbon::parse($period)->endOfMonth(); // 2025-06-30

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


    public function mutateFormDataBeforeCreate(array $data): array
    {
        $result = PerformanceResult::where('employee_id', $data['employee_id'])
            ->where('evaluation_id', $data['evaluation_id'])
            ->first();

        $score = $result?->score ?? 0;
        $bonus = $score * 0.1 * $data['base_salary'];
        $potongan = self::calculateLeaveDeduction($data['employee_id'], Evaluation::find($data['evaluation_id'])->period);

        $data['performance_score'] = $score;
        $data['bonus'] = $bonus;
        $data['potongan'] = $potongan;
        $data['final_salary'] = $data['base_salary'] + $bonus;

        return $data;
    }
}
