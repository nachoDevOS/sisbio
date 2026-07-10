<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Nombre visible del equipo
            $table->string('ip'); // IP del biométrico en la LAN
            $table->unsignedInteger('puerto')->default(4370); // Puerto TCP ZKTeco
            $table->unsignedInteger('comm_key')->default(0); // COMM key / password del equipo
            $table->string('ubicacion')->nullable(); // Ubicación física (ej. "Puerta principal")
            $table->string('algoritmo')->nullable(); // Firma de algoritmo (plataforma + firmware) para compatibilidad de huella
            $table->boolean('es_master')->default(false); // Equipo maestro/origen de huellas
            $table->boolean('en_linea')->default(false); // Último estado de conexión conocido
            $table->timestamp('ultima_sync')->nullable(); // Última vez que se conectó/sincronizó
            $table->boolean('activo')->default(true); // Si participa en la sincronización
            $table->timestamps();

            $table->unique(['ip', 'puerto']); // Evita registrar el mismo equipo dos veces
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
