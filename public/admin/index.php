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

// Summary stats
$count   = count($rows);
$avgPct  = $count ? round(array_sum(array_map(fn($r) => (float) $r['percent'], $rows)) / $count) : 0;
$thisMon = 0;
$highRisk = 0;
$cm = date('Y-m');
foreach ($rows as $r) {
    if (strpos((string) $r['created_at'], $cm) === 0) $thisMon++;
    if ($r['band'] === 'High Risk') $highRisk++;
}

sc_admin_head('Submissions');
sc_admin_topbar('submissions');
?>
<div class="wrap">
  <div class="page-h">
    <div>
      <h1><i data-lucide="inbox"></i> Client Submissions</h1>
      <div class="sub">Completed scorecards — only you can see these.</div>
    </div>
  </div>

  <div class="stats">
    <div class="stat" style="--accent:var(--brand)"><div class="k"><i data-lucide="users"></i> Total</div><div class="v"><?= $count ?></div><div class="m">completed scorecards</div></div>
    <div class="stat" style="--accent:var(--blue)"><div class="k"><i data-lucide="gauge"></i> Average</div><div class="v"><?= $avgPct ?>%</div><div class="m">across all clients</div></div>
    <div class="stat" style="--accent:var(--violet)"><div class="k"><i data-lucide="calendar"></i> This month</div><div class="v"><?= $thisMon ?></div><div class="m"><?= date('M Y') ?></div></div>
    <div class="stat" style="--accent:var(--s1)"><div class="k"><i data-lucide="alert-triangle"></i> High risk</div><div class="v"><?= $highRisk ?></div><div class="m">need attention</div></div>
  </div>

  <?php if (!$rows): ?>
    <div class="empty"><i data-lucide="clipboard-list"></i><div>No submissions yet. Share <b>your scorecard link</b> with a client to get started.</div></div>
  <?php else: ?>
  <div class="tbl-wrap">
  <table class="tbl">
    <tr>
      <th><i data-lucide="user"></i>Client</th>
      <th><i data-lucide="at-sign"></i>Contact</th>
      <th><i data-lucide="target"></i>Score</th>
      <th><i data-lucide="award"></i>Result</th>
      <th><i data-lucide="clock"></i>When</th>
      <th></th>
    </tr>
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
  </div>
  <?php endif; ?>
</div>
<?php sc_admin_foot(); ?>
