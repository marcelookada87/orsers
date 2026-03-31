<?php
/**
 * ImageCompressor — redimensiona e comprime imagens para ≤ MAX_IMAGE_SIZE_KB
 */
class ImageCompressor
{
    private static array $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    public static function isAllowed(string $mime): bool
    {
        return isset(self::$allowedMimes[$mime]);
    }

    /**
     * Processa o upload, redimensiona e comprime para ≤ MAX_IMAGE_SIZE_KB
     * Retorna array com: arquivo, tamanho_kb, largura, altura
     */
    public static function process(array $file, string $destDir): array
    {
        if (!function_exists('imagecreatefromjpeg')) {
            throw new RuntimeException('Extensão GD não está habilitada no PHP.');
        }

        $name = $file['name'] ?? '';
        $mime = self::detectMime($file['tmp_name'], is_string($name) ? $name : '');
        if (!self::isAllowed($mime)) {
            throw new RuntimeException("Tipo de imagem não permitido: {$mime}");
        }

        $image = self::createFromFile($file['tmp_name'], $mime);
        if (!$image) {
            throw new RuntimeException('Falha ao carregar a imagem.');
        }

        $origW = imagesx($image);
        $origH = imagesy($image);
        $maxDim = IMAGE_MAX_DIMENSION;

        [$newW, $newH] = self::calcDimensions($origW, $origH, $maxDim);

        if ($newW !== $origW || $newH !== $origH) {
            $resized = imagecreatetruecolor($newW, $newH);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($image);
            $image = $resized;
        }

        $filename  = uniqid('img_', true) . '.jpg';
        $destPath  = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        $maxBytes  = MAX_IMAGE_SIZE_KB * 1024;
        $quality   = 85;

        do {
            imagejpeg($image, $destPath, $quality);
            $size  = filesize($destPath);
            $quality -= 5;
        } while ($size > $maxBytes && $quality >= 20);

        imagedestroy($image);

        return [
            'arquivo'    => $filename,
            'tamanho_kb' => (int)round(filesize($destPath) / 1024),
            'largura'    => $newW,
            'altura'     => $newH,
        ];
    }

    private static function createFromFile(string $path, string $mime): \GdImage|false
    {
        return match ($mime) {
            'image/png'           => imagecreatefrompng($path),
            'image/webp'          => imagecreatefromwebp($path),
            'image/gif'           => imagecreatefromgif($path),
            default               => imagecreatefromjpeg($path),
        };
    }

    /**
     * MIME do upload (finfo > mime_content_type > extensão do nome).
     */
    private static function detectMime(string $tmpPath, string $originalName = ''): string
    {
        if (is_file($tmpPath) && class_exists('finfo')) {
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $m  = $fi->file($tmpPath);
            if (is_string($m) && $m !== '' && $m !== 'application/octet-stream') {
                return $m;
            }
        }
        if (function_exists('mime_content_type')) {
            $m = @mime_content_type($tmpPath);
            if (is_string($m) && $m !== '' && $m !== 'application/octet-stream') {
                return $m;
            }
        }
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $map = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif', 'webp' => 'image/webp',
        ];

        return $map[$ext] ?? 'application/octet-stream';
    }

    private static function calcDimensions(int $w, int $h, int $max): array
    {
        if ($w <= $max && $h <= $max) return [$w, $h];
        if ($w >= $h) {
            return [$max, (int)round($h * ($max / $w))];
        }
        return [(int)round($w * ($max / $h)), $max];
    }
}
