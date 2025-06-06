<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
use App\Models\Division;
use App\Models\Document;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;




class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate'; // Contoh ikon
    protected static ?string $navigationGroup = 'Manajemen Kinerja'; // <<< Kelompokkan
    protected static ?int $navigationSort = 2; // Urutan kedua di Performance Management

    protected static ?string $modelLabel = 'Dokumen';
    protected static ?string $pluralModelLabel = 'Daftar Dokumen';
    protected static ?string $navigationLabel = 'Dokumen'; // <<< UBAH INI
    protected static ?string $slug = 'dokumen'; // Slug untuk URL


    protected static function isCurrentUserAdmin(): bool
    {
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin'); // Sesuaikan peran admin
    }

    protected static function isCurrentUserHeadOfDivision(): bool
    {
        $user = Filament::auth()->user();
        if (!$user || !$user->employee) {
            return false;
        }
        return Division::where('head_id', $user->employee->id)->exists();
    }

    protected static function getCurrentUserDivisionId(): ?int
    {
        $user = Filament::auth()->user();
        return $user->employee?->division?->id;
    }

    public static function getEloquentQuery(): Builder
    {
        $currentUser = Filament::auth()->user();

        // Jika user tidak login, langsung kembalikan query kosong
        if (!$currentUser) { // Hanya cek keberadaan user, bukan employee di sini
            return parent::getEloquentQuery()->whereRaw('1 = 0')->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        // Jika user adalah Admin, langsung kembalikan semua data
        if (static::isCurrentUserAdmin()) {
            return parent::getEloquentQuery()->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        // --- Logika untuk user biasa (termasuk Kepala Divisi) ---

        // Jika user bukan admin TAPI TIDAK MEMILIKI DATA EMPLOYEE
        // berarti dia tidak bisa melihat Dokumen yang terkait divisi.
        if (!$currentUser->employee) { // Cek employee hanya untuk non-admin
            return parent::getEloquentQuery()->whereRaw('1 = 0')->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        // Sekarang kita tahu user adalah user biasa dan memiliki data employee
        $userDivisionId = static::getCurrentUserDivisionId();

        if ($userDivisionId) {
            // User hanya bisa melihat Dokumen yang ditujukan untuk divisinya
            // ATAU Dokumen yang bersifat GLOBAL (division_id IS NULL)
            return parent::getEloquentQuery()
                ->where(function (Builder $query) use ($userDivisionId) {
                    $query->where('division_id', $userDivisionId)
                        ->orWhereNull('division_id');
                })
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]);
        }

        // Fallback: user login, bukan admin, punya employee tapi employee tidak terhubung ke divisi
        return parent::getEloquentQuery()->whereRaw('1 = 0')->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function canCreate(): bool
    {
        // Hanya Admin atau Kepala Divisi yang bisa membuat WorkPlan
        return static::isCurrentUserAdmin() || static::isCurrentUserHeadOfDivision();
    }

    public static function form(Form $form): Form
    {

        $currentUser = Filament::auth()->user();
        $isAdmin = static::isCurrentUserAdmin();
        $isHeadOfDivision = static::isCurrentUserHeadOfDivision();

        $currentDocumentDivisionId = $form->getRecord()?->division_id;
        $currentUserDivisionId = static::getCurrentUserDivisionId();

        // Apakah user ini kepala divisi DARI dokumen yang sedang diedit?
        $isEditingOwnDivisionDocument = $currentDocumentDivisionId === $currentUserDivisionId && $isHeadOfDivision;

        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Dokumen')
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionDocument),

                Forms\Components\Select::make('category')
                    ->options([
                        'Forms & Templates' => 'Forms & Templates',
                        'Company Policies' => 'Company Policies',
                        'Training Materials' => 'Training Materials',
                        'Reports' => 'Reports',
                        'Other' => 'Other',
                    ])
                    ->required()
                    ->disabled(fn($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionDocument),

                // Field untuk divisi terkait (division_id)
                Forms\Components\Select::make('division_id')
                    ->disabled(
                        fn($operation) =>
                        $operation === 'edit' && !$isAdmin // Admin bisa edit divisi di Dokumen apapun
                            ||
                            $operation === 'create' && !$isAdmin && $isHeadOfDivision // Jika create, bukan admin, tapi kepala divisi -> otomatis isi divisi dia
                            ||
                            $operation === 'edit' && !$isAdmin && $isEditingOwnDivisionDocument // Jika edit, bukan admin, dan kepala divisi dokumen ini
                    )
                    ->relationship('division', 'name')
                    ->required(fn() => !$isAdmin) // Hanya wajib jika bukan admin (admin bisa pilih null untuk global)
                    ->placeholder('Pilih Divisi Terkait')
                    ->searchable()
                    ->preload()
                    ->default(function () use ($isAdmin, $isHeadOfDivision, $currentUserDivisionId) {
                        return (!$isAdmin && $isHeadOfDivision) ? $currentUserDivisionId : null;
                    })
                    ->options(function () use ($isAdmin) {
                        $options = Division::pluck('name', 'id')->toArray();
                        if ($isAdmin) {
                            // Simpan di awal agar muncul paling atas
                            $options = [null => 'Semua Divisi (Global)'] + $options;
                        }
                        return $options;
                    }),

                Forms\Components\Select::make('status')
                    ->options([
                        'Published' => 'Published',
                        'Draft' => 'Draft',
                    ])
                    ->required()
                    ->default('Draft')
                    ->disabled(fn($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionDocument),

                FileUpload::make('file_path')
                    ->label('Upload File')
                    ->directory('documents')
                    ->disk('public')
                    ->preserveFilenames()
                    ->downloadable()
                    ->multiple(false)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/bmp',
                        'image/svg+xml',
                    ])
                    ->required()
                    ->disabled(fn($operation) => $operation === 'edit' && !$isAdmin && !$isEditingOwnDivisionDocument), // Hanya bisa upload/ganti file jika diizinkan edit

                // // Field type: Otomatis dari ekstensi file
                // Forms\Components\TextInput::make('type')
                //     ->label('Tipe File')
                //     // ->hiddenOn('create') // Sembunyikan saat create (akan diisi otomatis)
                //     ->disabled()         // Selalu disabled (hanya tampil)
                //     ->dehydrated()
                //     ->required(),      // Pastikan disimpan ke database

                // user_id: Siapa yang mengunggah (user_id)
                Forms\Components\Select::make('user_id') // Ganti dari uploaded_by
                    ->disabled() // Selalu disabled
                    ->relationship('user', 'name')
                    ->label('Diunggah Oleh')
                    ->required()
                    ->default(fn() => Filament::auth()->user()?->id), // Otomatis user yang login
                // ->hiddenOn('create'), // Sembunyikan saat create

                // uploaded_at: Otomatis tanggal/waktu upload
                Forms\Components\DateTimePicker::make('uploaded_at') // Ganti dari DatePicker
                    ->label('Tanggal Unggah')
                    ->default(now())
                    ->disabled() // Selalu disabled
                    ->dehydrated()
                    ->required(), // Pastikan disimpan
            ]);
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = Filament::auth()->user();
        $userId = $currentUser ? $currentUser->id : null;

        Log::info('DEBUG (Document Create) - User ID from login: ' . ($userId ?? 'NULL'));
        Log::info('DEBUG (Document Create) - Initial data: ' . json_encode($data));

        $data['user_id'] = $userId;
        $data['uploaded_at'] = Carbon::now();

        // Tangani opsi "Semua Divisi (Global)"
        $isAdmin = static::isCurrentUserAdmin();
        if ($isAdmin && isset($data['division_id']) && $data['division_id'] === 'null') {
            $data['division_id'] = null;
        }

        // // *** PERBAIKAN KRUSIAL UNTUK file_path DAN type ***
        // // Filament FileUpload mengembalikan objek temporer. Kita perlu string path-nya.
        // // Jika file_path adalah instance dari UploadedFile (dari Laravel)
        // if (isset($data['file_path']) && $data['file_path'] instanceof \Illuminate\Http\UploadedFile) {
        //     // Ini adalah objek file yang baru diupload
        //     // Filament akan menyimpannya otomatis, dan path-nya akan jadi string setelah disimpan.
        //     // Di sini kita tidak bisa langsung dapat path final, tapi kita bisa pastikan itu diproses Filament.

        //     // Ambil ekstensi dari objek UploadedFile
        //     $data['type'] = $data['file_path']->extension();

        //     // Kita TIDAK perlu set $data['file_path'] di sini, biarkan Filament yang simpan dan set path-nya.
        //     // Namun, untuk memastikan tipe data yang benar, kita bisa log.
        //     Log::info('DEBUG (Document Create) - UploadedFile detected. Extension: ' . $data['type']);

        // } else if (isset($data['file_path']) && is_string($data['file_path'])) {
        //     // Jika file_path sudah berupa string (misal saat edit file tidak diubah), ambil ekstensi dari string path
        //     $data['type'] = pathinfo($data['file_path'], PATHINFO_EXTENSION);
        //     Log::info('DEBUG (Document Create) - FilePath is string. Extension: ' . $data['type']);
        // } else {
        //     // Fallback jika tidak ada file_path atau tipe tidak terduga
        //     $data['type'] = null;
        //     $data['file_path'] = null; // Pastikan file_path null jika tidak ada file
        //     Log::info('DEBUG (Document Create) - FilePath is neither UploadedFile nor string. Set type/path to null.');
        // }
        // *** AKHIR PERBAIKAN KRUSIAL ***

        Log::info('DEBUG (Document Create) - Final data before save: ' . json_encode($data));

        return $data;
    }

    public static function table(Table $table): Table
    {
        $currentUser = Filament::auth()->user();
        $isAdmin = static::isCurrentUserAdmin();
        $isHeadOfDivision = static::isCurrentUserHeadOfDivision();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Dokumen')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('division.name')
                    ->label('Divisi')
                    ->getStateUsing(function ($record) {
                        return $record->division?->name ?? 'Semua Divisi';
                    }),
                // Tables\Columns\TextColumn::make('file_path') // Tipe file (otomatis)
                //     ->label('Tipe File')
                //     ->searchable()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('user.name') // Diunggah Oleh
                    ->label('Diunggah Oleh')
                    ->searchable()
                    ->sortable(),
                // ->toggleable(isToggledHiddenByDefault: true)
                // ->visible(fn () => $isAdmin),
                Tables\Columns\TextColumn::make('uploaded_at') // Tanggal Unggah
                    ->label('Tanggal Unggah')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'Published',
                        'warning' => 'Draft',
                    ])
                    ->sortable(),
                Tables\Columns\ViewColumn::make('download') // Kolom download (tetap sama)
                    ->label('Download')
                    ->view('tables.columns.download-link'),
            ])
            ->filters([
                // Filter berdasarkan Divisi
                Tables\Filters\SelectFilter::make('division_id')
                    ->relationship('division', 'name')
                    ->label('Filter Divisi')
                    ->placeholder('Semua Divisi')
                    ->searchable()
                    ->options(function () use ($isAdmin) {
                        $options = Division::pluck('name', 'id')->toArray();
                        if ($isAdmin) {
                            $options[null] = 'Semua Divisi (Global)';
                        }
                        return $options;
                    })
                    ->visible(fn() => $isAdmin), // Hanya Admin yang bisa melihat filter ini

                // Filter berdasarkan Kategori
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'Forms & Templates' => 'Forms & Templates',
                        'Company Policies' => 'Company Policies',
                        'Training Materials' => 'Training Materials',
                        'Reports' => 'Reports',
                        'Other' => 'Other',
                    ])
                    ->label('Filter Kategori')
                    ->placeholder('Semua Kategori'),

                // Filter berdasarkan Status
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Published' => 'Published',
                        'Draft' => 'Draft',
                    ])
                    ->label('Filter Status')
                    ->placeholder('Semua Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn(Document $record): bool =>
                        $isAdmin || // Admin bisa edit semua
                            (static::isCurrentUserHeadOfDivision() && $record->division_id === static::getCurrentUserDivisionId()) // Kepala Divisi bisa edit WorkPlan divisinya
                    ),
                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn(Document $record): bool =>
                        $isAdmin || // Admin bisa delete semua
                            (static::isCurrentUserHeadOfDivision() && $record->division_id === static::getCurrentUserDivisionId()) // Kepala Divisi bisa delete WorkPlan divisinya
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => $isAdmin || static::isCurrentUserHeadOfDivision()),
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
