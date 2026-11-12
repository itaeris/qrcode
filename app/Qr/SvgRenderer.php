<?php

namespace App\Qr;

use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use InvalidArgumentException;

class SvgRenderer
{
    /**
     * @param  array{fg: string, bg: string, shape: string, ecc: string, margin: int}  $style
     */
    public function render(string $text, array $style, ?string $logo = null, ?string $logoMime = null): string
    {
        $text = str_replace("\0", '', $text);

        if (trim($text) === '') {
            throw new InvalidArgumentException('QR kosong.');
        }

        $foreground = $this->hex($style['fg'] ?? '#111827', '#111827');
        $background = $this->hex($style['bg'] ?? '#ffffff', '#ffffff');
        $shape = in_array($style['shape'] ?? 'square', ['square', 'rounded', 'dots'], true) ? $style['shape'] : 'square';
        $margin = max(0, min(8, (int) ($style['margin'] ?? 4)));
        $ecc = in_array($style['ecc'] ?? 'M', ['L', 'M', 'Q', 'H'], true) ? $style['ecc'] : 'M';

        if ($logo) {
            $ecc = 'H';
        }

        $options = [
            'outputInterface' => $shape === 'rounded' ? RoundedQrMarkup::class : QRMarkupSVG::class,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'svgUseFillAttributes' => true,
            'drawLightModules' => true,
            'addQuietzone' => $margin > 0,
            'quietzoneSize' => max(1, $margin),
            'eccLevel' => $ecc,
            'drawCircularModules' => $shape === 'dots',
            'circleRadius' => 0.45,
            'keepAsSquare' => [
                QRMatrix::M_FINDER_DARK,
                QRMatrix::M_FINDER,
                QRMatrix::M_FINDER_DOT,
                QRMatrix::M_ALIGNMENT_DARK,
                QRMatrix::M_ALIGNMENT,
            ],
            'moduleValues' => $this->colors($foreground, $background),
            'addLogoSpace' => false,
        ];

        $logoSpace = null;

        if ($logo) {
            $probe = new QRCode(new QROptions($options));
            $probe->addByteSegment($text);
            $quiet = $options['addQuietzone'] ? $options['quietzoneSize'] : 0;
            $dimension = $probe->getQRMatrix()->getSize() - ($quiet * 2);
            $space = (int) floor($dimension * 0.2);

            if ($space % 2 === 0) {
                $space--;
            }

            $space = max(3, min($space, $dimension - 16));
            $options['addLogoSpace'] = true;
            $options['logoSpaceWidth'] = $space;
            $options['logoSpaceHeight'] = $space;
            $logoSpace = $space;
        }

        $qr = new QRCode(new QROptions($options));
        $qr->addByteSegment($text);
        $svg = $qr->render();
        $svg = preg_replace(
            '/(<svg\b[^>]*>)/',
            '$1<rect width="100%" height="100%" fill="'.$background.'"/>',
            $svg,
            1,
        ) ?? $svg;

        if ($logo && $logoSpace && $logoMime) {
            $svg = $this->embedLogo($svg, $logo, $logoMime, $logoSpace, $options['addQuietzone'] ? $options['quietzoneSize'] : 0, $background);
        }

        if (preg_match('/<script|javascript:|foreignObject|\son[a-z]+\s*=/i', $svg) === 1) {
            throw new InvalidArgumentException('QR image was rejected.');
        }

        return $svg;
    }

    /**
     * @return array<int, string>
     */
    private function colors(string $foreground, string $background): array
    {
        $dark = [
            QRMatrix::M_DARKMODULE,
            QRMatrix::M_DATA_DARK,
            QRMatrix::M_FINDER_DARK,
            QRMatrix::M_ALIGNMENT_DARK,
            QRMatrix::M_TIMING_DARK,
            QRMatrix::M_FORMAT_DARK,
            QRMatrix::M_VERSION_DARK,
            QRMatrix::M_FINDER_DOT,
            QRMatrix::M_SEPARATOR_DARK,
            QRMatrix::M_QUIETZONE_DARK,
            QRMatrix::M_LOGO_DARK,
        ];
        $light = [
            QRMatrix::M_DATA,
            QRMatrix::M_FINDER,
            QRMatrix::M_SEPARATOR,
            QRMatrix::M_ALIGNMENT,
            QRMatrix::M_TIMING,
            QRMatrix::M_FORMAT,
            QRMatrix::M_VERSION,
            QRMatrix::M_QUIETZONE,
            QRMatrix::M_LOGO,
            QRMatrix::M_DARKMODULE_LIGHT,
            QRMatrix::M_FINDER_DOT_LIGHT,
        ];
        $colors = [];

        foreach ($dark as $type) {
            $colors[$type] = $foreground;
        }

        foreach ($light as $type) {
            $colors[$type] = $background;
        }

        return $colors;
    }

    private function embedLogo(string $svg, string $logo, string $mime, int $space, int $margin, string $background): string
    {
        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/webp', 'image/gif'], true)) {
            return $svg;
        }

        if (! preg_match('/viewBox="0 0 (\d+(?:\.\d+)?) (\d+(?:\.\d+)?)"/', $svg, $match)) {
            return $svg;
        }

        $total = (float) $match[1];
        $start = $margin + (($total - ($margin * 2) - $space) / 2);
        $pad = 0.35;
        $image = sprintf(
            '<rect x="%1$F" y="%1$F" width="%2$d" height="%2$d" rx="0.55" fill="%3$s"/>'
            .'<image href="data:%4$s;base64,%5$s" x="%6$F" y="%6$F" width="%7$F" height="%7$F" preserveAspectRatio="xMidYMid meet"/>',
            $start,
            $space,
            $background,
            $mime,
            base64_encode($logo),
            $start + $pad,
            $space - ($pad * 2),
        );

        return preg_replace('/<\/svg>\s*$/', $image.'</svg>', $svg, 1) ?? $svg;
    }

    private function hex(string $color, string $fallback): string
    {
        $color = strtolower(trim($color));

        return preg_match('/^#[0-9a-f]{6}$/', $color) ? $color : $fallback;
    }
}
