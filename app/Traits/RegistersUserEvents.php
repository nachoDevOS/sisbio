<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
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
            if (Auth::check()) {
                $model->deleteUser_id = Auth::id();
                $model->deleteObservacion = request()->input('deleteObservacion');

                $model->save();
            }
        });
    }
}
