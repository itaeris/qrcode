<?php

namespace App\Qr;

use App\Models\QrLink;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class QrInput
{
    /**
     * @param  array<string, mixed>  $fields
     * @param  array{fg: string, bg: string, shape: string, ecc: string, margin: int}  $style
     */
    public function __construct(
        public string $type,
        public string $title,
        public array $fields,
        public array $style,
        public bool $smart,
        public bool $removeFile,
    ) {}

    public static function fromRequest(Request $request, ?QrLink $existing = null): self
    {
        $typeId = (string) $request->input('type');
        $type = Catalog::find($typeId);

        if (! $type) {
            throw ValidationException::withMessages(['type' => 'Jenis QR tidak dikenal.']);
        }

        $fields = self::decode($request->input('fields', []), 'fields');
        $normalized = self::fields($type, $fields);
        $style = self::style(self::decode($request->input('style', []), 'style'));
        $removeFile = $request->boolean('remove_file');
        $hasFile = $request->hasFile('file') || ($existing?->file_path && ! $removeFile);

        self::assertComplete($type, $normalized, $hasFile, $request->hasFile('logo') || (bool) $existing?->logo_path);

        $smart = $request->boolean('smart') || (bool) $type['force_smart'] || $request->hasFile('file');

        return new self(
            $typeId,
            mb_substr(trim((string) $request->input('title', '')), 0, 140),
            $normalized,
            $style,
            $smart,
            $removeFile,
        );
    }

    /**
     * @return array{fg: string, bg: string, shape: string, ecc: string, margin: int}
     */
    public static function defaultStyle(): array
    {
        return [
            'fg' => '#111827',
            'bg' => '#ffffff',
            'shape' => 'square',
            'ecc' => 'M',
            'margin' => 4,
        ];
    }

    /**
     * @param  array<string, mixed>  $type
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private static function fields(array $type, array $fields): array
    {
        $normalized = [];

        foreach ($type['fields'] as $field) {
            $key = $field['key'];
            $value = $fields[$key] ?? null;

            if ($field['type'] === 'links') {
                $normalized[$key] = self::links($value);

                continue;
            }

            if ($field['type'] === 'toggle') {
                $normalized[$key] = filter_var($value ?? $field['default'], FILTER_VALIDATE_BOOLEAN);

                continue;
            }

            if ($field['type'] === 'select') {
                $options = array_column($field['options'], 'value');
                $choice = is_scalar($value) ? (string) $value : '';
                $normalized[$key] = in_array($choice, $options, true) ? $choice : (string) $field['default'];

                continue;
            }

            $text = is_scalar($value) ? trim((string) $value) : '';
            $max = (int) ($field['max'] ?? 500);
            $normalized[$key] = mb_substr($text, 0, $max);
        }

        return $normalized;
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private static function links(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return [];
        }

        $links = [];

        foreach (array_slice($value, 0, 12) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $links[] = [
                'label' => mb_substr(trim((string) ($item['label'] ?? '')), 0, 60),
                'url' => mb_substr(trim((string) ($item['url'] ?? '')), 0, 500),
            ];
        }

        return $links;
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array{fg: string, bg: string, shape: string, ecc: string, margin: int}
     */
    private static function style(array $style): array
    {
        $defaults = self::defaultStyle();
        $shape = (string) ($style['shape'] ?? $defaults['shape']);
        $ecc = strtoupper((string) ($style['ecc'] ?? $defaults['ecc']));
        $margin = (int) ($style['margin'] ?? $defaults['margin']);

        return [
            'fg' => self::hex((string) ($style['fg'] ?? $defaults['fg']), $defaults['fg']),
            'bg' => self::hex((string) ($style['bg'] ?? $defaults['bg']), $defaults['bg']),
            'shape' => in_array($shape, ['square', 'rounded', 'dots'], true) ? $shape : 'square',
            'ecc' => in_array($ecc, ['L', 'M', 'Q', 'H'], true) ? $ecc : 'M',
            'margin' => max(0, min(8, $margin)),
        ];
    }

    /**
     * @param  array<string, mixed>  $type
     * @param  array<string, mixed>  $fields
     */
    private static function assertComplete(array $type, array $fields, bool $hasFile, bool $hasLogo): void
    {
        $any = $type['any'] ?? [];

        if ($any !== []) {
            $ok = false;

            foreach ($any as $key) {
                if ($key === 'file' && $hasFile) {
                    $ok = true;
                    break;
                }

                $value = $fields[$key] ?? null;

                if (is_array($value) && collect($value)->contains(fn ($item) => is_array($item) && trim((string) ($item['url'] ?? '')) !== '')) {
                    $ok = true;
                    break;
                }

                if (is_scalar($value) && trim((string) $value) !== '') {
                    $ok = true;
                    break;
                }
            }

            if (! $ok) {
                throw ValidationException::withMessages([
                    'fields' => 'Lengkapi data yang diminta untuk jenis ini.',
                ]);
            }
        }

        foreach ($type['fields'] as $field) {
            if (! ($field['required'] ?? false)) {
                continue;
            }

            $value = $fields[$field['key']] ?? null;
            $empty = $field['type'] === 'links'
                ? ! collect(is_array($value) ? $value : [])->contains(fn ($item) => is_array($item) && trim((string) ($item['url'] ?? '')) !== '')
                : ! is_scalar($value) || trim((string) $value) === '';

            if ($empty) {
                throw ValidationException::withMessages([
                    'fields.'.$field['key'] => $field['label'].' wajib diisi.',
                ]);
            }
        }

        if (($type['logo_required'] ?? false) && ! $hasLogo) {
            throw ValidationException::withMessages([
                'logo' => 'Unggah logo untuk jenis ini.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function decode(mixed $value, string $key): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                $key => 'Format data tidak valid.',
            ]);
        }

        return $decoded;
    }

    private static function hex(string $color, string $fallback): string
    {
        $color = strtolower(trim($color));

        return preg_match('/^#[0-9a-f]{6}$/', $color) ? $color : $fallback;
    }

    public static function logoFile(Request $request): ?UploadedFile
    {
        $file = $request->file('logo');

        return $file instanceof UploadedFile ? $file : null;
    }

    public static function contentFile(Request $request): ?UploadedFile
    {
        $file = $request->file('file');

        return $file instanceof UploadedFile ? $file : null;
    }
}
