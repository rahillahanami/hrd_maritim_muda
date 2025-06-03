<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DivisionResource\Pages;
use App\Models\Division;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class DivisionResource extends Resource
{
    protected static ?string $model = Division::class;

    // protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    // protected static ?string $navigationLabel = 'Divisi';

    protected static ?string $slug = 'divisi';

    // public static ?string $label = 'Divisi';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2'; // Contoh ikon
    protected static ?string $navigationGroup = 'Organisasi'; // <<< Kelompokkan, contoh: Organisasi
    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Divisi'; // Label untuk satu record
    protected static ?string $pluralModelLabel = 'Daftar Divisi'; // Label untuk banyak record
    protected static ?string $navigationLabel = 'Divisi'; // <<< Label di navigasi (sudah benar)



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                TextInput::make('description')
                    ->label('Deskripsi')
                    ->maxLength(255)
                    ->required(),
                Select::make('head_id')
                    ->relationship('head', 'name')
                    ->label('Kepala Divisi')
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Divisi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('head.name')
                    ->label('Kepala Divisi')
                    ->searchable(),
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
            'index' => Pages\ListDivisions::route('/'),
            'create' => Pages\CreateDivision::route('/create'),
            'edit' => Pages\EditDivision::route('/{record}/edit'),
        ];
    }
}
