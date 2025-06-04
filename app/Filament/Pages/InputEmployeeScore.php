<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationCriteria;
use App\Models\EmployeeScore;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;


class InputEmployeeScore extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static string $view = 'filament.pages.input-employee-score';
    protected static ?string $navigationLabel = 'Input Skor Karyawan';
    protected static ?string $title = 'Input Skor Karyawan';
    protected static ?string $navigationGroup = 'Sistem Pengambilan Keputusan'; // <<< NAMA GRUP
    protected static ?int $navigationSort = 1; // <<< URUTAN PERTAMA DI GRUP INI (untuk "Input Skor Karyawan")

    public $employee_id, $evaluation_id, $scores = [];

    public static function canAccess(): bool
    {
        return Auth::user() && Auth::user()->hasRole('super_admin');
    }

    public function mount(): void
    {
        $this->scores = EvaluationCriteria::all()->map(fn($c) => [
            'evaluation_criteria_id' => $c->id,
            'criteria_name' => $c->name,
            'score' => null,
        ])->toArray();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('employee_id')
                ->label('Karyawan')
                ->options(Employee::all()->pluck('name', 'id'))
                ->required(),

            Forms\Components\Select::make('evaluation_id')
                ->label('Periode Evaluasi')
                ->options(Evaluation::all()->pluck('period', 'id'))
                ->required(),

            Forms\Components\Repeater::make('scores')
                ->label('Input Skor Kriteria')
                ->schema([
                    Forms\Components\Hidden::make('evaluation_criteria_id'),
                    Forms\Components\TextInput::make('criteria_name')
                        ->label('Kriteria')
                        ->readOnly(),
                    Forms\Components\TextInput::make('score')
                        ->numeric()
                        ->required(),
                ])
                ->disableItemDeletion()
                ->disableItemCreation()
                ->columns(3),
        ];
    }

    protected function getFormModel(): string
    {
        return static::class; // biar tidak error model undefined
    }

    public function submit()
    {
        // Cek apakah skor sudah pernah diinput untuk karyawan dan periode ini
        $exists = EmployeeScore::where('employee_id', $this->employee_id)
            ->where('evaluation_id', $this->evaluation_id)
            ->exists();

        if ($exists) {
            // session()->flash('error', 'Skor untuk karyawan dan periode ini sudah pernah diinput.');
            Notification::make()->title('Gagal menyimpan skor')
                ->body('Skor untuk karyawan dan periode ini sudah pernah diinput.')
                ->danger()
                ->send();
            return;
        }

        // Simpan skor baru
        foreach ($this->scores as $score) {
            EmployeeScore::create([
                'employee_id' => $this->employee_id,
                'evaluation_id' => $this->evaluation_id,
                'evaluation_criteria_id' => $score['evaluation_criteria_id'],
                'score' => $score['score'],
            ]);
        }

        // session()->flash('success', 'Skor berhasil disimpan.');
        Notification::make()->title('Skor berhasil disimpan')
            ->body('Skor karyawan telah berhasil disimpan untuk periode evaluasi ini.')
            ->success()
            ->send();

        // Reset form
        $this->reset(['employee_id', 'evaluation_id']);
        $this->scores = EvaluationCriteria::all()->map(fn($c) => [
            'evaluation_criteria_id' => $c->id,
            'criteria_name' => $c->name,
            'score' => null,
        ])->toArray();
    }
}
