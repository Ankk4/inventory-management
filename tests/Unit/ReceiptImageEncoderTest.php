<?php

namespace Tests\Unit;

use App\Services\Receipt\ReceiptImageEncoder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ReceiptImageEncoderTest extends TestCase
{
    public function test_downscales_oversized_receipt_to_pixel_budget(): void
    {
        if (! extension_loaded('gd') && ! $this->imageMagickAvailable()) {
            $this->markTestSkipped('GD or ImageMagick is required to downscale receipt images.');
        }

        config(['inventory.ollama.max_image_edge' => 2048]);

        $encoded = (new ReceiptImageEncoder)->toBase64(base_path('tests/test_lidl_01.png'));
        $info = getimagesizefromstring(base64_decode($encoded));

        $this->assertNotFalse($info);
        $this->assertLessThan(1080 * 3944, $info[0] * $info[1]);
        $this->assertLessThanOrEqual(2048 * 2048 + 2048, $info[0] * $info[1]);
        $this->assertGreaterThan(800, $info[0]);
    }

    public function test_leaves_small_images_unchanged(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'receipt');
        $jpeg = base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGcP//Z'
        );
        file_put_contents($path, $jpeg);

        config(['inventory.ollama.max_image_edge' => 2048]);

        $encoded = (new ReceiptImageEncoder)->toBase64($path);

        $this->assertSame(base64_encode($jpeg), $encoded);
    }

    private function imageMagickAvailable(): bool
    {
        foreach (['magick', 'convert'] as $binary) {
            $process = new Process([$binary, '-version']);
            $process->run();

            if ($process->isSuccessful()) {
                return true;
            }
        }

        return false;
    }
}
