<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\EditRecord;

class EditEvaluation extends EditRecord
{
    protected static string $resource = EvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $period = $this->data['year'] . '-' . $this->data['month'];
        $currentRecordId = $this->record->id;

        // Cek apakah periode sudah ada, kecuali untuk record yang sedang diedit
        if (DB::table('evaluations')
            ->where('period', $period)
            ->where('id', '!=', $currentRecordId)
            ->exists()) {
            
            Notification::make()
                ->title('Periode Evaluasi Sudah Ada')
                ->body('Data evaluasi untuk periode ini sudah tersedia.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['month']) && isset($data['year'])) {
            $data['period'] = $data['year'] . '-' . $data['month'];
        }

        return $data;
    }
}