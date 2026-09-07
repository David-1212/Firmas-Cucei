<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Firma extends Model
{
    use HasFactory;

    protected $fillable = [
        'alumno_id',
        'documento_id',
        'ruta_imagen',
        'imagen',
        'formato',
        'user_id',
    ];

    /**
     * Fuente de la imagen para el <img>.
     * Prioriza la imagen guardada en base de datos (base64); para firmas
     * antiguas sin imagen, cae en la ruta pública del archivo.
     */
    public function getImagenSrcAttribute(): string
    {
        if ($this->imagen) {
            return 'data:image/' . ($this->formato ?: 'png') . ';base64,' . $this->imagen;
        }

        return $this->ruta_imagen ? asset($this->ruta_imagen) : '';
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class);
    }

    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
