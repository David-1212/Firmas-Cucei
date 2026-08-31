<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->foreignId('tipo_documento_id')->constrained('tipo_documentos');
            $table->string('folio')->nullable()->index();
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['pendiente', 'firmado', 'entregado'])->default('pendiente')->index();
            $table->dateTime('fecha')->nullable()->comment('Fecha y hora de generación del documento');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('Usuario que generó el documento');
            $table->timestamps();

            $table->index(['alumno_id', 'tipo_documento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
