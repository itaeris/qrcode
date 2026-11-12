<?php

namespace Tests\Unit;

use App\Qr\QrInput;
use App\Qr\SvgRenderer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SvgRendererTest extends TestCase
{
    #[Test]
    public function it_renders_svg_with_the_chosen_color_and_logo(): void
    {
        $renderer = new SvgRenderer;
        $style = QrInput::defaultStyle();
        $style['fg'] = '#112233';
        $svg = $renderer->render('https://contoh.com', $style);

        $this->assertStringStartsWith('<svg', $svg);
        $this->assertStringContainsString('#112233', $svg);

        $style['shape'] = 'rounded';
        $rounded = $renderer->render('https://contoh.com', $style);
        $this->assertStringContainsString('Q', $rounded);

        $style['shape'] = 'dots';
        $dots = $renderer->render('https://contoh.com', $style);
        $this->assertStringContainsString('a0.45', $dots);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $withLogo = $renderer->render('https://contoh.com', $style, $png, 'image/png');

        $this->assertStringContainsString('data:image/png;base64,', $withLogo);
    }
}
