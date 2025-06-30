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
use App\Models\Attendance;
use App\Models\Division;
use App\Models\Leave;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;

class SalaryResource extends Resource
{
    protected static ?string $model = Salary::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $modelLabel = 'Gaji';
    protected static ?string $pluralModelLabel = 'Data Gaji';
    protected static ?string $navigationLabel = 'Gaji';
    protected static ?string $slug = 'gaji';

    protected static function isCurrentUserAdmin(): bool
    {
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin');
    }

    protected static function isCurrentUserHeadOfDivision(): bool
    {
        $user = Filament::auth()->user();
        if (!$user || !$user->employee) {
            return false;
        }
        return Division::where('head_id', $user->employee->id)->exists();
    }

    protected static function getCurrentUserDivisionId(): ?int
    {
        $user = Filament::auth()->user();
        return $user->employee?->division?->id;
    }

    public static function getEloquentQuery(): Builder
    {
        $currentUser = Filament::auth()->user();

        if (!$currentUser) {
            return parent::getEloquentQuery()->whereRaw('1 = 0')->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        if (static::isCurrentUserAdmin()) {
            return parent::getEloquentQuery()->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        $employeeId = null;
        if ($currentUser->employee) {
            $employeeId = $currentUser->employee->id;
        }

        if ($employeeId) {
            return parent::getEloquentQuery()
                ->where('employee_id', $employeeId)
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]);
        }

        return parent::getEloquentQuery()->whereRaw('1 = 0')->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function canCreate(): bool
    {
        return static::isCurrentUserAdmin();
    }

    public static function calculateLeaveDeduction($employeeId, $period)
    {
        $start = Carbon::parse($period)->startOfMonth();
        $end = Carbon::parse($period)->endOfMonth();

        $leaves = Leave::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('leave_type', 'unpaid')
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

            if ($leaveStart < $start) {
                $leaveStart = $start;
            }
            if ($leaveEnd > $end) {
                $leaveEnd = $end;
            }

            $totalLeaveDays += $leaveEnd->diffInDays($leaveStart) + 1;
        }

        $employee = Employee::find($employeeId);
        $baseSalary = $employee?->base_salary ?? 0;
        $workDays = 22;
        $deduction = ($baseSalary / $workDays) * $totalLeaveDays;

        return round($deduction, 2);
    }

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
                    ->options(function () {
                        return \App\Models\Evaluation::all()->mapWithKeys(function ($eval) {
                            try {
                                $date = \Carbon\Carbon::createFromFormat('Y-m', $eval->period);
                                $englishMonth = $date->format('F');
                                $year = $date->format('Y');
                                $monthIndo = convertEnglishMonthToIndonesian($englishMonth);
                                return [$eval->id => $monthIndo . ' ' . $year];
                            } catch (\Exception $e) {
                                Log::error('Format period tidak valid: ' . $eval->period);
                                return [$eval->id => $eval->period];
                            }
                        });
                    })
                    ->required()
                    ->rules([
                        function (callable $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                $employeeId = $get('employee_id');
                                $recordId = $get('id'); // Untuk mendapatkan ID record yang sedang diedit
                                
                                if (!$employeeId || !$value) {
                                    return;
                                }

                                $query = Salary::where('employee_id', $employeeId)
                                    ->where('evaluation_id', $value);

                                // Jika sedang edit, exclude record yang sedang diedit
                                if ($recordId) {
                                    $query->where('id', '!=', $recordId);
                                }

                                if ($query->exists()) {
                                    $fail('Periode Evaluasi untuk karyawan ini sudah ada.');
                                }
                            };
                        }
                    ]),

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
                            return null;
                        }

                        $employee = Employee::find($employeeId);
                        $baseSalary = $employee?->base_salary ?? 0;

                        $performanceScore = PerformanceResult::where('employee_id', $employeeId)
                            ->where('evaluation_id', $evaluationId)
                            ->value('score');

                        if ($performanceScore === null) {
                            return $baseSalary;
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
        $currentUser = Filament::auth()->user();
        $isAdmin = static::isCurrentUserAdmin();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('evaluation.period')
                    ->label('Periode Evaluasi')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($state) {
                        try {
                            $date = \Carbon\Carbon::createFromFormat('Y-m', $state);
                            $englishMonth = $date->format('F');
                            $year = $date->format('Y');
                            return convertEnglishMonthToIndonesian($englishMonth) . ' ' . $year;
                        } catch (\Exception $e) {
                            return $state;
                        }
                    }),

                Tables\Columns\TextColumn::make('base_salary')
                    ->label('Gaji Dasar')
                    ->money('idr'),

                Tables\Columns\TextColumn::make('bonus')
                    ->label('Bonus')
                    ->money('idr'),

                Tables\Columns\TextColumn::make('potongan')
                    ->label('Potongan')
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
                Tables\Actions\ViewAction::make()
                    ->visible(fn() => !$isAdmin),
                Tables\Actions\EditAction::make()
                    ->visible(fn() => $isAdmin),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => $isAdmin),
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