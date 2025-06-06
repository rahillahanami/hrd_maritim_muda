<?php

namespace App\Filament\Resources\SalaryResource\Pages;

use App\Filament\Resources\SalaryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\PerformanceResult;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\Attendance;
use Carbon\Carbon;

class EditSalary extends EditRecord
{
    protected static string $resource = SalaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $result = PerformanceResult::where('employee_id', $data['employee_id'])
            ->where('evaluation_id', $data['evaluation_id'])
            ->first();

        $score = $result?->score ?? 0;
        $bonus = $score * 0.1 * $data['base_salary'];
        $potongan = CreateSalary::calculateLateDeduction($data['employee_id'], Evaluation::find($data['evaluation_id'])->period, $data['base_salary']);
        $data['performance_score'] = $score;
        $data['bonus'] = $bonus;
        $data['potongan'] = $potongan;
        $data['final_salary'] = $data['base_salary'] + $bonus - $potongan;

        return $data;
    }

    protected function afterSave(): void
    {
        $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record->getKey()]));
    }
}
