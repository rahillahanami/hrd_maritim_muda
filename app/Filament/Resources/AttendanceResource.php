<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    protected static ?string $navigationGroup = 'Presensi';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Absensi';
    protected static ?string $pluralModelLabel = 'Data Absensi';
    protected static ?string $navigationLabel = 'Absensi';
    protected static ?string $slug = 'absensi';

    public static function getEloquentQuery(): Builder
    {
        $currentUser = Filament::auth()->user();

        if ($currentUser && $currentUser->hasRole('super_admin')) {
            return parent::getEloquentQuery()->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        $employeeId = null;
        if ($currentUser && $currentUser->employee) {
            $employeeId = $currentUser->employee->id;
        }

        if ($employeeId) {
            return parent::getEloquentQuery()->where('employee_id', $employeeId)->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        return parent::getEloquentQuery()->whereRaw('1 = 0')->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function form(Form $form): Form
    {
        $currentUser = Filament::auth()->user();
        $isAdmin = $currentUser && $currentUser->hasRole('super_admin');

        return $form
            ->schema([
                Select::make('employee_id')
                    ->relationship('employee', 'name')
                    ->required()
                    ->default(function () use ($isAdmin, $currentUser) {
                        if ($isAdmin) {
                            return null;
                        }
                        return $currentUser?->employee?->id;
                    })
                    ->disabled(fn() => !$isAdmin)
                    ->hidden(fn() => !$isAdmin && !request()->routeIs('filament.admin.resources.attendances.create'))
                    ->searchable()
                    ->preload(),

                DatePicker::make('date')
                    ->required()
                    ->default(now()),

                DateTimePicker::make('check_in')
                    ->label('Check In Time')
                    ->nullable()
                    ->helperText('Late minutes will be calculated automatically based on 08:00 base time'),

                DateTimePicker::make('check_out')
                    ->label('Check Out Time')
                    ->nullable()
                    ->after('check_in'),

                TextInput::make('early_minutes')
                    ->label('Early Minutes')
                    ->numeric()
                    ->default(0)
                    ->readonly() // Make it readonly so users can see the calculated value
                    ->helperText('Automatically calculated based on check-in time'),

                TextInput::make('late_minutes')
                    ->label('Late Minutes')
                    ->numeric()
                    ->default(0)
                    ->readonly() // Make it readonly so users can see the calculated value
                    ->helperText('Automatically calculated when check-in > 08:00'),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currentUser = Filament::auth()->user();
        $isAdmin = $currentUser && $currentUser->hasRole('super_admin');

        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('check_in')
                    ->label('Check In')
                    ->dateTime('H:i')
                    ->sortable(),
                TextColumn::make('check_out')
                    ->label('Check Out')
                    ->dateTime('H:i')
                    ->sortable(),
                TextColumn::make('early_minutes')
                    ->label('Early (min)')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray'),
                TextColumn::make('late_minutes')
                    ->label('Late (min)')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employee_id')
                    ->relationship('employee', 'name')
                    ->label('Filter by Employee')
                    ->placeholder('All Employees')
                    ->searchable()
                    ->visible(fn() => $isAdmin),
                Tables\Filters\Filter::make('date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn(Builder $query, $date) => $query->whereDate('date', '>=', $date))
                            ->when($data['until'], fn(Builder $query, $date) => $query->whereDate('date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}