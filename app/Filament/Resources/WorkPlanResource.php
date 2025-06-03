<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkPlanResource\Pages;
use App\Filament\Resources\WorkPlanResource\RelationManagers;
use App\Models\WorkPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;
use App\Models\User;
use App\Models\Employee;
use App\Models\Division;
use Illuminate\Support\Facades\Log;

class WorkPlanResource extends Resource
{
    protected static ?string $model = WorkPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list'; // Sudah benar
    protected static ?string $navigationGroup = 'Manajemen Kinerja'; // Sudah benar
    protected static ?int $navigationSort = 1; // Urutan pertama di Performance Management

    protected static ?string $modelLabel = 'Rencana Kerja';
    protected static ?string $pluralModelLabel = 'Daftar Rencana Kerja';
    protected static ?string $navigationLabel = 'Rencana Kerja'; // <<< UBAH INI
    protected static ?string $slug = 'rencana-kerja'; // Slug untuk URL

    // *** Tambahkan method helper ini di dalam kelas WorkPlanResource ***
    protected static function isCurrentUserAdmin(): bool
{
    $user = Filament::auth()->user();
    $isAdmin = $user && $user->hasRole('super_admin');
    Log::info('DEBUG - isCurrentUserAdmin: ' . ($isAdmin ? 'TRUE' : 'FALSE') . ' for user ID: ' . ($user->id ?? 'NULL'));
    return $isAdmin;
}

protected static function isCurrentUserHeadOfDivision(): bool
{
    $user = Filament::auth()->user();
    $isHead = false;
    if ($user && $user->employee) {
        $isHead = Division::where('head_id', $user->employee->id)->exists();
    }
    Log::info('DEBUG - isCurrentUserHeadOfDivision: ' . ($isHead ? 'TRUE' : 'FALSE') . ' for user ID: ' . ($user->id ?? 'NULL') . ' (Employee ID: ' . ($user->employee->id ?? 'NULL') . ')');
    return $isHead;
}

public static function canCreate(): bool
{
    $canCreate = static::isCurrentUserAdmin() || static::isCurrentUserHeadOfDivision();
    $user = Filament::auth()->user(); // Ambil user lagi untuk log final
    Log::info('DEBUG - canCreate final decision: ' . ($canCreate ? 'TRUE' : 'FALSE') . ' for user ID: ' . ($user->id ?? 'NULL'));
    return $canCreate;
}

    protected static function getCurrentUserDivisionId(): ?int
    {
        $user = Filament::auth()->user();
        // Sekarang kita bisa langsung ambil dari relasi employee->division->id
        return $user->employee?->division?->id;
    }
    /**
     * Mendefinisikan skema formulir untuk membuat atau mengedit rencana kerja.
     */

     public static function getEloquentQuery(): Builder
    {
    $currentUser = Filament::auth()->user();

     if (static::isCurrentUserAdmin()) {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    // Jika user tidak login atau tidak punya data employee, jangan tampilkan apa-apa
    if (!$currentUser || !$currentUser->employee) {
        return parent::getEloquentQuery()->whereRaw('1 = 0')->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    // Jika user adalah Admin
   

    // Jika user adalah Kepala Divisi atau Anggota Divisi biasa
    $userDivisionId = static::getCurrentUserDivisionId();

    if ($userDivisionId) {
        // User hanya bisa melihat WorkPlan yang ditujukan untuk divisinya
        return parent::getEloquentQuery()
            ->where('division_id', $userDivisionId)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    // Jika user login, bukan admin, tapi tidak terhubung ke divisi manapun (misal, data employee belum lengkap)
    return parent::getEloquentQuery()->whereRaw('1 = 0')->withoutGlobalScopes([
        SoftDeletingScope::class,
    ]);
    }
    
    public static function form(Form $form): Form
{
    $currentUser = Filament::auth()->user();
    $isAdmin = static::isCurrentUserAdmin();
    $isHeadOfDivision = static::isCurrentUserHeadOfDivision();

    // Untuk mengontrol apakah field bisa diedit saat mode EDIT
    // Kita perlu ID WorkPlan yang sedang diedit untuk cek divisinya.
    // $record bisa diakses dari parameter closure make().
    $currentWorkPlanDivisionId = $form->getRecord()?->division_id;
    $currentUserDivisionId = static::getCurrentUserDivisionId();

    // Apakah user ini kepala divisi DARI WorkPlan yang sedang diedit?
    $isEditingOwnDivisionWorkPlan = $currentWorkPlanDivisionId === $currentUserDivisionId && $isHeadOfDivision;

    // Tentukan apakah field division_id harus disabled
    // Ini akan disabled jika bukan admin, dan saat create, atau saat edit workplan divisinya sendiri.
    $shouldDisableDivisionField = !$isAdmin && (request()->routeIs('filament.admin.resources.work-plans.create') || $isEditingOwnDivisionWorkPlan);


    return $form
        ->schema([
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->placeholder('Judul Rencana Kerja')
                ->disabled(fn ($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionWorkPlan), // Hanya admin/kepala divisi dari workplan yang bisa edit
            // ... (field lainnya seperti description, target_metric, target_value, start_date, due_date) ...
            // Terapkan logika disabled serupa untuk field yang hanya bisa diedit oleh admin/kepala divisi

            Forms\Components\Textarea::make('description')
                ->nullable()
                ->rows(4)
                ->placeholder('Detail Rencana Kerja')
                ->disabled(fn ($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionWorkPlan),

            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('target_metric')
                        ->nullable()
                        ->maxLength(255)
                        ->placeholder('Contoh: Jumlah Penjualan, Proyek Terselesaikan')
                        ->disabled(fn ($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionWorkPlan),

                    Forms\Components\TextInput::make('target_value')
                        ->numeric()
                        ->nullable()
                        ->step(0.01)
                        ->placeholder('Contoh: 15 (untuk 15%), 5 (untuk 5 Proyek)')
                        ->disabled(fn ($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionWorkPlan),
                ]),

            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\DatePicker::make('start_date')
                        ->required()
                        ->disabled(fn ($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionWorkPlan),

                    Forms\Components\DatePicker::make('due_date')
                        ->required()
                        ->afterOrEqual('start_date')
                        ->disabled(fn ($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionWorkPlan),
                ]),

            Forms\Components\Select::make('status')
                ->options([
                    'Draft' => 'Draft',
                    'On Progress' => 'On Progress',
                    'Completed' => 'Completed',
                    'Pending Review' => 'Pending Review',
                    'Cancelled' => 'Cancelled',
                ])
                ->required()
                ->default('Draft')
                ->disabled(fn ($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionWorkPlan), // Status juga hanya bisa diubah oleh admin/kepala divisi yang berhak

            Forms\Components\TextInput::make('progress_percentage')
                ->label('Progress (%)')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->default(0)
                ->suffix('%')
                ->required()
                ->disabled(fn ($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionWorkPlan), // Progress hanya bisa diubah oleh admin/kepala divisi yang berhak

            Forms\Components\Textarea::make('notes')
                ->nullable()
                ->rows(3)
                ->placeholder('Catatan atau pembaruan progres')
                ->disabled(fn ($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionWorkPlan),

            // user_id: Ini adalah siapa yang membuat WorkPlan, diisi otomatis dan tidak bisa diedit
            Forms\Components\Select::make('user_id')
                ->disabled()
                ->relationship('user', 'name')
                ->required()
                ->default(fn () => Filament::auth()->user()?->id),// Otomatis mengisi ID user yang login
                // ->readOnly(), // Selalu disabled karena hanya untuk pencatat
                // ->hiddenOn('create'), // Sembunyikan saat membuat (karena sudah otomatis)

            // Field untuk divisi terkait (division_id)
            Forms\Components\Select::make('division_id')
                ->relationship('division', 'name')
                ->required() // WorkPlan wajib ditujukan ke divisi
                ->placeholder('Pilih Divisi Terkait')
                ->searchable()
                ->preload()
                // ->disabled(fn ($operation) => $operation === 'edit' && !$isAdmin) // Saat edit, hanya admin yang bisa ubah divisi tujuan
                // // default untuk create: jika kepala divisi, defaultnya divisi dia.
                // // Jika bukan admin dan dia kepala divisi, default ke divisinya
                // ->default(fn () => !$isAdmin && $isHeadOfDivision ? static::getCurrentUserDivisionId() : null),
                ->disabled(fn ($operation) =>
                    $operation === 'edit' && !$isAdmin // Admin bisa edit divisi di WorkPlan apapun
                    || // ATAU
                    $operation === 'create' && !$isAdmin && $isHeadOfDivision // Jika create, bukan admin, tapi kepala divisi -> otomatis isi divisi dia
                    || // ATAU
                    $operation === 'edit' && !$isAdmin && $isEditingOwnDivisionWorkPlan // Jika edit, bukan admin, dan kepala divisi workplan ini
                )
                ->default(function () use ($isAdmin, $isHeadOfDivision, $currentUserDivisionId) {
                    // Jika bukan admin dan dia adalah Kepala Divisi, otomatis isi ID divisinya
                    return (!$isAdmin && $isHeadOfDivision) ? $currentUserDivisionId : null;
                }),


            // Field untuk user yang menyetujui (approved_by_user_id)
            Forms\Components\Select::make('approved_by_user_id')
                ->relationship('approvedBy', 'name')
                ->label('Disetujui Oleh')
                ->nullable()
                ->placeholder('Pilih Penyetuju')
                ->searchable()
                ->preload()
                ->disabled(fn ($operation) => $operation === 'edit' && !$isAdmin && !$isHeadOfDivision), // Hanya admin/kepala divisi yang bisa ubah penyetuju
        ]);
}

    /**
     * Mendefinisikan skema tabel untuk menampilkan daftar rencana kerja.
     */
  public static function table(Table $table): Table
{
    $currentUser = Filament::auth()->user();
    $isAdmin = static::isCurrentUserAdmin();
    $isHeadOfDivision = static::isCurrentUserHeadOfDivision();

    return $table
        ->columns([
            Tables\Columns\TextColumn::make('title')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('division.name')
                ->label('Divisi')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('start_date') // <<< Tambahkan
                ->label('Mulai')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('due_date') // <<< Tambahkan
                ->label('Tenggat')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('progress_percentage') // <<< Tambahkan
                ->label('Progres')
                ->suffix('%')
                ->sortable(),
            Tables\Columns\BadgeColumn::make('status') // <<< Tambahkan (sudah ada, pastikan di posisi yang baik)
                ->colors([
                    'gray' => 'Draft',
                    'info' => 'On Progress',
                    'success' => 'Completed',
                    'warning' => 'Pending Review',
                    'danger' => 'Cancelled',
                ])
                ->sortable(),
            Tables\Columns\TextColumn::make('user.name') // Created By
                ->label('Created By')
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->visible(fn () => $isAdmin),
            Tables\Columns\TextColumn::make('approvedBy.name') // Disetujui Oleh
                ->label('Disetujui Oleh')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->visible(fn () => $isAdmin),
            Tables\Columns\TextColumn::make('created_at') // Tanggal Dibuat
                ->label('Dibuat Pada')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->visible(fn () => $isAdmin), // Hanya admin yang melihat
        ])

        ->filters([
            // Filter berdasarkan Divisi (terlihat untuk semua, tapi hanya menampilkan opsi yang relevan jika bukan admin)
            Tables\Filters\SelectFilter::make('division_id')
                    ->relationship('division', 'name')
                    ->label('Filter Divisi')
                    ->placeholder('Semua Divisi')
                    ->searchable()
                    ->options(function () use ($isAdmin, $currentUser) {
                        if ($isAdmin) {
                            return Division::pluck('name', 'id')->toArray(); // Admin lihat semua divisi
                        }
                        // Ini akan disembunyikan untuk non-admin, tapi jika ingin tetap tampilkan opsi divisi sendiri jika tidak disembunyikan:
                        $userDivisionId = static::getCurrentUserDivisionId();
                        if ($userDivisionId) {
                            return Division::where('id', $userDivisionId)->pluck('name', 'id')->toArray();
                        }
                        return [];
                    })
                    ->visible(fn () => $isAdmin), // <<< TAMBAHKAN ATAU UBAH BARIS INI

            // // Filter berdasarkan Karyawan/User (hanya untuk Admin)
            // Tables\Filters\SelectFilter::make('user_id')
            //     ->relationship('user', 'name')
            //     ->label('Filter Karyawan (Created By)')
            //     ->placeholder('Semua Karyawan')
            //     ->searchable()
            //     ->visible(fn () => $isAdmin), // Hanya Admin yang bisa melihat filter ini

            // Filter berdasarkan Status (terlihat untuk semua)
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'Draft' => 'Draft',
                    'On Progress' => 'On Progress',
                    'Completed' => 'Completed',
                    'Pending Review' => 'Pending Review',
                    'Cancelled' => 'Cancelled',
                ])
                ->label('Filter Status')
                ->placeholder('Semua Status'),

            // Filter berdasarkan Tanggal Tenggat Waktu (terlihat untuk semua)
            Tables\Filters\Filter::make('due_date')
                ->form([
                    Forms\Components\DatePicker::make('from'),
                    Forms\Components\DatePicker::make('until'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('due_date', '>=', $date))
                        ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('due_date', '<=', $date));
                }),
        ])
        ->actions([
            Tables\Actions\EditAction::make()
                ->visible(fn (WorkPlan $record): bool => $isAdmin || (static::isCurrentUserHeadOfDivision() && $record->division_id === static::getCurrentUserDivisionId())), // Hanya Admin atau Kepala Divisi terkait yang bisa edit
            Tables\Actions\DeleteAction::make()
                ->visible(fn (WorkPlan $record): bool => $isAdmin || (static::isCurrentUserHeadOfDivision() && $record->division_id === static::getCurrentUserDivisionId())), // Hanya Admin atau Kepala Divisi terkait yang bisa delete
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => $isAdmin || static::isCurrentUserHeadOfDivision()), // Hanya Admin atau Kepala Divisi yang bisa bulk delete
            ]),
        ]);
}

    /**
     * Mendefinisikan relasi yang akan dimuat dengan resource ini.
     */
    public static function getRelations(): array
    {
        return [
            // Anda bisa menambahkan Relation Managers di sini jika diperlukan,
            // misalnya untuk Notes terpisah atau Task yang terkait.
        ];
    }

    /**
     * Mendefinisikan halaman-halaman yang terkait dengan resource ini.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkPlans::route('/'),
            'create' => Pages\CreateWorkPlan::route('/create'),
            'edit' => Pages\EditWorkPlan::route('/{record}/edit'),
        ];
    }

    /**
     * Mengisi user_id secara otomatis sebelum data dibuat.
     * Ini sebenarnya sudah ditangani oleh default() di form, tapi ini juga bisa menjadi fallback.
     */
    // protected static function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $data['user_id'] = Filament::auth()->user()?->id;
    //     return $data;
    // }

    
}