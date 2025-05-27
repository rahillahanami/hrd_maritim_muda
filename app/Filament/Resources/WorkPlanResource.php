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
use Filament\Facades\Filament; // Import Facade Filament

class WorkPlanResource extends Resource
{
    protected static ?string $model = WorkPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list'; // Ganti ikon navigasi

    // Konfigurasi label di navigasi
    protected static ?string $navigationGroup = 'Performance Management'; // Kelompokkan di navigasi
    protected static ?int $navigationSort = 1; // Urutan di dalam grup

    /**
     * Mendefinisikan skema formulir untuk membuat atau mengedit rencana kerja.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Judul Rencana Kerja'),

                Forms\Components\Textarea::make('description')
                    ->nullable()
                    ->rows(4)
                    ->placeholder('Detail Rencana Kerja'),

                Forms\Components\Grid::make(2) // Membuat layout 2 kolom untuk beberapa field
                    ->schema([
                        Forms\Components\TextInput::make('target_metric')
                            ->nullable()
                            ->maxLength(255)
                            ->placeholder('Contoh: Jumlah Penjualan, Proyek Terselesaikan'),

                        Forms\Components\TextInput::make('target_value')
                            ->numeric()
                            ->nullable()
                            ->step(0.01) // Memungkinkan nilai desimal
                            ->placeholder('Contoh: 15 (untuk 15%), 5 (untuk 5 Proyek)'),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->required(),

                        Forms\Components\DatePicker::make('due_date')
                            ->required()
                            ->afterOrEqual('start_date'), // Tenggat waktu harus setelah atau sama dengan tanggal mulai
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
                    ->default('Draft'),

                Forms\Components\TextInput::make('progress_percentage') // UBAH INI DARI SLIDER
                    ->label('Progress (%)')
                    ->numeric() // Pastikan hanya menerima angka
                    ->minValue(0) // Nilai minimum 0
                    ->maxValue(100) // Nilai maksimum 100
                    ->default(0)
                    ->suffix('%') // Tampilkan '%' di samping input
                    ->required(), // Pastikan diisi

                Forms\Components\Textarea::make('notes')
                    ->nullable()
                    ->rows(3)
                    ->placeholder('Catatan atau pembaruan progres'),

                // Field untuk pemilik rencana kerja (user_id)
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name') // Relasi ke model User, tampilkan nama user
                    ->required()
                    ->default(fn () => Filament::auth()->user()?->id) // Otomatis mengisi ID user yang login
                    ->searchable()
                    ->preload(), // Memuat semua opsi user saat form dibuka

                // Field untuk divisi terkait (division_id)
                Forms\Components\Select::make('division_id')
                    ->relationship('division', 'name')
                    ->nullable()
                    ->placeholder('Pilih Divisi Terkait')
                    ->searchable()
                    ->preload(),

                // Field untuk user yang menyetujui (approved_by_user_id)
                Forms\Components\Select::make('approved_by_user_id')
                    ->relationship('approvedBy', 'name') // Relasi ke method approvedBy di model WorkPlan
                    ->label('Disetujui Oleh')
                    ->nullable()
                    ->placeholder('Pilih Penyetuju')
                    ->searchable()
                    ->preload(),
            ]);
    }

    /**
     * Mendefinisikan skema tabel untuk menampilkan daftar rencana kerja.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name') // Menampilkan nama pemilik rencana
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('division.name') // Menampilkan nama divisi
                    ->label('Divisi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status') // Tampilan status dengan badge
                    ->colors([
                        'gray' => 'Draft',
                        'info' => 'On Progress',
                        'success' => 'Completed',
                        'warning' => 'Pending Review',
                        'danger' => 'Cancelled',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('approvedBy.name') // Menampilkan nama penyetuju
                    ->label('Disetujui Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Bisa disembunyikan/ditampilkan
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan secara default
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan secara default
            ])
            ->filters([
                // Filter berdasarkan Karyawan/User
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Filter Karyawan')
                    ->placeholder('Semua Karyawan')
                    ->searchable(),

                // Filter berdasarkan Divisi
                Tables\Filters\SelectFilter::make('division_id')
                    ->relationship('division', 'name')
                    ->label('Filter Divisi')
                    ->placeholder('Semua Divisi')
                    ->searchable(),

                // Filter berdasarkan Status
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

                // Filter berdasarkan Tanggal Tenggat Waktu
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