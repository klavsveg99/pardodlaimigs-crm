<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Server-side image optimisation for CRM uploads.
 *
 * Downscales images to a max dimension (1920px longest side), fixes EXIF
 * orientation, strips metadata by re-encoding and (re)generates a small
 * preview thumbnail (`thumb-<name>`) next to the source file so the admin
 * gallery never has to load full resolution images.
 */
class ImageOptimizer
{
    public const MAX_DIMENSION = 1920;

    public const JPEG_QUALITY = 85;

    public const WEBP_QUALITY = 85;

    public const PNG_COMPRESSION = 6;

    public const THUMB_WIDTH = 400;

    public const THUMB_HEIGHT = 300;

    /**
     * Optimise an image file in place.
     *
     * @return array{
     *     optimized: bool,
     *     changed: bool,
     *     width: int,
     *     height: int,
     *     size: int,
     *     thumb: bool,
     * }
     */
    public function optimize(string $path, int $maxDim = self::MAX_DIMENSION, bool $dryRun = false): array
    {
        if (! is_file($path)) {
            return $this->noOp($path);
        }

        $info = @getimagesize($path);
        if ($info === false) {
            return $this->noOp($path);
        }

        [$width, $height, $type] = $info;

        // Animated/GIF and exotic formats are left untouched to avoid
        // destroying animation or losing data.
        if (! in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return $this->noOp($path);
        }

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => null,
        };

        if (! $src) {
            return $this->noOp($path);
        }

        $dst = null;

        try {
            if ($type === IMAGETYPE_JPEG) {
                $src = $this->applyExifOrientation($src, $path);
            }

            $srcW = imagesx($src);
            $srcH = imagesy($src);
            $outW = $srcW;
            $outH = $srcH;

            if ($srcW > $maxDim || $srcH > $maxDim) {
                $ratio = min($maxDim / $srcW, $maxDim / $srcH);
                $outW = max(1, (int) round($srcW * $ratio));
                $outH = max(1, (int) round($srcH * $ratio));
            }

            $dst = imagecreatetruecolor($outW, $outH);
            if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $outW, $outH, $transparent);
            }

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $outW, $outH, $srcW, $srcH);

            $encoded = $this->encode($type, $dst);
            if ($encoded === null) {
                return $this->noOp($path);
            }

            $sizeBefore = (int) @filesize($path);

            if ($dryRun) {
                $changed = $outW !== $srcW || $outH !== $srcH || strlen($encoded) !== $sizeBefore;

                return [
                    'optimized' => true,
                    'changed' => $changed,
                    'width' => $outW,
                    'height' => $outH,
                    'size' => strlen($encoded),
                    'thumb' => false,
                ];
            }

            $this->writeAtomic($path, $encoded);
            $sizeAfter = (int) @filesize($path);

            $thumb = $this->makeThumbnail($path, $type, $outW, $outH);

            return [
                'optimized' => true,
                'changed' => $outW !== $srcW || $outH !== $srcH || $sizeAfter !== $sizeBefore,
                'width' => $outW,
                'height' => $outH,
                'size' => $sizeAfter,
                'thumb' => $thumb,
            ];
        } finally {
            imagedestroy($src);
            if ($dst) {
                imagedestroy($dst);
            }
        }
    }

    /**
     * @return array{optimized: bool, changed: bool, width: int, height: int, size: int, thumb: bool}
     */
    private function noOp(string $path): array
    {
        return [
            'optimized' => false,
            'changed' => false,
            'width' => 0,
            'height' => 0,
            'size' => (int) @filesize($path),
            'thumb' => is_file($this->thumbPath($path)),
        ];
    }

    private function applyExifOrientation(\GdImage $src, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $src;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        return match ($orientation) {
            1 => $src,
            2 => $this->flip($src, true, false),
            3 => $this->rotateOr($src, 180),
            4 => $this->flip($src, false, true),
            5 => $this->flip($this->rotateOr($src, 90), true, false),
            6 => $this->rotateOr($src, -90),
            7 => $this->flip($this->rotateOr($src, -90), true, false),
            8 => $this->rotateOr($src, 90),
            default => $src,
        };
    }

    private function rotateOr(\GdImage $src, int $angle): \GdImage
    {
        $rotated = @imagerotate($src, $angle, 0);

        if ($rotated === false) {
            return $src;
        }

        imagedestroy($src);

        return $rotated;
    }

    private function flip(\GdImage $src, bool $horizontal, bool $vertical): \GdImage
    {
        if (! $horizontal && ! $vertical) {
            return $src;
        }

        $w = imagesx($src);
        $h = imagesy($src);

        $dst = imagecreatetruecolor($w, $h);
        if (imagealphablending($dst, false) && imagesavealpha($dst, true)) {
            imagefilledrectangle($dst, 0, 0, $w, $h, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        }

        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $sx = $horizontal ? $w - $x - 1 : $x;
                $sy = $vertical ? $h - $y - 1 : $y;
                imagesetpixel($dst, $x, $y, imagecolorat($src, $sx, $sy));
            }
        }

        imagedestroy($src);

        return $dst;
    }

    private function encode(int $type, \GdImage $image, int $scaleQuality = 0): ?string
    {
        ob_start();
        try {
            $ok = match ($type) {
                IMAGETYPE_JPEG => imagejpeg($image, null, $scaleQuality > 0 ? $scaleQuality : self::JPEG_QUALITY),
                IMAGETYPE_PNG => imagepng($image, null, self::PNG_COMPRESSION),
                IMAGETYPE_WEBP => imagewebp($image, null, $scaleQuality > 0 ? $scaleQuality : self::WEBP_QUALITY),
                default => false,
            };
        } finally {
            $data = (string) ob_get_contents();
            ob_end_clean();
        }

        return $ok ? $data : null;
    }

    private function makeThumbnail(string $path, int $type, int $width, int $height): bool
    {
        $thumb = $this->thumbPath($path);

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => null,
        };

        if (! $src) {
            return false;
        }

        $dst = null;

        try {
            if ($width <= self::THUMB_WIDTH && $height <= self::THUMB_HEIGHT) {
                if (is_file($thumb)) {
                    @unlink($thumb);
                }

                return false;
            }

            $ratio = min(self::THUMB_WIDTH / $width, self::THUMB_HEIGHT / $height, 1);
            $tw = max(1, (int) round($width * $ratio));
            $th = max(1, (int) round($height * $ratio));

            $dst = imagecreatetruecolor($tw, $th);
            if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $tw, $th, $transparent);
            }

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $width, $height);

            $quality = match ($type) {
                IMAGETYPE_JPEG => 82,
                IMAGETYPE_WEBP => 82,
                default => 6,
            };
            $encoded = $this->encode($type, $dst, $quality);
            if ($encoded === null) {
                return false;
            }

            $this->writeAtomic($thumb, $encoded);

            return true;
        } finally {
            imagedestroy($src);
            if ($dst) {
                imagedestroy($dst);
            }
        }
    }

    private function thumbPath(string $path): string
    {
        return dirname($path).DIRECTORY_SEPARATOR.'thumb-'.basename($path);
    }

    private function writeAtomic(string $path, string $data): void
    {
        $dir = dirname($path);
        $tmp = $dir.DIRECTORY_SEPARATOR.'.pdc-opt-'.bin2hex(random_bytes(6)).'.tmp';

        if (@file_put_contents($tmp, $data) === false) {
            throw new RuntimeException('Unable to write optimized image.');
        }

        if (! @rename($tmp, $path)) {
            @unlink($tmp);
            if (@file_put_contents($path, $data) === false) {
                throw new RuntimeException('Unable to write optimized image.');
            }
        }
    }
}
