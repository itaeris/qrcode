<?php

namespace App\Qr;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Uploads
{
    public function assertLogo(UploadedFile $file): void
    {
        Validator::make(
            ['logo' => $file],
            ['logo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,gif', 'max:2048']],
        )->validate();
    }

    public function assertContent(UploadedFile $file, string $type): void
    {
        $rules = match ($type) {
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:15360'],
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:15360'],
            'png' => ['required', 'file', 'mimes:png', 'max:15360'],
            'audio' => ['required', 'file', 'mimes:mp3,wav,m4a,ogg,aac', 'max:20480'],
            'video' => ['required', 'file', 'mimes:mp4,webm,mov', 'max:51200'],
            'pptx' => ['required', 'file', 'mimes:ppt,pptx', 'max:20480'],
            'excel' => ['required', 'file', 'mimes:xls,xlsx,csv', 'max:15360'],
            'file' => ['required', 'file', 'max:20480'],
            default => throw ValidationException::withMessages(['file' => 'This type does not accept a file.']),
        };

        Validator::make(['file' => $file], ['file' => $rules])->validate();
        $extension = strtolower($file->getClientOriginalExtension());
        $blocked = ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd', 'com', 'cgi', 'htaccess', 'js', 'mjs', 'html', 'htm', 'svg', 'xml'];

        if (in_array($extension, $blocked, true)) {
            throw ValidationException::withMessages(['file' => 'This file type is not allowed.']);
        }
    }

    public function storeLogo(string $code, UploadedFile $file): string
    {
        $this->assertLogo($file);
        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'png',
        };

        Storage::disk('local')->delete(array_filter([
            "qr-logos/{$code}.png",
            "qr-logos/{$code}.jpg",
            "qr-logos/{$code}.webp",
            "qr-logos/{$code}.gif",
        ]));

        $path = "qr-logos/{$code}.{$extension}";
        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()) ?: '');

        return $path;
    }

    public function storeContent(string $code, UploadedFile $file, string $type): array
    {
        $this->assertContent($file, $type);
        Storage::disk('local')->deleteDirectory("qr-files/{$code}");
        $name = $this->filename($file);
        $path = "qr-files/{$code}/{$name}";
        Storage::disk('local')->putFileAs("qr-files/{$code}", $file, $name);

        return [
            'path' => $path,
            'name' => $name,
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
        ];
    }

    public function deleteFiles(string $code, ?string $logoPath): void
    {
        Storage::disk('local')->deleteDirectory("qr-files/{$code}");

        if ($logoPath) {
            Storage::disk('local')->delete($logoPath);
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    public function readLogo(?string $path): array
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return [null, null];
        }

        return [
            Storage::disk('local')->get($path),
            Storage::disk('local')->mimeType($path) ?: 'image/png',
        ];
    }

    private function filename(UploadedFile $file): string
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $name = preg_replace('/[^A-Za-z0-9_-]+/', '-', $name) ?? 'berkas';
        $name = trim($name, '-');
        $name = substr($name !== '' ? $name : 'berkas', 0, 60);
        $extension = strtolower($file->getClientOriginalExtension());
        $extension = preg_replace('/[^a-z0-9]+/', '', $extension) ?? '';

        return $extension !== '' ? $name.'.'.$extension : $name;
    }
}
