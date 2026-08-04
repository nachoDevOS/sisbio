<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * Auditoría de alta y baja en el propio modelo: quién registró la fila y, al
 * eliminarla, quién la dio de baja y por qué.
 *
 * El motivo llega como `deleteObservacion` desde el modal global de
 * eliminación. Ningún controlador toca estas columnas.
 */
trait RegistersUserEvents
{
    protected static function bootRegistersUserEvents(): void
    {
        static::creating(function (Model $model): void {
            if (Auth::check()) {
                $model->registerUser_id = Auth::id();
            }
        });

        static::deleting(function (Model $model): void {
            if (! Auth::check()) {
                return;
            }

            // Las columnas de auditoría de baja acompañan a la eliminación
            // lógica: viven en las tablas cuyo modelo usa SoftDeletes. En las
            // que no —`asistencias`, donde no se dan de baja marcaciones—, el
            // borrado es físico y no hay dónde guardar quién ni por qué;
            // escribirlas igual reventaría con «Unknown column».
            if (! in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                return;
            }

            $model->deleteUser_id = Auth::id();
            $model->deleteObservacion = request()->input('deleteObservacion');

            $model->save();
        });
    }
}
