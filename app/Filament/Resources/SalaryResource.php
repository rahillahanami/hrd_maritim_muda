<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryResource\Pages;
use App\Filament\Resources\SalaryResource\RelationManagers;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\PerformanceResult;
use App\Models\Salary;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;

class SalaryResource extends Resource
{
    protected static ?string $model = Salary::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->label('Karyawan')
                    ->options(Employee::all()->pluck('name', 'id'))
                    ->required(),

                Forms\Components\Select::make('evaluation_id')
                    ->label('Periode Evaluasi')
                    ->options(Evaluation::all()->pluck('period', 'id'))
                    ->required()
                    ->rule(function (callable $get) {
                        return Rule::unique('salaries', 'evaluation_id')
                            ->where(function ($query) use ($get) {
                                return $query->where('employee_id', $get('employee_id'));
                            });
                    }),

                Forms\Components\TextInput::make('base_salary')
                    ->numeric()
                    ->required()
                    ->default(fn($get) => Employee::find($get('employee_id'))?->base_salary ?? null),

                Forms\Components\TextInput::make('final_salary')
                    ->numeric()
                    ->default(function ($get) {
                        $employeeId = $get('employee_id');
                        $evaluationId = $get('evaluation_id');

                        if (! $employeeId || ! $evaluationId) {
                            return null; // Jangan isi kalau belum lengkap
                        }

                        $employee = Employee::find($employeeId);
                        $baseSalary = $employee?->base_salary ?? 0;

                        $performanceScore = PerformanceResult::where('employee_id', $employeeId)
                            ->where('evaluation_id', $evaluationId)
                            ->value('score');

                        if ($performanceScore === null) {
                            return $baseSalary; // <- fallback aman
                        }

                        $bonus = $performanceScore * 0.1 * $baseSalary;
                        return round($baseSalary + $bonus);
                    })
                    ->disabled()
                    ->dehydrated(true),
            ]);
    }




    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('evaluation.period')
                    ->label('Periode Evaluasi')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('base_salary')
                    ->label('Gaji Dasar')
                    ->money('idr'),

                Tables\Columns\TextColumn::make('bonus')
                    ->label('Bonus')
                    ->money('idr'),

                Tables\Columns\TextColumn::make('final_salary')
                    ->label('Gaji Akhir')
                    ->money('idr'),
            ])
            ->filters([
                SelectFilter::make('evaluation_id')
                    ->label('Periode Evaluasi')
                    ->options(Evaluation::whereNotNull('period')->pluck('period', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalaries::route('/'),
            'create' => Pages\CreateSalary::route('/create'),
            'edit' => Pages\EditSalary::route('/{record}/edit'),
        ];
    }
}
