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

class SalaryResource extends Resource
{
    protected static ?string $model = Salary::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar'; // Ikon
    protected static ?string $modelLabel = 'Gaji';
    protected static ?string $pluralModelLabel = 'Data Gaji';
    protected static ?string $navigationLabel = 'Gaji';
    protected static ?string $slug = 'gaji';


    protected static function isCurrentUserAdmin(): bool
    {
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin'); // Sesuaikan peran admin
    }

    // isCurrentUserHeadOfDivision tidak terlalu relevan untuk Salary (biasanya admin/HR yang atur gaji)
    protected static function isCurrentUserHeadOfDivision(): bool
    {
        $user = Filament::auth()->user();
        if (!$user || !$user->employee) {
            return false;
        }
        return Division::where('head_id', $user->employee->id)->exists();
    }

    // getCurrentUserDivisionId juga tidak terlalu relevan untuk Salary
    protected static function getCurrentUserDivisionId(): ?int
    {
        $user = Filament::auth()->user();
        return $user->employee?->division?->id;
    }

    // --- Query Scoping ---
    public static function getEloquentQuery(): Builder
    {
        $currentUser = Filament::auth()->user();

        // Jika user tidak login, jangan tampilkan apa-apa
        if (!$currentUser) {
            return parent::getEloquentQuery()->whereRaw('1 = 0')->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        // Jika user adalah Admin
        if (static::isCurrentUserAdmin()) {
            return parent::getEloquentQuery()->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        // --- Logika untuk user biasa (karyawan) ---
        // User biasa hanya bisa melihat gajinya sendiri.
        // Kita perlu employee_id dari user yang login.
        $employeeId = null;
        if ($currentUser->employee) {
            $employeeId = $currentUser->employee->id;
        }

        if ($employeeId) {
            return parent::getEloquentQuery()
                ->where('employee_id', $employeeId) // Filter berdasarkan employee_id karyawan itu sendiri
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]);
        }

        // Jika user login, bukan admin, tapi tidak punya data employee
        return parent::getEloquentQuery()->whereRaw('1 = 0')->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    // --- canCreate() ---
    public static function canCreate(): bool
    {
        // Hanya Admin yang bisa membuat record gaji
        return static::isCurrentUserAdmin();
    }

    public static function calculateLeaveDeduction($employeeId, $period)
    {
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

                // Forms\Components\TextInput::make('potongan')
                //     ->label('Potongan')
                //     ->numeric()
                //     ->disabled()
                //     ->default(0)
                //     ->visible(),


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
                    ->searchable(),

                Tables\Columns\TextColumn::make('base_salary')
                    ->label('Gaji Dasar')
                    ->money('idr'),

                Tables\Columns\TextColumn::make('bonus')
                    ->label('Bonus')
                    ->money('idr'),

                Tables\Columns\TextColumn::make('potongan')
                    ->label('Potongan')
                    ->money('idr'), // sesuaikan format mata uang


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
                    ->visible(fn() => !$isAdmin), // User biasa hanya bisa view
                Tables\Actions\EditAction::make()
                    ->visible(fn() => $isAdmin), // Hanya Admin yang bisa edit
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => $isAdmin), // Hanya Admin yang bisa delete
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
