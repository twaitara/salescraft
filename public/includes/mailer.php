<?php
/**
 * Sends the consultant a notification when a client completes the scorecard.
 * Uses vendored PHPMailer (includes/PHPMailer/src) — no Composer required.
 */
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

/**
 * @param array $cfg  full config array
 * @param array $sub  ['id','client_name','client_company','client_email','total','percent','band','categories']
 * @param ?string $pdf  optional PDF bytes to attach (the completed scorecard)
 * @param ?string $overrideTo  optional recipient override (used by the test-email button)
 * @return array{ok:bool,error:?string}
 */
function sc_send_notification(array $cfg, array $sub, ?string $pdf = null, ?string $overrideTo = null): array
{
    $mail = new PHPMailer(true);
    try {
        $s = $cfg['smtp'];
        $mail->isSMTP();
        $mail->Host       = $s['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $s['username'];
        $mail->Password   = $s['password'];
        $mail->SMTPSecure = $s['secure'] === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $s['port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($s['from_email'], $s['from_name']);
        $mail->addAddress($overrideTo ?: $cfg['notify']['to_email'], $cfg['notify']['to_name']);
        if (!empty($sub['client_email'])) {
            $mail->addReplyTo($sub['client_email'], $sub['client_name']);
        }
        if ($pdf !== null && $pdf !== '') {
            $fname = 'SalesCraft-Scorecard-' . preg_replace('/[^A-Za-z0-9]+/', '-', (string) $sub['client_name']) . '.pdf';
            $mail->addStringAttachment($pdf, $fname, 'base64', 'application/pdf');
        }

        $company = $sub['client_company'] ? " ({$sub['client_company']})" : '';
        $link    = rtrim($cfg['base_url'], '/') . '/admin/view.php?id=' . (int) $sub['id'];
        $pct     = round((float) $sub['percent']);
        $phone   = htmlspecialchars((string) ($sub['client_phone'] ?? ''), ENT_QUOTES) ?: '&mdash;';
        $max     = (int) ($sub['max'] ?? 200);

        $mail->Subject = "New scorecard: {$sub['client_name']}{$company} — {$sub['total']}/{$max} ({$sub['band']})";
        $mail->isHTML(true);

        $rows = '';
        foreach ($sub['categories'] as $c) {
            $cmax = (int) ($c['max'] ?? 20);
            $rows .= '<tr><td style="padding:4px 12px 4px 0;color:#334155;">'
                . htmlspecialchars($c['name'], ENT_QUOTES) . '</td>'
                . '<td style="padding:4px 0;font-weight:700;color:#0f172a;">'
                . (int) $c['score'] . '/' . $cmax . '</td></tr>';
        }

        $mail->Body = <<<HTML
        <div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;color:#0f172a;">
          <h2 style="margin:0 0 4px;">A client just completed the Sales Diagnostic</h2>
          <p style="color:#64748b;margin:0 0 18px;">Here's a quick summary. Open the dashboard for the full breakdown.</p>
          <table style="border-collapse:collapse;margin-bottom:16px;">
            <tr><td style="padding:4px 12px 4px 0;color:#64748b;">Client</td><td style="font-weight:700;">{$sub['client_name']}{$company}</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#64748b;">Email</td><td>{$sub['client_email']}</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#64748b;">Phone</td><td>{$phone}</td></tr>
            <tr><td style="padding:4px 12px 4px 0;color:#64748b;">Score</td><td style="font-weight:700;">{$sub['total']}/{$max} &middot; {$pct}% &middot; {$sub['band']}</td></tr>
          </table>
          <table style="border-collapse:collapse;font-size:14px;margin-bottom:20px;">{$rows}</table>
          <a href="{$link}" style="display:inline-block;background:#f5901e;color:#fff;text-decoration:none;padding:11px 20px;border-radius:10px;font-weight:600;">View full results &rarr;</a>
        </div>
        HTML;

        $mail->AltBody = "New scorecard from {$sub['client_name']}{$company}\n"
            . "Score: {$sub['total']}/{$max} ({$sub['band']})\n"
            . "Client email: {$sub['client_email']}\n"
            . "Client phone: " . ($sub['client_phone'] ?? '') . "\n"
            . "Full results: {$link}";

        $mail->send();
        return ['ok' => true, 'error' => null];
    } catch (MailException $e) {
        return ['ok' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}
