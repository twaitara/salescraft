<?php
require __DIR__ . '/auth.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = sc_db()->prepare('SELECT * FROM submissions WHERE id = ?');
$stmt->execute([$id]);
$sub = $stmt->fetch();
if (!$sub) {
    http_response_code(404);
    sc_admin_head('Not found');
    sc_admin_topbar('submissions');
    echo '<div class="wrap"><div class="empty">Submission not found.</div></div>';
    sc_admin_foot();
    exit;
}

$categories = json_decode($sub['categories'], true) ?: [];
$answers    = json_decode($sub['answers'], true) ?: [];
$titles     = require __DIR__ . '/../includes/questions.php';
$catNames   = array_keys($titles);

function scolor(int $v): string
{
    return ['', '#ef4444', '#f97316', '#f59e0b', '#84cc16', '#16a34a'][$v] ?? '#e7eaef';
}
function bandc(string $b): string
{
    return ['Scalable Engine'=>'#16a34a','Strong Foundation'=>'#84cc16','Needs Structure'=>'#f59e0b','High Risk'=>'#ef4444'][$b] ?? '#7c8b99';
}
$pct = (int) round($sub['percent']);

sc_admin_head($sub['client_name']);
sc_admin_topbar('submissions');
?>
<div class="wrap">
  <a class="btn ghost" href="index.php" style="padding-left:0;margin-bottom:6px"><i data-lucide="arrow-left"></i> All submissions</a>

  <div class="card pad" style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:16px;align-items:center;margin-bottom:16px">
    <div>
      <h1 style="margin:0 0 4px;font-size:22px"><?= sc_e($sub['client_name']) ?><?= $sub['client_company'] ? ' <span class="muted" style="font-weight:500">· ' . sc_e($sub['client_company']) . '</span>' : '' ?></h1>
      <div class="muted" style="font-size:14px">
        <?= sc_e($sub['client_email']) ?: 'No email' ?>
        <?= $sub['client_phone'] ? ' &middot; ' . sc_e($sub['client_phone']) : '' ?>
        &middot; <?= date('j M Y, H:i', strtotime($sub['created_at'])) ?>
      </div>
    </div>
    <div style="text-align:right">
      <div style="font-size:34px;font-weight:800;color:<?= bandc($sub['band']) ?>"><?= (int) $sub['total'] ?><span style="font-size:16px;color:var(--muted)">/200</span></div>
      <span class="pill" style="background:<?= bandc($sub['band']) ?>"><?= $pct ?>% · <?= sc_e($sub['band']) ?></span>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px" class="two">
    <div class="card pad">
      <h3 style="margin:0 0 12px;font-size:15px">Balance across 10 areas</h3>
      <div id="radar"></div>
    </div>
    <div class="card pad">
      <h3 style="margin:0 0 12px;font-size:15px">Score by area</h3>
      <?php foreach ($categories as $c): $p = $c['score'] / 20; ?>
        <div style="margin:10px 0">
          <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
            <b style="font-weight:600"><?= sc_e($c['name']) ?></b><span class="muted"><?= (int) $c['score'] ?>/20</span>
          </div>
          <div style="height:8px;background:var(--surface2);border-radius:99px;overflow:hidden">
            <i style="display:block;height:100%;width:<?= $p * 100 ?>%;background:<?= scolor((int) round($c['score'] / 4)) ?>"></i>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card pad" style="margin-top:16px">
    <h3 style="margin:0 0 12px;font-size:15px">Every answer</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px" class="two">
    <?php foreach ($catNames as $si => $cat): ?>
      <div>
        <h4 style="margin:0 0 8px;font-size:14px;display:flex;justify-content:space-between"><?= sc_e($cat) ?> <span class="muted" style="font-weight:600"><?= (int) ($categories[$si]['score'] ?? 0) ?>/20</span></h4>
        <?php foreach ($titles[$cat] as $qi => $qt): $v = (int) ($answers["$si-$qi"] ?? 0); ?>
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;padding:5px 0;color:var(--ink)">
            <span><?= sc_e($qt) ?></span>
            <span style="display:inline-grid;place-items:center;width:22px;height:22px;border-radius:6px;color:#fff;font-weight:700;font-size:12px;background:<?= scolor($v) ?>"><?= $v ?: '&ndash;' ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
</div>

<style>@media(max-width:680px){.two{grid-template-columns:1fr!important}}</style>
<script>
const cats = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;
(function(){
  const n=cats.length, cx=230, cy=190, R=150;
  const cv=k=>getComputedStyle(document.documentElement).getPropertyValue(k).trim();
  const line=cv('--line')||'#e7eaef', muted=cv('--muted')||'#7c8b99';
  const pt=(i,rad)=>{const a=-Math.PI/2+i*2*Math.PI/n;return[cx+rad*Math.cos(a),cy+rad*Math.sin(a)];};
  let rings='';[0.25,0.5,0.75,1].forEach(f=>{rings+=`<polygon points="${cats.map((_,i)=>pt(i,R*f).join(',')).join(' ')}" fill="none" stroke="${line}"/>`;});
  let axes='',labels='';
  cats.forEach((c,i)=>{const[x,y]=pt(i,R);axes+=`<line x1="${cx}" y1="${cy}" x2="${x}" y2="${y}" stroke="${line}"/>`;
    const[lx,ly]=pt(i,R+22);const anc=Math.abs(lx-cx)<8?'middle':(lx>cx?'start':'end');
    labels+=`<text x="${lx}" y="${ly}" font-size="10.5" fill="${muted}" text-anchor="${anc}" dominant-baseline="middle" font-weight="600">${c.name}</text>`;});
  const poly=cats.map((c,i)=>pt(i,R*(c.score/20)).join(',')).join(' ');
  document.getElementById('radar').innerHTML=`<svg viewBox="0 0 460 380" style="max-width:100%;height:auto">${rings}${axes}
    <polygon points="${poly}" fill="rgba(245,144,30,.18)" stroke="#f5901e" stroke-width="2" stroke-linejoin="round"/>
    ${cats.map((c,i)=>{const[x,y]=pt(i,R*(c.score/20));return `<circle cx="${x}" cy="${y}" r="3.5" fill="#f5901e"/>`;}).join('')}${labels}</svg>`;
})();
</script>
<?php sc_admin_foot(); ?>
