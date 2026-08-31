<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    use HasFactory;

    protected $table = 'alumnos';

    protected $fillable = [
        'matricula',
        'codigo',
        'nombre_completo',
        'carrera',
        'ciclo_ingreso',
        'status',
    ];

    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }

    public function firmas()
    {
        return $this->hasMany(Firma::class);
    }

    public function scopeBuscar($query, $termino)
    {
        $termino = trim($termino);
        if ($termino === '') {
            return $query;
        }

        return $query->where(function ($q) use ($termino) {
            $q->where('codigo', 'like', "%{$termino}%")
                ->orWhere('matricula', 'like', "%{$termino}%")
                ->orWhere('nombre_completo', 'like', "%{$termino}%")
                ->orWhere('carrera', 'like', "%{$termino}%")
                ->orWhere('ciclo_ingreso', 'like', "%{$termino}%")
                ->orWhere('status', 'like', "%{$termino}%");
        });
    }
}
