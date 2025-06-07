<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Filament\Resources\LeaveResource\RelationManagers;
use App\Models\Leave;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament; // Import Facade Filament
use App\Models\User; // Pastikan ini ada
use App\Models\Employee; // Pastikan ini ada
use Illuminate\Support\Carbon; // Pastikan ini ada
use Filament\Notifications\Notification; // Pastikan ini ada jika menggunakan Filament::notify()

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days'; // Contoh ikon
    protected static ?string $navigationGroup = 'Presensi'; // <<< Kelompokkan
    protected static ?int $navigationSort = 2; // Urutan kedua di Presensi

    protected static ?string $modelLabel = 'Pengajuan Cuti'; // Label untuk satu record
    protected static ?string $pluralModelLabel = 'Daftar Pengajuan Cuti'; // Label untuk banyak record
    protected static ?string $navigationLabel = 'Pengajuan Cuti'; // <<< UBAH INI
    protected static ?string $slug = 'cuti'; // Slug untuk URL

    // *** Helper methods (copy dari ResignationResource) ***
    protected static function isCurrentUserAdmin(): bool
    {
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin'); // Sesuaikan 'super_admin' dengan peran admin Anda
    }

    // --- Query Scoping ---

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

        // Sekarang kita filter berdasarkan user_id langsung
        return parent::getEloquentQuery()
            ->where('user_id', $currentUser->id)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    // --- Aturan Create ---
    public static function canCreate(): bool
    {
        // Hanya user biasa (bukan admin) yang bisa membuat pengajuan cuti
        return !static::isCurrentUserAdmin();
    }

    /**
     * Mendefinisikan skema formulir untuk membuat atau mengedit pengajuan cuti.
     */
    public static function form(Form $form): Form
    {

        $currentUser = Filament::auth()->user();
        $isAdmin = static::isCurrentUserAdmin();

        $record = $form->getRecord();
        $recordStatus = $record?->status;

        $isCreating = $record === null;
        // isOwner sekarang merujuk ke user_id
        $isOwner = $record && $record->user_id === $currentUser->id; // <<< UBAH INI

        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        // Field untuk Karyawan yang Mengajukan (user_id)
                        Forms\Components\Select::make('user_id')
                            ->disabled() // Selalu disabled
                            ->relationship('user', 'name')
                            ->required()
                            ->default(fn() => $currentUser->id),

                        // Field untuk Tipe Cuti
                        Forms\Components\Select::make('type')
                            ->options([
                                'annual' => 'Cuti Tahunan',
                                'sick' => 'Cuti Sakit',
                                'permission' => 'Izin',
                                'maternity' => 'Cuti Melahirkan',
                                'other' => 'Cuti Lain-lain',
                            ])
                            ->required()
                            ->placeholder('Pilih Tipe Cuti')
                            ->disabled(
                                fn() =>
                                // Jika mode CREATE, field ini ENABLED (false = tidak disabled)
                                $isCreating ? false : (
                                    // Jika mode EDIT dan user BUKAN ADMIN
                                    !$isAdmin && (
                                        // Dan user adalah pemilik, tapi status BUKAN pending
                                        ($isOwner && $recordStatus !== 'pending') ||
                                        // Atau user BUKAN pemilik
                                        (!$isOwner)
                                    )
                                )
                            ),

                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        // Field untuk Tanggal Mulai Cuti
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->afterOrEqual(now())
                            ->placeholder('Tanggal Mulai Cuti')
                            ->disabled(
                                fn() =>
                                $isCreating ? false : (
                                    !$isAdmin && (
                                        ($isOwner && $recordStatus !== 'pending') ||
                                        (!$isOwner)
                                    )
                                )
                            ),

                        // Field untuk Tanggal Selesai Cuti
                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->afterOrEqual('start_date')
                            ->placeholder('Tanggal Selesai Cuti')
                            ->disabled(
                                fn() =>
                                $isCreating ? false : (
                                    !$isAdmin && (
                                        ($isOwner && $recordStatus !== 'pending') ||
                                        (!$isOwner)
                                    )
                                )
                            ),
                    ]),

                // Field untuk Alasan Cuti
                Forms\Components\Textarea::make('reason')
                    ->required()
                    ->rows(4)
                    ->maxLength(65535)
                    ->placeholder('Jelaskan alasan pengajuan cuti Anda.')
                    ->disabled(
                        fn() =>
                        $isCreating ? false : (
                            !$isAdmin && (
                                ($isOwner && $recordStatus !== 'pending') ||
                                (!$isOwner)
                            )
                        )
                    ),

                // Field untuk Status Pengajuan
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('pending')
                    ->columnSpanFull()
                    ->disabled(fn() => !$isAdmin)
                    ->visible(fn() => $isAdmin), // Hanya Admin yang bisa mengubah status

                // Field untuk User yang Menyetujui/Menolak
                Forms\Components\Select::make('approved_by_user_id')
                    ->disabled(fn() => !$isAdmin) // Hanya Admin yang bisa memilih penyetuju
                    ->relationship('approvedBy', 'name')
                    ->label('Disetujui/Ditolak Oleh')
                    ->nullable()
                    ->placeholder('Pilih Penyetuju/Penolak')
                    ->searchable()
                    ->preload()
                    ->visible(fn() => $isAdmin),
            ]);
    }

    /**
     * Mendefinisikan skema tabel untuk menampilkan daftar pengajuan cuti.
     */
    public static function table(Table $table): Table
    {
        $currentUser = Filament::auth()->user();
        $isAdmin = static::isCurrentUserAdmin();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name') // Menampilkan nama karyawan
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                // ->visible(fn () => $isAdmin), // Hanya Admin yang bisa melihat kolom ini
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe Cuti')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai Cuti')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai Cuti')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan')
                    ->words(10)
                    ->tooltip(fn(?string $state): ?string => $state)
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => 'cancelled',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Disetujui Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn() => $isAdmin),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filter berdasarkan Karyawan (hanya untuk Admin)
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Filter Karyawan')
                    ->placeholder('Semua Karyawan')
                    ->searchable()
                    ->visible(fn() => $isAdmin),

                // Filter berdasarkan Tipe Cuti
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'annual' => 'Cuti Tahunan',
                        'sick' => 'Cuti Sakit',
                        'permission' => 'Izin',
                        'maternity' => 'Cuti Melahirkan',
                        'other' => 'Cuti Lain-lain',
                    ])
                    ->label('Filter Tipe Cuti')
                    ->placeholder('Semua Tipe Cuti'),

                // Filter berdasarkan Status
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ])
                    ->label('Filter Status')
                    ->placeholder('Semua Status'),

                // Filter berdasarkan Tanggal Pengajuan
                Tables\Filters\Filter::make('submission_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn(Builder $query, $date) => $query->whereDate('created_at', '>=', $date)) // Menggunakan created_at sebagai submission_date
                            ->when($data['until'], fn(Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn(Leave $record): bool =>
                        $isAdmin || // Admin bisa edit semua
                            ($record->user_id === $currentUser->id && $record->status === 'pending') // Pemilik bisa edit jika Pending
                    ),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => $isAdmin), // Hanya Admin yang bisa delete

                // *** AKSI KUSTOM BARU: APPROVE ***
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn(Leave $record): bool => $isAdmin && $record->status === 'pending') // Hanya Admin & status Pending
                    ->action(function (Leave $record) use ($currentUser) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by_user_id' => $currentUser->id,
                        ]);
                        Notification::make() // Menggunakan Notification Facade
                            ->title('Pengajuan Cuti telah disetujui.')
                            ->success()
                            ->send();
                    }),

                // *** AKSI KUSTOM BARU: REJECT ***
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(fn(Leave $record): bool => $isAdmin && $record->status === 'pending') // Hanya Admin & status Pending
                    ->action(function (Leave $record) use ($currentUser) {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by_user_id' => $currentUser->id,
                        ]);
                        Notification::make()
                            ->title('Pengajuan Cuti telah ditolak.')
                            ->danger()
                            ->send();
                    }),

                // *** AKSI KUSTOM BARU: CANCEL (untuk pemilik pengajuan) ***
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->color('gray')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->visible(fn(Leave $record): bool => $record->user_id === $currentUser->id && $record->status === 'pending') // Hanya pemilik & status Pending
                    ->action(function (Leave $record) {
                        $record->update([
                            'status' => 'cancelled',
                        ]);
                        Notification::make()
                            ->title('Pengajuan Cuti Anda telah dibatalkan.')
                            ->info()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => $isAdmin),
                ]),
            ]);
    }

    /**
     * Mendefinisikan relasi yang akan dimuat dengan resource ini.
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Mendefinisikan halaman-halaman yang terkait dengan resource ini.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
        ];
    }

    // *** Tambahkan method ini: Mengisi employee_id secara otomatis sebelum data dibuat. ***
    // Mutate FormData Before Create
    // protected static function mutateFormDataBeforeCreate(array $data): array
    // {
    // $currentUser = Filament::auth()->user();
    // $data['user_id'] = $currentUser ? $currentUser->id : null; // <<< UBAH DARI employee_id

    // // Pastikan status defaultnya 'pending'
    // if (!isset($data['status'])) {
    //     $data['status'] = 'pending';
    // }

    // return $data;

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $data['user_id'] = auth()->id();
    //     return $data;
    // }

    // }
}
