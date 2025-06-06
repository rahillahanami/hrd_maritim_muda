<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResignationResource\Pages;
use App\Filament\Resources\ResignationResource\RelationManagers;
use App\Models\Resignation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament; // Import Facade Filament
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

class ResignationResource extends Resource
{
    protected static ?string $model = Resignation::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-minus'; // Sudah benar
    protected static ?string $navigationGroup = 'Manajemen HRD'; // Sudah benar
    protected static ?int $navigationSort = 1; // Urutan pertama di HR Management

    protected static ?string $modelLabel = 'Pengunduran Diri';
    protected static ?string $pluralModelLabel = 'Daftar Pengunduran Diri';
    protected static ?string $navigationLabel = 'Pengunduran Diri'; // <<< UBAH INI
    protected static ?string $slug = 'pengunduran-diri';



    protected static function isCurrentUserAdmin(): bool
    {
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin'); // Sesuaikan 'admin' dengan nama peran admin Anda
    }

    public static function canCreate(): bool
    {
        // Hanya user biasa (bukan admin) yang bisa membuat pengajuan resign
        return !static::isCurrentUserAdmin();
    }

    /**
     * Membatasi query Eloquent berdasarkan peran user yang login.
     */
    public static function getEloquentQuery(): Builder
    {
        $currentUser = Filament::auth()->user();

        // Jika user tidak login, jangan tampilkan apa-apa
        if (!$currentUser) {
            return parent::getEloquentQuery()->whereRaw('1 = 0')->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        // Jika user adalah Admin (atau peran HR, sesuaikan dengan logic role Anda)
        // Asumsi admin juga bertindak sebagai HR
        if (static::isCurrentUserAdmin()) { // Atau $currentUser->hasRole('hr')
            return parent::getEloquentQuery()->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        // Jika user adalah user biasa, hanya bisa melihat pengajuannya sendiri
        return parent::getEloquentQuery()
            ->where('user_id', $currentUser->id) // Filter berdasarkan user_id yang login
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Mendefinisikan skema formulir untuk membuat atau mengedit pengajuan resign.
     */
    public static function form(Form $form): Form
    {
        $currentUser = Filament::auth()->user();
        $isAdmin = static::isCurrentUserAdmin();

        $record = $form->getRecord(); // Ambil record langsung
        $recordStatus = $record?->status; // Status record (null saat create)

        $isCreating = $record === null; // Cara lebih akurat cek mode create

        // Tentukan apakah user yang login adalah pemilik resign ini
        // is_owner hanya relevan saat edit
        $isOwner = $record && $record->user_id === $currentUser->id;


        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        // Field untuk Karyawan yang Mengajukan (user_id)
                        Forms\Components\Select::make('user_id')
                            ->disabled()
                            ->dehydrated(fn() => true)
                            ->relationship('user', 'name')
                            ->default(fn() => Filament::auth()->user()?->id)
                            ->required(),

                        Forms\Components\DatePicker::make('submission_date')
                            ->disabled()
                            ->dehydrated(fn() => true)
                            ->default(now())
                            ->required(),

                    ]),

                // Field untuk Tanggal Efektif Resign
                // Field untuk Tanggal Efektif Resign
                Forms\Components\DatePicker::make('effective_date')
                    ->required()
                    ->minDate(now()->addDays(7))
                    ->placeholder('Tanggal Terakhir Bekerja')
                    // >>> UBAH LOGIKA DISABLED INI <<<
                    ->disabled(
                        fn() =>
                        // Jika mode CREATE, field ini ENABLED
                        $isCreating ? false : (
                            // Jika mode EDIT dan user BUKAN ADMIN
                            !$isAdmin && (
                                // Dan user adalah pemilik, tapi status BUKAN pending
                                ($isOwner && $recordStatus !== 'Pending') ||
                                // Atau user BUKAN pemilik
                                (!$isOwner)
                            )
                        )
                    ),

                // Field untuk Alasan Resign
                Forms\Components\Textarea::make('reason')
                    ->required()
                    ->rows(5)
                    ->maxLength(65535)
                    ->placeholder('Jelaskan alasan pengunduran diri Anda.')
                    // >>> UBAH LOGIKA DISABLED INI <<<
                    ->disabled(
                        fn() =>
                        // Jika mode CREATE, field ini ENABLED
                        $isCreating ? false : (
                            // Jika mode EDIT dan user BUKAN ADMIN
                            !$isAdmin && (
                                // Dan user adalah pemilik, tapi status BUKAN pending
                                ($isOwner && $recordStatus !== 'Pending') ||
                                // Atau user BUKAN pemilik
                                (!$isOwner)
                            )
                        )
                    ),

                // Field untuk Status Pengajuan
                Forms\Components\Select::make('status')
                    ->options([
                        'Pending' => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                        'Cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->disabled(fn() => !$isAdmin) // <<< HANYA ADMIN YANG BISA MENGUBAH STATUS (tetap)
                    ->default('Pending')
                    ->columnSpanFull()
                    ->visible(fn() => $isAdmin),

                // Field untuk Catatan Internal (hanya terlihat dan bisa diubah oleh HR/Manajer)
                Forms\Components\Textarea::make('notes')
                    ->nullable()
                    ->rows(3)
                    ->disabled(fn() => !$isAdmin) // <<< HANYA ADMIN YANG BISA MENGUBAH CATATAN (tetap)
                    ->placeholder('Catatan internal untuk HR/Manajer.')
                    ->visible(fn() => $isAdmin),

                // Field untuk User yang Menyetujui/Menolak
                Forms\Components\Select::make('approved_by_user_id')
                    ->disabled(fn() => !$isAdmin) // <<< HANYA ADMIN YANG BISA MEMILIH PENYETUJU (tetap)
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
     * Mendefinisikan skema tabel untuk menampilkan daftar pengajuan resign.
     */

    public static function table(Table $table): Table
    {

        $currentUser = Filament::auth()->user();
        $isAdmin = static::isCurrentUserAdmin();


        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name') // Menampilkan nama karyawan yang mengajukan
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('submission_date')
                    ->label('Tanggal Pengajuan')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Tanggal Efektif')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan Resign')
                    ->words(10)
                    ->tooltip(fn(?string $state): ?string => $state),


                Tables\Columns\BadgeColumn::make('status') // Tampilan status dengan badge
                    ->colors([
                        'warning' => 'Pending',
                        'success' => 'Approved',
                        'danger' => 'Rejected',
                        'gray' => 'Cancelled',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('approvedBy.name') // Menampilkan nama penyetuju
                    ->label('Disetujui Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn() => $isAdmin), // Sembunyikan secara default
                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->wrap()
                    ->toggleable()
                    ->visible(fn() => $isAdmin),
                    // ->modalHeading('Catatan Internal')
                    // ->modalContent(fn(Resignation $record) => $record->notes),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn() => $isAdmin),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filter berdasarkan Karyawan
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Filter Karyawan')
                    ->placeholder('Semua Karyawan')
                    ->searchable()
                    ->visible(fn() => $isAdmin), // Hanya Admin yang bisa melihat filter ini

                // Filter berdasarkan Status Pengajuan
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Pending' => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                        'Cancelled' => 'Cancelled',
                    ])
                    ->label('Filter Status')
                    ->placeholder('Semua Status'),

                // Filter berdasarkan Rentang Tanggal Efektif Resign
                Tables\Filters\Filter::make('effective_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn(Builder $query, $date) => $query->whereDate('effective_date', '>=', $date))
                            ->when($data['until'], fn(Builder $query, $date) => $query->whereDate('effective_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    // Visibilitas edit action tetap untuk semua user (karena field di dalamnya sudah diatur disabled)
                    // Tapi kita bisa batasi agar admin bisa edit semua, user hanya bisa edit pengajuannya sendiri jika masih Pending
                    ->visible(
                        fn(Resignation $record): bool =>
                        $isAdmin || // Admin bisa edit semua
                            ($record->user_id === $currentUser->id && $record->status === 'Pending') // Owner bisa edit jika Pending
                    ),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => $isAdmin), // Hanya Admin yang bisa delete (tetap)

                // *** AKSI KUSTOM BARU: APPROVE ***
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn(Resignation $record): bool => $isAdmin && $record->status === 'Pending') // Hanya Admin & status Pending
                    ->action(function (Resignation $record) use ($currentUser) {
                        $record->update([
                            'status' => 'Approved',
                            'approved_by_user_id' => $currentUser->id,
                        ]);
                        Notification::make()
                            ->title("Pengajuan resign oleh {$record->user->name} telah disetujui.")
                            ->success()
                            ->send();
                    }),

                // *** AKSI KUSTOM BARU: REJECT ***
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(fn(Resignation $record): bool => $isAdmin && $record->status === 'Pending') // Hanya Admin & status Pending
                    ->action(function (Resignation $record) use ($currentUser) {
                        $record->update([
                            'status' => 'Rejected',
                            'approved_by_user_id' => $currentUser->id,
                        ]);

                        Notification::make()
                            ->title("Pengajuan resign oleh {$record->user->name} telah ditolak.")
                            ->success()
                            ->send();
                    }),

                // *** AKSI KUSTOM BARU: CANCEL (untuk pemilik pengajuan) ***
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->color('gray')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->visible(fn(Resignation $record): bool => $record->user_id === $currentUser->id && $record->status === 'Pending') // Hanya pemilik & status Pending
                    ->action(function (Resignation $record) {
                        $record->update([
                            'status' => 'Cancelled',
                        ]);
                        // Filament::notify('info', 'Pengajuan Resign Anda telah dibatalkan.');
                        Notification::make()->title('Pengajuan Resign Anda telah dibatalkan.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListResignations::route('/'),
            'create' => Pages\CreateResignation::route('/create'),
            'edit' => Pages\EditResignation::route('/{record}/edit'),
        ];
    }
}
