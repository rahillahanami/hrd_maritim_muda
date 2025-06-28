<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationCriteria;
use App\Models\EmployeeScore;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EditEmployeeScore extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-pencil';
    protected static ?string $navigationLabel = 'Edit Skor Karyawan';
    protected static string $view = 'filament.pages.edit-employee-score';
    protected static ?string $title = 'Edit Skor Karyawan';
    protected static ?string $navigationGroup = 'Sistem Pengambilan Keputusan'; // <<< NAMA GRUP
    protected static ?int $navigationSort = 3; // <<< URUTAN KETIGA DI GRUP INI


    public $employee_id, $evaluation_id, $scores = [];

    public static function canAccess(): bool
    {
        return Auth::user() && Auth::user()->hasRole('super_admin');
    }

    public function updated($field)
    {
        // Saat user milih karyawan dan periode
        if ($field === 'employee_id' || $field === 'evaluation_id') {
            $this->loadScores();
        }
    }

    public function loadScores()
    {
        if ($this->employee_id && $this->evaluation_id) {
            $this->scores = EvaluationCriteria::all()->map(function ($criteria) {
                $score = EmployeeScore::where('employee_id', $this->employee_id)
                    ->where('evaluation_id', $this->evaluation_id)
                    ->where('evaluation_criteria_id', $criteria->id)
                    ->first();

                return [
                    'evaluation_criteria_id' => $criteria->id,
                    'criteria_name' => $criteria->name,
                    'score' => $score?->score,
                ];
            })->toArray();
        }
    }

    public function submit()
    {
        foreach ($this->scores as $score) {
            EmployeeScore::updateOrCreate(
                [
                    'employee_id' => $this->employee_id,
                    'evaluation_id' => $this->evaluation_id,
                    'evaluation_criteria_id' => $score['evaluation_criteria_id'],
                ],
                [
                    'score' => $score['score'],
                ]
            );
        }


        Notification::make()
            ->title('Skor berhasil diperbarui.')
            ->success()
            ->send();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('employee_id')
                ->label('Karyawan')
                ->options(Employee::all()->pluck('name', 'id'))
                ->reactive()
                ->required(),

            Forms\Components\Select::make('evaluation_id')
                ->label('Periode Evaluasi')
                ->options(function () {
                    return Evaluation::all()->mapWithKeys(function ($eval) {
                        try {
                            $date = Carbon::createFromFormat('Y-m', $eval->period);
                            $englishMonth = $date->format('F');
                            $year = $date->format('Y');
                            $localized = convertEnglishMonthToIndonesian($englishMonth);
                            return [$eval->id => $localized . ' ' . $year];
                        } catch (\Exception $e) {
                            Log::error('Invalid date format in Evaluation: ' . $eval->period);
                            return [$eval->id => $eval->period];
                        }
                    });
                })
                ->reactive()
                ->required(),

            Forms\Components\Repeater::make('scores')
                ->label('Edit Skor Kriteria')
                ->schema([
                    Forms\Components\Hidden::make('evaluation_criteria_id'),
                    Forms\Components\TextInput::make('criteria_name')
                        ->label('Kriteria')
                        ->readOnly(),
                    Forms\Components\TextInput::make('score')
                        ->numeric()
                        ->required(),
                ])
                ->columns(3)
                ->disableItemCreation()
                ->disableItemDeletion()
                ->required(),

        ];
    }

    protected function getFormModel(): string
    {
        return static::class;
    }

    public function mount(): void
    {
        // Kosong di awal, user pilih manual
    }
}
