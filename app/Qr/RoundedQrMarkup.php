<?php

namespace App\Qr;

use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRMarkupSVG;

class RoundedQrMarkup extends QRMarkupSVG
{
    protected function moduleTransform(int $x, int $y, int $M_TYPE, int $M_TYPE_LAYER): ?string
    {
        if (! $this->options->drawLightModules && ! $this->matrix->isDark($M_TYPE)) {
            return null;
        }

        $square = ! $this->matrix->isDark($M_TYPE)
            || $this->matrix->checkType($x, $y, QRMatrix::M_FINDER)
            || $this->matrix->checkType($x, $y, QRMatrix::M_FINDER_DOT)
            || $this->matrix->checkType($x, $y, QRMatrix::M_ALIGNMENT);

        if ($square) {
            return "M{$x} {$y} h1 v1 h-1Z";
        }

        $radius = 0.22;
        $right = $x + 1;
        $bottom = $y + 1;
        $x1 = $x + $radius;
        $y1 = $y + $radius;
        $x2 = $right - $radius;
        $y2 = $bottom - $radius;

        return "M{$x1} {$y} H{$x2} Q{$right} {$y} {$right} {$y1} V{$y2} Q{$right} {$bottom} {$x2} {$bottom} H{$x1} Q{$x} {$bottom} {$x} {$y2} V{$y1} Q{$x} {$y} {$x1} {$y} Z";
    }
}
