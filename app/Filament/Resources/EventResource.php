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
use App\Models\User; // Pastikan ini ada
use App\Models\Employee; // Pastikan ini ada
use App\Models\Division; // Pastikan ini ada
use Illuminate\Support\Carbon; // Untuk default now()

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

    public static function isCurrentUserAdmin(): bool
    {
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin');
    }

    public static function isCurrentUserHeadOfDivision(): bool
    {
        $user = Filament::auth()->user();
        if (!$user || !$user->employee) {
            return false;
        }
        return Division::where('head_id', $user->employee->id)->exists();
    }

    public static function getCurrentUserDivisionId(): ?int
    {
        $user = Filament::auth()->user();
        return $user->employee?->division?->id;
    }

    // --- Query Scoping (sama seperti Dokumen/WorkPlan) ---
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

        if (!$currentUser->employee) {
            return parent::getEloquentQuery()->whereRaw('1 = 0')->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        $userDivisionId = static::getCurrentUserDivisionId();

        if ($userDivisionId) {
            // User bisa melihat event yang ditujukan untuk divisinya
            // ATAU event yang bersifat GLOBAL (division_id IS NULL)
            return parent::getEloquentQuery()
                ->where(function (Builder $query) use ($userDivisionId) {
                    $query->where('division_id', $userDivisionId)
                        ->orWhereNull('division_id');
                })
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]);
        }

        return parent::getEloquentQuery()->whereRaw('1 = 0')->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    // --- canCreate() ---
    public static function canCreate(): bool
    {
        // Hanya Admin atau Kepala Divisi yang bisa membuat Event
        return static::isCurrentUserAdmin() || static::isCurrentUserHeadOfDivision();
    }

    public static function form(Form $form): Form
    {

        $currentUser = Filament::auth()->user();
        $isAdmin = static::isCurrentUserAdmin();
        $isHeadOfDivision = static::isCurrentUserHeadOfDivision();

        // Untuk mengontrol apakah field bisa diedit saat mode EDIT
        $currentEventDivisionId = $form->getRecord()?->division_id;
        $currentUserDivisionId = static::getCurrentUserDivisionId();

        // Apakah user ini kepala divisi DARI event yang sedang diedit?
        $isEditingOwnDivisionEvent = $currentEventDivisionId === $currentUserDivisionId && $isHeadOfDivision;

        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama Acara') // Ubah label
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionEvent),
                TextInput::make('description')
                    ->label('Deskripsi') // Ubah label
                    ->required()
                    ->maxLength(500) // Jika ini Textarea, ubah ke Forms\Components\Textarea::make()
                    ->disabled(fn($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionEvent),
                DateTimePicker::make('starts_at')
                    ->label('Dimulai Pada') // Ubah label
                    ->required()
                    ->disabled(fn($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionEvent),
                DateTimePicker::make('ends_at')
                    ->label('Berakhir Pada') // Ubah label
                    ->required()
                    ->afterOrEqual('starts_at')
                    ->disabled(fn($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionEvent),

                // Field untuk divisi (division_id)
                Select::make('division_id')
                    ->label('Divisi') // Ubah label
                    ->relationship('division', 'name')
                    ->required(fn() => !$isAdmin) // Hanya wajib jika bukan admin (admin bisa pilih null untuk global)
                    ->placeholder('Pilih Divisi Terkait')
                    ->searchable()
                    ->preload()
                    ->disabled(
                        fn($operation) =>
                        $operation === 'edit' && !$isAdmin // Admin bisa edit divisi di Event apapun
                            ||
                            $operation === 'create' && !$isAdmin && $isHeadOfDivision // Jika create, bukan admin, tapi kepala divisi -> otomatis isi divisi dia
                            ||
                            $operation === 'edit' && !$isAdmin && $isEditingOwnDivisionEvent // Jika edit, bukan admin, dan kepala divisi event ini
                    )
                    ->default(function () use ($isAdmin, $isHeadOfDivision, $currentUserDivisionId) {
                        return (!$isAdmin && $isHeadOfDivision) ? $currentUserDivisionId : null;
                    })
                    ->options(function () use ($isAdmin) {
                        $options = \App\Models\Division::pluck('name', 'id')->toArray(); // Panggil model Division dengan namespace lengkap
                        if ($isAdmin) {
                            $options = [null => 'Semua Divisi (Global)'] + $options;
                        }
                        return $options;
                    }),

                // created_by: Nama user yang membuat (sebagai string)
                TextInput::make('created_by')
                    ->label('Dibuat Oleh') // Ubah label
                    ->required()
                    ->default(fn() => Filament::auth()->user()?->name) // Otomatis nama user yang login
                    ->disabled() // Selalu disabled
                    ->hiddenOn('create'), // Sembunyikan saat membuat (karena otomatis)
            ]);
    }

    public static function table(Table $table): Table
    {
        $currentUser = Filament::auth()->user();
        $isAdmin = static::isCurrentUserAdmin();
        $isHeadOfDivision = static::isCurrentUserHeadOfDivision();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Acara') // Ubah label
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Deskripsi') // Ubah label
                    ->words(10) // Tampilkan beberapa kata
                    ->tooltip(fn(?string $state): ?string => $state)
                    ->toggleable(),
                TextColumn::make('starts_at')
                    ->label('Dimulai') // Ubah label
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Berakhir') // Ubah label
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('division.name')
                    ->label('Divisi')
                    ->getStateUsing(function ($record) {
                        return $record->division?->name ?? 'Semua Divisi';
                    }),
                TextColumn::make('created_by')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn() => $isAdmin), // Hanya Admin yang melihat ini
            ])
            ->filters([
                SelectFilter::make('division_id')
                    ->relationship('division', 'name')
                    ->label('Filter Divisi')
                    ->placeholder('Semua Divisi')
                    ->searchable()
                    ->options(function () use ($isAdmin) {
                        $options = \App\Models\Division::pluck('name', 'id')->toArray(); // Panggil model Division dengan namespace lengkap
                        if ($isAdmin) {
                            $options = [null => 'Semua Divisi (Global)'] + $options;
                        }
                        return $options;
                    })
                    ->visible(fn() => $isAdmin), // Hanya Admin yang bisa melihat filter ini
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn(Event $record): bool => // <<< Pastikan tipe hint Event
                        $isAdmin || // Admin bisa edit semua
                            // Hitung ulang logika isEditingOwnDivisionEvent di sini
                            (static::isCurrentUserHeadOfDivision() && $record->division_id === static::getCurrentUserDivisionId())
                    ),
                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn(Event $record): bool => // <<< Pastikan tipe hint Event
                        $isAdmin || // Admin bisa delete semua
                            // Hitung ulang logika isEditingOwnDivisionEvent di sini
                            (static::isCurrentUserHeadOfDivision() && $record->division_id === static::getCurrentUserDivisionId())
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => $isAdmin || $isHeadOfDivision), // Hanya Admin atau Kepala Divisi yang bisa bulk delete
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
