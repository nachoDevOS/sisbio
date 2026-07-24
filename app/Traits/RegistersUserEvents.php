<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Rellena las columnas de auditoría (quién registró / quién eliminó) al crear y
 * al eliminar un modelo. Solo escribe las columnas que existen en la tabla, para
 * no romper si un modelo las tiene parcialmente o todavía no las migró.
 */
trait RegistersUserEvents
{
    protected static function bootRegistersUserEvents(): void
    {
        static::creating(function (Model $model): void {
            if (Auth::check()) {
                $model->rellenarColumnaAuditoria('registerUser_id', Auth::id());
            }
        });

        static::deleting(function (Model $model): void {
            if (! Auth::check()) {
                return;
            }

            // En un borrado físico (forceDelete) la fila desaparece: no tiene
            // sentido grabarle quién la eliminó.
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            $model->rellenarColumnaAuditoria('deleteUser_id', Auth::id());
            $model->rellenarColumnaAuditoria('deleteObservacion', request()->input('deleteObservacion'));

            // Persiste los campos de auditoría antes de que SoftDeletes escriba
            // su propio `deleted_at` (que solo actualiza esa columna).
            if ($model->isDirty()) {
                $model->saveQuietly();
            }
        });
    }

    /**
     * Asigna el valor a la columna solo si existe en la tabla del modelo.
     */
    protected function rellenarColumnaAuditoria(string $columna, mixed $valor): void
    {
        if (in_array($columna, $this->columnasDeLaTabla(), true)) {
            $this->setAttribute($columna, $valor);
        }
    }

    /**
     * Columnas de la tabla del modelo, cacheadas por tabla durante el proceso
     * para no consultar el esquema en cada fila (importante en cargas masivas).
     *
     * @var array<string, list<string>>
     */
    protected static array $columnasPorTabla = [];

    /**
     * @return list<string>
     */
    protected function columnasDeLaTabla(): array
    {
        $clave = ($this->getConnectionName() ?? '').'.'.$this->getTable();

        return static::$columnasPorTabla[$clave] ??= Schema::connection($this->getConnectionName())
            ->getColumnListing($this->getTable());
    }
}
