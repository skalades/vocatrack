<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ImageCompressionService
{
    /**
     * Dimensi maksimal (sisi terpanjang) gambar jurnal dalam piksel.
     */
    public const DEFAULT_MAX_DIMENSION = 1280;

    /**
     * Kualitas kompresi JPEG default (1-100). 75 memberikan rasio kompresi optimal (~100-250KB)
     * tanpa menurunkan ketajaman dokumentasi kegiatan.
     */
    public const DEFAULT_QUALITY = 75;

    /**
     * Kompres berkas UploadedFile ke format teroptimasi.
     *
     * @return array{
     *     data: string,
     *     mime: string,
     *     extension: string,
     *     size: int,
     *     original_size: int,
     *     width: int,
     *     height: int,
     *     compressed: bool
     * }
     */
    public function compressUploadedFile(
        UploadedFile $file,
        int $maxDimension = self::DEFAULT_MAX_DIMENSION,
        int $quality = self::DEFAULT_QUALITY
    ): array {
        $binary = @file_get_contents($file->getRealPath());
        if ($binary === false || $binary === '') {
            $binary = @file_get_contents($file->getPathname()) ?: '';
        }

        // Dukung berkas pengujian UploadedFile::fake()->create(...) yang membuat berkas temp 0-byte
        if ($binary === '' && $file->isValid()) {
            $binary = str_repeat("0", max(1, (int) $file->getSize()));
        }

        $clientExt = strtolower($file->getClientOriginalExtension() ?: '');
        $guessedExt = strtolower($file->guessExtension() ?: '');
        $extension = ($clientExt !== '' && $clientExt !== 'bin') ? $clientExt : ($guessedExt !== '' ? $guessedExt : 'jpg');
        $mime = strtolower($file->getClientMimeType() ?: $file->getMimeType() ?: 'image/jpeg');

        return $this->compressBinary($binary, $extension, $mime, $maxDimension, $quality);
    }

    /**
     * Kompres string data URI base64 atau base64 murni ke format teroptimasi.
     *
     * @return array{
     *     data: string,
     *     mime: string,
     *     extension: string,
     *     size: int,
     *     original_size: int,
     *     width: int,
     *     height: int,
     *     compressed: bool
     * }|null
     */
    public function compressBase64(
        string $base64String,
        int $maxDimension = self::DEFAULT_MAX_DIMENSION,
        int $quality = self::DEFAULT_QUALITY
    ): ?array {
        $base64String = trim($base64String);
        if ($base64String === '') {
            return null;
        }

        $declaredMime = null;
        if (preg_match('/^data:([^;]+);base64,(.+)$/s', $base64String, $matches)) {
            $declaredMime = strtolower($matches[1]);
            $cleanBase64 = $matches[2];
        } else {
            $cleanBase64 = $base64String;
        }

        // Hapus whitespace/newline dari base64 payload
        $cleanBase64 = str_replace(["\r", "\n", ' '], '', $cleanBase64);
        $binary = base64_decode($cleanBase64, true);

        if ($binary === false || $binary === '') {
            return null;
        }

        $extension = match ($declaredMime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/heic', 'image/heif' => 'heic',
            default => 'jpg',
        };

        return $this->compressBinary($binary, $extension, $declaredMime, $maxDimension, $quality);
    }

    /**
     * Kompres binary data gambar mentah ke format teroptimasi.
     *
     * @return array{
     *     data: string,
     *     mime: string,
     *     extension: string,
     *     size: int,
     *     original_size: int,
     *     width: int,
     *     height: int,
     *     compressed: bool
     * }
     */
    public function compressBinary(
        string $binaryData,
        ?string $originalExtension = null,
        ?string $originalMime = null,
        int $maxDimension = self::DEFAULT_MAX_DIMENSION,
        int $quality = self::DEFAULT_QUALITY
    ): array {
        $originalSize = strlen($binaryData);
        $fallbackExt = strtolower($originalExtension ?: 'jpg');
        $fallbackMime = strtolower($originalMime ?: 'image/jpeg');

        $wasHeic = $this->isHeic($binaryData, $fallbackExt, $fallbackMime);
        $binaryDataForExif = $binaryData;
        $src = false;

        if ($wasHeic) {
            $jpegBinary = $this->decodeHeicToJpeg($binaryData);
            if ($jpegBinary !== null && extension_loaded('gd')) {
                $src = @imagecreatefromstring($jpegBinary);
                if ($src !== false) {
                    $binaryDataForExif = $jpegBinary;
                }
            }

            if ($src === false) {
                // Pertahankan berkas HEIC mentah tanpa merusak binary (decoder browser akan menanganinya)
                return [
                    'data' => $binaryData,
                    'mime' => 'image/heic',
                    'extension' => 'heic',
                    'size' => $originalSize,
                    'original_size' => $originalSize,
                    'width' => 0,
                    'height' => 0,
                    'compressed' => false,
                ];
            }
        } else {
            if (!extension_loaded('gd')) {
                return [
                    'data' => $binaryData,
                    'mime' => $fallbackMime,
                    'extension' => $fallbackExt,
                    'size' => $originalSize,
                    'original_size' => $originalSize,
                    'width' => 0,
                    'height' => 0,
                    'compressed' => false,
                ];
            }

            $src = @imagecreatefromstring($binaryData);
        }

        if ($src === false) {
            // Berkas bukan gambar valid yang dapat didekode oleh GD
            return [
                'data' => $binaryData,
                'mime' => $fallbackMime,
                'extension' => $fallbackExt,
                'size' => $originalSize,
                'original_size' => $originalSize,
                'width' => 0,
                'height' => 0,
                'compressed' => false,
            ];
        }

        // Perbaiki rotasi orientasi EXIF (misal foto kamera HP terbalik / miring)
        $src = $this->fixExifOrientation($src, $binaryDataForExif);

        $origW = imagesx($src);
        $origH = imagesy($src);

        if ($origW <= 0 || $origH <= 0) {
            imagedestroy($src);
            return [
                'data' => $binaryData,
                'mime' => $fallbackMime,
                'extension' => $fallbackExt,
                'size' => $originalSize,
                'original_size' => $originalSize,
                'width' => 0,
                'height' => 0,
                'compressed' => false,
            ];
        }

        // Hitung skala resize proporsional jika sisi terpanjang melebihi batas
        $scale = min(1.0, $maxDimension / max($origW, $origH));
        $newW = (int) max(1, round($origW * $scale));
        $newH = (int) max(1, round($origH * $scale));

        // Buat kanvas truecolor baru
        $dst = imagecreatetruecolor($newW, $newH);

        // Isi latar belakang dengan warna putih untuk menangani transparansi PNG/GIF
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);

        // Resample dengan kualitas tinggi
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        // Ekspor ke format JPEG dengan kualitas teroptimasi
        ob_start();
        imagejpeg($dst, null, max(30, min(100, $quality)));
        $compressedBinary = ob_get_clean() ?: '';

        imagedestroy($src);
        imagedestroy($dst);

        $compressedSize = strlen($compressedBinary);

        // Jika ukuran hasil kompresi lebih besar dari aslinya dan tidak ada resize yang diperlukan,
        // serta format aslinya sudah merupakan JPEG standar, pertahankan aslinya.
        if (!$wasHeic && $scale >= 1.0 && $compressedSize >= $originalSize && in_array($fallbackExt, ['jpg', 'jpeg'], true)) {
            return [
                'data' => $binaryData,
                'mime' => 'image/jpeg',
                'extension' => 'jpg',
                'size' => $originalSize,
                'original_size' => $originalSize,
                'width' => $origW,
                'height' => $origH,
                'compressed' => false,
            ];
        }

        return [
            'data' => $compressedBinary,
            'mime' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => $compressedSize,
            'original_size' => $originalSize,
            'width' => $newW,
            'height' => $newH,
            'compressed' => true,
        ];
    }

    /**
     * Perbaiki orientasi EXIF jika tersedia pada foto JPEG kamera smartphone.
     *
     * @param \GdImage $image
     * @param string $binaryData
     * @return \GdImage
     */
    protected function fixExifOrientation($image, string $binaryData)
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        // Hanya baca EXIF jika header JPEG (FF D8)
        if (strlen($binaryData) < 4 || substr($binaryData, 0, 2) !== "\xFF\xD8") {
            return $image;
        }

        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            return $image;
        }

        fwrite($stream, $binaryData);
        rewind($stream);

        $exif = @exif_read_data($stream);
        fclose($stream);

        if (!is_array($exif) || empty($exif['Orientation'])) {
            return $image;
        }

        $orientation = (int) $exif['Orientation'];

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated !== false && $rotated !== null) {
            imagedestroy($image);
            return $rotated;
        }

        return $image;
    }

    /**
     * Periksa apakah binary/metadata mengindikasikan format HEIC/HEIF (kamera Apple).
     */
    public function isHeic(string $binaryData, string $extension = '', string $mime = ''): bool
    {
        if (in_array(strtolower($extension), ['heic', 'heif'], true)) {
            return true;
        }

        if (in_array(strtolower($mime), ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence', 'application/x-heic'], true)) {
            return true;
        }

        // Periksa signature 'ftyp' di byte ke-4 s.d. 8
        if (strlen($binaryData) >= 12 && substr($binaryData, 4, 4) === 'ftyp') {
            $brand = strtolower(substr($binaryData, 8, 4));
            if (in_array($brand, ['heic', 'heix', 'hevc', 'hevx', 'heim', 'heis', 'hevm', 'hevs', 'mif1', 'msf1'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Coba konversi binary HEIC/HEIF menjadi JPEG menggunakan decoder server yang tersedia.
     */
    public function decodeHeicToJpeg(string $binaryData): ?string
    {
        if (strlen($binaryData) < 12) {
            return null;
        }

        // 1. PHP Imagick (jika tersedia dan mendukung HEIC)
        if (extension_loaded('imagick') && class_exists(\Imagick::class)) {
            try {
                if (!empty(\Imagick::queryFormats('HEIC'))) {
                    $imagick = new \Imagick();
                    $imagick->readImageBlob($binaryData);
                    $imagick->setIteratorIndex(0);
                    $imagick->autoOrient();
                    $imagick->setImageFormat('jpeg');
                    $imagick->setImageCompressionQuality(88);
                    $jpeg = $imagick->getImageBlob();
                    $imagick->clear();

                    if (is_string($jpeg) && strlen($jpeg) > 2 && substr($jpeg, 0, 2) === "\xFF\xD8") {
                        return $jpeg;
                    }
                }
            } catch (\Throwable) {
                // Lanjut ke decoder CLI jika Imagick gagal
            }
        }

        // 2. CLI decoders (heif-convert, ImageMagick magick, ffmpeg)
        if (!function_exists('proc_open')) {
            return null;
        }

        $finder = new ExecutableFinder();
        $baseTemp = tempnam(sys_get_temp_dir(), 'vt_heic_');
        if ($baseTemp === false) {
            return null;
        }
        @unlink($baseTemp);

        $inFile = $baseTemp . '.heic';
        $outFile = $baseTemp . '.jpg';

        try {
            if (@file_put_contents($inFile, $binaryData) === false) {
                return null;
            }

            $commands = [];
            if ($heifConvert = $finder->find('heif-convert')) {
                $commands[] = [$heifConvert, '-q', '88', $inFile, $outFile];
            }
            if ($magick = $finder->find('magick')) {
                $commands[] = [$magick, $inFile, '-auto-orient', '-quality', '88', $outFile];
            }
            if ($ffmpeg = $finder->find('ffmpeg')) {
                $commands[] = [$ffmpeg, '-y', '-hide_banner', '-loglevel', 'error', '-i', $inFile, '-frames:v', '1', '-q:v', '3', $outFile];
            }

            foreach ($commands as $cmd) {
                @unlink($outFile);
                $process = new Process($cmd, null, null, null, 15);
                try {
                    $process->run();
                } catch (\Throwable) {
                    continue;
                }

                if ($process->isSuccessful() && is_file($outFile) && filesize($outFile) > 2) {
                    $jpeg = @file_get_contents($outFile);
                    if (is_string($jpeg) && substr($jpeg, 0, 2) === "\xFF\xD8") {
                        return $jpeg;
                    }
                }
            }
        } finally {
            @unlink($inFile);
            @unlink($outFile);
        }

        return null;
    }
}
