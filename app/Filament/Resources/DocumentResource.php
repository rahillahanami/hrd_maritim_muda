<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Storage;



class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

     protected static ?string $navigationIcon = 'heroicon-o-document-duplicate'; // Contoh ikon
    protected static ?string $navigationGroup = 'Manajemen Kinerja'; // <<< Kelompokkan
    protected static ?int $navigationSort = 2; // Urutan kedua di Performance Management

    protected static ?string $modelLabel = 'Dokumen';
    protected static ?string $pluralModelLabel = 'Daftar Dokumen';
    protected static ?string $navigationLabel = 'Dokumen'; // <<< UBAH INI
    protected static ?string $slug = 'dokumen'; // Slug untuk URL



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required(),

                Forms\Components\Select::make('category')
                    ->options([
                        'Forms & Templates' => 'Forms & Templates',
                        'Company Policies' => 'Company Policies',
                        'Training Materials' => 'Training Materials',
                        'Reports' => 'Reports',
                        'Other' => 'Other',
                    ])
                    ->required(),

                 Select::make('division_id')
                        ->relationship('division', 'name')
                        ->required()
                        ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'Published' => 'Published',
                        'Draft' => 'Draft',
                    ])
                    ->required(),

                FileUpload::make('file_path')
                    ->label('Upload File')
                    ->directory('documents')
                    ->disk('public')
                    ->preserveFilenames()
                    ->downloadable()
                    ->multiple(false)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'image/*',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('type')
                    ->label('File Type')
                    ->default(function ($get) {
                        $path = $get('file_path');

                        if (is_array($path)) {
                            $path = $path[0] ?? null;
                        }

                        return $path ? pathinfo($path, PATHINFO_EXTENSION) : null;
                    })
                    ->dehydrated(),

                Forms\Components\TextInput::make('uploaded_by')
                    ->default(fn() => Filament::auth()->user()?->name)

                    ->disabled()
                    ->dehydrated(),

                Forms\Components\DatePicker::make('uploaded_at')
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('category'),
                TextColumn::make('type'),
                TextColumn::make('uploaded_by'),
                TextColumn::make('uploaded_at')->date(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'Published',
                        'warning' => 'Draft',
                    ]),
                TextColumn::make('division.name')
                    ->searchable()
                    ->sortable(),

                ViewColumn::make('download')
                    ->label('Download')
                    ->view('tables.columns.download-link')

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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
