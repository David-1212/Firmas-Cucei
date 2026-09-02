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

    public function importaciones()
    {
        return $this->belongsToMany(Importacion::class, 'alumno_importacion');
    }

    public function ultimoCiclo(): ?string
    {
        return $this->importaciones()
            ->whereNotNull('importaciones.ciclo')
            ->where('importaciones.ciclo', '!=', '')
            ->latest('importaciones.id')
            ->value('importaciones.ciclo');
    }

    public function scopeBuscar($query, $termino)
    {
        $termino = trim($termino);
        if ($termino === '') {
            return $query;
        }

        $words = preg_split('/\s+/', $termino);
        $esFrase = count($words) > 1;

        // Un término de una sola palabra se trata como BÚSQUEDA DE CÓDIGO si
        // contiene dígitos, aprovechando el índice B-tree de alumnos.codigo.
        // Solo se filtra por codigo (prefijo): añadir OR sobre matricula u otras
        // columnas hacía que el optimizador dejara de usar el índice y escaneara
        // todo el pivot (~137k filas), pasando de ~3 ms a ~800 ms.
        if (!$esFrase && preg_match('/\d/', $termino)) {
            return $query->where('codigo', 'like', $this->escapeLike($termino) . '%');
        }

        // Frases o términos solo-letras -> FULLTEXT sobre nombre/codigo (rápido).
        $fulltext = self::fulltextTerms($words);
        if ($fulltext !== '') {
            return $query->whereRaw(
                'MATCH(nombre_completo, codigo) AGAINST(? IN BOOLEAN MODE)',
                [$fulltext]
            );
        }

        return $query->where('codigo', 'like', $this->escapeLike($termino) . '%');
    }

    private function escapeLike(string $valor): string
    {
        return addcslashes($valor, '\\%_');
    }

    private static function fulltextTerms(array $words): string
    {
        $clean = [];
        foreach ($words as $w) {
            $sanitized = preg_replace('/[^\p{L}\p{N}]/u', '', $w);
            if ($sanitized !== '' && mb_strlen($sanitized) >= 2) {
                $clean[] = "{$sanitized}*";
            }
        }
        return implode(' ', $clean);
    }
}
