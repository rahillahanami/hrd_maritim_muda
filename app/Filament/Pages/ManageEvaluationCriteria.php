<?php

namespace App\Filament\Pages;

use App\Models\EvaluationCriteria;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class ManageEvaluationCriteria extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.manage-evaluation-criteria';
    protected static ?string $navigationLabel = 'Kelola Kriteria Evaluasi';
    protected static ?string $title = 'Kelola Kriteria Evaluasi';
    protected static ?string $navigationGroup = 'Sistem Pengambilan Keputusan';
    protected static ?int $navigationSort = 1;

    public $name = '';
    public $weight = '';
    public $type = 'benefit';
    public $editing_id = null;

    public static function canAccess(): bool
    {
        return Auth::user() && Auth::user()->hasRole('super_admin');
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Kriteria')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Contoh: Kedisiplinan, Kualitas Kerja, dll'),
                    
                    Forms\Components\TextInput::make('weight')
                        ->label('Bobot')
                        ->numeric()
                        ->required()
                        ->step(0.01)
                        ->minValue(0.01)
                        ->maxValue(1.00)
                        ->placeholder('0.25')
                        ->helperText('Masukkan nilai antara 0.01 - 1.00 (contoh: 0.25 = 25%)'),
                ]),
            
            Forms\Components\Select::make('type')
                ->label('Tipe Kriteria')
                ->options([
                    'benefit' => 'Benefit (Semakin tinggi semakin baik)',
                    'cost' => 'Cost (Semakin rendah semakin baik)',
                ])
                ->required()
                ->default('benefit')
                ->helperText('Benefit: nilai tinggi lebih baik (kedisiplinan). Cost: nilai rendah lebih baik (tingkat kesalahan)'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return EvaluationCriteria::query()->orderBy('created_at', 'desc');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Nama Kriteria')
                ->searchable()
                ->sortable(),
            
            Tables\Columns\TextColumn::make('weight')
                ->label('Bobot')
                ->formatStateUsing(fn ($state) => number_format($state * 100, 1) . '%')
                ->sortable(),
            
            Tables\Columns\BadgeColumn::make('type')
                ->label('Tipe')
                ->colors([
                    'success' => 'benefit',
                    'warning' => 'cost',
                ])
                ->formatStateUsing(fn ($state) => $state === 'benefit' ? 'Benefit' : 'Cost'),
            
            Tables\Columns\TextColumn::make('created_at')
                ->label('Dibuat')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->color('warning')
                ->action(function (EvaluationCriteria $record) {
                    $this->editing_id = $record->id;
                    $this->name = $record->name;
                    $this->weight = $record->weight;
                    $this->type = $record->type;
                }),
            
            Tables\Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Hapus Kriteria')
                ->modalDescription('Apakah Anda yakin ingin menghapus kriteria ini? Data yang sudah dihapus tidak dapat dikembalikan.')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->before(function (EvaluationCriteria $record) {
                    // Check if the record being deleted is currently being edited
                    if ($this->editing_id == $record->id) {
                        // Clear the form immediately
                        $this->reset(['name', 'weight', 'type', 'editing_id']);
                        
                        // Show notification that edit was cancelled due to deletion
                        Notification::make()
                            ->title('Edit dibatalkan')
                            ->body('Data yang sedang diedit telah dihapus. Form telah direset.')
                            ->info()
                            ->send();
                    }
                })
                ->after(function () {
                    Notification::make()
                        ->title('Kriteria berhasil dihapus')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            Tables\Actions\DeleteBulkAction::make()
                ->requiresConfirmation()
                ->modalHeading('Hapus Kriteria Terpilih')
                ->modalDescription('Apakah Anda yakin ingin menghapus kriteria yang dipilih?')
                ->before(function ($records) {
                    // Check if any of the records being deleted is currently being edited
                    $recordIds = $records->pluck('id')->toArray();
                    
                    if ($this->editing_id && in_array($this->editing_id, $recordIds)) {
                        // Clear the form immediately
                        $this->reset(['name', 'weight', 'type', 'editing_id']);
                        
                        // Show notification that edit was cancelled due to deletion
                        Notification::make()
                            ->title('Edit dibatalkan')
                            ->body('Salah satu data yang sedang diedit telah dihapus. Form telah direset.')
                            ->info()
                            ->send();
                    }
                })
                ->after(function () {
                    Notification::make()
                        ->title('Kriteria berhasil dihapus')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('checkTotalWeight')
                ->label('Cek Total Bobot')
                ->icon('heroicon-o-calculator')
                ->color('info')
                ->action(function () {
                    $totalWeight = EvaluationCriteria::sum('weight');
                    $percentage = number_format($totalWeight * 100, 1);
                    
                    if ($totalWeight == 1.0) {
                        Notification::make()
                            ->title('Total Bobot Seimbang')
                            ->body("Total bobot kriteria: {$percentage}% ")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Peringatan Total Bobot')
                            ->body("Total bobot kriteria: {$percentage}%. Idealnya harus 100% untuk hasil evaluasi yang akurat.")
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0.01|max:1.00',
            'type' => 'required|in:benefit,cost',
        ]);

        if ($this->editing_id) {
            // Check if the record still exists before updating
            $criteria = EvaluationCriteria::find($this->editing_id);
            
            if (!$criteria) {
                // Record was deleted while editing
                $this->reset(['name', 'weight', 'type', 'editing_id']);
                
                Notification::make()
                    ->title('Data tidak ditemukan')
                    ->body('Data yang sedang diedit telah dihapus oleh pengguna lain. Form telah direset.')
                    ->warning()
                    ->send();
                    
                return;
            }
            
            // Update existing criteria
            $criteria->update([
                'name' => $this->name,
                'weight' => $this->weight,
                'type' => $this->type,
            ]);

            Notification::make()
                ->title('Kriteria berhasil diperbarui')
                ->success()
                ->send();
        } else {
            // Create new criteria
            EvaluationCriteria::create([
                'name' => $this->name,
                'weight' => $this->weight,
                'type' => $this->type,
            ]);

            Notification::make()
                ->title('Kriteria berhasil ditambahkan')
                ->success()
                ->send();
        }

        // Reset form
        $this->reset(['name', 'weight', 'type', 'editing_id']);
        
        // Check total weight after save
        $this->checkWeightWarning();
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'weight', 'type', 'editing_id']);
        
        Notification::make()
            ->title('Edit dibatalkan')
            ->body('Form telah direset')
            ->info()
            ->send();
    }

    private function checkWeightWarning()
    {
        $totalWeight = EvaluationCriteria::sum('weight');
        
        if ($totalWeight > 1.0) {
            Notification::make()
                ->title('Peringatan: Total Bobot Berlebih')
                ->body('Total bobot kriteria melebihi 100%. Silakan sesuaikan bobot untuk hasil evaluasi yang akurat.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function getFormModel(): string
    {
        return static::class;
    }
}