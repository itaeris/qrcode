<?php

namespace App\Http\Controllers;

use App\Models\QrLink;
use App\Qr\Catalog;
use Inertia\Inertia;
use Inertia\Response;

class GeneratorController extends Controller
{
    public function index(): Response
    {
        $projects = QrLink::query()
            ->latest()
            ->get(['code', 'type', 'title', 'scans', 'created_at'])
            ->map(fn (QrLink $link): array => [
                'code' => $link->code,
                'title' => $link->title,
                'type' => $link->type,
                'type_label' => Catalog::find($link->type)['label'] ?? $link->type,
                'scans' => $link->scans,
                'url' => route('qr.show', $link->code),
                'created_at' => $link->created_at?->toIso8601String(),
            ])
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
}
