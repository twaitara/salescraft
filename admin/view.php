<?php
require __DIR__ . '/auth.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = sc_db()->prepare('SELECT * FROM submissions WHERE id = ?');
$stmt->execute([$id]);
$sub = $stmt->fetch();
if (!$sub) {
    http_response_code(404);
    exit('Submission not found.');
}

$categories = json_decode($sub['categories'], true) ?: [];
$answers    = json_decode($sub['answers'], true) ?: [];
$titles     = require __DIR__ . '/../includes/questions.php';
$catNames   = array_keys($titles);

function scolor(int $v): string
{
    return ['', '#ef4444', '#f97316', '#f59e0b', '#84cc16', '#16a34a'][$v] ?? '#e6e9f2';
}
function band_color(string $b): string
{
    return ['Scalable Engine'=>'#16a34a','Strong Foundation'=>'#84cc16','Needs Structure'=>'#f59e0b','High Risk'=>'#ef4444'][$b] ?? '#64748b';
}
$pct = (int) round($sub['percent']);
?><!doctype html>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= sc_e($sub['client_name']) ?> — SalesCraft</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{--line:#e6e9f2;--muted:#64748b;--brand:#4f46e5}
  *{box-sizing:border-box}
  body{font-family:Inter,system-ui,Segoe UI,sans-serif;background:#f4f6fb;color:#0f172a;margin:0}
  .wrap{max-width:920px;margin:0 auto;padding:24px 20px 60px}
  a.back{color:var(--muted);text-decoration:none;font-size:14px}
  .head{background:#fff;border-radius:16px;padding:24px;margin:16px 0;box-shadow:0 8px 24px rgba(16,24,40,.05);display:flex;justify-content:space-between;flex-wrap:wrap;gap:16px;align-items:center}
  .head h1{margin:0 0 4px;font-size:22px}
  .head .muted{color:var(--muted);font-size:14px}
  .pill{display:inline-block;padding:5px 14px;border-radius:99px;color:#fff;font-weight:700;font-size:14px}
  .big{font-size:34px;font-weight:800}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  .card{background:#fff;border-radius:16px;padding:22px;box-shadow:0 8px 24px rgba(16,24,40,.05)}
  .card h3{margin:0 0 14px;font-size:15px}
  svg.radar{max-width:100%;height:auto}
  .cat{margin-bottom:16px}
  .cat h4{margin:0 0 8px;font-size:14px;display:flex;justify-content:space-between}
  .cat h4 span{color:var(--muted);font-weight:600}
  .q{display:flex;justify-content:space-between;align-items:center;font-size:13px;padding:5px 0;color:#334155}
  .dot{display:inline-grid;place-items:center;width:22px;height:22px;border-radius:6px;color:#fff;font-weight:700;font-size:12px}
  @media(max-width:680px){.grid{grid-template-columns:1fr}}
</style>
<div class="wrap">
  <a class="back" href="index.php">&larr; All submissions</a>
  <div class="head">
    <div>
      <h1><?= sc_e($sub['client_name']) ?><?= $sub['client_company'] ? ' <span class="muted">· ' . sc_e($sub['client_company']) . '</span>' : '' ?></h1>
      <div class="muted"><?= sc_e($sub['client_email']) ?: 'No email given' ?> &middot; <?= date('j M Y, H:i', strtotime($sub['created_at'])) ?></div>
    </div>
    <div style="text-align:right">
      <div class="big" style="color:<?= band_color($sub['band']) ?>"><?= (int) $sub['total'] ?><span style="font-size:16px;color:var(--muted)">/200</span></div>
      <span class="pill" style="background:<?= band_color($sub['band']) ?>"><?= $pct ?>% · <?= sc_e($sub['band']) ?></span>
    </div>
  </div>

  <div class="grid">
    <div class="card">
      <h3>Balance across 10 areas</h3>
      <div id="radar"></div>
    </div>
    <div class="card">
      <h3>Score by area</h3>
      <?php foreach ($categories as $c): $p = $c['score'] / 20; ?>
        <div style="margin:10px 0">
          <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
            <b style="font-weight:600"><?= sc_e($c['name']) ?></b><span class="muted"><?= (int) $c['score'] ?>/20</span>
          </div>
          <div style="height:8px;background:#eef1f7;border-radius:99px;overflow:hidden">
            <i style="display:block;height:100%;width:<?= $p * 100 ?>%;background:<?= scolor((int) round($c['score'] / 4)) ?>"></i>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card" style="margin-top:16px">
    <h3>Every answer</h3>
    <div class="grid">
    <?php foreach ($catNames as $si => $cat): ?>
      <div class="cat">
        <h4><?= sc_e($cat) ?> <span><?= (int) ($categories[$si]['score'] ?? 0) ?>/20</span></h4>
        <?php foreach ($titles[$cat] as $qi => $qt): $v = (int) ($answers["$si-$qi"] ?? 0); ?>
          <div class="q"><span><?= sc_e($qt) ?></span><span class="dot" style="background:<?= scolor($v) ?>"><?= $v ?: '–' ?></span></div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
const cats = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;
(function(){
  const n=cats.length, cx=230, cy=190, R=150;
  const pt=(i,rad)=>{const a=-Math.PI/2+i*2*Math.PI/n;return[cx+rad*Math.cos(a),cy+rad*Math.sin(a)];};
  let rings='';[0.25,0.5,0.75,1].forEach(f=>{rings+=`<polygon points="${cats.map((_,i)=>pt(i,R*f).join(',')).join(' ')}" fill="none" stroke="#e6e9f2"/>`;});
  let axes='',labels='';
  cats.forEach((c,i)=>{const[x,y]=pt(i,R);axes+=`<line x1="${cx}" y1="${cy}" x2="${x}" y2="${y}" stroke="#e6e9f2"/>`;
    const[lx,ly]=pt(i,R+22);const anc=Math.abs(lx-cx)<8?'middle':(lx>cx?'start':'end');
    labels+=`<text x="${lx}" y="${ly}" font-size="10.5" fill="#64748b" text-anchor="${anc}" dominant-baseline="middle" font-weight="600">${c.name}</text>`;});
  const poly=cats.map((c,i)=>pt(i,R*(c.score/20)).join(',')).join(' ');
  document.getElementById('radar').innerHTML=`<svg class="radar" viewBox="0 0 460 380">${rings}${axes}
    <polygon points="${poly}" fill="rgba(79,70,229,.18)" stroke="#4f46e5" stroke-width="2" stroke-linejoin="round"/>
    ${cats.map((c,i)=>{const[x,y]=pt(i,R*(c.score/20));return `<circle cx="${x}" cy="${y}" r="3.5" fill="#4f46e5"/>`;}).join('')}${labels}</svg>`;
})();
</script>
