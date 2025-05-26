<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\EventResource;
use App\Models\Division;
use Faker\Core\Color;
use Filament\Widgets\Widget;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\Event;
use Filament\Forms\Components\Builder;
// use Filament\Actions\DeleteAction;
// use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions\DeleteAction;
use Saade\FilamentFullCalendar\Actions\EditAction;
use Saade\FilamentFullCalendar\Data\EventData;

class Calendarwidget extends FullCalendarWidget
{
    //protected static string $view = 'filament.widgets.calendarwidget';

     public Model | string | null $model = Event::class;

    public function fetchEvents(array $fetchInfo): array
    {
        return Event::query()
         ->when($this->filterData['division_id'] ?? null, function (Builder $query, $divisionId) {
                $query->where('division_id', $divisionId);
            })
            ->where('starts_at', '>=', $fetchInfo['start'])
            ->where('ends_at', '<=', $fetchInfo['end'])
            ->get()
            ->map(
                fn (Event $event) => EventData::make()
                    ->id($event->id)
                    ->title($event->name)
                    ->start($event->starts_at)
                    ->end($event->ends_at)
                    ->url(
                        url: EventResource::getUrl(name: 'view', parameters: ['record' => $event]),
                        shouldOpenUrlInNewTab: true
                    )
                    
            )
            ->toArray();
    }

    public function getFormSchema(): array
    {
        return [
            Select::make('division_id')
                ->label('Filter by Divisi')
                ->placeholder('Semua Divisi')
                ->options(Division::pluck('name', 'id')->toArray())
                ->default(null), // Defaultnya tidak memfilter
            TextInput::make('name'),
            TextInput::make('description'),
            Grid::make()
                ->schema([
                    DateTimePicker::make('starts_at'),
 
                    DateTimePicker::make('ends_at'),
                ]),
        ];
    }

    protected function modalActions(): array
 {
     return [
         EditAction::make()
             ->mountUsing(
                 function (Event $record, Form $form, array $arguments) {
                     $form->fill([
                         'name' => $record->name,
                         'description' => $record->description,
                         'starts_at' => $arguments['event']['start'] ?? $record->starts_at,
                         'ends_at' => $arguments['event']['end'] ?? $record->ends_at
                     ]);
                 }
             ),
         DeleteAction::make(),
     ];
 }

}
