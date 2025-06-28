<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationResource\Pages;
use App\Models\Evaluation;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class EvaluationResource extends Resource
{
    protected static ?string $model = Evaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $modelLabel = 'Evaluasi';
    protected static ?string $pluralModelLabel = 'Data Evaluasi';
    protected static ?string $navigationLabel = 'Evaluasi';
    protected static ?string $slug = 'evaluasi';
    protected static ?string $navigationGroup = 'Sistem Pengambilan Keputusan';

    protected static function isCurrentUserAdmin(): bool
    {
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin');
    }

    public static function canAccess(): bool
    {
        return static::isCurrentUserAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('month')
                ->label('Bulan')
                ->options([
                    '01' => 'Januari',
                    '02' => 'Februari',
                    '03' => 'Maret',
                    '04' => 'April',
                    '05' => 'Mei',
                    '06' => 'Juni',
                    '07' => 'Juli',
                    '08' => 'Agustus',
                    '09' => 'September',
                    '10' => 'Oktober',
                    '11' => 'November',
                    '12' => 'Desember',
                ])
                ->required(),

            Forms\Components\Select::make('year')
                ->label('Tahun')
                ->options(array_combine(
                    range(date('Y'), date('Y') + 5),
                    range(date('Y'), date('Y') + 5)
                ))
                ->required()
                ->rule(function (callable $get) {
                    $month = $get('month');
                    $year = $get('year');
                    $period = $year && $month ? "$year-$month" : null;

                    return Rule::unique('evaluations', 'period')->where(fn($q) => $q->where('period', $period));
                }),

            Forms\Components\Placeholder::make('')
                ->content('')
                ->extraAttributes(['class' => 'block h-2']),
        ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period')
                    ->label('Periode Evaluasi')
                    ->formatStateUsing(function (string $state): string {
                        try {
                            return Carbon::createFromFormat('Y-m', $state)->translatedFormat('F Y');
                        } catch (\Exception $e) {
                            return $state; // fallback jika error
                        }
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Dibuat Pada'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvaluations::route('/'),
            'create' => Pages\CreateEvaluation::route('/create'),
            'edit' => Pages\EditEvaluation::route('/{record}/edit'),
        ];
    }
}
