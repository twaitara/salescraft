<?php
require __DIR__ . '/auth.php';

$flash = ''; $flashType = 'ok';

/* ---- Save (validate + normalise the posted JSON) ---- */
if (($_POST['_action'] ?? '') === 'save') {
    $in = json_decode((string) ($_POST['schema'] ?? ''), true);
    $cats = [];
    if (is_array($in) && !empty($in['categories']) && is_array($in['categories'])) {
        foreach ($in['categories'] as $c) {
            $name = trim((string) ($c['name'] ?? ''));
            if ($name === '') continue;
            $qs = [];
            foreach (($c['questions'] ?? []) as $q) {
                $t = trim((string) ($q['t'] ?? ''));
                if ($t === '') continue;
                $qs[] = [
                    't'  => $t,
                    'a1' => trim((string) ($q['a1'] ?? '')),
                    'a3' => trim((string) ($q['a3'] ?? '')),
                    'a5' => trim((string) ($q['a5'] ?? '')),
                ];
            }
            if (!$qs) continue;
            $icon = trim((string) ($c['icon'] ?? 'circle'));
            $cats[] = [
                'name'      => $name,
                'icon'      => $icon !== '' ? $icon : 'circle',
                'desc'      => trim((string) ($c['desc'] ?? '')),
                'fix'       => trim((string) ($c['fix'] ?? '')),
                'questions' => $qs,
            ];
        }
    }
    if (!$cats) {
        header('Location: scorecard.php?err=1'); exit;
    }
    sc_setting_set('scorecard_schema', json_encode(['categories' => $cats], JSON_UNESCAPED_UNICODE));
    header('Location: scorecard.php?saved=1'); exit;
}

/* ---- Reset to default ---- */
if (($_POST['_action'] ?? '') === 'reset') {
    sc_setting_set('scorecard_schema', '');
    header('Location: scorecard.php?reset=1'); exit;
}

if (isset($_GET['saved'])) $flash = 'Scorecard saved — clients will see the new questions immediately.';
if (isset($_GET['reset'])) $flash = 'Scorecard reset to the default set.';
if (isset($_GET['err'])) { $flash = 'You need at least one category with one question.'; $flashType = 'err'; }

$schema = sc_scorecard();

sc_admin_head('Scorecard');
sc_admin_topbar('scorecard');
?>
<style>
.sc-editbar{position:sticky;top:63px;z-index:9;display:flex;justify-content:space-between;align-items:center;gap:12px;
  flex-wrap:wrap;padding:14px 16px;margin-bottom:18px;border-radius:14px;border:1px solid var(--line);
  background-image:var(--glass);background-color:var(--card);box-shadow:var(--shadow)}
.sc-editbar .counts{font-size:13px;color:var(--muted)}
.sc-editbar .counts b{color:var(--ink)}
.ecat{margin-bottom:16px;padding:20px}
.ecat-head{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.ecat-num{width:26px;height:26px;border-radius:8px;background:var(--brand-soft);color:var(--brand);font-weight:800;
  font-size:13px;display:grid;place-items:center;flex:none}
.ecat-head .ecat-name{flex:1;font-weight:700;font-size:16px;padding:9px 12px}
.tools{display:flex;gap:4px}
.tools button{width:32px;height:32px;border-radius:9px;border:1px solid var(--line);background:var(--surface);
  color:var(--muted);cursor:pointer;display:grid;place-items:center}
.tools button:hover{color:var(--ink);background:var(--surface2)}
.tools button.danger:hover{color:var(--s1);border-color:var(--s1)}
.tools button svg{width:15px;height:15px}
.eqs{margin-top:6px}
.eq{border:1px solid var(--line);border-radius:12px;padding:14px;margin-bottom:12px;background:var(--surface)}
.eq-head{display:flex;align-items:center;gap:9px;margin-bottom:10px}
.eq-head .eq-q{font-size:11px;font-weight:800;color:var(--muted);letter-spacing:.4px}
.eq-head .eq-t{flex:1;font-weight:600;padding:9px 12px}
.eq-anchors{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.eq-anchors label{display:flex;align-items:center;gap:6px}
.eq-anchors .dot{width:9px;height:9px;border-radius:50%}
.addq{margin-top:2px}
@media(max-width:760px){.eq-anchors{grid-template-columns:1fr}}
@media(max-width:600px){
  .sc-editbar{position:static;top:auto;flex-direction:column;align-items:stretch}
  .sc-editbar > div{display:flex;gap:8px}
  .sc-editbar .btn{flex:1;justify-content:center}
  .ecat{padding:16px}
}
</style>

<div class="wrap">
  <div class="page-h">
    <div>
      <h1><i data-lucide="list-checks"></i> Scorecard editor</h1>
      <div class="sub">Edit everything clients see — categories, questions, the 1/3/5 meanings, and recommendations.</div>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="flash <?= $flashType ?>"><i data-lucide="<?= $flashType === 'ok' ? 'check-circle' : 'alert-triangle' ?>"></i><?= sc_e($flash) ?></div>
  <?php endif; ?>

  <form method="post" id="scForm">
    <input type="hidden" name="_action" value="save">
    <input type="hidden" name="schema" id="schemaField">

    <div class="sc-editbar">
      <div class="counts" id="counts"></div>
      <div style="display:flex;gap:10px">
        <button type="button" class="btn" onclick="resetDefault()"><i data-lucide="rotate-ccw"></i> Reset to default</button>
        <button type="button" class="btn primary" onclick="saveSchema()"><i data-lucide="save"></i> Save changes</button>
      </div>
    </div>

    <div id="cats"></div>

    <button type="button" class="btn" onclick="addCat()" style="margin-top:4px"><i data-lucide="plus"></i> Add category</button>
  </form>
</div>

<form method="post" id="resetForm" style="display:none"><input type="hidden" name="_action" value="reset"></form>

<script>
let data = <?= json_encode($schema['categories'], JSON_UNESCAPED_UNICODE) ?> || [];

const E = s => String(s==null?'':s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));

function catCard(c,ci){
  const qs=(c.questions||[]).map((q,qi)=>`
    <div class="eq">
      <div class="eq-head">
        <span class="eq-q">Q${qi+1}</span>
        <input class="field eq-t" value="${E(q.t)}" placeholder="Question title">
        <div class="tools">
          <button type="button" title="Move up" onclick="qMove(${ci},${qi},-1)"><i data-lucide="chevron-up"></i></button>
          <button type="button" title="Move down" onclick="qMove(${ci},${qi},1)"><i data-lucide="chevron-down"></i></button>
          <button type="button" class="danger" title="Remove question" onclick="qDel(${ci},${qi})"><i data-lucide="trash-2"></i></button>
        </div>
      </div>
      <div class="eq-anchors">
        <div class="field"><label><span class="dot" style="background:var(--s1)"></span>Score 1 means…</label><textarea class="eq-a1" placeholder="What a 1 looks like">${E(q.a1)}</textarea></div>
        <div class="field"><label><span class="dot" style="background:var(--s3)"></span>Score 3 means…</label><textarea class="eq-a3" placeholder="What a 3 looks like">${E(q.a3)}</textarea></div>
        <div class="field"><label><span class="dot" style="background:var(--s5)"></span>Score 5 (best practice)</label><textarea class="eq-a5" placeholder="What a 5 looks like">${E(q.a5)}</textarea></div>
      </div>
    </div>`).join('');
  return `<div class="ecat card" data-ci="${ci}">
    <div class="ecat-head">
      <span class="ecat-num">${ci+1}</span>
      <input class="field ecat-name" value="${E(c.name)}" placeholder="Category name">
      <div class="tools">
        <button type="button" title="Move up" onclick="cMove(${ci},-1)"><i data-lucide="chevron-up"></i></button>
        <button type="button" title="Move down" onclick="cMove(${ci},1)"><i data-lucide="chevron-down"></i></button>
        <button type="button" class="danger" title="Remove category" onclick="cDel(${ci})"><i data-lucide="trash-2"></i></button>
      </div>
    </div>
    <div class="frow">
      <div class="field"><label>Icon (Lucide name)</label><input class="ecat-icon" value="${E(c.icon||'circle')}" placeholder="e.g. target"></div>
      <div class="field"><label>Short description</label><input class="ecat-desc" value="${E(c.desc)}" placeholder="Shown under the category title"></div>
    </div>
    <div class="field full" style="margin-bottom:14px"><label>Recommendation (used as the priority fix)</label><textarea class="ecat-fix" placeholder="What to do first if this area scores low">${E(c.fix)}</textarea></div>
    <div class="eqs">${qs}</div>
    <button type="button" class="btn addq" onclick="qAdd(${ci})"><i data-lucide="plus"></i> Add question</button>
  </div>`;
}

function render(){
  document.getElementById('cats').innerHTML = data.map(catCard).join('');
  const nq = data.reduce((s,c)=>s+(c.questions||[]).length,0);
  document.getElementById('counts').innerHTML =
    `<b>${data.length}</b> categories · <b>${nq}</b> questions · max score <b>${nq*5}</b>`;
  lucide.createIcons();
}

/* Read the DOM back into the data model (call before any structural change). */
function sync(){
  const cards=[...document.querySelectorAll('.ecat')];
  data = cards.map(card=>{
    const q=[...card.querySelectorAll('.eq')].map(eq=>({
      t:  eq.querySelector('.eq-t').value,
      a1: eq.querySelector('.eq-a1').value,
      a3: eq.querySelector('.eq-a3').value,
      a5: eq.querySelector('.eq-a5').value,
    }));
    return {
      name: card.querySelector('.ecat-name').value,
      icon: card.querySelector('.ecat-icon').value,
      desc: card.querySelector('.ecat-desc').value,
      fix:  card.querySelector('.ecat-fix').value,
      questions: q,
    };
  });
}

function addCat(){ sync(); data.push({name:'New category',icon:'circle',desc:'',fix:'',questions:[{t:'New question',a1:'',a3:'',a5:''}]}); render(); }
function cDel(ci){ sync(); if(!confirm('Remove this whole category?'))return; data.splice(ci,1); render(); }
function cMove(ci,d){ sync(); const j=ci+d; if(j<0||j>=data.length)return; [data[ci],data[j]]=[data[j],data[ci]]; render(); }
function qAdd(ci){ sync(); data[ci].questions.push({t:'New question',a1:'',a3:'',a5:''}); render(); }
function qDel(ci,qi){ sync(); data[ci].questions.splice(qi,1); render(); }
function qMove(ci,qi,d){ sync(); const q=data[ci].questions,j=qi+d; if(j<0||j>=q.length)return; [q[qi],q[j]]=[q[j],q[qi]]; render(); }

function saveSchema(){
  sync();
  const clean=data.filter(c=>c.name.trim()&&(c.questions||[]).some(q=>q.t.trim()));
  if(!clean.length){ alert('Add at least one category with a question.'); return; }
  document.getElementById('schemaField').value = JSON.stringify({categories:data});
  document.getElementById('scForm').submit();
}
function resetDefault(){
  if(confirm('Reset the scorecard to the built-in default? Your custom questions will be replaced.'))
    document.getElementById('resetForm').submit();
}

render();
</script>
<?php sc_admin_foot(); ?>
