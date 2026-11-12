<?php

namespace App\Http\Controllers;

use App\Models\QrLink;
use App\Qr\Catalog;
use App\Qr\PayloadBuilder;
use App\Qr\QrInput;
use App\Qr\Uploads;
use App\Services\QrPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrCodeController extends Controller
{
    public function __construct(
        private QrPublisher $publisher,
        private Uploads $uploads,
        private PayloadBuilder $payloads,
    ) {}

    public function preview(Request $request): JsonResponse
    {
        $existing = $this->existing($request);
        $input = QrInput::fromRequest($request, $existing);
        $built = $this->payloads->build($input->type, $input->fields);
        $logo = QrInput::logoFile($request);
        $bytes = null;
        $mime = null;

        if ($logo) {
            $this->uploads->assertLogo($logo);
            $bytes = file_get_contents($logo->getRealPath()) ?: null;
            $mime = $logo->getMimeType();
        } elseif ($existing) {
            [$bytes, $mime] = $this->uploads->readLogo($existing->logo_path);
        }

        if ($input->style['shape'] === 'square' && $input->type === 'shaped') {
            $input->style['shape'] = 'dots';
        }

        return response()->json([
            'svg' => $this->publisher->render($built->text, $input->style, $bytes, $mime),
            'title' => $input->title !== '' ? $input->title : $built->title,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $input = QrInput::fromRequest($request);
        $input->smart = true;
        $saved = $this->publisher->store($input, QrInput::contentFile($request), QrInput::logoFile($request));

        return response()->json($this->describe($saved['link'], $saved['svg'], $saved['url'], true));
    }

    public function update(Request $request, string $code): JsonResponse
    {
        $link = $this->find($code);
        $this->authorizeLink($link, (string) $request->input('secret'));
        $input = QrInput::fromRequest($request, $link);
        $saved = $this->publisher->update($link, $input, QrInput::contentFile($request), QrInput::logoFile($request));

        return response()->json($this->describe($saved['link'], $saved['svg'], $saved['url']));
    }

    public function destroy(string $code): JsonResponse
    {
        $this->publisher->destroy($this->find($code));

        return response()->json(['ok' => true]);
    }

    public function open(Request $request, string $code): JsonResponse
    {
        $link = $this->find($code);
        $this->authorizeLink($link, (string) $request->input('secret'));

        return response()->json([
            ...$this->describe($link, $this->publisher->svgFor($link), route('qr.show', $link->code)),
            'fields' => $link->payload,
            'style' => $link->style,
            'has_file' => (bool) $link->file_path,
            'has_logo' => (bool) $link->logo_path,
            'file_name' => $link->file_name,
            'attendances' => $link->attendances()
                ->latest('id')
                ->limit(50)
                ->get(['name', 'created_at'])
                ->map(fn ($row) => [
                    'name' => $row->name,
                    'created_at' => $row->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function owned(Request $request): JsonResponse
    {
        $items = $request->input('items', []);

        if (! is_array($items)) {
            $items = [];
        }

        $found = [];

        foreach (array_slice($items, 0, 100) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $code = (string) ($item['code'] ?? '');
            $secret = (string) ($item['secret'] ?? '');
            $link = QrLink::query()->where('code', $code)->first();

            if (! $link || $secret === '' || ! hash_equals($link->secret, $secret)) {
                continue;
            }

            $type = Catalog::find($link->type);
            $found[] = [
                'code' => $link->code,
                'secret' => $link->secret,
                'title' => $link->title,
                'type' => $link->type,
                'type_label' => $type['label'] ?? $link->type,
                'scans' => $link->scans,
                'url' => route('qr.show', $link->code),
                'created_at' => $link->created_at?->toIso8601String(),
            ];
        }

        return response()->json(['items' => $found]);
    }

    private function find(string $code): QrLink
    {
        return QrLink::query()->where('code', $code)->firstOrFail();
    }

    private function existing(Request $request): ?QrLink
    {
        $code = (string) $request->input('code');
        $secret = (string) $request->input('secret');

        if ($code === '' || $secret === '') {
            return null;
        }

        $link = QrLink::query()->where('code', $code)->first();

        if (! $link || ! hash_equals($link->secret, $secret)) {
            return null;
        }

        return $link;
    }

    private function authorizeLink(QrLink $link, string $secret): void
    {
        abort_unless($secret !== '' && hash_equals($link->secret, $secret), 403, 'Kunci QR tidak cocok.');
    }

    /**
     * @return array<string, mixed>
     */
    private function describe(QrLink $link, string $svg, string $url, bool $withSecret = false): array
    {
        $type = Catalog::find($link->type);
        $data = [
            'code' => $link->code,
            'url' => $url,
            'svg' => $svg,
            'title' => $link->title,
            'type' => $link->type,
            'type_label' => $type['label'] ?? $link->type,
            'scans' => $link->scans,
        ];

        if ($withSecret) {
            $data['secret'] = $link->secret;
        }

        return $data;
    }
}
