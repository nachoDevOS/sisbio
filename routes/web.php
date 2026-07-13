<?php

use Illuminate\Support\Facades\Route;

// La raíz manda al panel: Filament redirige al login si no hay sesión.
Route::redirect('/', '/admin');
