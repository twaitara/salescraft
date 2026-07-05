<?php
require __DIR__ . '/auth.php';

$rows = sc_db()->query(
    'SELECT id, client_name, client_company, client_email, client_phone, total, max_score, percent, band, created_at
     FROM submissions ORDER BY created_at DESC'
)->fetchAll();

// Leads who cleared the gate (name/email/phone) but haven't completed.
$inprog = [];
try {
    $inprog = sc_db()->query(
        'SELECT email, answers, step, meta, updated_at FROM progress
         WHERE email NOT IN (SELECT client_email FROM submissions WHERE client_email IS NOT NULL)
         ORDER BY updated_at DESC'
    )->fetchAll();
} catch (Throwable $e) {
    $inprog = []; // progress table may not exist yet
}
$sc = sc_scorecard();
$numQuestions = 0;
foreach ($sc['categories'] as $c) $numQuestions += count($c['questions'] ?? []);

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
    <div class="stat" style="--accent:var(--s3)"><div class="k"><i data-lucide="hourglass"></i> In progress</div><div class="v"><?= count($inprog) ?></div><div class="m">started, not finished</div></div>
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
        <td data-label="Client">
          <div><div style="font-weight:600"><?= sc_e($r['client_name']) ?></div>
          <?php if ($r['client_company']): ?><div class="muted" style="font-size:12.5px"><?= sc_e($r['client_company']) ?></div><?php endif; ?></div>
        </td>
        <td data-label="Contact" class="muted" style="font-size:13px">
          <div><?= sc_e($r['client_email']) ?>
          <?php if ($r['client_phone']): ?><div><?= sc_e($r['client_phone']) ?></div><?php endif; ?></div>
        </td>
        <td data-label="Score" style="font-weight:700"><?= (int) $r['total'] ?>/<?= (int) ($r['max_score'] ?: 200) ?> <span class="muted" style="font-weight:400">(<?= (int) round($r['percent']) ?>%)</span></td>
        <td data-label="Result"><span class="pill" style="background:<?= band_color($r['band']) ?>"><?= sc_e($r['band']) ?></span></td>
        <td data-label="When" class="muted"><?= date('j M Y, H:i', strtotime($r['created_at'])) ?></td>
        <td data-label=""><a class="rowlink" href="view.php?id=<?= (int) $r['id'] ?>">View &rarr;</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <?php endif; ?>

  <div class="page-h" style="margin-top:34px">
    <div>
      <h1><i data-lucide="hourglass"></i> In progress</h1>
      <div class="sub">Started the scorecard but haven't finished — good people to follow up with.</div>
    </div>
  </div>
  <?php if (!$inprog): ?>
    <div class="empty"><i data-lucide="check-circle"></i><div>Nobody is mid-way right now.</div></div>
  <?php else: ?>
  <div class="tbl-wrap">
  <table class="tbl">
    <tr>
      <th><i data-lucide="user"></i>Person</th>
      <th><i data-lucide="at-sign"></i>Contact</th>
      <th><i data-lucide="list-checks"></i>Progress</th>
      <th><i data-lucide="clock"></i>Last active</th>
      <th></th>
    </tr>
    <?php foreach ($inprog as $p):
        $m = json_decode((string) ($p['meta'] ?? ''), true) ?: [];
        $ans = json_decode((string) ($p['answers'] ?? ''), true);
        $answered = is_array($ans) ? count($ans) : 0;
        $pctDone = $numQuestions ? round($answered / $numQuestions * 100) : 0;
    ?>
      <tr>
        <td data-label="Person">
          <div><div style="font-weight:600"><?= sc_e($m['name'] ?? '—') ?></div>
          <?php if (!empty($m['company'])): ?><div class="muted" style="font-size:12.5px"><?= sc_e($m['company']) ?></div><?php endif; ?></div>
        </td>
        <td data-label="Contact" class="muted" style="font-size:13px">
          <div><?= sc_e($p['email']) ?>
          <?php if (!empty($m['phone'])): ?><div><?= sc_e($m['phone']) ?></div><?php endif; ?></div>
        </td>
        <td data-label="Progress">
          <div style="min-width:120px">
            <div style="font-size:13px;margin-bottom:4px"><b style="font-weight:600"><?= $answered ?></b><span class="muted">/<?= $numQuestions ?> answered · <?= $pctDone ?>%</span></div>
            <div style="height:6px;background:var(--surface2);border-radius:99px;overflow:hidden"><i style="display:block;height:100%;width:<?= $pctDone ?>%;background:var(--brand)"></i></div>
          </div>
        </td>
        <td data-label="Last active" class="muted"><?= date('j M Y, H:i', strtotime($p['updated_at'])) ?></td>
        <td data-label=""><a class="rowlink" href="mailto:<?= sc_e($p['email']) ?>">Email &rarr;</a></td>
      </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php sc_admin_foot(); ?>
