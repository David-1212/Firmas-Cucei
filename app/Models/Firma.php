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
        'formato',
        'user_id',
    ];

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

    public function getRutaStorageAttribute(): string
    {
        return public_path($this->ruta_imagen);
    }
}
