<?php

namespace App\Filament\Resources;

use App\Enums\Gender;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Hidden;
use Illuminate\Database\Eloquent\Model;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Pegawai';
    protected static ?string $slug = 'employee';
    public static ?string $label = 'Pegawai';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Info Akun')
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(table: User::class, ignorable: fn(?Model $record) => $record?->user)
                            ->autocomplete(),
                        TextInput::make('password')
                            ->password()
                            ->required(fn(?Model $record): bool => $record === null)
                            ->dehydrated(fn($state) => filled($state))
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->label('Password')
                            ->autocomplete('new-password')
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->required(fn(?Model $record): bool => $record === null)
                            ->dehydrated(false)
                            ->label('Konfirmasi Password')
                            ->autocomplete('new-password'),
                    ]),
                Section::make('Info Pegawai')
                    ->schema([
                        TextInput::make('nip')
                            ->label('NIP')
                            ->placeholder('1234567890')
                            ->maxLength(20)
                            ->required(),
                        TextInput::make('name')
                            ->label('Nama Pegawai')
                            ->placeholder('Nama Lengkap')
                            ->required(),
                        Select::make('gender')
                            ->label('Jenis kelamin')
                            ->options([
                                Gender::Male->value => 'Pria',
                                Gender::Female->value => 'Wanita',
                            ])
                            ->required(),
                        DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->required(),
                        TextInput::make('phone_number')
                            ->label('Nomor HP')
                            ->placeholder('08123456789')
                            ->required(),
                        Textarea::make('address')
                            ->rows(3)
                            ->label('Alamat Lengkap')
                            ->placeholder('Jl. Sukajadi No. 123, Jakarta')
                            ->maxLength(255)
                            ->required(),
                    ])
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
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
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
