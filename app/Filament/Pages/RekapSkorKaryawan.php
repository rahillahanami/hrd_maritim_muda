<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationCriteria;
use App\Models\EmployeeScore;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class RekapSkorKaryawan extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $title = 'Rekap Skor Karyawan';
    protected static ?string $navigationLabel = 'Rekap Skor';
    protected static string $view = 'filament.pages.rekap-skor-karyawan';
    protected static ?string $navigationGroup = 'Sistem Pengambilan Keputusan'; // <<< NAMA GRUP
    protected static ?int $navigationSort = 2; // <<< URUTAN KEDUA DI GRUP INI

    public $employee_id, $evaluation_id;
    public $results = [];

    public function updated($property): void
    {
        if ($this->employee_id && $this->evaluation_id) {
            $this->results = EmployeeScore::where('employee_id', $this->employee_id)
                ->where('evaluation_id', $this->evaluation_id)
                ->with('criteria')
                ->get();
        }
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('employee_id')
                ->label('Karyawan')
                ->options(Employee::pluck('name', 'id'))
                ->reactive()
                ->required(),

            Forms\Components\Select::make('evaluation_id')
                ->label('Periode Evaluasi')
                ->options(Evaluation::pluck('period', 'id'))
                ->reactive()
                ->required(),
        ];
    }
}
