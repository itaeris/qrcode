<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\QrLink;
use App\Models\QrScan;
use App\Qr\Catalog;
use App\Qr\PayloadBuilder;
use App\Qr\SafeUrl;
use App\Qr\Visitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicQrController extends Controller
{
    public function __construct(private PayloadBuilder $payloads) {}

    public function show(string $code): Response|RedirectResponse
    {
        $link = $this->find($code);
        $this->recordScan($link, request());

        $target = $link->target_url ? SafeUrl::normalizeHttp($link->target_url) : null;

        if ($link->kind === 'redirect' && $target && ! $link->file_path) {
            return redirect()->away($target);
        }

        return Inertia::render('Smart/Show', [
            'qr' => [
                'code' => $link->code,
                'type' => $link->type,
                'type_label' => Catalog::find($link->type)['label'] ?? $link->type,
                'title' => $link->title,
                'presentation' => $this->decorate($link),
                'file_url' => $link->file_path ? route('qr.file', $link->code) : null,
            ],
        ]);
    }

    public function file(Request $request, string $code): StreamedResponse
    {
        $link = $this->find($code);
        $path = $this->storedFile($link);
        $name = $this->downloadName($link->file_name);
        $mime = $this->publicMime($link->file_mime);
        $headers = [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ];

        if ($request->boolean('download') || ! $this->inline($mime)) {
            return Storage::disk('local')->download($path, $name, $headers);
        }

        return Storage::disk('local')->response($path, $name, $headers);
    }

    public function vcard(string $code): \Illuminate\Http\Response
    {
        $link = $this->find($code);
        abort_unless($link->type === 'vcard', 404);
        $built = $this->payloads->build('vcard', $link->payload);

        return response($built->text, 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="contact.vcf"',
        ]);
    }

    public function calendar(string $code): \Illuminate\Http\Response
    {
        $link = $this->find($code);
        abort_unless($link->type === 'calendar', 404);
        $built = $this->payloads->build('calendar', $link->payload);

        return response($built->text, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="event.ics"',
        ]);
    }

    public function checkIn(Request $request, string $code): RedirectResponse
    {
        $link = $this->find($code);
        abort_unless($link->type === 'attendance', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ], [
            'name.required' => 'Name is required.',
        ]);

        Attendance::query()->create([
            'qr_link_id' => $link->id,
            'name' => trim($data['name']),
        ]);

        return back()->with('success', 'Attendance recorded.');
    }

    private function find(string $code): QrLink
    {
        return QrLink::query()->where('code', $code)->firstOrFail();
    }

    private function recordScan(QrLink $link, Request $request): void
    {
        $ip = $request->ip();
        $place = Visitor::place($ip);

        $link->increment('scans');
        QrScan::query()->create([
            'qr_link_id' => $link->id,
            'ip' => $ip,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'device' => Visitor::device($request->userAgent()),
            'city' => $place['city'],
            'region' => $place['region'],
            'country' => $place['country'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decorate(QrLink $link): array
    {
        $presentation = $link->presentation ?? [];
        $actions = $presentation['actions'] ?? [];

        if ($link->type === 'vcard') {
            $actions[] = ['label' => 'Save contact', 'href' => route('qr.vcard', $link->code)];
        }

        if ($link->type === 'calendar') {
            $actions[] = ['label' => 'Add to calendar', 'href' => route('qr.calendar', $link->code)];
        }

        if ($link->file_path) {
            $actions[] = ['label' => 'Download file', 'href' => route('qr.file', ['code' => $link->code, 'download' => 1])];
            $presentation['media'] = match ($link->type) {
                'image', 'png' => 'image',
                'audio' => 'audio',
                'video' => 'video',
                'pdf' => 'pdf',
                default => $presentation['media'] ?? null,
            };
        }

        if ($link->type === 'attendance') {
            $presentation['check_in'] = true;
        }

        $presentation['actions'] = array_values(array_filter(array_map(
            function (mixed $action): ?array {
                if (! is_array($action)) {
                    return null;
                }

                $href = SafeUrl::href((string) ($action['href'] ?? ''));

                if ($href === null) {
                    return null;
                }

                return [
                    'label' => mb_substr((string) ($action['label'] ?? 'Open'), 0, 80),
                    'href' => $href,
                ];
            },
            $actions,
        )));

        return $presentation;
    }

    private function storedFile(QrLink $link): string
    {
        $path = str_replace('\\', '/', (string) $link->file_path);

        abort_unless(
            $path !== ''
            && ! str_contains($path, '..')
            && str_starts_with($path, 'qr-files/')
            && Storage::disk('local')->exists($path),
            404,
        );

        return $path;
    }

    private function downloadName(?string $name): string
    {
        $name = str_replace(['"', '\\', '/', "\r", "\n"], '', (string) $name);
        $name = trim($name);

        return $name !== '' ? mb_substr($name, 0, 120) : 'file';
    }

    private function publicMime(?string $mime): string
    {
        $mime = strtolower(trim(explode(';', (string) $mime)[0]));

        return $this->inline($mime) ? $mime : 'application/octet-stream';
    }

    private function inline(string $mime): bool
    {
        return in_array($mime, [
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/webp',
            'audio/mpeg',
            'audio/wav',
            'audio/mp4',
            'audio/ogg',
            'audio/aac',
            'video/mp4',
            'video/webm',
            'video/quicktime',
            'application/pdf',
        ], true);
    }
}
