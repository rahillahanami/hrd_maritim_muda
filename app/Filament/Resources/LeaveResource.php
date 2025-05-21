<?php

namespace App\Filament\Resources;

use App\Enums\LeaveType;
use App\Enums\Status;
use App\Filament\Resources\LeaveResource\Pages;
use App\Models\Leave;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{Select, Textarea, DatePicker};
use Filament\Tables\Columns\{TextColumn, BadgeColumn};

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'HR Management';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('employee_id')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->required(),

                Select::make('leave_type')
                    ->options([
                        LeaveType::ANNUAL->value => 'Annual',
                        LeaveType::SICK->value => 'Sick',
                        LeaveType::PERSONAL->value => 'Personal',
                        LeaveType::OTHER => 'Other',
                    ])
                    ->required(),

                DatePicker::make('start_date')->required(),
                DatePicker::make('end_date')->required(),

                Textarea::make('reason')->required(),

                Select::make('status')
                    ->options([
                        Status::PENDING->value => 'Pending',
                        Status::APPROVED->value => 'Approved',
                        Status::REJECTED->value => 'Rejected',
                    ])
                    ->default(Status::PENDING->value)
                    ->required()
                    ->native(false),

                Select::make('handled_by')
                    ->relationship('handler', 'name')
                    ->searchable()
                    ->label('Handled By')
                    ->hiddenOn('create'),

                Textarea::make('reason')
                    ->label('Handler Reason')
                    ->hidden(fn($get) => $get('status') !== Status::REJECTED->value)
                    ->required(fn($get) => $get('status') === Status::REJECTED->value)
                    ->columnSpan('full'),
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employees.name')->label('Pegawai'),
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
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
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
