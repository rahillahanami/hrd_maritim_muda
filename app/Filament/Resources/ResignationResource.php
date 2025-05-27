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

class ResignationResource extends Resource
{
    protected static ?string $model = Resignation::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-minus'; // Ganti ikon navigasi

    // Konfigurasi label di navigasi
    protected static ?string $navigationGroup = 'HR Management'; // Kelompokkan di navigasi
    protected static ?int $navigationSort = 3; // Urutan di dalam grup

    /**
     * Mendefinisikan skema formulir untuk membuat atau mengedit pengajuan resign.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2) // Membuat layout 2 kolom
                    ->schema([
                        // Field untuk Karyawan yang Mengajukan (user_id)
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name') // Relasi ke model User, tampilkan nama user
                            ->required()
                            ->default(fn () => Filament::auth()->user()?->id) // Otomatis mengisi ID user yang login
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit'), // Tidak bisa diubah saat mengedit

                        // Field untuk Tanggal Pengajuan
                        Forms\Components\DatePicker::make('submission_date')
                            ->required()
                            ->default(now()) // Otomatis mengisi tanggal hari ini
                            ->disabledOn('edit'), // Tidak bisa diubah saat mengedit
                    ]),

                // Field untuk Tanggal Efektif Resign
                Forms\Components\DatePicker::make('effective_date')
                    ->required()
                    ->minDate(now()->addDays(7)) // Minimal 7 hari dari sekarang (contoh, bisa disesuaikan)
                    ->placeholder('Tanggal Terakhir Bekerja'),

                // Field untuk Alasan Resign
                Forms\Components\Textarea::make('reason')
                    ->required()
                    ->rows(5)
                    ->maxLength(65535) // Sesuaikan dengan tipe TEXT di DB
                    ->placeholder('Jelaskan alasan pengunduran diri Anda.'),

                // Field untuk Status Pengajuan
                Forms\Components\Select::make('status')
                    ->options([
                        'Pending' => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                        'Cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('Pending')
                    ->columnSpanFull(), // Mengambil lebar penuh kolom

                // Field untuk Catatan Internal (hanya terlihat oleh HR/Manajer)
                Forms\Components\Textarea::make('notes')
                    ->nullable()
                    ->rows(3)
                    ->placeholder('Catatan internal untuk HR/Manajer.'),

                // Field untuk User yang Menyetujui/Menolak
                Forms\Components\Select::make('approved_by_user_id')
                    ->relationship('approvedBy', 'name') // Relasi ke method approvedBy di model Resignation
                    ->label('Disetujui/Ditolak Oleh')
                    ->nullable()
                    ->placeholder('Pilih Penyetuju/Penolak')
                    ->searchable()
                    ->preload(),
            ]);
    }

    /**
     * Mendefinisikan skema tabel untuk menampilkan daftar pengajuan resign.
     */
    public static function table(Table $table): Table
    {
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
                    ->tooltip(fn (?string $state): ?string => $state) // <<< UBAH BARIS INI
                    ->toggleable(),

                    
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
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan secara default
                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->words(10)
                    ->tooltip(fn (?string $state): ?string => $state) // <<< UBAH BARIS INI
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->searchable(),

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
                            ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('effective_date', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('effective_date', '<=', $date));
                    }),
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