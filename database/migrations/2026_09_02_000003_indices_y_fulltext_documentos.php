<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            // El listado ordena por created_at desc: sin índice esto hace
            // filesort sobre toda la tabla al crecer.
            $table->index('created_at');

            // Para los filtros combinados con ordenación (muy frecuentes).
            $table->index(['estado', 'created_at']);
            $table->index(['tipo_documento_id', 'created_at']);

            // Búsqueda por texto libre (folio/observaciones) con FULLTEXT.
            $table->fullText(['folio', 'observaciones']);
        });
    }

    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropFullText(['folio', 'observaciones']);
            $table->dropIndex(['tipo_documento_id', 'created_at']);
            $table->dropIndex(['estado', 'created_at']);
            $table->dropIndex(['created_at']);
        });
    }
};
