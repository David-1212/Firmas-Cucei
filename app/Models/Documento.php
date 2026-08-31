<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    protected $fillable = [
        'alumno_id',
        'tipo_documento_id',
        'folio',
        'observaciones',
        'estado',
        'fecha',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function firmas()
    {
        return $this->hasMany(Firma::class);
    }

    public function firma()
    {
        return $this->hasOne(Firma::class)->latestOfMany();
    }

    public function scopeBuscar($query, $termino)
    {
        $termino = trim($termino);
        if ($termino === '') {
            return $query;
        }

        return $query->where(function ($q) use ($termino) {
            $q->where('folio', 'like', "%{$termino}%")
                ->orWhere('observaciones', 'like', "%{$termino}%")
                ->orWhereHas('alumno', function ($a) use ($termino) {
                    $a->where('codigo', 'like', "%{$termino}%")
                        ->orWhere('matricula', 'like', "%{$termino}%")
                        ->orWhere('nombre_completo', 'like', "%{$termino}%")
                        ->orWhere('carrera', 'like', "%{$termino}%");
                })
                ->orWhereHas('tipoDocumento', function ($t) use ($termino) {
                    $t->where('nombre', 'like', "%{$termino}%");
                });
        });
    }
}
