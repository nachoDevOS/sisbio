<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eliminación lógica de roles: App\Models\Role usa SoftDeletes, así el
     * borrado de un rol solo marca deleted_at (coherente con el resto del
     * sistema, donde todo el borrado es lógico).
     */
    public function up(): void
    {
        Schema::table($this->tabla(), function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table($this->tabla(), function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }

    private function tabla(): string
    {
        return config('permission.table_names.roles', 'roles');
    }
};
