<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use App\Models\ShortLinkClick;
use App\Qr\SafeUrl;
use App\Qr\Visitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ShortLinkController extends Controller
{
    public function index(): Response
    {
        $links = ShortLink::query()
            ->latest()
            ->get()
            ->map(fn (ShortLink $link): array => $this->summary($link))
            ->values();

        return Inertia::render('ShortLinks/Index', [
            'links' => $links,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $code = $data['code'] !== '' ? $data['code'] : ShortLink::issueCode();

        ShortLink::query()->create([
            'code' => $code,
            'title' => $data['title'] !== '' ? $data['title'] : $this->host($data['url']),
            'url' => $data['url'],
        ]);

        return redirect()
            ->route('short-links.index')
            ->with('success', 'Short link created.');
    }

    public function show(Request $request, string $code): Response
    {
        $link = $this->find($code);
        [$from, $until] = $this->range($request);
        $clicks = $link->visits()
            ->whereBetween('created_at', [$from, $until])
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $byDay = $clicks->groupBy(fn (ShortLinkClick $click): string => $click->created_at->toDateString())
            ->map(fn ($group): int => $group->count());

        $days = [];
        $cursor = $from->copy()->startOfDay();
        $end = $until->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $days[] = [
                'date' => $key,
                'count' => (int) ($byDay[$key] ?? 0),
            ];
            $cursor->addDay();
        }

        $places = $clicks
            ->groupBy(fn (ShortLinkClick $click): string => $this->location($click))
            ->map(fn ($group, string $name): array => [
                'name' => $name,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        return Inertia::render('ShortLinks/Show', [
            'link' => $this->summary($link),
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $until->toDateString(),
            ],
            'total' => $clicks->count(),
            'days' => $days,
            'places' => $places,
            'clicks' => $clicks->map(fn (ShortLinkClick $click): array => [
                'at' => $click->created_at?->toIso8601String(),
                'device' => $click->device ?: Visitor::device($click->user_agent),
                'location' => $this->location($click),
            ])->values()->all(),
        ]);
    }

    public function update(Request $request, string $code): RedirectResponse
    {
        $link = $this->find($code);
        $data = $this->validated($request, false);

        $link->update([
            'title' => $data['title'] !== '' ? $data['title'] : $this->host($data['url']),
            'url' => $data['url'],
        ]);

        return back()->with('success', 'Link destination updated.');
    }

    public function destroy(string $code): RedirectResponse
    {
        $this->find($code)->delete();

        return redirect()
            ->route('short-links.index')
            ->with('success', 'Short link deleted.');
    }

    public function visit(string $code): RedirectResponse
    {
        $link = $this->find($code);
        $request = request();
        $ip = $request->ip();
        $place = Visitor::place($ip);

        $link->increment('clicks');
        $link->visits()->create([
            'ip' => $ip,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'device' => Visitor::device($request->userAgent()),
            'city' => $place['city'],
            'region' => $place['region'],
            'country' => $place['country'],
        ]);

        return redirect()->away($link->url);
    }

    private function find(string $code): ShortLink
    {
        return ShortLink::query()->where('code', $code)->firstOrFail();
    }

    /**
     * @return array{url: string, title: string, code: string}
     */
    private function validated(Request $request, bool $withCode = true): array
    {
        $rules = [
            'url' => ['required', 'string', 'max:2000'],
            'title' => ['nullable', 'string', 'max:140'],
        ];

        if ($withCode) {
            $request->merge([
                'code' => strtolower(trim((string) $request->input('code'))),
            ]);
            $rules['code'] = ['nullable', 'string', 'min:3', 'max:16', 'regex:/^[a-z0-9]+$/', 'unique:short_links,code'];
        }

        $data = $request->validate($rules, [
            'url.required' => 'Destination is required.',
            'code.min' => 'The code must be at least 3 characters.',
            'code.max' => 'The code may not be longer than 16 characters.',
            'code.regex' => 'The code may only contain lowercase letters and numbers.',
            'code.unique' => 'This code is already in use.',
        ]);

        $url = SafeUrl::normalizeHttp((string) $data['url']);

        if ($url === null) {
            throw ValidationException::withMessages([
                'url' => 'Enter a valid http or https link.',
            ]);
        }

        return [
            'url' => $url,
            'title' => trim((string) ($data['title'] ?? '')),
            'code' => strtolower(trim((string) ($data['code'] ?? ''))),
        ];
    }

    /**
     * @return array{code: string, title: string, url: string, short_url: string, clicks: int, created_at: ?string}
     */
    private function summary(ShortLink $link): array
    {
        return [
            'code' => $link->code,
            'title' => $link->title,
            'url' => $link->url,
            'short_url' => route('short.visit', $link->code),
            'clicks' => $link->clicks,
            'created_at' => $link->created_at?->toIso8601String(),
        ];
    }

    private function host(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : 'Link';
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $from = $this->day($request->string('dari')->toString()) ?? now()->subDays(29)->startOfDay();
        $until = $this->day($request->string('sampai')->toString()) ?? now()->startOfDay();

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

    private function location(ShortLinkClick $click): string
    {
        $parts = array_values(array_filter([$click->city, $click->region, $click->country]));

        return $parts === [] ? 'Unknown' : implode(', ', $parts);
    }
}
