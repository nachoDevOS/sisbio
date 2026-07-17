<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use App\Models\Sia\Asistencia;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Todas las marcaciones de un funcionario, en una tabla nativa de Filament
 * (dentro del panel, con su sidebar). La tabla Asistencia tiene millones de
 * filas: la consulta siempre se acota al IdPersona del funcionario.
 */
class MarcacionesPersona extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = PersonaResource::class;

    protected string $view = 'filament.resources.personas.pages.marcaciones';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        $nombre = $this->record->nombre_completo ?: 'CI '.trim($this->record->IdPersona);

        return "Marcaciones · {$nombre}";
    }

    public function getSubheading(): ?string
    {
        return 'Registros de asistencia del funcionario en el SIA.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')
                ->label('Volver a la ficha')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(PersonaResource::getUrl('view', ['record' => $this->record])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Asistencia::query()->where('IdPersona', $this->record->IdPersona)
            )
            ->columns([
                TextColumn::make('Fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('Hora')
                    ->label('Hora')
                    ->time('H:i:s'),
                TextColumn::make('Tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match (trim($state)) {
                        'R' => 'Reloj',
                        'M' => 'Manual',
                        default => trim($state),
                    }),
            ])
            ->filters([
                Filter::make('rango')
                    ->schema([
                        DatePicker::make('desde')
                            ->label('Desde')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('hasta')
                            ->label('Hasta')
                            ->default(now()),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, string $desde) => $q->whereDate('Fecha', '>=', $desde))
                        ->when($data['hasta'] ?? null, fn (Builder $q, string $hasta) => $q->whereDate('Fecha', '<=', $hasta)))
                    ->columns(2),
            ])
            // Asistencia no tiene clave primaria simple: sin esto Filament
            // ordena por una PK inexistente y revienta el ORDER BY.
            ->defaultKeySort(false)
            ->defaultSort('Fecha', 'desc')
            ->paginated([25, 50, 100])
            ->emptyStateHeading('Sin marcaciones en el rango seleccionado');
    }

    /**
     * La tabla Asistencia tiene clave primaria compuesta (IdPersona, Fecha,
     * Hora): la clave de fila se arma con esos tres campos.
     */
    public function getTableRecordKey(Model|array $record): string
    {
        if ($record instanceof Asistencia) {
            return implode('|', [
                trim((string) $record->IdPersona),
                $record->Fecha?->format('Ymd') ?? '',
                $record->Hora?->format('His') ?? '',
            ]);
        }

        return parent::getTableRecordKey($record);
    }

    public function render(): View
    {
        return view($this->view);
    }
}
