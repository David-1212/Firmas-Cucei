<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipo_documentos', function (Blueprint $table) {
            $table->boolean('sistema')->default(false)->after('activo');
        });

        // Tipo fijo del sistema, no se puede eliminar ni desactivar.
        DB::table('tipo_documentos')->updateOrInsert(
            ['nombre' => 'Credencial'],
            [
                'descripcion' => 'Credencial de estudiante',
                'activo' => true,
                'sistema' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::table('tipo_documentos', function (Blueprint $table) {
            $table->dropColumn('sistema');
        });
    }
};