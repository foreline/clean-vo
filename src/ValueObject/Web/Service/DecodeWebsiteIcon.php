<?php
declare(strict_types=1);

namespace Domain\ValueObject\Web\Service;

use Domain\ValueObject\Web\WebsiteIcon;
use RuntimeException;

/** Проверка и преобразование favicon в небольшое статическое PNG. @noCodeGen */
class DecodeWebsiteIcon
{
    public function decode(string $bytes): WebsiteIcon
    {
        if (!function_exists('imagecreatefromstring')) {
            throw new RuntimeException('Decoding website icons requires ext-gd.');
        }
        if (524288 < strlen($bytes)) {
            throw new RuntimeException('Иконка сайта слишком велика.');
        }
        if (str_starts_with($bytes, "\x00\x00\x01\x00")) {
            $bytes = $this->decodeIco($bytes);
        }
        $size = @getimagesizefromstring($bytes);
        if (false === $size || !in_array($size[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP, IMAGETYPE_BMP], true)
            || 0 >= $size[0] || 0 >= $size[1] || 1024 < $size[0] || 1024 < $size[1]) {
            throw new RuntimeException('Формат иконки сайта не поддерживается.');
        }
        $source = @imagecreatefromstring($bytes);
        if (false === $source) { throw new RuntimeException('Иконка сайта повреждена.'); }
        try {
            $scale = min(1, 64 / max($size[0], $size[1]));
            $width = max(1, (int) round($size[0] * $scale));
            $height = max(1, (int) round($size[1] * $scale));
            $image = imagecreatetruecolor($width, $height);
            try {
                imagealphablending($image, false);
                imagesavealpha($image, true);
                imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
                imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
                ob_start();
                try {
                    imagepng($image);
                    $png = ob_get_contents();
                } finally { ob_end_clean(); }
                return new WebsiteIcon($png);
            } finally { imagedestroy($image); }
        } finally { imagedestroy($source); }
    }

    /** Извлечь PNG или несжатый DIB 24/32 bpp из контейнера ICO. */
    private function decodeIco(string $bytes): string
    {
        if (6 > strlen($bytes)) { throw new RuntimeException('Повреждён заголовок ICO.'); }
        $count = unpack('v', substr($bytes, 4, 2))[1];
        if (1 > $count || 64 < $count || 6 + 16 * $count > strlen($bytes)) {
            throw new RuntimeException('Повреждён каталог ICO.');
        }
        $candidates = [];
        for ($i = 0; $i < $count; ++$i) {
            $entry = unpack('Cwidth/Cheight/Ccolors/Creserved/vplanes/vbits/Vsize/Voffset', substr($bytes, 6 + 16 * $i, 16));
            if (6 + 16 * $count > $entry['offset'] || 0 === $entry['size']
                || $entry['offset'] + $entry['size'] > strlen($bytes)) { continue; }
            $payload = substr($bytes, $entry['offset'], $entry['size']);
            if (str_starts_with($payload, "\x89PNG\r\n\x1a\n")) {
                $candidates[$entry['width'] ?: 256] = $payload;
                continue;
            }
            if (40 > strlen($payload)) { continue; }
            $dib = unpack('Vheader/Vwidth/Vheight/vplanes/vbits/Vcompression', substr($payload, 0, 20));
            $width = $entry['width'] ?: 256;
            $height = $entry['height'] ?: 256;
            if (40 !== $dib['header'] || $width !== $dib['width'] || $height * 2 !== $dib['height']
                || 1 !== $dib['planes'] || !in_array($dib['bits'], [24, 32], true) || 0 !== $dib['compression']) { continue; }
            $pixels = (int) (ceil($width * $dib['bits'] / 32) * 4) * $height;
            if (40 + $pixels > strlen($payload)) { continue; }
            $dibBytes = substr_replace(substr($payload, 0, 40 + $pixels), pack('V', $height), 8, 4);
            $candidates[$width] = 'BM' . pack('VvvV', 14 + strlen($dibBytes), 0, 0, 54) . $dibBytes;
        }
        if ([] === $candidates) { throw new RuntimeException('В ICO нет поддерживаемого изображения.'); }
        krsort($candidates);
        return reset($candidates);
    }
}
