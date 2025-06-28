<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreateEvaluation extends CreateRecord
{
    protected static string $resource = EvaluationResource::class;

    protected function beforeCreate(): void
    {
        $period = $this->data['year'] . '-' . $this->data['month'];

        if (DB::table('evaluations')->where('period', $period)->exists()) {
            Notification::make()
                ->title('Periode Evaluasi Sudah Ada')
                ->body('Data evaluasi untuk periode ini sudah tersedia.')
                ->danger()
                ->send();

            $this->halt(); // <<<<<< Ini yang menghentikan proses simpan!
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['period'] = $data['year'] . '-' . $data['month'];
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['month']) && isset($data['year'])) {
            $data['period'] = $data['year'] . '-' . $data['month'];
        }

        return $data;
    }

    public function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
