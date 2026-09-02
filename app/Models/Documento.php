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

        $words = preg_split('/\s+/', $termino);
        $tieneDigitos = preg_match('/\d/', $termino);

        // Término con dígitos (folio o código de alumno): busca por PREFIJO, lo
        // que permite usar los índices B-tree (documentos.folio y alumnos.codigo).
        // Nunca se usa %...% de subcadena aquí, para no forzar un full scan.
        if ($tieneDigitos) {
            $prefijo = $this->escapeLike($termino) . '%';

            return $query->where(function ($q) use ($prefijo) {
                // Prefijo directo sobre el folio (índice en documentos.folio).
                $q->where('folio', 'like', $prefijo)
                    // O prefijo sobre el código/matricula del alumno (índice en
                    // alumnos.codigo; matricula sin índice pero rara vez usada).
                    ->orWhereHas('alumno', fn ($a) => $a->where('codigo', 'like', $prefijo)
                        ->orWhere('matricula', 'like', $prefijo));
            });
        }

        // Término solo texto (nombre de alumno, observaciones, tipo): FULLTEXT.
        // documentos(folio, observaciones) y alumnos(nombre_completo, codigo).
        $fulltextDocs = $this->fulltextTerms($words);
        $fulltextAlumno = $this->fulltextTerms($words);

        return $query->where(function ($q) use ($fulltextDocs, $fulltextAlumno) {
            $q->whereRaw('MATCH(folio, observaciones) AGAINST(? IN BOOLEAN MODE)', [$fulltextDocs]);

            $q->orWhereHas('alumno', function ($a) use ($fulltextAlumno, $fulltextDocs) {
                $a->whereRaw('MATCH(nombre_completo, codigo) AGAINST(? IN BOOLEAN MODE)', [$fulltextAlumno]);
            });
        });
    }

    private function escapeLike(string $valor): string
    {
        return addcslashes($valor, '\\%_');
    }

    private static function fulltextTerms(array $words): string
    {
        $terms = array_map(fn ($w) => $w . '*', array_diff($words, ['']));
        return implode(' ', $terms);
    }
}
