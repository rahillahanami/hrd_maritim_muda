<?php

namespace App\Filament\Resources;

use App\Enums\Gender;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Employee;
use App\Models\User;
use App\Models\Division;
use Filament\Facades\Filament;
// use Filament\Forms\Components\Builder; // Remove this unused import
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Carbon; // Pastikan ini di-import
use Illuminate\Database\Eloquent\SoftDeletingScope; // <<< PASTIKAN INI ADA

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static ?string $navigationIcon = 'heroicon-o-users'; // Contoh ikon
    protected static ?string $navigationGroup = 'Organisasi'; // <<< Kelompokkan bersama Divisi
    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Pegawai'; // Label untuk satu record
    protected static ?string $pluralModelLabel = 'Daftar Pegawai'; // Label untuk banyak record
    protected static ?string $navigationLabel = 'Pegawai'; // <<< UBAH INI

    protected static ?string $slug = 'pegawai';

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

    // *** Modifikasi getEloquentQuery() ***
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery(); // Dapatkan builder awal

        if (static::isCurrentUserAdmin()) {
            // Admin bisa melihat semua Employee (termasuk yang soft-deleted)
            return $query->withTrashed(); // Ini yang sudah kita konfirmasi bekerja
        }

        // User biasa (non-admin) hanya melihat Employee yang aktif (tidak di-soft delete)
        // Default Eloquent sudah tidak menyertakan yang soft-deleted, jadi tidak perlu withTrashed() di sini.
        return $query;
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Info Akun')
                    ->schema([
                        // *** Field user_id (hidden) untuk menghubungkan Employee ke User ***
                        TextInput::make('user_id')
                            ->hidden() // Sembunyikan field ini dari UI
                            ->dehydrated() // Pastikan ini disimpan ke DB
                            ->default(function (?Model $record, Get $get, Set $set, $operation) {
                                // Saat create, ini akan null. User akan dibuat di mutateFormDataBeforeCreate/Save
                                // Saat edit, jika employee sudah punya user, ambil user_id-nya
                                if ($operation === 'edit' && $record && $record->user) {
                                    return $record->user->id;
                                }
                                return null;
                            }),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->rule(fn(?Model $record) => \Illuminate\Validation\Rule::unique('users', 'email')->ignore(optional($record?->user)->id))
                            ->autocomplete()
                            ->dehydrated(fn($state) => $state !== null)
                            ->afterStateUpdated(function (Set $set, Get $get, $state, ?Model $record) {
                                if ($record && $record->user) {
                                    $record->user->email = $state;
                                    $record->user->save();
                                }
                            }),

                        Select::make('roles')
                            ->options(fn() => Role::all()->pluck('name', 'name')) // Pluck 'name' ke 'name' untuk value dan label
                            ->multiple()
                            ->preload()
                            ->required()
                            ->label('Role')
                            ->dehydrated(true) // Pastikan dikirimkan
                            ->afterStateHydrated(function ($component, $state, ?Model $record) {
                                if ($record && $record->user) {
                                    $roleNames = $record->user->roles->pluck('name')->toArray();
                                    $component->state($roleNames);
                                }
                            }),

                        TextInput::make('password')
                            ->password()
                            ->required(fn(?Model $record): bool => $record === null) // Wajib saat create
                            ->dehydrated(fn($state) => filled($state)) // Hanya dehidrasi jika ada isinya
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->label('Password')
                            ->autocomplete('new-password')
                            ->confirmed(), // Membutuhkan password_confirmation

                        TextInput::make('password_confirmation')
                            ->password()
                            ->required(fn(?Model $record): bool => $record === null)
                            ->dehydrated(false) // Jangan dehidrasi, hanya untuk konfirmasi
                            ->label('Konfirmasi Password')
                            ->autocomplete('new-password'),
                    ])->columns(2), // Atur layout 2 kolom untuk Info Akun

                Section::make('Info Pegawai')
                    ->schema([
                        // ... (field nip, name, gender, birth_date, phone_number, address tetap sama) ...
                        TextInput::make('nip')
                            ->label('NIP')
                            ->placeholder('1234567890')
                            ->maxLength(20)
                            ->required(),
                        TextInput::make('name')
                            ->label('Nama Pegawai')
                            ->placeholder('Nama Lengkap')
                            ->required(),
                        Select::make('gender')
                            ->label('Jenis kelamin')
                            ->options([
                                Gender::Male->value => 'Pria',
                                Gender::Female->value => 'Wanih',
                            ])
                            ->required(),
                        DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->required(),
                        TextInput::make('phone_number')
                            ->label('Nomor HP')
                            ->placeholder('08123456789')
                            ->required(),
                        Textarea::make('address')
                            ->rows(3)
                            ->label('Alamat Lengkap')
                            ->placeholder('Jl. Sukajadi No. 123, Jakarta')
                            ->maxLength(255)
                            ->required(),
                        Select::make('division_id') // Pastikan ini division_id bukan divisi_id
                            ->relationship('division', 'name')
                            ->required(),
                    ])->columns(2), // Atur layout 2 kolom untuk Info Pegawai
            ])->columns(1); // Atur layout utama ke 1 kolom
    }

    public static function table(Table $table): Table
    {
        $currentUser = Filament::auth()->user();
        $isAdmin = static::isCurrentUserAdmin();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pegawai')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email') // Menampilkan email dari User terkait
                    ->label('Email Akun')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => $isAdmin), // Hanya Admin yang bisa melihat email akun

                // *** KOLOM STATUS AKUN/KARYAWAN ***
                Tables\Columns\TextColumn::make('status_karyawan') // Nama kolom dummy untuk tampilan
                    ->label('Status Akun')
                    ->badge()
                    // Hapus (string $state, Model $record): string $state
                    // Cukup pakai Model $record
                    ->formatStateUsing(function (Model $record): string { // <<< UBAH INI
                        // Periksa apakah user terkait di-soft delete
                        if ($record->user && $record->user->trashed()) {
                            return 'Nonaktif (Akun Resign)'; // Lebih spesifik untuk resign
                        }
                        // Periksa apakah employee sendiri di-soft delete
                        if ($record->trashed()) {
                            return 'Nonaktif (Data Pegawai)'; // Nonaktif karena alasan lain (misal di-delete manual)
                        }
                        return 'Aktif';
                    })
                    ->color(function (Model $record): string { // <<< UBAH INI (tipe hint)
                        if ($record->user && $record->user->trashed() || $record->trashed()) {
                            return 'danger';
                        }
                        return 'success';
                    })
                    ->icon(function (Model $record): string { // <<< UBAH INI (tipe hint)
                        if ($record->user && $record->user->trashed() || $record->trashed()) {
                            return 'heroicon-o-x-circle';
                        }
                        return 'heroicon-o-check-circle';
                    })
                    ->visible(fn() => $isAdmin),

                // ... (kolom gender, birth_date, phone_number, division.name, status) ...
                TextColumn::make('gender')
                    ->label('Jenis Kelamin')
                    ->sortable(),
                TextColumn::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->date()
                    ->sortable(),
                TextColumn::make('phone_number')
                    ->label('Nomor HP'),
                TextColumn::make('division.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // *** FILTER UNTUK STATUS AKTIF/NONAKTIF ***
                Tables\Filters\TernaryFilter::make('deleted_at')
                    ->nullable()
                    ->label('Status Karyawan')
                    ->boolean()
                    ->trueLabel('Nonaktif')
                    ->falseLabel('Aktif')
                    ->placeholder('Semua')
                    ->visible(fn() => $isAdmin)
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value'])) {
                            // Jika 'Nonaktif' dipilih (value=true)
                            if ($data['value'] === true) {
                                // Tampilkan employee yang soft deleted ATAU employee yang user-nya soft deleted
                                return $query->onlyTrashed()->orWhereHas('user', fn($q) => $q->onlyTrashed());
                            }
                            // Jika 'Aktif' dipilih (value=false)
                            else if ($data['value'] === false) {
                                // Tampilkan employee yang tidak soft deleted DAN user-nya tidak soft deleted
                                return $query->withoutTrashed()->whereDoesntHave('user', fn($q) => $q->onlyTrashed());
                            }
                        }
                        // Default: Tampilkan semua (termasuk trashed jika admin, hanya aktif jika user biasa)
                        return $query;
                    }),
                // ... (filter lain) ...
                Tables\Filters\SelectFilter::make('division_id')
                    ->relationship('division', 'name')
                    ->label('Filter Divisi')
                    ->placeholder('Semua Divisi')
                    ->searchable()
                    ->visible(fn() => $isAdmin),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // *** Modifikasi AKSI DELETE (SOFT DELETE) ***
                Tables\Actions\DeleteAction::make()
                    ->action(function (Model $record) { // Gunakan closure action kustom
                        // Lakukan soft delete pada Employee
                        $record->delete();

                        // Lakukan soft delete pada User terkait juga
                        if ($record->user) { // Pastikan employee punya user
                            $record->user->delete();
                            Notification::make() // Notifikasi tambahan
                                ->title('Akun user ' . $record->user->name . ' juga dinonaktifkan.')
                                ->success()
                                ->send();
                        }

                        Notification::make() // Notifikasi utama
                            ->title('Data pegawai ' . $record->name . ' telah dinonaktifkan.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Model $record): bool => static::isCurrentUserAdmin() && !$record->trashed()), // Admin hanya bisa delete (soft delete) yang belum nonaktif

                // *** AKSI RESTORE ***
                Tables\Actions\Action::make('restore')
                    ->label('Pulihkan')
                    ->color('info')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (Model $record) { // Gunakan Model $record
                        $record->restore(); // Mengaktifkan Employee kembali

                        // Aktifkan juga user yang terhubung
                        if ($record->user && $record->user->trashed()) {
                            $record->user->restore(); // Mengaktifkan user kembali
                            Notification::make()
                                ->title('Akun user ' . $record->user->name . ' juga diaktifkan kembali.')
                                ->success()
                                ->send();
                        }
                        Notification::make()
                            ->title('Data pegawai ' . $record->name . ' telah diaktifkan kembali.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Model $record): bool => static::isCurrentUserAdmin() && $record->trashed()), // Hanya Admin & employee sudah soft-deleted
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // *** Modifikasi BULK DELETE ***
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($livewire) {
                            $livewire->getSelectedTableRecords()->each(function (Employee $employee) {
                                $employee->delete(); // Soft delete employee
                                if ($employee->user) {
                                    $employee->user->delete(); // Soft delete user terkait
                                }
                            });
                            Notification::make()
                                ->title('Data pegawai yang dipilih telah dinonaktifkan.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn() => $isAdmin),
                    // *** Modifikasi BULK RESTORE ***
                    Tables\Actions\RestoreBulkAction::make()
                        ->action(function ($livewire) {
                            $livewire->getSelectedTableRecords()->each(function (Employee $employee) {
                                $employee->restore(); // Restore employee
                                if ($employee->user) {
                                    $employee->user->restore(); // Restore user terkait
                                }
                            });
                            Notification::make()
                                ->title('Data pegawai yang dipilih telah diaktifkan kembali.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn() => $isAdmin),
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        // Buat user baru
        $user = User::create([
            'name' => $data['name'], // Ambil nama dari data Employee
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Berikan roles kepada user
        $user->assignRole($data['roles']);

        // Hubungkan user_id dari user yang baru dibuat ke data employee
        $data['user_id'] = $user->id;

        // Hapus data yang tidak perlu disimpan ke tabel employee (email, password, roles)
        unset($data['email'], $data['password'], $data['password_confirmation'], $data['roles']);

        return $data;
    }

    // *** Tambahkan method ini untuk update employee ***
    protected static function mutateFormDataBeforeSave(array $data, ?Model $record): array
    {
        // Jika mode edit dan employee sudah punya user
        if ($record && $record->user) {
            $user = $record->user;

            // Update email user jika diubah
            if (isset($data['email']) && $user->email !== $data['email']) {
                $user->email = $data['email'];
            }

            // Update nama user jika diubah (dari field name employee)
            if (isset($data['name']) && $user->name !== $data['name']) {
                $user->name = $data['name'];
            }

            // Update password user jika diisi
            if (isset($data['password']) && filled($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            // Update roles user
            if (isset($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            $user->save();

            // Pastikan user_id tetap terhubung ke employee
            $data['user_id'] = $user->id;

            // Hapus field yang tidak ada di tabel employee
            unset($data['email'], $data['password'], $data['password_confirmation'], $data['roles']);
        }

        return $data;
    }
}
