<?php
/**
 * Builds the completed scorecard as a PDF (returned as a binary string).
 * Uses vendored FPDF (includes/fpdf) — no Composer, core fonts only.
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

/** RGB for a result band. */
function sc_pdf_band_rgb(string $b): array
{
    return [
        'Scalable Engine'   => [22, 163, 74],
        'Strong Foundation' => [132, 204, 22],
        'Needs Structure'   => [245, 158, 11],
        'High Risk'         => [239, 68, 68],
    ][$b] ?? [124, 139, 153];
}

/** RGB for a single 1-5 score. */
function sc_pdf_score_rgb(int $v): array
{
    return [1 => [239, 68, 68], 2 => [249, 115, 22], 3 => [245, 158, 11], 4 => [132, 204, 22], 5 => [22, 163, 74]][$v] ?? [210, 214, 220];
}

/**
 * @param array $d  ['client_name','client_company','client_email','client_phone',
 *                   'total','percent','band','categories'=>[['name','score']],
 *                   'answers'=>['si-qi'=>v], 'date'=>'5 Jul 2026']
 * @return string PDF bytes
 */
function sc_build_pdf(array $d): string
{
    $FIX = [
        'Sales Strategy'  => 'Write a one-page ICP and cascade a revenue target down to monthly numbers per rep.',
        'Sales Process'   => 'Map your deal stages with entry/exit criteria and assign an owner to each.',
        'Lead Management' => 'Set a response-time rule (e.g. under 1 hour) and a fixed multi-touch follow-up cadence.',
        'Sales Team'      => 'Run role-plays on discovery and objection handling; build a benefit/ROI cheat-sheet.',
        'Messaging'       => 'Lock a single value proposition and rewrite pitch + website to say it the same way.',
        'Objections'      => 'Document your top 10 objections with a tested response and a proof point for each.',
        'Sales Tools'     => 'Ship a starter kit: call script, updated one-pager, FAQ and a 30-day onboarding plan.',
        'CRM & Data'      => 'Get every live lead into the CRM with source + stage, and turn on follow-up reminders.',
        'Management'      => 'Start a weekly pipeline review and monthly 1:1 coaching against a short KPI set.',
        'Scalability'     => 'Remove single points of failure - document the playbook so anyone can run it.',
    ];
    $titles = require __DIR__ . '/questions.php';
    $catNames = array_keys($titles);

    $cats  = $d['categories'] ?? [];
    $total = (int) ($d['total'] ?? 0);
    $pct   = (int) round((float) ($d['percent'] ?? 0));
    $band  = (string) ($d['band'] ?? '');
    $answers = $d['answers'] ?? [];
    $brand = (string) ($d['brand'] ?? 'SalesCraft');

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AddPage();
    $W = 180; // usable width

    // ---- Header band ----
    $pdf->SetFillColor(245, 144, 30);
    $pdf->Rect(0, 0, 210, 28, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 22);
    $pdf->SetXY(15, 7);
    $pdf->Cell(120, 9, sc_pdf_text($brand), 0, 2);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(120, 6, sc_pdf_text('Sales Diagnostic Scorecard'), 0, 0);

    // ---- Client info ----
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

    // ---- Score summary box ----
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
    $pdf->SetXY(60, $y + 8);
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->SetTextColor(124, 139, 153);
    $pdf->Cell(20, 8, '/ 200', 0, 0);
    // band pill
    $pdf->SetXY(120, $y + 8);
    $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 11);
    $label = ' ' . $pct . '%   ' . $band . ' ';
    $pw = $pdf->GetStringWidth(sc_pdf_text($label)) + 4;
    $pdf->Cell($pw, 9, sc_pdf_text($label), 0, 0, 'C', true);

    // ---- Score by area ----
    $pdf->SetXY(15, $y + 30);
    $pdf->SetTextColor(43, 57, 72);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->Cell($W, 8, sc_pdf_text('Score by area'), 0, 2);
    $pdf->Ln(1);
    $pdf->SetFont('Helvetica', '', 10);
    foreach ($cats as $c) {
        $score = (int) $c['score'];
        $frac  = $score / 20;
        $yy = $pdf->GetY();
        // label
        $pdf->SetTextColor(43, 57, 72);
        $pdf->Cell(58, 7, sc_pdf_text($c['name']), 0, 0);
        // track
        $bx = 75; $bw = 100;
        $pdf->SetFillColor(238, 241, 245);
        $pdf->Rect($bx, $yy + 1.5, $bw, 4.5, 'F');
        $sr = sc_pdf_score_rgb((int) round($score / 4));
        $pdf->SetFillColor($sr[0], $sr[1], $sr[2]);
        if ($frac > 0) $pdf->Rect($bx, $yy + 1.5, $bw * $frac, 4.5, 'F');
        // score
        $pdf->SetXY(178, $yy);
        $pdf->SetTextColor(124, 139, 153);
        $pdf->Cell(17, 7, $score . '/20', 0, 2, 'R');
        $pdf->SetXY(15, $yy + 7.5);
    }

    // ---- Priority fixes ----
    $sorted = $cats;
    usort($sorted, fn($a, $b) => $a['score'] <=> $b['score']);
    $weak = array_slice($sorted, 0, 3);
    $pdf->Ln(3);
    $pdf->SetTextColor(43, 57, 72);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->Cell($W, 8, sc_pdf_text('Your 3 priority fixes'), 0, 2);
    $pdf->Ln(1);
    $i = 1;
    foreach ($weak as $c) {
        $sr = sc_pdf_score_rgb((int) round($c['score'] / 4));
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
        $pdf->Cell(0, 6, sc_pdf_text($c['name'] . '  -  ' . (int) $c['score'] . '/20'), 0, 2);
        $pdf->SetX(24);
        $pdf->SetTextColor(90, 100, 110);
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->MultiCell($W - 9, 5, sc_pdf_text($FIX[$c['name']] ?? ''), 0, 'L');
        $pdf->Ln(2);
        $i++;
    }

    // ---- Every answer ----
    $pdf->AddPage();
    $pdf->SetTextColor(43, 57, 72);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->Cell($W, 8, sc_pdf_text('Every answer'), 0, 2);
    $pdf->Ln(1);
    foreach ($catNames as $si => $cat) {
        $catScore = (int) ($cats[$si]['score'] ?? 0);
        $pdf->SetFont('Helvetica', 'B', 10.5);
        $pdf->SetTextColor(43, 57, 72);
        $pdf->Cell(0, 7, sc_pdf_text($cat . '  (' . $catScore . '/20)'), 0, 2);
        $pdf->SetFont('Helvetica', '', 9.5);
        foreach ($titles[$cat] as $qi => $qt) {
            $v = (int) ($answers["$si-$qi"] ?? 0);
            $yy = $pdf->GetY();
            $pdf->SetTextColor(90, 100, 110);
            $pdf->Cell(150, 6, sc_pdf_text('   ' . $qt), 0, 0);
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

    // Footer note
    $pdf->SetY(-15);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(150, 158, 166);
    $pdf->Cell(0, 6, sc_pdf_text($brand . ' - Sales Diagnostic Scorecard'), 0, 0, 'C');

    return $pdf->Output('S');
}
