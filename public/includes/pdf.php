<?php
/**
 * Builds the completed scorecard as a PDF (returned as a binary string).
 * Renders entirely from the submission snapshot passed in — categories each
 * carry name/score/max/fix and their questions [{t,v}] — so it is independent
 * of any later edits to the scorecard. Uses vendored FPDF (core fonts).
 */
declare(strict_types=1);

require_once __DIR__ . '/fpdf/fpdf.php';

/** Convert UTF-8 text to something the core fonts (cp1252) can render. */
function sc_pdf_text(string $s): string
{
    $s = strtr($s, [
        "\u{2013}" => '-', "\u{2014}" => '-', "\u{2022}" => '-', "\u{00B7}" => '-',
        "\u{2026}" => '...', "\u{2019}" => "'", "\u{2018}" => "'",
        "\u{201C}" => '"', "\u{201D}" => '"', "\u{2192}" => '->', "\u{00A0}" => ' ',
    ]);
    if (function_exists('iconv')) {
        $o = @iconv('UTF-8', 'windows-1252//TRANSLIT', $s);
        if ($o !== false) return $o;
    }
    return mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
}

function sc_pdf_band_rgb(string $b): array
{
    return [
        'Scalable Engine'   => [22, 163, 74],
        'Strong Foundation' => [132, 204, 22],
        'Needs Structure'   => [245, 158, 11],
        'High Risk'         => [239, 68, 68],
    ][$b] ?? [124, 139, 153];
}

function sc_pdf_score_rgb(int $v): array
{
    return [1 => [239, 68, 68], 2 => [249, 115, 22], 3 => [245, 158, 11], 4 => [132, 204, 22], 5 => [22, 163, 74]][$v] ?? [210, 214, 220];
}

/** Colour index 1..5 from a 0..1 fraction. */
function sc_pdf_frac_idx(float $frac): int
{
    $i = (int) round($frac * 5);
    return max(1, min(5, $i ?: 1));
}

/**
 * @param array $d  ['client_name','client_company','client_email','client_phone',
 *                   'total','max','band','brand','date',
 *                   'categories'=>[['name','score','max','fix','questions'=>[['t','v']]]]]
 * @return string PDF bytes
 */
function sc_build_pdf(array $d): string
{
    $cats  = $d['categories'] ?? [];
    $total = (int) ($d['total'] ?? 0);
    $max   = (int) ($d['max'] ?? array_sum(array_map(fn($c) => (int) ($c['max'] ?? 0), $cats)));
    if ($max <= 0) $max = 1;
    $pct   = (int) round($total / $max * 100);
    $band  = (string) ($d['band'] ?? '');
    $brand = (string) ($d['brand'] ?? 'SalesCraft');

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AddPage();
    $W = 180;

    // Header band
    $pdf->SetFillColor(245, 144, 30);
    $pdf->Rect(0, 0, 210, 28, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 22);
    $pdf->SetXY(15, 7);
    $pdf->Cell(150, 9, sc_pdf_text($brand . ' Scorecard'), 0, 2);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(150, 6, sc_pdf_text('Sales Diagnostic'), 0, 0);

    // Client info
    $pdf->SetTextColor(43, 57, 72);
    $pdf->SetXY(15, 36);
    $pdf->SetFont('Helvetica', 'B', 13);
    $who = $d['client_name'] ?? '';
    if (!empty($d['client_company'])) $who .= '  (' . $d['client_company'] . ')';
    $pdf->Cell($W, 7, sc_pdf_text($who), 0, 2);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(124, 139, 153);
    $meta = trim(($d['client_email'] ?? '') . '   ' . ($d['client_phone'] ?? ''));
    if (!empty($d['date'])) $meta .= '   ' . $d['date'];
    $pdf->Cell($W, 6, sc_pdf_text($meta), 0, 2);

    // Score summary
    $pdf->Ln(3);
    $y = $pdf->GetY();
    $pdf->SetDrawColor(231, 234, 239);
    $pdf->SetFillColor(250, 250, 251);
    $pdf->Rect(15, $y, $W, 24, 'DF');
    $rgb = sc_pdf_band_rgb($band);
    $pdf->SetXY(20, $y + 4);
    $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
    $pdf->SetFont('Helvetica', 'B', 28);
    $pdf->Cell(46, 16, (string) $total, 0, 0);
    $pdf->SetXY(58, $y + 8);
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->SetTextColor(124, 139, 153);
    $pdf->Cell(30, 8, '/ ' . $max, 0, 0);
    $pdf->SetXY(120, $y + 8);
    $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 11);
    $label = ' ' . $pct . '%   ' . $band . ' ';
    $pw = $pdf->GetStringWidth(sc_pdf_text($label)) + 4;
    $pdf->Cell($pw, 9, sc_pdf_text($label), 0, 0, 'C', true);

    // Score by area
    $pdf->SetXY(15, $y + 30);
    $pdf->SetTextColor(43, 57, 72);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->Cell($W, 8, sc_pdf_text('Score by area'), 0, 2);
    $pdf->Ln(1);
    $pdf->SetFont('Helvetica', '', 10);
    foreach ($cats as $c) {
        $score = (int) $c['score'];
        $cmax  = max(1, (int) ($c['max'] ?? 20));
        $frac  = $score / $cmax;
        $yy = $pdf->GetY();
        $pdf->SetTextColor(43, 57, 72);
        $pdf->Cell(58, 7, sc_pdf_text($c['name']), 0, 0);
        $bx = 75; $bw = 100;
        $pdf->SetFillColor(238, 241, 245);
        $pdf->Rect($bx, $yy + 1.5, $bw, 4.5, 'F');
        $sr = sc_pdf_score_rgb(sc_pdf_frac_idx($frac));
        $pdf->SetFillColor($sr[0], $sr[1], $sr[2]);
        if ($frac > 0) $pdf->Rect($bx, $yy + 1.5, $bw * $frac, 4.5, 'F');
        $pdf->SetXY(178, $yy);
        $pdf->SetTextColor(124, 139, 153);
        $pdf->Cell(17, 7, $score . '/' . $cmax, 0, 2, 'R');
        $pdf->SetXY(15, $yy + 7.5);
    }

    // Priority fixes (3 lowest by fraction)
    $ranked = $cats;
    usort($ranked, function ($a, $b) {
        $fa = ((int) $a['score']) / max(1, (int) ($a['max'] ?? 20));
        $fb = ((int) $b['score']) / max(1, (int) ($b['max'] ?? 20));
        return $fa <=> $fb;
    });
    $weak = array_slice($ranked, 0, 3);
    $pdf->Ln(3);
    $pdf->SetTextColor(43, 57, 72);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->Cell($W, 8, sc_pdf_text('Priority fixes'), 0, 2);
    $pdf->Ln(1);
    $i = 1;
    foreach ($weak as $c) {
        if (($c['fix'] ?? '') === '') continue;
        $frac = ((int) $c['score']) / max(1, (int) ($c['max'] ?? 20));
        $sr = sc_pdf_score_rgb(sc_pdf_frac_idx($frac));
        $yy = $pdf->GetY();
        $pdf->SetFillColor($sr[0], $sr[1], $sr[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Rect(15, $yy, 6, 6, 'F');
        $pdf->SetXY(15, $yy);
        $pdf->Cell(6, 6, (string) $i, 0, 0, 'C');
        $pdf->SetXY(24, $yy);
        $pdf->SetTextColor(43, 57, 72);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(0, 6, sc_pdf_text($c['name'] . '  -  ' . (int) $c['score'] . '/' . (int) ($c['max'] ?? 20)), 0, 2);
        $pdf->SetX(24);
        $pdf->SetTextColor(90, 100, 110);
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->MultiCell($W - 9, 5, sc_pdf_text((string) $c['fix']), 0, 'L');
        $pdf->Ln(2);
        $i++;
    }

    // Every answer
    $pdf->AddPage();
    $pdf->SetTextColor(43, 57, 72);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->Cell($W, 8, sc_pdf_text('Every answer'), 0, 2);
    $pdf->Ln(1);
    foreach ($cats as $c) {
        $pdf->SetFont('Helvetica', 'B', 10.5);
        $pdf->SetTextColor(43, 57, 72);
        $pdf->Cell(0, 7, sc_pdf_text($c['name'] . '  (' . (int) $c['score'] . '/' . (int) ($c['max'] ?? 20) . ')'), 0, 2);
        $pdf->SetFont('Helvetica', '', 9.5);
        foreach (($c['questions'] ?? []) as $q) {
            $v = (int) ($q['v'] ?? 0);
            $yy = $pdf->GetY();
            $pdf->SetTextColor(90, 100, 110);
            $pdf->Cell(150, 6, sc_pdf_text('   ' . ($q['t'] ?? '')), 0, 0);
            $sr = sc_pdf_score_rgb($v);
            $pdf->SetFillColor($sr[0], $sr[1], $sr[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetX(178);
            $pdf->Cell(7, 5.5, $v ? (string) $v : '-', 0, 2, 'C', true);
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->SetXY(15, $yy + 6);
        }
        $pdf->Ln(2);
    }

    $pdf->SetY(-15);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(150, 158, 166);
    $pdf->Cell(0, 6, sc_pdf_text($brand . ' Scorecard - Sales Diagnostic'), 0, 0, 'C');

    return $pdf->Output('S');
}
