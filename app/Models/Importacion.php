<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Importacion extends Model
{
    use HasFactory;

    protected $table = 'importaciones';

    protected $fillable = [
        'user_id',
        'archivo',
        'nombre_original',
        'total_filas',
        'procesadas',
        'insertadas',
        'duplicadas',
        'errores',
        'ultimos_codigos',
        'duplicados_detalle',
        'errores_detalle',
        'estado',
        'mensaje_error',
    ];

    protected $casts = [
        'ultimos_codigos' => 'array',
        'duplicados_detalle' => 'array',
        'errores_detalle' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
