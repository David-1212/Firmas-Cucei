<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('archivo')->nullable();
            $table->string('nombre_original')->nullable();
            $table->integer('total_filas')->default(0);
            $table->integer('procesadas')->default(0);
            $table->integer('insertadas')->default(0);
            $table->integer('duplicadas')->default(0);
            $table->integer('errores')->default(0);
            $table->enum('estado', ['pendiente', 'procesando', 'completado', 'fallido', 'cancelado'])->default('pendiente')->index();
            $table->text('mensaje_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones');
    }
};
