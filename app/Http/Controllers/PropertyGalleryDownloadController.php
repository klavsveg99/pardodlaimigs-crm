<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\CrmProperty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * "Lejupielādēt visus" — visu īpašuma galerijas attēlu ZIP arhīvs.
 *
 * Attēli tiek pārkodēti (samazināts izmērs līdz 2560px, JPEG kvalitāte 80),
 * lai arhīvs būtu mazāks pirms ievietošanas ZIP; pārējie faili tiek pievienoti
 * bez izmaiņām.
 */
class PropertyGalleryDownloadController extends Controller
{
    private const MAX_DIMENSION = 2560;

    private const JPEG_QUALITY = 80;

    public function download(Request $request, string $propertySlug): BinaryFileResponse
    {
        $property = CrmProperty::query()->where('slug', $propertySlug)->first();
        abort_if(! $property, 404);

        $user = $request->user();
        abort_unless($user->can('manage') || $property->owner_user_id === $user->id, 403);

        $attachments = $property->attachments()
            ->where('collection', 'gallery')
            ->orderBy('sort_order')
            ->get();

        abort_if($attachments->isEmpty(), 404, 'Galerijā nav attēlu.');

        if (! class_exists(\ZipArchive::class)) {
            abort(500, 'ZIP paplašinājums nav pieejams.');
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'pdc-gallery-');
        if ($zipPath === false) {
            abort(500, 'Neizdevās izveidot arhīvu.');
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($zipPath);
            abort(500, 'Neizdevās atvērt arhīvu.');
        }

        $used = [];

        foreach ($attachments as $attachment) {
            $name = $this->uniqueName($this->archiveName($attachment), $used);
            $data = $this->compressedContents($attachment);

            if ($data !== null) {
                $zip->addFromString($name, $data);
            } elseif ($this->isLocal($attachment)) {
                $absolute = Storage::disk($attachment->disk)->path($attachment->path);
                if (is_file($absolute)) {
                    $zip->addFile($absolute, $name);
                }
            }
        }

        $zip->close();

        $filename = ($property->slug ?: 'ipasums').'-galerija.zip';

        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }

    private function isLocal(Attachment $attachment): bool
    {
        return ! str_starts_with($attachment->path, 'http://')
            && ! str_starts_with($attachment->path, 'https://');
    }

    /**
     * Pārkodēts attēls (JPEG/PNG/WEBP) vai null, ja failu nevar apstrādāt
     * (tad to pievieno oriģinālā veidā).
     */
    private function compressedContents(Attachment $attachment): ?string
    {
        if (! $this->isLocal($attachment)) {
            return null;
        }

        try {
            $absolute = Storage::disk($attachment->disk)->path($attachment->path);
        } catch (\Throwable) {
            return null;
        }

        if (! is_file($absolute)) {
            return null;
        }

        $info = @getimagesize($absolute);
        if ($info === false) {
            return null;
        }

        [$width, $height, $type] = $info;
        if (! in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absolute),
            IMAGETYPE_PNG => @imagecreatefrompng($absolute),
            IMAGETYPE_WEBP => @imagecreatefromwebp($absolute),
            default => null,
        };

        if (! $src) {
            return null;
        }

        try {
            $ratio = min(1, self::MAX_DIMENSION / max($width, $height));
            $outW = max(1, (int) round($width * $ratio));
            $outH = max(1, (int) round($height * $ratio));

            $dst = imagecreatetruecolor($outW, $outH);
            if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $outW, $outH, $transparent);
            }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $outW, $outH, $width, $height);

            ob_start();
            try {
                $ok = match ($type) {
                    IMAGETYPE_JPEG => imagejpeg($dst, null, self::JPEG_QUALITY),
                    IMAGETYPE_PNG => imagepng($dst, null, 7),
                    IMAGETYPE_WEBP => imagewebp($dst, null, self::JPEG_QUALITY),
                    default => false,
                };
            } finally {
                $encoded = (string) ob_get_contents();
                ob_end_clean();
                imagedestroy($dst);
            }

            return $ok ? $encoded : null;
        } finally {
            imagedestroy($src);
        }
    }

    private function archiveName(Attachment $attachment): string
    {
        $name = basename((string) $attachment->original_name);

        return $name !== '' ? $name : ('attels-'.$attachment->id.'.jpg');
    }

    /**
     * @param  array<string, bool>  $used
     */
    private function uniqueName(string $name, array &$used): string
    {
        $dir = pathinfo($name, PATHINFO_FILENAME);
        $ext = pathinfo($name, PATHINFO_EXTENSION);

        $candidate = $name;
        $i = 1;
        while (isset($used[$candidate])) {
            $candidate = $dir.'-'.$i.($ext !== '' ? '.'.$ext : '');
            $i++;
        }

        $used[$candidate] = true;

        return $candidate;
    }
}
