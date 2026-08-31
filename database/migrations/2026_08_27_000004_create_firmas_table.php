<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->foreignId('documento_id')->constrained('documentos')->cascadeOnDelete();
            $table->string('ruta_imagen')->comment('Ruta de la imagen de la firma en storage');
            $table->string('formato', 10)->default('png');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('Usuario de ventanilla que capturó la firma');
            $table->timestamps();

            $table->index(['alumno_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firmas');
    }
};
