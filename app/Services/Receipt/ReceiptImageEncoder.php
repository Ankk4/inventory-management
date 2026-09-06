<?php

namespace App\Services\Receipt;

use RuntimeException;
use Symfony\Component\Process\Process;

class ReceiptImageEncoder
{
    public function toBase64(string $path): string
    {
        $bytes = $this->downscale($path) ?? file_get_contents($path);

        if (! is_string($bytes) || $bytes === '') {
            throw new RuntimeException('Unable to read receipt image.');
        }

        return base64_encode($bytes);
    }

    private function downscale(string $path): ?string
    {
        $maxEdge = (int) config('inventory.ollama.max_image_edge');

        if ($maxEdge < 1) {
            return null;
        }

        $size = @getimagesize($path);
        $maxPixels = $maxEdge * $maxEdge;

        if ($size !== false && ($size[0] * $size[1]) <= $maxPixels) {
            return null;
        }

        return $this->downscaleWithGd($path, $maxPixels)
            ?? $this->downscaleWithImageMagick($path, $maxPixels);
    }

    private function downscaleWithGd(string $path, int $maxPixels): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $pixels = $width * $height;

        if ($pixels <= $maxPixels) {
            imagedestroy($source);

            return null;
        }

        $scale = sqrt($maxPixels / $pixels);
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($resized, 255, 255, 255);
        imagefill($resized, 0, 0, $white);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagejpeg($resized, null, 85);
        $jpeg = ob_get_clean();

        imagedestroy($source);
        imagedestroy($resized);

        return is_string($jpeg) && $jpeg !== '' ? $jpeg : null;
    }

    private function downscaleWithImageMagick(string $path, int $maxPixels): ?string
    {
        $jpegPath = tempnam(sys_get_temp_dir(), 'ollama-img-');

        if ($jpegPath === false) {
            return null;
        }

        $outputPath = $jpegPath.'.jpg';
        @unlink($jpegPath);

        try {
            foreach (['magick', 'convert'] as $binary) {
                $process = new Process([
                    $binary,
                    $path,
                    '-background', 'white',
                    '-alpha', 'remove',
                    '-alpha', 'off',
                    '-resize', $maxPixels.'@>',
                    '-strip',
                    '-quality', '85',
                    $outputPath,
                ]);
                $process->setTimeout(30);
                $process->run();

                if ($process->isSuccessful() && is_file($outputPath) && filesize($outputPath) > 0) {
                    $bytes = file_get_contents($outputPath);

                    return is_string($bytes) && $bytes !== '' ? $bytes : null;
                }
            }
        } finally {
            @unlink($outputPath);
        }

        return null;
    }
}
