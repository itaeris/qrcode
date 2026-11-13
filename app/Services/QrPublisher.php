<?php

namespace App\Services;

use App\Models\QrLink;
use App\Qr\PayloadBuilder;
use App\Qr\QrInput;
use App\Qr\SvgRenderer;
use App\Qr\Uploads;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class QrPublisher
{
    public function __construct(
        private PayloadBuilder $payloads,
        private SvgRenderer $renderer,
        private Uploads $uploads,
    ) {}

    /**
     * @return array{link: QrLink, svg: string, url: string}
     */
    public function store(QrInput $input, ?UploadedFile $file, ?UploadedFile $logo, int $userId): array
    {
        $built = $this->payloads->build($input->type, $input->fields);
        $code = QrLink::issueCode();
        $stored = $file ? $this->uploads->storeContent($code, $file, $input->type) : null;
        $logoPath = $logo ? $this->uploads->storeLogo($code, $logo) : null;

        $link = QrLink::query()->create([
            'user_id' => $userId,
            'code' => $code,
            'secret' => Str::random(40),
            'type' => $input->type,
            'title' => $input->title !== '' ? $input->title : $built->title,
            'kind' => $stored ? 'page' : $built->kind,
            'payload' => $input->fields,
            'style' => $input->style,
            'presentation' => $built->presentation,
            'target_url' => $built->url,
            'file_path' => $stored['path'] ?? null,
            'file_name' => $stored['name'] ?? null,
            'file_mime' => $stored['mime'] ?? null,
            'logo_path' => $logoPath,
        ]);

        return [
            'link' => $link,
            'svg' => $this->svgFor($link),
            'url' => route('qr.show', $link->code),
        ];
    }

    /**
     * @return array{link: QrLink, svg: string, url: string}
     */
    public function update(QrLink $link, QrInput $input, ?UploadedFile $file, ?UploadedFile $logo): array
    {
        $built = $this->payloads->build($input->type, $input->fields);
        $stored = null;

        if ($file) {
            $stored = $this->uploads->storeContent($link->code, $file, $input->type);
        } elseif ($input->removeFile && $link->file_path) {
            Storage::disk('local')->deleteDirectory('qr-files/'.$link->code);
        }

        if ($logo) {
            $link->logo_path = $this->uploads->storeLogo($link->code, $logo);
        }

        $link->fill([
            'type' => $input->type,
            'title' => $input->title !== '' ? $input->title : $built->title,
            'kind' => ($stored || ($link->file_path && ! $input->removeFile && ! $file)) ? 'page' : $built->kind,
            'payload' => $input->fields,
            'style' => $input->style,
            'presentation' => $built->presentation,
            'target_url' => $built->url,
        ]);

        if ($stored) {
            $link->file_path = $stored['path'];
            $link->file_name = $stored['name'];
            $link->file_mime = $stored['mime'];
        } elseif ($input->removeFile) {
            $link->file_path = null;
            $link->file_name = null;
            $link->file_mime = null;
        }

        $link->save();

        return [
            'link' => $link->refresh(),
            'svg' => $this->svgFor($link),
            'url' => route('qr.show', $link->code),
        ];
    }

    public function destroy(QrLink $link): void
    {
        $this->uploads->deleteFiles($link->code, $link->logo_path);
        $link->delete();
    }

    public function svgFor(QrLink $link): string
    {
        [$logo, $mime] = $this->uploads->readLogo($link->logo_path);

        return $this->render(route('qr.show', $link->code), $link->style, $logo, $mime);
    }

    /**
     * @param  array{fg: string, bg: string, shape: string, ecc: string, margin: int}  $style
     */
    public function render(string $text, array $style, ?string $logo = null, ?string $mime = null): string
    {
        try {
            return $this->renderer->render($text, $style, $logo, $mime);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'fields' => 'A QR cannot be built from this data. Shorten the content.',
            ]);
        }
    }
}
