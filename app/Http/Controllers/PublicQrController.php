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
        abort_unless($link->file_path && Storage::disk('local')->exists($link->file_path), 404);

        $download = $request->boolean('unduh');
        $headers = [
            'Content-Type' => $link->file_mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($download || ! $this->inline($link->file_mime)) {
            return Storage::disk('local')->download($link->file_path, $link->file_name ?: 'berkas', $headers);
        }

        return Storage::disk('local')->response($link->file_path, $link->file_name, $headers);
    }

    public function vcard(string $code): \Illuminate\Http\Response
    {
        $link = $this->find($code);
        abort_unless($link->type === 'vcard', 404);
        $built = $this->payloads->build('vcard', $link->payload);

        return response($built->text, 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="kontak.vcf"',
        ]);
    }

    public function calendar(string $code): \Illuminate\Http\Response
    {
        $link = $this->find($code);
        abort_unless($link->type === 'calendar', 404);
        $built = $this->payloads->build('calendar', $link->payload);

        return response($built->text, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="acara.ics"',
        ]);
    }

    public function checkIn(Request $request, string $code): RedirectResponse
    {
        $link = $this->find($code);
        abort_unless($link->type === 'attendance', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ], [
            'name.required' => 'Nama wajib diisi.',
        ]);

        Attendance::query()->create([
            'qr_link_id' => $link->id,
            'name' => trim($data['name']),
        ]);

        return back()->with('success', 'Kehadiran tercatat.');
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
            $actions[] = ['label' => 'Simpan kontak', 'href' => route('qr.vcard', $link->code)];
        }

        if ($link->type === 'calendar') {
            $actions[] = ['label' => 'Tambah ke kalender', 'href' => route('qr.calendar', $link->code)];
        }

        if ($link->file_path) {
            $actions[] = ['label' => 'Unduh berkas', 'href' => route('qr.file', ['code' => $link->code, 'unduh' => 1])];
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

        $presentation['actions'] = $actions;

        return $presentation;
    }

    private function inline(?string $mime): bool
    {
        if (! $mime) {
            return false;
        }

        return str_starts_with($mime, 'image/')
            || str_starts_with($mime, 'audio/')
            || str_starts_with($mime, 'video/')
            || $mime === 'application/pdf';
    }
}
