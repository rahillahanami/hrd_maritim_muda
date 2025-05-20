<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Filament\Resources\LeaveRequestResource\RelationManagers;
use App\Models\LeaveRequestModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\LeaveRequest;
use Closure;
use Filament\Forms\Components\{Select, Textarea, DatePicker};
use Filament\Tables\Columns\{TextColumn, BadgeColumn};

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequestModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'HR Management';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
               Select::make('pegawai_id')
                ->relationship('pegawai', 'nama')
                ->searchable()
                ->required(),

                Select::make('leave_type')
                    ->options([
                        'annual' => 'Annual',
                        'sick' => 'Sick',
                        'personal' => 'Personal',
                        'other' => 'Other',
                    ])
                    ->required(),

                DatePicker::make('start_date')->required(),
                DatePicker::make('end_date')->required(),

                Textarea::make('reason')->required(),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending')
                    ->required()
                    ->native(false),

                Select::make('approved_by')
                    ->relationship('approver', 'name')
                    ->searchable()
                    ->label('Approved By')
                    ->hiddenOn('create'),

                Textarea::make('rejection_reason')
                    ->label('Rejection Reason')
                    ->hidden(fn ($get) => $get('status') !== 'rejected')
                    ->required(fn ($get) => $get('status') === 'rejected')
                    ->columnSpan('full'),

                // TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                //     'approved' => 'success',
                //     'rejected' => 'danger',
                //     'pending' => 'warning',
                // }),

                    
            ]);
           
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                     TextColumn::make('pegawai.nama')->label('Pegawai'),
                     TextColumn::make('leave_type')->label('Type')->sortable(),
                     TextColumn::make('start_date')->date(),
                     TextColumn::make('end_date')->date(),
                     BadgeColumn::make('status')
                        ->colors([
                            'primary' => 'pending',
                            'success' => 'approved',
                            'danger' => 'rejected',
                        ]),
            ])
            ->filters([
                //
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }

    // protected function mutateFormDataBeforeSave(array $data): array
    // {
    //     if (in_array($data['status'], ['approved', 'rejected'])) {
    //         $data['approved_by'] = auth()->id();
    //     }

    //     return $data;
    // }

}
