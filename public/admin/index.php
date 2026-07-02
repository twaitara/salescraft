<?php
require __DIR__ . '/auth.php';

$rows = sc_db()->query(
    'SELECT id, client_name, client_company, client_email, client_phone, total, percent, band, created_at
     FROM submissions ORDER BY created_at DESC'
)->fetchAll();

function band_color(string $b): string
{
    return [
        'Scalable Engine'   => '#16a34a',
        'Strong Foundation' => '#84cc16',
        'Needs Structure'   => '#f59e0b',
        'High Risk'         => '#ef4444',
    ][$b] ?? '#7c8b99';
}

sc_admin_head('Submissions');
sc_admin_topbar('submissions');
?>
<div class="wrap">
  <div class="page-h">
    <div>
      <h1>Client Submissions</h1>
      <div class="sub"><?= count($rows) ?> completed scorecard<?= count($rows) === 1 ? '' : 's' ?> — only you can see these.</div>
    </div>
  </div>

  <?php if (!$rows): ?>
    <div class="empty">No submissions yet. Share <b>your scorecard link</b> with a client to get started.</div>
  <?php else: ?>
  <table class="tbl">
    <tr><th>Client</th><th>Contact</th><th>Score</th><th>Result</th><th>When</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td>
          <div style="font-weight:600"><?= sc_e($r['client_name']) ?></div>
          <?php if ($r['client_company']): ?><div class="muted" style="font-size:12.5px"><?= sc_e($r['client_company']) ?></div><?php endif; ?>
        </td>
        <td class="muted" style="font-size:13px">
          <?= sc_e($r['client_email']) ?>
          <?php if ($r['client_phone']): ?><div><?= sc_e($r['client_phone']) ?></div><?php endif; ?>
        </td>
        <td style="font-weight:700"><?= (int) $r['total'] ?>/200 <span class="muted" style="font-weight:400">(<?= (int) round($r['percent']) ?>%)</span></td>
        <td><span class="pill" style="background:<?= band_color($r['band']) ?>"><?= sc_e($r['band']) ?></span></td>
        <td class="muted"><?= date('j M Y, H:i', strtotime($r['created_at'])) ?></td>
        <td><a class="rowlink" href="view.php?id=<?= (int) $r['id'] ?>">View &rarr;</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php sc_admin_foot(); ?>
