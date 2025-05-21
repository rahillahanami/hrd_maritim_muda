<?php

namespace App\Filament\Resources;

use App\Enums\Gender;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;



class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Pegawai';

    protected static ?string $slug = 'pegawai';

    public static ?string $label = 'pegawai';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama Pegawai')
                    ->required(),
                Select::make('gender')
                    ->label('Jenis kelamin')
                    ->options([
                        Gender::Male->value => 'Pria',
                        Gender::Female->value => 'Wanita',
                    ])
                    ->required(),
                DatePicker::label('birth_date')
                    ->label('Tanggal Lahir')
                    ->required(),
                TextInput::make('phone_number')
                    ->label('Nomor HP')
                    ->placeholder('08123456789')
                    ->helperText('Masukkan nomor HP pegawai')
                    ->required(),
                TextInput::make('address')
                    ->label('Alamat Lengkap')
                    ->placeholder('Jl. Sukajadi No. 123, Jakarta')
                    ->helperText('Masukkan alamat lengkap pegawai')
                    ->maxLength(255)
                    ->required(),
                Select::make('division_id')
                    ->relationship('divisi', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('gender')
                    ->label('Jenis Kelamin')
                    ->sortable(),
                TextColumn::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->date()
                    ->sortable(),
                TextColumn::make('phone_number')
                    ->label('Nomor HP'),
                TextColumn::make('division.name')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
