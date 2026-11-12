<?php

namespace Tests\Feature;

use App\Models\QrLink;
use App\Qr\QrInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicFileAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_html_file_is_downloaded_instead_of_shown(): void
    {
        Storage::fake('local');
        $link = $this->link([
            'file_path' => 'qr-files/abcd2345/page.html',
            'file_name' => 'page.html',
            'file_mime' => 'text/html',
            'presentation' => [
                'actions' => [
                    ['label' => 'Open', 'href' => 'javascript:alert(1)'],
                ],
            ],
        ]);
        Storage::disk('local')->put($link->file_path, '<html><script>alert(1)</script></html>');

        $response = $this->get('/q/'.$link->code.'/file');

        $response->assertOk();
        $this->assertStringStartsWith('application/octet-stream', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('attachment', (string) $response->headers->get('content-disposition'));
        $response->assertHeader('content-security-policy', "default-src 'none'; sandbox");

        $this->get('/q/'.$link->code)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('qr.presentation.actions', 1)
                ->where('qr.presentation.actions.0.href', fn (string $href): bool => str_contains($href, '/q/'.$link->code.'/file')));
    }

    #[Test]
    public function a_file_outside_the_qr_folder_is_not_served(): void
    {
        Storage::fake('local');
        $link = $this->link([
            'file_path' => '../.env',
            'file_name' => 'env',
            'file_mime' => 'text/plain',
        ]);

        $this->get('/q/'.$link->code.'/file')->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function link(array $overrides): QrLink
    {
        return QrLink::query()->create(array_merge([
            'code' => 'abcd2345',
            'secret' => str_repeat('a', 40),
            'type' => 'file',
            'title' => 'File',
            'kind' => 'page',
            'payload' => [],
            'style' => QrInput::defaultStyle(),
            'presentation' => [],
        ], $overrides));
    }
}
