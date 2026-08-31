<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class CaptchaService
{
    /**
     * Genera una imagen captcha con una operación matemática sencilla
     * y devuelve el contenido de la imagen en PNG (base64).
     */
    public function generar(): string
    {
        $a = random_int(1, 20);
        $b = random_int(1, 9);
        $operacion = random_int(0, 2);
        $expresion = '';
        $resultado = 0;

        switch ($operacion) {
            case 0:
                $expresion = "$a + $b = ?";
                $resultado = $a + $b;
                break;
            case 1:
                $a = max($a, $b);
                $expresion = "$a - $b = ?";
                $resultado = $a - $b;
                break;
            case 2:
                $expresion = "$a x $b = ?";
                $resultado = $a * $b;
                break;
        }

        Session::put('captcha_answer', $resultado);

        $width = 220;
        $height = 60;
        $im = imagecreatetruecolor($width, $height);

        $bg = imagecolorallocate($im, 248, 250, 252);
        imagefill($im, 0, 0, $bg);

        for ($i = 0; $i < 8; $i++) {
            $lineColor = imagecolorallocate($im, random_int(150, 200), random_int(150, 200), random_int(150, 200));
            imageline($im, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $lineColor);
        }

        $textColor = imagecolorallocate($im, 30, 41, 59);
        $fontSize = 20;
        $font = $this->buscarFuente();
        imagestring($im, 5, 20, 20, $expresion, $textColor);

        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);

        return 'data:image/png;base64,' . base64_encode($data);
    }

    /**
     * Verifica la respuesta dada por el usuario.
     */
    public function verificar(string $respuesta): bool
    {
        $esperado = Session::pull('captcha_answer');
        if ($esperado === null) {
            return false;
        }
        return (int)$respuesta === (int)$esperado;
    }

    private function buscarFuente(): string
    {
        $candidatos = [
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/verdana.ttf',
            'C:/Windows/Fonts/tahoma.ttf',
        ];
        foreach ($candidatos as $c) {
            if (file_exists($c)) {
                return $c;
            }
        }
        return '';
    }
}
