<?php

namespace App\Filament\Resources\SalaryResource\Pages;

use App\Filament\Resources\SalaryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PerformanceResult;
use App\Models\Employee;


class CreateSalary extends CreateRecord
{
    protected static string $resource = SalaryResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $result = PerformanceResult::where('employee_id', $data['employee_id'])
            ->where('evaluation_id', $data['evaluation_id'])
            ->first();
 
        $score = $result?->score ?? 0;
        $bonus = $score * 0.1 * $data['base_salary'];

        $data['performance_score'] = $score;
        $data['bonus'] = $bonus;
        $data['final_salary'] = $data['base_salary'] + $bonus;

        return $data;
    }
}
