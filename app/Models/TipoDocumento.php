<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'sistema',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'sistema' => 'boolean',
    ];

    /** Tipos fijos creados por el sistema y que no pueden eliminarse ni desactivarse. */
    public function scopeFijos($query)
    {
        return $query->where('sistema', true);
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
