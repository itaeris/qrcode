<?php

namespace App\Http\Controllers;

use App\Models\QrLink;
use App\Models\QrScan;
use App\Qr\Catalog;
use App\Qr\Visitor;
use App\Services\QrPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class GeneratorController extends Controller
{
    public function index(QrPublisher $publisher): Response
    {
        $projects = QrLink::query()
            ->latest()
            ->get()
            ->map(function (QrLink $link) use ($publisher): array {
                try {
                    $svg = $publisher->svgFor($link);
                } catch (\Throwable) {
                    $svg = null;
                }

                return [
                    'code' => $link->code,
                    'title' => $link->title,
                    'type' => $link->type,
                    'type_label' => Catalog::find($link->type)['label'] ?? $link->type,
                    'scans' => $link->scans,
                    'url' => route('qr.show', $link->code),
                    'created_at' => $link->created_at?->toIso8601String(),
                    'svg' => $svg,
                    'background' => $link->style['bg'] ?? '#ffffff',
                ];
            })
            ->values();

        return Inertia::render('Projects', [
            'projects' => $projects,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Generate', [
            'types' => Catalog::all(),
            'editingCode' => null,
        ]);
    }

    public function edit(string $code): Response
    {
        abort_unless(QrLink::query()->where('code', $code)->exists(), 404);

        return Inertia::render('Generate', [
            'types' => Catalog::all(),
            'editingCode' => $code,
        ]);
    }

    public function manage(Request $request, string $code): Response
    {
        $link = QrLink::query()->where('code', $code)->firstOrFail();
        [$from, $until] = $this->range($request);

        $scans = $link->scansLog()
            ->whereBetween('created_at', [$from, $until])
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $byDay = $scans->groupBy(fn (QrScan $scan): string => $scan->created_at->toDateString())
            ->map(fn ($group): int => $group->count());

        $days = [];
        $cursor = $from->copy()->startOfDay();
        $end = $until->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $days[] = [
                'date' => $key,
                'label' => $cursor->locale('en')->translatedFormat('d MMM'),
                'count' => (int) ($byDay[$key] ?? 0),
            ];
            $cursor->addDay();
        }

        $places = $scans
            ->groupBy(fn (QrScan $scan): string => $this->location($scan))
            ->map(fn ($group, string $name): array => [
                'name' => $name,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        return Inertia::render('Manage', [
            'qr' => [
                'code' => $link->code,
                'title' => $link->title,
                'type_label' => Catalog::find($link->type)['label'] ?? $link->type,
                'scans' => $link->scans,
                'edit_url' => route('qr.edit', $link->code),
            ],
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $until->toDateString(),
            ],
            'total' => $scans->count(),
            'days' => $days,
            'places' => $places,
            'scans' => $scans->map(fn (QrScan $scan): array => [
                'at' => $scan->created_at?->toIso8601String(),
                'device' => $scan->device ?: Visitor::device($scan->user_agent),
                'location' => $this->location($scan),
            ])->values()->all(),
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $from = $this->day($request->string('from')->toString()) ?? now()->subDays(29)->startOfDay();
        $until = $this->day($request->string('to')->toString()) ?? now()->startOfDay();

        if ($until->lt($from)) {
            [$from, $until] = [$until->copy(), $from->copy()];
        }

        if ($from->diffInDays($until) > 92) {
            $from = $until->copy()->subDays(92);
        }

        return [$from->startOfDay(), $until->endOfDay()];
    }

    private function day(string $value): ?Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function location(QrScan $scan): string
    {
        $parts = array_values(array_filter([$scan->city, $scan->region, $scan->country]));

        return $parts === [] ? 'Unknown' : implode(', ', $parts);
    }
}
