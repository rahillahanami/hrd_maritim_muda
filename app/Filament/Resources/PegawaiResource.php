<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PegawaiResource\Pages;
use App\Filament\Resources\PegawaiResource\RelationManagers;
use App\Models\PegawaiModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;



class PegawaiResource extends Resource
{
    protected static ?string $model = PegawaiModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Pegawai';

    protected static ?string $slug = 'pegawai';

    public static ?string $label = 'pegawai';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama')
                ->required(),
                Select::make('kelamin')
                      ->label('Jenis Kelamin')
                      ->options([
                          'pria' => 'Pria',
                          'wanita' => 'Wanita',
                ])
                ->required(),
                DatePicker::make('tanggal_lahir')
                ->required(),
                TextInput::make('nomor_hp')
                ->required(),
                TextInput::make('alamat')
                ->required(),
                Select::make('divisi_id')
                ->relationship('divisi', 'nama_divisi')
                ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                ->searchable()
                ->sortable(),
                TextColumn::make('kelamin')
                      ->label('Jenis Kelamin')
                      ->sortable(),
                TextColumn::make('tanggal_lahir'),
                TextColumn::make('nomor_hp'),
                TextColumn::make('alamat'),
                TextColumn::make('divisi.nama_divisi')
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
            'index' => Pages\ListPegawais::route('/'),
            'create' => Pages\CreatePegawai::route('/create'),
            'edit' => Pages\EditPegawai::route('/{record}/edit'),
        ];
    }
}
