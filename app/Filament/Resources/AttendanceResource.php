<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Facades\Filament;
// use Filament\Forms\Components\Builder;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletingScope;



class AttendanceResource extends Resource
{


    protected static ?string $model = Attendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-finger-print'; // Contoh ikon
    protected static ?string $navigationGroup = 'Presensi'; // <<< Kelompokkan (sudah)
    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Absensi';
    protected static ?string $pluralModelLabel = 'Data Absensi';
    protected static ?string $navigationLabel = 'Absensi'; // <<< (sudah benar)
    protected static ?string $slug = 'absensi';

//     protected static function mutateFormDataBeforeCreate(array $data): array
// {
//     // Asumsi: Setiap User memiliki 1 Employee (one-to-one)
//     // atau Anda bisa mencari Employee berdasarkan user_id.
//     $loggedInUser = Filament::auth()->user();
//     if ($loggedInUser && $loggedInUser->employee) { // Asumsi ada relasi employee di model User
//         $data['employee_id'] = $loggedInUser->employee->id;
//     } else {
//         // Handle jika user tidak memiliki employee (misal: user admin tidak punya employee_id)
//         // Atau jika employee_id di attendance boleh null.
//         $data['employee_id'] = null; // atau throw error
//     }
//     return $data;
// }

public static function getEloquentQuery(): Builder
    {
        // Ambil user yang sedang login
        $currentUser = Filament::auth()->user();

        // Jika user adalah admin (atau peran lain yang bisa melihat semua)
        // Anda harus menyesuaikan 'admin' dengan nama peran di Filament Shield Anda
        if ($currentUser && $currentUser->hasRole('super_admin')) {
            // Admin bisa melihat semua data attendance
            return parent::getEloquentQuery()->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        // Jika user adalah user biasa (bukan admin)
        // Kita perlu menemukan employee_id dari user yang login
        $employeeId = null;
        if ($currentUser && $currentUser->employee) { // Mengakses relasi employee dari user
            $employeeId = $currentUser->employee->id;
        }

        // User biasa hanya bisa melihat attendance mereka sendiri
        // Jika employeeId ditemukan, filter berdasarkan itu
        if ($employeeId) {
            return parent::getEloquentQuery()->where('employee_id', $employeeId)->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
        }

        // Jika user tidak login, atau user login tapi tidak punya data employee
        // Kita bisa mengembalikan query kosong atau mencegah mereka melihat apa-apa.
        return parent::getEloquentQuery()->whereRaw('1 = 0')->withoutGlobalScopes([ // Mengembalikan query kosong
            SoftDeletingScope::class,
        ]);
    }

    public static function form(Form $form): Form
    {

        $currentUser = Filament::auth()->user();
        $isAdmin = $currentUser && $currentUser->hasRole('super_admin');

        return $form
           ->schema([
            Select::make('employee_id')
                ->relationship('employee', 'name')
                ->required()
                ->default(function () use ($isAdmin, $currentUser) {
                    if ($isAdmin) {
                        return null;
                    }
                    return $currentUser?->employee?->id;
                })
                ->disabled(fn () => !$isAdmin)
                ->hidden(fn () => !$isAdmin && !request()->routeIs('filament.admin.resources.attendances.create'))
                ->searchable()
                ->preload(),

            DatePicker::make('date') // <<< Sesuaikan dari DateTimePicker
                ->required()
                ->default(now()),

            DateTimePicker::make('check_in') // <<< Sesuaikan dari check_in_time
                ->label('Check In Time') // Label yang lebih jelas
                ->nullable(), // Bisa null karena mungkin belum check in

            DateTimePicker::make('check_out') // <<< Sesuaikan dari check_out_time
                ->label('Check Out Time') // Label yang lebih jelas
                ->nullable()
                ->after('check_in'), // Check out harus setelah check in

            TextInput::make('early_minutes') // <<< Tambahkan
                ->label('Early Minutes')
                ->numeric()
                ->default(0)
                ->disabled(fn () => !$isAdmin), // Hanya admin yang bisa mengisi ini

            TextInput::make('late_minutes') // <<< Tambahkan
                ->label('Late Minutes')
                ->numeric()
                ->default(0)
                ->disabled(fn () => !$isAdmin), // Hanya admin yang bisa mengisi ini
        ]);
    }

    public static function table(Table $table): Table
    {

        // Ambil user yang sedang login
        $currentUser = Filament::auth()->user();
        // Asumsi 'admin' adalah peran admin di Filament Shield
        $isAdmin = $currentUser && $currentUser->hasRole('super_admin');

        return $table
            ->columns([
            TextColumn::make('employee.name') // Menampilkan nama employee
                ->label('Employee Name')
                ->searchable()
                ->sortable(),
            TextColumn::make('date') // <<< Sesuaikan
                ->date()
                ->sortable(),
            TextColumn::make('check_in') // <<< Sesuaikan
                ->label('Check In')
                ->dateTime()
                ->sortable(),
            TextColumn::make('check_out') // <<< Sesuaikan
                ->label('Check Out')
                ->dateTime()
                ->sortable(),
            TextColumn::make('early_minutes') // <<< Tambahkan
                ->label('Early (min)')
                ->sortable(),
            TextColumn::make('late_minutes') // <<< Tambahkan
                ->label('Late (min)')
                ->sortable(),
            // TextColumn::make('created_at')
            //     ->dateTime()
            //     ->sortable()
            //     ->toggleable(isToggledHiddenByDefault: true),
            // TextColumn::make('updated_at')
            //     ->dateTime()
            //     ->sortable()
            //     ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            // ... filter yang sudah ada ...
            Tables\Filters\SelectFilter::make('employee_id')
                ->relationship('employee', 'name')
                ->label('Filter by Employee')
                ->placeholder('All Employees')
                ->searchable()
                ->visible(fn () => $isAdmin), // <<< TAMBAHKAN ATAU UBAH BARIS INI
            // ...
            Tables\Filters\Filter::make('date')
                ->form([
                    DatePicker::make('from'),
                    DatePicker::make('until'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('date', '>=', $date))
                        ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('date', '<=', $date));
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
