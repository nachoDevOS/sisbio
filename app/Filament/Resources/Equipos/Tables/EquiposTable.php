<?php

namespace App\Filament\Resources\Equipos\Tables;

use App\Exceptions\DeviceServiceException;
use App\Models\Equipo;
use App\Services\DeviceService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EquiposTable
{
    /**
     * Tabla del listado de equipos biométricos.
     *
     * Muestra el estado de cada equipo de la red. La acción "Probar conexión"
     * se agrega más adelante, cuando exista el servicio que habla con el
     * microservicio Python.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ip')
                    ->label('IP')
                    ->searchable(),
                TextColumn::make('ubicacion')
                    ->label('Ubicación')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('algoritmo')
                    ->label('Algoritmo')
                    ->placeholder('Sin detectar')
                    ->badge(),
                IconColumn::make('es_master')
                    ->label('Maestro')
                    ->boolean(),
                IconColumn::make('en_linea')
                    ->label('En línea')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('ultima_sync')
                    ->label('Última conexión')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Nunca')
                    ->sortable(),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // Fila de botones ícono (sin dropdown: no hay nada destructivo que ocultar).
                ActionGroup::make([
                    // Conecta con el equipo real a través del microservicio y
                    // actualiza su estado (en línea, algoritmo, última conexión).
                    Action::make('probar_conexion')
                        ->label('Probar conexión')
                        ->hiddenLabel()
                        ->icon('heroicon-o-signal')
                        ->color('info')
                        ->action(function (Equipo $record): void {
                            $deviceService = app(DeviceService::class);

                            try {
                                $info = $deviceService->info($record);

                                // Éxito: guardar estado en línea, algoritmo detectado y la hora.
                                $record->update([
                                    'en_linea' => true,
                                    'algoritmo' => $info['algoritmo'] ?? $record->algoritmo,
                                    'ultima_sync' => now(),
                                ]);

                                Notification::make()
                                    ->title('Equipo en línea')
                                    ->body("Conectado a «{$record->nombre}». Algoritmo: ".($info['algoritmo'] ?? 'N/D'))
                                    ->success()
                                    ->send();
                            } catch (DeviceServiceException $e) {
                                // Fallo: marcar fuera de línea y mostrar el motivo.
                                $record->update(['en_linea' => false]);

                                Notification::make()
                                    ->title('No se pudo conectar')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    // Lee las marcaciones en vivo del equipo y las muestra en un modal.
                    Action::make('ver_marcaciones')
                        ->label('Ver marcaciones')
                        ->hiddenLabel()
                        ->icon('heroicon-o-clock')
                        ->color('gray')
                        ->modalHeading(fn (Equipo $record): string => "Marcaciones de «{$record->nombre}»")
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Cerrar')
                        ->modalContent(function (Equipo $record) {
                            try {
                                $respuesta = app(DeviceService::class)->attendance($record);

                                return view('filament.equipos.marcaciones', [
                                    'marcaciones' => $respuesta['marcaciones'] ?? [],
                                    'error' => null,
                                ]);
                            } catch (DeviceServiceException $e) {
                                // Si el equipo no responde, mostramos el motivo dentro del modal.
                                return view('filament.equipos.marcaciones', [
                                    'marcaciones' => [],
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }),
                    EditAction::make()
                        ->hiddenLabel(),
                ])->buttonGroup(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
