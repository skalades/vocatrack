<?php

namespace Tests\Unit;

use App\Services\ImageCompressionService;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class ImageCompressionServiceTest extends TestCase
{
    protected ImageCompressionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImageCompressionService();
    }

    public function test_compresses_large_image_and_resizes_dimensions(): void
    {
        // Buat gambar buatan resolusi 2000x1500
        $im = imagecreatetruecolor(2000, 1500);
        $red = imagecolorallocate($im, 220, 50, 50);
        imagefilledrectangle($im, 0, 0, 2000, 1500, $red);

        ob_start();
        imagejpeg($im, null, 100);
        $binary = ob_get_clean();
        imagedestroy($im);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_img_');
        file_put_contents($tempPath, $binary);

        $file = new UploadedFile($tempPath, 'camera_huge.jpg', 'image/jpeg', null, true);

        $result = $this->service->compressUploadedFile($file, 1280, 75);

        @unlink($tempPath);

        $this->assertTrue($result['compressed']);
        $this->assertSame('jpg', $result['extension']);
        $this->assertSame('image/jpeg', $result['mime']);
        $this->assertSame(1280, $result['width']);
        $this->assertSame(960, $result['height']);
        $this->assertLessThan(strlen($binary), $result['size']);
    }

    public function test_compresses_base64_data_uri(): void
    {
        $im = imagecreatetruecolor(600, 400);
        $blue = imagecolorallocate($im, 30, 100, 200);
        imagefilledrectangle($im, 0, 0, 600, 400, $blue);

        ob_start();
        imagepng($im);
        $png = ob_get_clean();
        imagedestroy($im);

        $base64 = 'data:image/png;base64,' . base64_encode($png);

        $result = $this->service->compressBase64($base64, 1280, 75);

        $this->assertNotNull($result);
        $this->assertSame('jpg', $result['extension']);
        $this->assertSame('image/jpeg', $result['mime']);
        $this->assertSame(600, $result['width']);
        $this->assertSame(400, $result['height']);
    }

    public function test_safely_preserves_unsupported_heic(): void
    {
        $fakeHeicData = "RIFF\x00\x00\x00\x00ftypheic" . str_repeat('X', 500);
        $result = $this->service->compressBinary($fakeHeicData, 'heic', 'image/heic');

        $this->assertFalse($result['compressed']);
        $this->assertSame('heic', $result['extension']);
        $this->assertSame('image/heic', $result['mime']);
        $this->assertSame($fakeHeicData, $result['data']);
    }

    public function test_returns_null_for_empty_base64(): void
    {
        $this->assertNull($this->service->compressBase64(''));
        $this->assertNull($this->service->compressBase64('invalid base64 content!!!'));
    }

    public function test_detects_heic_by_magic_bytes_extension_and_mime(): void
    {
        $heicHeader = "\x00\x00\x00\x18ftypheic\x00\x00\x00\x00";
        $mif1Header = "\x00\x00\x00\x18ftypmif1\x00\x00\x00\x00";
        $jpegHeader = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01";

        $this->assertTrue($this->service->isHeic($heicHeader, 'bin', 'application/octet-stream'));
        $this->assertTrue($this->service->isHeic($mif1Header, '', ''));
        $this->assertTrue($this->service->isHeic($jpegHeader, 'HEIC', 'application/octet-stream'));
        $this->assertTrue($this->service->isHeic($jpegHeader, 'jpg', 'image/heif'));
        $this->assertFalse($this->service->isHeic($jpegHeader, 'jpg', 'image/jpeg'));
    }
}
