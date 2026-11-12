<?php

namespace Tests\Feature;

use App\Models\QrLink;
use App\Models\User;
use App\Qr\QrInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QrGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function the_generator_page_renders(): void
    {
        $this->get('/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Generate')
                ->has('types', 61));

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Projects')->has('projects', 0));
    }

    #[Test]
    public function a_preview_returns_svg(): void
    {
        $this->postJson('/qr/preview', [
            'type' => 'text',
            'fields' => ['body' => 'Halo Aeris'],
            'style' => QrInput::defaultStyle(),
        ])->assertOk()->assertJsonStructure(['svg', 'title']);
    }

    #[Test]
    public function an_unknown_type_is_rejected(): void
    {
        $this->postJson('/qr/preview', [
            'type' => 'tidak-ada',
            'fields' => [],
            'style' => QrInput::defaultStyle(),
        ])->assertUnprocessable()->assertJsonValidationErrors('type');
    }

    #[Test]
    public function a_smart_url_redirects_and_counts_the_scan(): void
    {
        $response = $this->postJson('/qr', [
            'type' => 'url',
            'title' => 'Situs',
            'fields' => ['url' => 'https://example.com/promo'],
            'style' => QrInput::defaultStyle(),
            'smart' => true,
        ])->assertOk();

        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Projects')
            ->has('projects', 1)
            ->where('projects.0.title', 'Situs')
            ->where('projects.0.svg', fn (string $svg) => str_starts_with($svg, '<svg')));

        $code = $response->json('code');

        $this->get('/q/'.$code)->assertRedirect('https://example.com/promo');
        $this->assertSame(1, QrLink::query()->first()->scans);

        $this->postJson('/qr/'.$code, [
            'secret' => 'salah',
            'type' => 'url',
            'fields' => ['url' => 'https://example.com/baru'],
            'style' => QrInput::defaultStyle(),
        ])->assertForbidden();

        $this->postJson('/qr/'.$code, [
            'secret' => $response->json('secret'),
            'type' => 'url',
            'fields' => ['url' => 'https://example.com/baru'],
            'style' => QrInput::defaultStyle(),
        ])->assertOk();

        $this->get('/q/'.$code)->assertRedirect('https://example.com/baru');

        $this->postJson('/qr/'.$code.'/delete')->assertOk();
        $this->assertDatabaseMissing('qr_links', ['code' => $code]);
    }

    #[Test]
    public function attendance_can_be_recorded_from_the_public_page(): void
    {
        $response = $this->postJson('/qr', [
            'type' => 'attendance',
            'fields' => ['event' => 'Rapat', 'location' => 'Kantor', 'when' => ''],
            'style' => QrInput::defaultStyle(),
        ])->assertOk();

        $code = $response->json('code');

        $this->get('/q/'.$code)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Smart/Show')->where('qr.presentation.check_in', true));

        $this->post('/q/'.$code.'/check-in', ['name' => 'Aeris'])->assertRedirect();
        $this->assertDatabaseHas('attendances', ['name' => 'Aeris']);
    }

    #[Test]
    public function an_uploaded_pdf_can_be_downloaded_from_the_short_link(): void
    {
        Storage::fake('local');

        $response = $this->post('/qr', [
            'type' => 'pdf',
            'fields' => ['name' => 'Catatan', 'url' => ''],
            'style' => QrInput::defaultStyle(),
            'file' => UploadedFile::fake()->createWithContent('catatan.pdf', "%PDF-1.4\n%%EOF"),
        ])->assertOk();

        $code = $response->json('code');

        $this->get('/q/'.$code)->assertOk()->assertInertia(fn ($page) => $page->component('Smart/Show'));
        $this->get('/q/'.$code.'/file?download=1')->assertOk();
    }
}
