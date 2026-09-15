<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Process\Process;

/**
 * PDF optimisation for CRM uploads using Ghostscript.
 *
 * Re-writes the PDF with /ebook image downsampling (150dpi, JPEG quality)
 * which typically cuts file size severalfold for scan/photo-heavy PDFs
 * while keeping text intact. The original is only replaced when the new
 * PDF is verifiably valid AND smaller — every other case keeps the
 * original untouched. Non-PDF documents are never handled here.
 */
class PdfOptimizer
{
    private const MAX_INPUT_BYTES = 25 * 1024 * 1024;

    /**
     * Re-compress the PDF in place.
     *
     * @return int|null new size, or null when unchanged / skipped / failed
     */
    public function optimize(string $path): ?int
    {
        if (! is_file($path) || @filesize($path) > self::MAX_INPUT_BYTES) {
            return null;
        }

        // Never trust the saved mime alone — check the actual PDF magic bytes
        // so mislabelled docx/images can't be fed into Ghostscript.
        $fh = @fopen($path, 'rb');
        if (! $fh) {
            return null;
        }
        $magic = (string) fread($fh, 5);
        fclose($fh);

        if ($magic !== '%PDF-') {
            return null;
        }

        $out = dirname($path).DIRECTORY_SEPARATOR.'.pdc-pdf-'.bin2hex(random_bytes(6));

        try {
            $binary = $this->ghostscriptBinary();
            if ($binary === null) {
                return null;
            }

            $command = [
                $binary, '-q', '-dNOPAUSE', '-dBATCH', '-dSAFER',
                '-sDEVICE=pdfwrite', '-dCompatibilityLevel=1.5',
                '-dPDFSETTINGS=/ebook', '-dDetectDuplicateImages=true',
                '-dAutoRotatePages=/None',
                '-sOutputFile='.$out, $path,
            ];

            $process = new Process($command);
            $process->setTimeout(60);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($out)) {
                return null;
            }

            $outSize = (int) @filesize($out);

            // Only overwrite if the output is a valid, non-trivial, smaller PDF.
            if ($outSize < 1024 || $outSize >= (int) @filesize($path) || ! $this->isPdf($out)) {
                return null;
            }

            if (! @rename($out, $path)) {
                if (@copy($out, $path) === false) {
                    return null;
                }
                @unlink($out);
            }

            return $outSize;
        } catch (Throwable) {
            return null;
        } finally {
            @unlink($out);
        }
    }

    private function ghostscriptBinary(): ?string
    {
        $which = new Process(['which', 'gs']);
        $which->setTimeout(10);
        $which->run();

        if (! $which->isSuccessful()) {
            return null;
        }

        return trim($which->getOutput()) ?: null;
    }

    private function isPdf(string $path): bool
    {
        $fh = @fopen($path, 'rb');
        if (! $fh) {
            return false;
        }
        $head = (string) fread($fh, 8);
        fclose($fh);

        if (! str_starts_with($head, '%PDF-')) {
            return false;
        }

        // Valid PDFs end with %%EOF; rewrites by Ghostscript always include it.
        $tail = (string) @file_get_contents($path, false, null, max(0, (int) @filesize($path) - 32), 32);

        return str_contains($tail, '%%EOF');
    }
}
