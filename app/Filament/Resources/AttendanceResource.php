<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Presensi';
    protected static ?string $navigationLabel = 'Absensi';
    protected static ?string $label = 'Absensi';
    protected static ?string $slug = 'Absensi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('employee_id')
                    ->relationship('employee', 'name')
                    ->required(),
                DatePicker::make('date')->required(),
                DateTimePicker::make('check_in')
                    ->label('Check In')
                    ->seconds(false)
                    ->nullable(),
                DateTimePicker::make('check_out')
                    ->label('Check Out')
                    ->seconds(false)
                    ->nullable(),
                TextInput::make('early_minutes')
                    ->label('Menit Pulang Awal')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('late_minutes')
                    ->label('Menit Terlambat')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')->label('Pegawai'),
                TextColumn::make('date')->date(),
                TextColumn::make('check_in')->time(),
                TextColumn::make('check_out')->time(),
                BadgeColumn::make('status')->colors([
                    'success' => 'present',
                    'danger' => 'absent',
                    'warning' => 'late',
                ]),
            ])
            ->filters([
                //
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
