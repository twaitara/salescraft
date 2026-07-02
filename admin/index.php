<?php
require __DIR__ . '/auth.php';

$rows = sc_db()->query(
    'SELECT id, client_name, client_company, client_email, total, percent, band, created_at
     FROM submissions ORDER BY created_at DESC'
)->fetchAll();

function band_color(string $b): string
{
    return [
        'Scalable Engine'   => '#16a34a',
        'Strong Foundation' => '#84cc16',
        'Needs Structure'   => '#f59e0b',
        'High Risk'         => '#ef4444',
    ][$b] ?? '#64748b';
}
?><!doctype html>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Submissions — SalesCraft Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{--line:#e6e9f2;--muted:#64748b;--brand:#4f46e5}
  *{box-sizing:border-box}
  body{font-family:Inter,system-ui,Segoe UI,sans-serif;background:#f4f6fb;color:#0f172a;margin:0}
  .wrap{max-width:1000px;margin:0 auto;padding:28px 20px 60px}
  .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
  .top h1{font-size:20px;margin:0}
  .logout{color:var(--muted);text-decoration:none;font-size:14px}
  table{width:100%;border-collapse:collapse;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 1px 2px rgba(16,24,40,.04),0 8px 24px rgba(16,24,40,.05)}
  th,td{text-align:left;padding:14px 16px;font-size:14px;border-bottom:1px solid var(--line)}
  th{font-size:12px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);background:#fbfcfe}
  tr:last-child td{border-bottom:none}
  tr:hover td{background:#fafbff}
  .pill{display:inline-block;padding:3px 10px;border-radius:99px;color:#fff;font-size:12px;font-weight:700}
  .score{font-weight:700}
  a.row{color:var(--brand);text-decoration:none;font-weight:600}
  .muted{color:var(--muted)}
  .empty{background:#fff;padding:48px;text-align:center;border-radius:14px;color:var(--muted)}
</style>
<div class="wrap">
  <div class="top">
    <h1>Client Submissions <span class="muted">(<?= count($rows) ?>)</span></h1>
    <a class="logout" href="?logout=1">Sign out</a>
  </div>

  <?php if (!$rows): ?>
    <div class="empty">No submissions yet. Share the scorecard link with a client to get started.</div>
  <?php else: ?>
  <table>
    <tr><th>Client</th><th>Email</th><th>Score</th><th>Result</th><th>When</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td>
          <div style="font-weight:600"><?= sc_e($r['client_name']) ?></div>
          <?php if ($r['client_company']): ?><div class="muted" style="font-size:12.5px"><?= sc_e($r['client_company']) ?></div><?php endif; ?>
        </td>
        <td class="muted"><?= sc_e($r['client_email']) ?: '&mdash;' ?></td>
        <td class="score"><?= (int) $r['total'] ?>/200 <span class="muted" style="font-weight:400">(<?= (int) round($r['percent']) ?>%)</span></td>
        <td><span class="pill" style="background:<?= band_color($r['band']) ?>"><?= sc_e($r['band']) ?></span></td>
        <td class="muted"><?= date('j M Y, H:i', strtotime($r['created_at'])) ?></td>
        <td><a class="row" href="view.php?id=<?= (int) $r['id'] ?>">View &rarr;</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
