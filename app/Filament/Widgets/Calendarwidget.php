<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\EventResource;
use App\Models\Division;
use App\Models\Event;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions\DeleteAction;
use Saade\FilamentFullCalendar\Actions\EditAction;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class Calendarwidget extends FullCalendarWidget
{

    protected static ?int $sort = 5; // Opsional
    // // protected int | string | array $columnSpan = ; // <<< TAMBAHKAN INI

    public Model | string | null $model = Event::class;

    protected function isCurrentUserAdmin(): bool
    {
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin');
    }

    protected function getCurrentUserDivisionId(): ?int
    {
        $user = Filament::auth()->user();
        return $user->employee?->division?->id;
    }

    public function fetchEvents(array $fetchInfo): array
    {
        $user = Filament::auth()->user();
        $isAdmin = $this->isCurrentUserAdmin();
        $divisionId = $this->filterData['division_id'] ?? null;
        $userDivisionId = $this->getCurrentUserDivisionId();

        return Event::query()
            ->when(!$isAdmin, function (EloquentBuilder $query) use ($userDivisionId) {
                $query->where(function ($sub) use ($userDivisionId) {
                    $sub->where('division_id', $userDivisionId)
                        ->orWhereNull('division_id');
                });
            })
            ->when($divisionId, fn ($q) => $q->where('division_id', $divisionId))
            ->where('starts_at', '>=', $fetchInfo['start'])
            ->where('ends_at', '<=', $fetchInfo['end'])
            ->get()
            ->map(fn (Event $event) => EventData::make()
                ->id($event->id)
                ->title($event->name)
                ->start($event->starts_at)
                ->end($event->ends_at)
                ->url(EventResource::getUrl('view', ['record' => $event]), true)
            )
            ->toArray();
    }

    public function getFormSchema(): array
    {
        $isAdmin = $this->isCurrentUserAdmin();

        return [
            Select::make('division_id')
                ->label('Filter Divisi')
                ->placeholder('Semua Divisi')
                ->options(function () use ($isAdmin) {
                    $options = Division::pluck('name', 'id')->toArray();
                    return $isAdmin ? [null => 'Semua Divisi (Global)'] + $options : $options;
                })
                ->visible($isAdmin), // Hanya admin yang bisa pilih filter ini

            TextInput::make('name')->label('Nama Acara'),
            TextInput::make('description')->label('Deskripsi'),
            Grid::make()->schema([
                DateTimePicker::make('starts_at')->label('Mulai'),
                DateTimePicker::make('ends_at')->label('Selesai'),
            ]),
        ];
    }

    protected function modalActions(): array
    {
        return [
            EditAction::make()->mountUsing(function (Event $record, Form $form, array $arguments) {
                $form->fill([
                    'name' => $record->name,
                    'description' => $record->description,
                    'starts_at' => $arguments['event']['start'] ?? $record->starts_at,
                    'ends_at' => $arguments['event']['end'] ?? $record->ends_at,
                ]);
            }),
            DeleteAction::make(),
        ];
    }
}
