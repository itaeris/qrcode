<?php

namespace App\Qr;

readonly class BuiltQr
{
    /**
     * @param  array<string, mixed>  $presentation
     */
    public function __construct(
        public string $text,
        public string $kind,
        public string $title,
        public ?string $url,
        public array $presentation,
    ) {}
}
