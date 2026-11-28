<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PwaManifestService
{
    private const PWA_ICON_SUBDIR = 'branding/pwa';
    private const DEFAULT_MASKABLE_BG = [241, 244, 236]; // #F1F4EC (senada background_color manifest)

    /**
     * Sinkronkan manifest PWA siswa dan guru dengan logo serta identitas platform aktif.
     */
    public function sync(): void
    {
        try {
            $customLogo = PlatformSetting::get('system_logo');
            $hasCustomLogo = $customLogo && Storage::disk('public')->exists($customLogo);

            $appName = PlatformSetting::get('platform_name', 'VokaTrack');
            $description = PlatformSetting::get(
                'platform_tagline',
                'Presensi selfie, jurnal harian, dan laporan PKL untuk siswa SMK.'
            );

            $iconSet = $this->resolveIconSet($hasCustomLogo ? $customLogo : null);

            $studentManifest = $this->buildManifest($iconSet, [
                'id' => '/siswa/beranda',
                'name' => "{$appName} Siswa — Presensi & Jurnal PKL",
                'short_name' => $appName,
                'description' => $description,
                'start_url' => '/siswa/beranda?source=pwa',
                'shortcuts' => [
                    [
                        'name' => 'Presensi',
                        'url' => '/siswa/absensi',
                        'icons' => [['src' => $iconSet['shortcut'], 'sizes' => $iconSet['shortcut_size']]],
                    ],
                    [
                        'name' => 'Jurnal Harian',
                        'url' => '/siswa/jurnal',
                        'icons' => [['src' => $iconSet['shortcut'], 'sizes' => $iconSet['shortcut_size']]],
                    ],
                ],
            ]);

            $teacherManifest = $this->buildManifest($iconSet, [
                'id' => '/guru/dashboard',
                'name' => "{$appName} Guru — Pembimbing PKL",
                'short_name' => "{$appName} Guru",
                'description' => 'Monitoring presensi real-time, verifikasi jurnal, supervisi, dan penilaian PKL untuk guru pembimbing.',
                'start_url' => '/guru/dashboard?source=pwa',
                'shortcuts' => [
                    [
                        'name' => 'Monitoring Siswa',
                        'url' => '/guru/monitoring',
                        'icons' => [['src' => $iconSet['shortcut'], 'sizes' => $iconSet['shortcut_size']]],
                    ],
                    [
                        'name' => 'Verifikasi Jurnal',
                        'url' => '/guru/journals',
                        'icons' => [['src' => $iconSet['shortcut'], 'sizes' => $iconSet['shortcut_size']]],
                    ],
                    [
                        'name' => 'Izin & Sakit',
                        'url' => '/guru/requests',
                        'icons' => [['src' => $iconSet['shortcut'], 'sizes' => $iconSet['shortcut_size']]],
                    ],
                ],
            ]);

            @file_put_contents(
                public_path('manifest.webmanifest'),
                json_encode($studentManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            @file_put_contents(
                public_path('manifest-guru.webmanifest'),
                json_encode($teacherManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        } catch (\Throwable $e) {
            Log::warning('PwaManifestService: Gagal sinkronisasi PWA manifest: ' . $e->getMessage());
        }
    }

    /**
     * Tentukan daftar ikon valid (192x192, 512x512, maskable 512x512) untuk manifest.
     *
     * @return array{icons: array<int, array<string, string>>, shortcut: string, shortcut_size: string}
     */
    protected function resolveIconSet(?string $customLogoPath): array
    {
        if (! $customLogoPath) {
            $this->cleanupGeneratedIcons(null);

            return $this->defaultIconSet();
        }

        $ext = strtolower(pathinfo($customLogoPath, PATHINFO_EXTENSION));
        if ($ext === 'svg') {
            $this->cleanupGeneratedIcons(null);
            $svgPath = $this->storageRelativeUrl($customLogoPath);

            return [
                'icons' => [
                    ['src' => $svgPath, 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any'],
                    ['src' => $svgPath, 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'maskable'],
                ],
                'shortcut' => $svgPath,
                'shortcut_size' => 'any',
            ];
        }

        $generated = $this->generateSquarePngIcons($customLogoPath);
        if ($generated !== null) {
            return $generated;
        }

        $this->cleanupGeneratedIcons(null);

        return $this->defaultIconSet();
    }

    /**
     * @return array{icons: array<int, array<string, string>>, shortcut: string, shortcut_size: string}
     */
    protected function defaultIconSet(): array
    {
        return [
            'icons' => [
                ['src' => '/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcut' => '/icons/icon-192.png',
            'shortcut_size' => '192x192',
        ];
    }

    /**
     * Hasilkan ikon PNG persegi 192x192, 512x512, dan maskable 512x512 dari logo kustom
     * agar memenuhi syarat installability PWA Chromium meski logo asli kecil atau non-persegi.
     *
     * @return array{icons: array<int, array<string, string>>, shortcut: string, shortcut_size: string}|null
     */
    protected function generateSquarePngIcons(string $customLogoPath): ?array
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $raw = Storage::disk('public')->get($customLogoPath);
        if (! $raw) {
            return null;
        }

        $source = @imagecreatefromstring($raw);
        if (! $source) {
            return null;
        }

        $baseName = pathinfo($customLogoPath, PATHINFO_FILENAME) . '.png';
        $targets = [
            ['folder' => '192', 'size' => 192, 'purpose' => 'any', 'fill' => null, 'scale' => 1.0],
            ['folder' => '512', 'size' => 512, 'purpose' => 'any', 'fill' => null, 'scale' => 1.0],
            ['folder' => 'maskable-512', 'size' => 512, 'purpose' => 'maskable', 'fill' => self::DEFAULT_MASKABLE_BG, 'scale' => 0.72],
        ];

        $this->cleanupGeneratedIcons($baseName);

        $icons = [];
        try {
            foreach ($targets as $target) {
                $pngBytes = $this->renderContainedSquarePng(
                    $source,
                    $target['size'],
                    $target['fill'],
                    $target['scale']
                );
                if ($pngBytes === null) {
                    return null;
                }

                $storagePath = self::PWA_ICON_SUBDIR . '/' . $target['folder'] . '/' . $baseName;
                Storage::disk('public')->put($storagePath, $pngBytes);

                $icons[] = [
                    'src' => $this->storageRelativeUrl($storagePath),
                    'sizes' => "{$target['size']}x{$target['size']}",
                    'type' => 'image/png',
                    'purpose' => $target['purpose'],
                ];
            }
        } finally {
            imagedestroy($source);
        }

        return [
            'icons' => $icons,
            'shortcut' => $icons[0]['src'],
            'shortcut_size' => '192x192',
        ];
    }

    /**
     * Render sumber gambar ke kanvas PNG persegi dengan menjaga rasio aspek asli.
     *
     * @param  array{0: int, 1: int, 2: int}|null  $backgroundRgb
     */
    protected function renderContainedSquarePng(\GdImage $source, int $targetSize, ?array $backgroundRgb, float $scale): ?string
    {
        $srcW = imagesx($source);
        $srcH = imagesy($source);
        if ($srcW <= 0 || $srcH <= 0) {
            return null;
        }

        $canvas = imagecreatetruecolor($targetSize, $targetSize);
        if (! $canvas) {
            return null;
        }

        try {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);

            if ($backgroundRgb === null) {
                $bg = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            } else {
                $bg = imagecolorallocate($canvas, $backgroundRgb[0], $backgroundRgb[1], $backgroundRgb[2]);
            }
            imagefill($canvas, 0, 0, $bg);
            imagealphablending($canvas, true);

            $maxBox = max(1, (int) round($targetSize * $scale));
            $ratio = min($maxBox / $srcW, $maxBox / $srcH);
            $destW = max(1, (int) round($srcW * $ratio));
            $destH = max(1, (int) round($srcH * $ratio));
            $destX = (int) floor(($targetSize - $destW) / 2);
            $destY = (int) floor(($targetSize - $destH) / 2);

            imagecopyresampled($canvas, $source, $destX, $destY, 0, 0, $destW, $destH, $srcW, $srcH);

            ob_start();
            $ok = imagepng($canvas, null, 6);
            $bytes = ob_get_clean();

            return ($ok && is_string($bytes) && $bytes !== '') ? $bytes : null;
        } finally {
            imagedestroy($canvas);
        }
    }

    /**
     * Hapus ikon PWA hasil generate sebelumnya agar tidak menumpuk saat logo diganti atau di-reset.
     */
    protected function cleanupGeneratedIcons(?string $keepFileName): void
    {
        $disk = Storage::disk('public');
        foreach (['192', '512', 'maskable-512'] as $folder) {
            $dir = self::PWA_ICON_SUBDIR . '/' . $folder;
            if (! $disk->exists($dir)) {
                continue;
            }
            foreach ($disk->files($dir) as $file) {
                if ($keepFileName === null || basename($file) !== $keepFileName) {
                    $disk->delete($file);
                }
            }
        }
    }

    protected function storageRelativeUrl(string $storagePath): string
    {
        $path = parse_url(asset('storage/' . ltrim($storagePath, '/')), PHP_URL_PATH)
            ?: ('/storage/' . ltrim($storagePath, '/'));

        return str_starts_with($path, '/') ? $path : '/' . $path;
    }

    /**
     * @param  array{icons: array<int, array<string, string>>, shortcut: string, shortcut_size: string}  $iconSet
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function buildManifest(array $iconSet, array $overrides): array
    {
        return array_merge([
            'lang' => 'id',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#F1F4EC',
            'theme_color' => '#0B2B25',
            'categories' => ['education', 'productivity'],
            'icons' => $iconSet['icons'],
        ], $overrides);
    }
}
