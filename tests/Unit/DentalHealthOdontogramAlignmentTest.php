<?php

namespace Tests\Unit;

use App\Http\Controllers\Dentist\DentistReportController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use setasign\Fpdi\Fpdi;

class DentalHealthOdontogramAlignmentTest extends TestCase
{
    public function test_pdf_marks_match_template_teeth_and_status_cells_for_every_surface_and_color(): void
    {
        $rows = [
            [[55, 54, 53, 52, 51, 61, 62, 63, 64, 65], [224, 268, 313, 357, 401, 456, 501, 545, 590, 634], 78, 12],
            [[18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28], [90, 135, 179, 224, 268, 313, 357, 401, 456, 501, 545, 590, 634, 679, 723, 768], 185, 119],
            [[48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38], [90, 135, 179, 224, 268, 313, 357, 401, 456, 501, 545, 590, 634, 679, 723, 768], 220, 253],
            [[85, 84, 83, 82, 81, 71, 72, 73, 74, 75], [229, 273, 318, 362, 406, 451, 496, 540, 585, 629], 327, 362],
        ];
        $controller = new DentistReportController;
        $draw = new ReflectionMethod($controller, 'drawDentalHealthOdontogram');
        $records = [
            ['code' => 'D', 'colorHex' => '#ef4444'],
            ['code' => 'LC', 'colorHex' => '#3b82f6'],
            ['code' => 'RF', 'colorHex' => '#22c55e'],
            ['code' => 'F', 'colorHex' => '#a855f7'],
            ['code' => 'X', 'colorHex' => '#f59e0b'],
        ];
        foreach ($rows as [$teeth, $centers, $nativeY, $statusY]) {
            foreach ($teeth as $index => $tooth) {
                foreach (['whole', 'top', 'right', 'bottom', 'left', 'center', 'multi', 'threeD', 'three_d'] as $mode) {
                    $pdf = new OdontogramAlignmentPdf('P', 'pt', [612, 792]);
                    $pdf->SetAutoPageBreak(false);
                    $pdf->AddPage();
                    $record = $records[$index % count($records)];
                    $item = ['tooth' => $tooth];
                    if ($mode === 'multi') {
                        $item['surfaces'] = array_combine(['top', 'right', 'bottom', 'left', 'center'], $records);
                    } elseif (in_array($mode, ['whole', 'threeD', 'three_d'], true)) {
                        $item[$mode === 'whole' ? 'status' : $mode] = $record;
                    } else {
                        $item['surfaces'][$mode] = $record;
                    }
                    $original = $item;
                    $draw->invoke($controller, $pdf, [$item]);
                    self::assertSame($original, $item);
                    self::assertCount($mode === 'multi' ? 5 : 1, $pdf->paths);
                    $cx = 72 + $centers[$index] * 467.844818 / 858;
                    $cy = 221.579742 + $nativeY * 217.080017 / 413;
                    foreach ($pdf->paths as $pathIndex => $path) {
                        $pathRecord = $mode === 'multi' ? $records[$pathIndex] : $record;
                        $hex = ltrim($pathRecord['colorHex'], '#');
                        self::assertStringStartsWith(sprintf('%.3F %.3F %.3F rg',
                            hexdec(substr($hex, 0, 2)) / 255,
                            hexdec(substr($hex, 2, 2)) / 255,
                            hexdec(substr($hex, 4, 2)) / 255), $path);
                        preg_match_all('/(-?\d+\.\d+) (-?\d+\.\d+) [mlc]/', $path, $points, PREG_SET_ORDER);
                        foreach ($points as $point) {
                            $dx = (float) $point[1] - $cx;
                            $dy = 792 - (float) $point[2] - $cy;
                            self::assertLessThan(8.9, hypot($dx, $dy), "Tooth $tooth / $mode");
                            if ($mode === 'center') {
                                self::assertLessThan(4.6, hypot($dx, $dy));
                            }
                            if ($mode === 'top') {
                                self::assertLessThan(0, $dy);
                            }
                            if ($mode === 'bottom') {
                                self::assertGreaterThan(0, $dy);
                            }
                            if ($mode === 'left') {
                                self::assertLessThan(0, $dx);
                            }
                            if ($mode === 'right') {
                                self::assertGreaterThan(0, $dx);
                            }
                        }
                    }
                    $expected = $mode === 'multi' ? $records[4] : $record;
                    self::assertSame($expected['code'], $pdf->labels[0]['code']);
                    $halfSize = count($teeth) / 2;
                    $left = $index < $halfSize ? ($halfSize === 5 ? 202 : 69) : 434;
                    $column = $index % $halfSize;
                    $boxX = 72 + ($left + $column * 44.4) * 467.844818 / 858;
                    $boxY = 221.579742 + $statusY * 217.080017 / 413;
                    self::assertEqualsWithDelta($boxX, $pdf->labels[0]['x'], 0.01);
                    self::assertGreaterThan($boxY + 8, $pdf->labels[0]['y']);
                    self::assertLessThan($boxY + 18, $pdf->labels[0]['y'] + $pdf->labels[0]['height']);
                    self::assertEqualsWithDelta($boxX + 1, $pdf->bars[0][0], 0.01);
                    self::assertEqualsWithDelta($boxY + 1, $pdf->bars[0][1], 0.01);
                    self::assertStringStartsWith('%PDF-', $pdf->Output('S'));
                }
            }
        }
    }
}

class OdontogramAlignmentPdf extends Fpdi
{
    public array $paths = [];

    public array $labels = [];

    public array $bars = [];

    public function _out($s)
    {
        if (str_ends_with($s, ' h f')) {
            $this->paths[] = $s;
        }
        parent::_out($s);
    }

    public function Rect($x, $y, $w, $h, $style = '')
    {
        $this->bars[] = [$x, $y, $w, $h];
        parent::Rect($x, $y, $w, $h, $style);
    }

    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        $this->labels[] = ['x' => $this->GetX(), 'y' => $this->GetY(), 'height' => $h, 'code' => $txt];
        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }
}
