<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar'; // Contoh ikon
    protected static ?string $navigationGroup = 'Manajemen Kinerja'; // <<< Kelompokkan
    protected static ?int $navigationSort = 3; // Urutan ketiga di Performance Management

    protected static ?string $modelLabel = 'Acara/Kegiatan';
    protected static ?string $pluralModelLabel = 'Daftar Acara/Kegiatan';
    protected static ?string $navigationLabel = 'Acara/Kegiatan'; // <<< UBAH INI
    protected static ?string $slug = 'acara-kegiatan'; // Slug untuk URL

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('description')
                    ->label('Description')
                    ->required()
                    ->maxLength(500),
                    DateTimePicker::make('starts_at'),
                    DateTimePicker::make('ends_at'),
                // Field baru: Divisi
                Select::make('division_id')
                    ->relationship('division', 'name')
                    ->required() // Atau ->nullable() jika event tidak wajib punya divisi
                    ->placeholder('Pilih Divisi'),

                // Field baru: Created By (akan diisi otomatis, jadi disabled dan hidden di form create)
                TextInput::make('created_by') 
                    ->default(fn() => Filament::auth()->user()?->name) // Mengisi ID user yang login
                    ->disabled()         // Disable saat mengedit
                    ->dehydrated(),      // Pastikan disimpan ke database


                // // Field dummy untuk menampilkan nama user saat edit (optional, tapi bagus untuk UX)
                // TextInput::make('user.name') // Menampilkan nama dari relasi user
                //     ->label('Created By')
                //     ->disabled()
                //     ->hiddenOn('create')
                //     ->dehydrated(false), // Penting: Jangan simpan field ini ke DB
                    
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('description'),
                TextColumn::make('starts_at'),
                TextColumn::make('ends_at'),   
                TextColumn::make('division.name') // Menampilkan nama divisi
                ->label('Divisi')
                ->searchable()
                ->sortable(),
                TextColumn::make('created_by') // Menampilkan nama user yang membuat
            ])
            ->filters([
                SelectFilter::make('division_id')
                ->relationship('division', 'name')
                ->label('Filter by Divisi')
                ->placeholder('Semua Divisi'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'view' => Pages\ViewEvent::route('/{record}'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
