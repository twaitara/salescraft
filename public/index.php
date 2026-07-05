<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/brand.php';
session_start();

$brand        = sc_setting('brand_name', 'SalesCraft');
$headline     = sc_setting('intro_headline', 'Where is your sales engine leaking?');
$captchaMode  = sc_setting('captcha_mode', 'builtin');
$requirePhone = sc_setting('require_phone', '1') === '1';
$siteKey      = sc_setting('recaptcha_site_key');
$captchaQ     = $captchaMode === 'builtin' ? sc_captcha_new() : '';
$scorecard    = sc_scorecard();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= sc_e($brand) ?> Scorecard — Sales Diagnostic</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<?php if ($captchaMode === 'recaptcha'): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
<script>(function(){try{var t=localStorage.getItem("sc-theme")||"dark";document.documentElement.setAttribute("data-theme",t);}catch(e){document.documentElement.setAttribute("data-theme","dark");}})();</script>
<style>
:root{
  --bg:#f4f6f9; --bg-2:#eef1f6; --card:#ffffff; --card-2:#ffffff; --ink:#1f2b38; --muted:#6b7885; --line:#e6eaf0;
  --brand:#f5901e; --brand-d:#d9790f; --brand-soft:#fff3e5; --brand-glow:rgba(245,144,30,.18);
  --slate:#1f2b38; --grey:#6b7885;
  --surface:#fbfcfe; --surface2:#eef1f6;
  --s1:#ef4444; --s2:#f97316; --s3:#f59e0b; --s4:#84cc16; --s5:#16a34a; --blue:#3b82f6; --violet:#8b5cf6;
  --shadow:0 1px 2px rgba(31,43,56,.05),0 10px 30px rgba(31,43,56,.06);
  --glass:linear-gradient(180deg,rgba(0,0,0,.015),rgba(0,0,0,0));
  --radius:16px;
}
:root[data-theme="dark"]{
  --bg:#0a0e14; --bg-2:#0d131b; --card:#151c26; --card-2:#111823; --ink:#e8eef5; --muted:#8b97a6; --line:#232e3b;
  --brand-soft:#3a2a12; --slate:#e8eef5; --grey:#8b97a6;
  --surface:#101722; --surface2:#1c2632;
  --shadow:0 1px 0 rgba(255,255,255,.03) inset,0 14px 34px rgba(0,0,0,.5);
  --glass:linear-gradient(180deg,rgba(255,255,255,.035),rgba(255,255,255,0));
}
*{box-sizing:border-box}
html,body{margin:0}
body{font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:var(--ink);
  -webkit-font-smoothing:antialiased;line-height:1.5;min-height:100vh;background-attachment:fixed;
  background:
    radial-gradient(1100px 480px at 82% -8%, var(--brand-glow), transparent 60%),
    radial-gradient(900px 500px at -8% 10%, rgba(59,130,246,.07), transparent 60%),
    var(--bg)}
.wrap{max-width:920px;margin:0 auto;padding:24px 20px 80px}
.brandrow{display:flex;align-items:center;gap:14px;margin-bottom:22px;flex-wrap:wrap;
  background-image:var(--glass);background-color:var(--card);border:1px solid var(--line);
  border-radius:16px;padding:12px 16px;box-shadow:var(--shadow)}
.sc-logo{display:inline-flex;align-items:center;gap:11px}
.sc-tile{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;flex:none;
  background:linear-gradient(150deg,#f9a63f,#f5901e 55%,#e07d0d);box-shadow:0 6px 16px rgba(245,144,30,.4)}
.sc-tile svg{width:22px;height:23px}
.sc-word{font-size:19px;font-weight:800;letter-spacing:-.3px;line-height:1}
.sc-word b{color:var(--ink);font-weight:800}
.sc-word span{color:var(--brand);font-weight:800}
.brandrow .tag{color:var(--muted);font-size:12.5px;padding-left:14px;border-left:1px solid var(--line)}
.theme-toggle{margin-left:auto;background:transparent;border:1px solid var(--line);color:var(--muted);width:40px;height:40px;
  border-radius:11px;cursor:pointer;display:grid;place-items:center}
.theme-toggle:hover{color:var(--ink);background:var(--surface2)}
.theme-toggle svg{width:19px;height:19px}

.card{background-image:var(--glass);background-color:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow)}

/* progress */
.progress{display:flex;gap:6px;margin:0 0 22px}
.progress .seg{flex:1;height:7px;border-radius:99px;background:#e7eaef;overflow:hidden;position:relative}
.progress .seg i{position:absolute;inset:0;background:var(--brand);transform:scaleX(0);transform-origin:left;transition:transform .4s ease;border-radius:99px}
.progress .seg.done i{transform:scaleX(1)}
.progress .seg.active i{transform:scaleX(1);background:linear-gradient(90deg,var(--brand),#f8b25e)}

/* intro */
.intro{padding:34px}
.intro h2{margin:.2em 0 .3em;font-size:26px}
.intro .sub{color:var(--muted);font-size:15px;max-width:620px}
.fields{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:26px 0 8px}
.field label{display:block;font-size:12.5px;font-weight:600;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px}
.field input{width:100%;padding:12px 14px;border:1px solid var(--line);border-radius:11px;font:inherit;font-size:14.5px;background:var(--surface);color:var(--ink)}
.field input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px var(--brand-soft)}
.field.full{grid-column:1/-1}
.captcha-row{display:flex;gap:10px;align-items:center}
.captcha-row input{max-width:130px}
.captcha-row a{font-size:13px;color:var(--brand-d);font-weight:600;text-decoration:none}
.legend{display:flex;flex-wrap:wrap;gap:10px;margin:22px 0}
.legend .chip{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--muted);background:var(--surface);border:1px solid var(--line);padding:7px 11px;border-radius:99px}
.dot{width:11px;height:11px;border-radius:50%}

/* section */
.section{padding:30px 30px 26px}
.section-head{display:flex;align-items:center;gap:14px;margin-bottom:6px}
.sec-ico{width:46px;height:46px;border-radius:13px;background:var(--brand-soft);color:var(--brand);display:grid;place-items:center;flex:none}
.sec-ico svg{width:23px;height:23px}
.section-head h2{margin:0;font-size:20px}
.section-head .kicker{font-size:12px;font-weight:700;color:var(--brand-d);text-transform:uppercase;letter-spacing:.6px}
.section-desc{color:var(--muted);font-size:14px;margin:2px 0 20px}

.q{padding:18px 0;border-top:1px solid var(--line)}
.q:first-of-type{border-top:none}
.q-top{display:flex;justify-content:space-between;align-items:baseline;gap:14px}
.q-title{font-weight:600;font-size:15.5px}
.q-target{font-size:12.5px;color:var(--muted);margin-top:3px}
.q-target b{color:var(--s5);font-weight:600}
.scale{display:flex;gap:8px;margin-top:13px}
.scale button{flex:1;padding:11px 4px;border:1.5px solid var(--line);background:var(--surface);border-radius:11px;cursor:pointer;
  font:inherit;font-weight:700;font-size:16px;color:var(--muted);transition:.15s;position:relative}
.scale button small{display:block;font-size:9.5px;font-weight:600;letter-spacing:.3px;margin-top:2px;color:#a5b1bc;text-transform:uppercase}
.scale button:hover{border-color:#cdd4dc;transform:translateY(-1px)}
.scale button.sel{color:#fff;border-color:transparent;box-shadow:0 6px 16px rgba(245,144,30,.28)}
.scale button.sel[data-v="1"]{background:var(--s1)} .scale button.sel[data-v="2"]{background:var(--s2)}
.scale button.sel[data-v="3"]{background:var(--s3)} .scale button.sel[data-v="4"]{background:var(--s4)}
.scale button.sel[data-v="5"]{background:var(--s5)}
.anchor{margin-top:11px;font-size:13px;background:var(--surface);border:1px solid var(--line);border-left:3px solid var(--brand);
  padding:10px 13px;border-radius:9px;display:none}
.anchor.show{display:block;animation:fade .25s ease}
.anchor b{color:var(--ink)}
@keyframes fade{from{opacity:0;transform:translateY(-3px)}to{opacity:1}}

/* section result */
.secresult{margin-top:22px;border-radius:14px;padding:18px 20px;display:none;align-items:center;gap:18px;color:#fff}
.secresult.show{display:flex;animation:fade .3s ease}
.secresult .big{font-size:34px;font-weight:800;line-height:1}
.secresult .big span{font-size:16px;opacity:.8;font-weight:600}
.secresult .msg{font-size:14px;font-weight:500}
.secresult .band{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;opacity:.9}

/* nav */
.nav{display:flex;justify-content:space-between;gap:12px;margin-top:26px}
.btn{padding:13px 22px;border-radius:12px;font:inherit;font-weight:600;font-size:14.5px;cursor:pointer;border:1px solid var(--line);
  background:var(--card);color:var(--ink);display:inline-flex;align-items:center;gap:8px;transition:.15s}
.btn:hover{background:var(--surface2)}
.btn svg{width:17px;height:17px}
.btn.primary{background:linear-gradient(160deg,#f9a63f,#f5901e 60%,#e07d0d);color:#fff;border-color:transparent;
  box-shadow:0 8px 20px rgba(245,144,30,.32)}
.btn.primary:hover{filter:brightness(1.05)}
.btn.primary:disabled{background:#c98f52;border-color:transparent;cursor:not-allowed;box-shadow:none;filter:none}
.btn.ghost{border-color:transparent;background:transparent;color:var(--muted)}
.btn.tiny{padding:8px 13px;font-size:13px;border-radius:10px}
.sec-toolbar{display:flex;justify-content:flex-end;gap:8px;margin-bottom:10px}
#sc-toast{position:fixed;left:50%;bottom:24px;transform:translate(-50%,140%);z-index:99;
  display:flex;align-items:center;gap:9px;padding:12px 18px;border-radius:12px;font-size:14px;font-weight:500;
  background:linear-gradient(180deg,#1f2833,#151c26);color:#e8eef5;border:1px solid var(--line);
  box-shadow:0 14px 34px rgba(0,0,0,.5);opacity:0;transition:transform .3s cubic-bezier(.2,.8,.2,1),opacity .3s}
#sc-toast.show{transform:translate(-50%,0);opacity:1}
#sc-toast svg{width:18px;height:18px;color:#34d399}

/* results */
.results{padding:32px}
.gauge-wrap{display:flex;flex-wrap:wrap;gap:26px;align-items:center;justify-content:center;text-align:center;
  background:radial-gradient(520px 240px at 26% 6%, var(--brand-glow), transparent 66%);
  border:1px solid var(--line);border-radius:16px;padding:24px}
.verdict .badge{box-shadow:0 6px 18px rgba(0,0,0,.18)}
.gauge{position:relative;width:200px;height:200px;flex:none}
.gauge .num{position:absolute;inset:0;display:grid;place-content:center;text-align:center}
.gauge .num b{font-size:44px;font-weight:800;line-height:1}
.gauge .num span{font-size:14px;color:var(--muted)}
.verdict{max-width:340px;text-align:left}
.verdict .badge{display:inline-block;padding:6px 14px;border-radius:99px;font-weight:700;font-size:13px;color:#fff;margin-bottom:10px}
.verdict h2{margin:0 0 8px;font-size:24px}
.verdict p{margin:0;color:var(--muted);font-size:14px}

.grid2{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-top:30px}
.panel{padding:22px}
.panel h3{margin:0 0 4px;font-size:15px;display:flex;align-items:center;gap:8px}
.panel h3 svg{width:18px;height:18px;color:var(--brand)}
.panel .hint{font-size:12.5px;color:var(--muted);margin:0 0 16px}

.catbar{margin:12px 0}
.catbar .lab{display:flex;justify-content:space-between;font-size:13px;margin-bottom:5px}
.catbar .lab b{font-weight:600}
.catbar .lab span{color:var(--muted);font-variant-numeric:tabular-nums}
.track{height:9px;background:var(--surface2);border-radius:99px;overflow:hidden}
.track i{display:block;height:100%;border-radius:99px;transition:width .8s cubic-bezier(.2,.8,.2,1)}

.fix{display:flex;gap:12px;padding:13px 0;border-top:1px solid var(--line)}
.fix:first-child{border-top:none}
.fix .rank{width:26px;height:26px;border-radius:8px;background:var(--s1);color:#fff;font-weight:700;font-size:13px;display:grid;place-items:center;flex:none}
.fix .rank.r2{background:var(--s2)} .fix .rank.r3{background:var(--s3)}
.fix .t{font-weight:600;font-size:14px}
.fix .d{font-size:12.5px;color:var(--muted);margin-top:2px}

.radar-card{padding:22px;margin-top:22px;text-align:center}
svg.radar{max-width:460px;width:100%;height:auto}
.actions{display:flex;gap:12px;justify-content:center;margin-top:26px;flex-wrap:wrap}
.small{font-size:12px;color:var(--muted);text-align:center;margin-top:22px}
.submitbar{display:flex;align-items:center;gap:12px;margin-top:22px;padding:14px 18px;border-radius:13px;font-size:14px;border:1px solid var(--line)}
.submitbar svg{width:20px;height:20px;flex:none}
.submitbar a{color:var(--brand-d);font-weight:600}
.submitbar.idle{background:var(--brand-soft);color:var(--brand-d);border-color:#f6d9b4}
.submitbar.sending{background:rgba(245,158,11,.14);color:#d9790f;border-color:rgba(245,158,11,.3)}
.submitbar.sending svg{animation:spin 1s linear infinite}
.submitbar.ok{background:rgba(22,163,74,.14);color:#22c55e;border-color:rgba(22,163,74,.3)}
.submitbar.error{background:rgba(239,68,68,.14);color:#f87171;border-color:rgba(239,68,68,.3)}
@keyframes spin{to{transform:rotate(360deg)}}

@media(max-width:680px){
  .fields,.grid2{grid-template-columns:1fr}
  .scale button small{display:none}
  .section,.intro,.results{padding:22px 18px}
  .gauge-wrap{flex-direction:column}
  .wrap{padding:18px 14px 70px}
  .intro h2{font-size:22px}
  .brandrow{padding:11px 14px;gap:10px}
  .brandrow .tag{display:none}
  .sec-toolbar{margin-bottom:8px}
  .nav{gap:8px}
  .btn{padding:12px 16px;font-size:14px}
}
@media(max-width:420px){
  .scale{gap:6px}
  .scale button{font-size:15px;padding:12px 2px}
  .sc-word{font-size:17px}
  .section-head h2{font-size:18px}
  .verdict h2{font-size:20px}
}
@media print{.nav,.actions,.progress,.brandrow .tag{display:none}body{background:#fff}}
</style>
</head>
<body>
<div class="wrap">
  <div class="brandrow">
    <?= sc_logo_lockup(40) ?>
    <span class="tag">Score your sales engine in ~6 min</span>
    <button class="theme-toggle" onclick="scToggleTheme()" title="Toggle light / dark"><i data-lucide="moon"></i></button>
  </div>

  <div class="progress" id="progress"></div>
  <div id="app"></div>
</div>

<script>
window.SC = {
  brand: <?= json_encode($brand) ?>,
  headline: <?= json_encode($headline) ?>,
  requirePhone: <?= $requirePhone ? 'true' : 'false' ?>,
  captchaMode: <?= json_encode($captchaMode) ?>,
  captchaQuestion: <?= json_encode($captchaQ) ?>,
  recaptchaSiteKey: <?= json_encode($siteKey) ?>,
  scorecard: <?= json_encode($scorecard, JSON_UNESCAPED_UNICODE) ?>
};
</script>
<script>
/* ============================================================
   DATA — default scorecard (fallback if the injected schema is empty).
   The live content comes from window.SC.scorecard (admin-editable).
   ============================================================ */
const SECTIONS_DEFAULT = [
 {cat:"Sales Strategy", icon:"target", desc:"Do you have a clear, deliberate plan for who you sell to and how you hit your numbers?",
  q:[
   {t:"Clear sales goals", a1:'No targets — the goal is "sell as much as possible."', a3:"An annual revenue target exists, but it isn't broken down by quarter, month, rep or product.", a5:"Specific, time-bound targets cascaded to quarter/month/rep/product, reviewed regularly — everyone knows their number."},
   {t:"Defined target customers", a1:"You'll sell to anyone with a pulse.", a3:"A general idea of the ideal customer, but nothing documented.", a5:"A documented ICP (industry, size, buyer, pain, budget) — reps can name it and confidently disqualify poor-fit leads."},
   {t:"Clear sales channels", a1:"You rely on random referrals and walk-ins.", a3:"One or two channels work, but none are deliberately managed.", a5:"A deliberate channel mix (inbound, outbound, partners…), each with known cost and yield, actively prioritised."},
   {t:"Sales & marketing alignment", a1:"Sales and marketing work in silos and blame each other.", a3:"Occasional coordination, but no shared definitions.", a5:"Shared lead definitions (MQL/SQL), response SLAs, joint pipeline reviews and closed-loop feedback."},
  ]},
 {cat:"Sales Process", icon:"git-branch", desc:"Is there one repeatable way deals move forward — or does every rep improvise?",
  q:[
   {t:"Documented sales process", a1:"Every rep does it their own way; nothing is written down.", a3:"Steps are loosely understood and only partly documented.", a5:"The end-to-end process is documented, taught and followed — a new hire could learn it from the doc."},
   {t:"Consistent lead handling", a1:"Leads are handled ad hoc and often dropped.", a3:"Most leads get handled, but inconsistently.", a5:"Every lead follows the same intake, qualification and routing rules, regardless of which rep gets it."},
   {t:"Defined pipeline stages", a1:'No stages — deals are just "in progress."', a3:"Stages exist but their definitions are fuzzy.", a5:"Clear stages with entry/exit criteria — a deal only advances when the criteria are genuinely met."},
   {t:"Stage ownership clarity", a1:"It's unclear who owns a deal at any given point.", a3:"Ownership is generally known but hand-offs are messy.", a5:"Every stage has a named owner with clean hand-off rules — nothing falls through the cracks."},
  ]},
 {cat:"Lead Management", icon:"filter", desc:"How predictably do leads arrive, and how well do you work them?",
  q:[
   {t:"Reliable lead sources", a1:"Lead flow is unpredictable — feast or famine.", a3:"A source or two, but volume swings a lot.", a5:"Multiple predictable sources producing a steady, forecastable volume of leads."},
   {t:"Speed of response", a1:"It takes days to respond, or you never do.", a3:"Leads are contacted within a day.", a5:"Inbound leads are contacted within minutes / the first hour, consistently — and it's tracked."},
   {t:"Follow-up consistency", a1:"One touch, then you give up.", a3:"A few follow-ups, but no set cadence.", a5:"A defined multi-touch cadence over days/weeks, executed for every lead, with automated reminders."},
   {t:"Conversion tracking", a1:"You have no idea of your conversion rates.", a3:"A rough sense of the overall close rate.", a5:"Conversion is measured at every stage — you know lead→close rates by source and by rep."},
  ]},
 {cat:"Sales Team", icon:"users", desc:"Can your people actually sell — knowledge, benefits, objections, discovery?",
  q:[
   {t:"Product knowledge", a1:"Reps are unsure of product details.", a3:"Reps know the product's features.", a5:"Deep command of product, use cases and competitor comparisons — reps answer any question confidently."},
   {t:"Benefit-based selling", a1:"Reps just list features.", a3:"Reps mention some benefits.", a5:"Reps consistently translate features into quantified customer outcomes and ROI."},
   {t:"Objection handling", a1:"Reps freeze or discount the moment they're challenged.", a3:"Common objections are handled adequately.", a5:"Reps anticipate objections and respond with a proven framework plus proof, keeping deals moving."},
   {t:"Consultative skills", a1:"Reps pitch immediately and don't listen.", a3:"Reps ask some questions.", a5:"Reps diagnose before prescribing — strong discovery questions and a tailored solution."},
  ]},
 {cat:"Messaging", icon:"message-square", desc:"Is your value clear, differentiated and told the same way by everyone?",
  q:[
   {t:"Clear value proposition", a1:"You can't clearly articulate why someone should buy.", a3:"A value statement exists, but it's generic.", a5:"A sharp, specific value prop tied to customer outcomes — everyone delivers it the same way."},
   {t:"Customer pain clarity", a1:"You don't really know the customer's real pains.", a3:"A general understanding of customer pains.", a5:"You can state the customer's top pains in their own words, and messaging leads with them."},
   {t:"Differentiation", a1:'Just "we\'re cheaper/better," with no proof.', a3:"Some differentiators are named.", a5:"Clear, defensible differentiation vs named competitors, backed by proof."},
   {t:"Consistent messaging", a1:"Every rep says something different.", a3:"Messaging is broadly similar.", a5:"Unified messaging across every rep, the website and all materials."},
  ]},
 {cat:"Objections", icon:"shield", desc:"Are objections anticipated, answered with proof, and price defended on value?",
  q:[
   {t:"Objection identification", a1:"You're regularly surprised by objections.", a3:"You know the common ones.", a5:"A documented list of every common objection with root causes, updated over time."},
   {t:"Structured responses", a1:"You improvise a response every time.", a3:"You have a few go-to lines.", a5:"A tested response framework for each objection — trained and rehearsed by the team."},
   {t:"Use of proof", a1:"No evidence is offered.", a3:"You occasionally cite a case or result.", a5:"A ready library of case studies, testimonials, data and guarantees used at the right moment."},
   {t:"Price confidence", a1:"You cave to discounts quickly.", a3:"You hold price some of the time.", a5:"You defend price on value, rarely discount, and can justify the ROI clearly."},
  ]},
 {cat:"Sales Tools", icon:"wrench", desc:"Do reps have the scripts, materials and onboarding to sell well from day one?",
  q:[
   {t:"Sales scripts", a1:"There are none.", a3:"A rough script exists.", a5:"Proven call/discovery/demo scripts and talk tracks that the team actually uses."},
   {t:"Brochures / materials", a1:"None, or badly outdated.", a3:"Some materials exist.", a5:"Professional, current collateral mapped to each stage of the buying journey."},
   {t:"FAQs", a1:"No FAQ resource.", a3:"Answers are handled informally.", a5:"A maintained FAQ covering common questions and objections, accessible to every rep."},
   {t:"Onboarding tools", a1:"New reps sink or swim.", a3:"Some onboarding docs exist.", a5:"A structured onboarding program (playbook, training, ramp plan) that gets reps productive fast."},
  ]},
 {cat:"CRM & Data", icon:"database", desc:"Is every lead captured, visible, followed up and reported on — reliably?",
  q:[
   {t:"Lead tracking", a1:"Leads live in people's heads, notebooks or scattered sheets.", a3:"A CRM exists but is under-used.", a5:"Every lead is captured in the CRM with source and status, and kept current."},
   {t:"Pipeline visibility", a1:"No visibility into where deals stand.", a3:"Some pipeline view exists, but it isn't trusted.", a5:"A real-time, trusted pipeline by stage, value and owner."},
   {t:"Follow-up reminders", a1:"You rely on memory.", a3:"Reminders are set manually.", a5:"Automated tasks and reminders ensure no follow-up is ever missed."},
   {t:"Reporting", a1:"No reports.", a3:"Basic reports pulled manually.", a5:"Automated dashboards on activity, conversion and forecast, reviewed regularly."},
  ]},
 {cat:"Management", icon:"clipboard-check", desc:"Are reps reviewed, coached and held accountable to the right KPIs?",
  q:[
   {t:"Sales reviews", a1:"They never really happen.", a3:"Occasional check-ins.", a5:"Regular, structured pipeline and deal reviews on a set cadence."},
   {t:"Coaching", a1:"No coaching at all.", a3:"Ad hoc feedback.", a5:"Ongoing 1:1 coaching against skills and real calls, with development plans."},
   {t:"KPI usage", a1:"No KPIs are tracked.", a3:"You track revenue only.", a5:"Balanced KPIs (activity, conversion, pipeline, revenue) actually drive decisions."},
   {t:"Accountability", a1:"No consequences for missed targets.", a3:"Some accountability.", a5:"Clear ownership of numbers, a transparent scoreboard and consistent follow-through."},
  ]},
 {cat:"Scalability", icon:"trending-up", desc:"Could you double the volume without the whole thing breaking?",
  q:[
   {t:"Process independence", a1:"Everything depends on the owner or one star rep.", a3:"There's some independence.", a5:"Sales runs on process, not heroics — it doesn't depend on any single person."},
   {t:"Onboarding ease", a1:"Adding reps takes forever, if it works at all.", a3:"You can onboard, but slowly.", a5:"New reps ramp quickly and predictably through a repeatable system."},
   {t:"Revenue predictability", a1:"You can't predict next month.", a3:"You have a rough forecast.", a5:"An accurate, repeatable forecast you can plan and invest against."},
   {t:"Scalability readiness", a1:"Growth would break the system.", a3:"You could grow, but with real strain.", a5:"People, process and systems are ready to double volume without breaking."},
  ]},
];

const FIX_DEFAULT = {
 "Sales Strategy":"Write a one-page ICP and cascade a revenue target down to monthly numbers per rep.",
 "Sales Process":"Map your deal stages with entry/exit criteria and assign an owner to each.",
 "Lead Management":"Set a response-time rule (e.g. under 1 hour) and a fixed multi-touch follow-up cadence.",
 "Sales Team":"Run role-plays on discovery and objection handling; build a benefit/ROI cheat-sheet.",
 "Messaging":"Lock a single value proposition and rewrite pitch + website to say it the same way.",
 "Objections":"Document your top 10 objections with a tested response and a proof point for each.",
 "Sales Tools":"Ship a starter kit: call script, updated one-pager, FAQ and a 30-day onboarding plan.",
 "CRM & Data":"Get every live lead into the CRM with source + stage, and turn on follow-up reminders.",
 "Management":"Start a weekly pipeline review and monthly 1:1 coaching against a short KPI set.",
 "Scalability":"Remove single points of failure — document the playbook so anyone can run it.",
};

const SCOLORS=['','var(--s1)','var(--s2)','var(--s3)','var(--s4)','var(--s5)'];

/* Build the live scorecard from the injected schema, falling back to defaults. */
const SCHEMA = (window.SC && SC.scorecard && Array.isArray(SC.scorecard.categories) && SC.scorecard.categories.length)
  ? SC.scorecard.categories : null;
const SECTIONS = SCHEMA
  ? SCHEMA.map(c => ({cat:c.name||'', icon:c.icon||'circle', desc:c.desc||'',
      q:(c.questions||[]).map(q => ({t:q.t||'', a1:q.a1||'', a3:q.a3||'', a5:q.a5||''}))}))
  : SECTIONS_DEFAULT;
const FIX = SCHEMA
  ? (() => { const f={}; SCHEMA.forEach(c => { f[c.name]=c.fix||''; }); return f; })()
  : FIX_DEFAULT;
const secMax = si => (SECTIONS[si] ? SECTIONS[si].q.length*5 : 0);
const MAX = SECTIONS.reduce((s,x) => s + x.q.length*5, 0) || 1;

/* ---------- state ---------- */
let state = JSON.parse(localStorage.getItem('salescraft')||'{}');
state.answers = state.answers||{};
state.meta = state.meta||{name:'',email:'',phone:'',company:''};
let step = state.step||0;
const save=()=>localStorage.setItem('salescraft',JSON.stringify({...state,step}));

const app=document.getElementById('app');
const prog=document.getElementById('progress');
let recaptchaId=null;

function renderProgress(){
  prog.innerHTML='';
  SECTIONS.forEach((s,i)=>{
    const seg=document.createElement('div');
    seg.className='seg'+(step>i+1?' done':'')+(step===i+1?' active':'');
    seg.innerHTML='<i></i>'; prog.appendChild(seg);
  });
  prog.style.display = (step===0||step>SECTIONS.length)?'none':'flex';
}

function sectionScore(si){
  let sum=0,ans=0;
  SECTIONS[si].q.forEach((_,qi)=>{const v=state.answers[si+'-'+qi]; if(v){sum+=v;ans++;}});
  return {sum,ans,full:ans===SECTIONS[si].q.length};
}
function bandFor(pct){
  if(pct>=0.8) return {t:"Strong",c:"var(--s5)",msg:"This area is a real strength — protect and leverage it."};
  if(pct>=0.6) return {t:"Solid",c:"var(--s4)",msg:"Good foundation with room to sharpen a couple of things."};
  if(pct>=0.4) return {t:"Developing",c:"var(--s3)",msg:"Partly there — inconsistency is costing you deals here."};
  if(pct>=0.2) return {t:"Weak",c:"var(--s2)",msg:"A clear gap. Fixing this should be near the top of the list."};
  return {t:"Critical",c:"var(--s1)",msg:"High risk — this is likely leaking revenue right now."};
}

/* ---------- intro / lead gate ---------- */
function captchaField(){
  if(SC.captchaMode==='builtin'){
    return `<div class="field full">
      <label id="cap-label">Quick check — what is ${SC.captchaQuestion}? *</label>
      <div class="captcha-row">
        <input id="f-captcha" inputmode="numeric" autocomplete="off" placeholder="?">
        <a href="#" onclick="refreshCaptcha();return false;">New question</a>
      </div></div>`;
  }
  if(SC.captchaMode==='recaptcha'){
    return `<div class="field full"><div id="recaptcha-box"></div></div>`;
  }
  return '';
}
function renderIntro(){
  recaptchaId=null;
  app.innerHTML=`<div class="card intro">
    <h2>${esc(SC.headline)}</h2>
    <p class="sub">Score your business across <b>10 areas</b> and <b>40 questions</b>. After each section you'll get an instant score and diagnosis, then a full breakdown with your priority fixes. Takes about 6 minutes.</p>
    <div class="fields">
      <div class="field"><label>Your name *</label><input id="f-name" placeholder="Jane Doe" value="${esc(state.meta.name||'')}"></div>
      <div class="field"><label>Work email *</label><input id="f-email" type="email" placeholder="jane@acme.com" value="${esc(state.meta.email||'')}"></div>
      ${SC.requirePhone?`<div class="field"><label>Phone *</label><input id="f-phone" type="tel" placeholder="+254 7xx xxx xxx" value="${esc(state.meta.phone||'')}"></div>`:''}
      <div class="field ${SC.requirePhone?'':'full'}"><label>Company</label><input id="f-co" placeholder="Acme Ltd" value="${esc(state.meta.company||'')}"></div>
    </div>
    ${captchaField()}
    <div id="intro-err" style="color:var(--s1);font-size:13px;margin-top:10px;display:none"></div>
    <div class="legend">
      <span class="chip"><span class="dot" style="background:var(--s1)"></span>1 · Not in place</span>
      <span class="chip"><span class="dot" style="background:var(--s3)"></span>3 · Partly / inconsistent</span>
      <span class="chip"><span class="dot" style="background:var(--s5)"></span>5 · Best practice</span>
    </div>
    <div class="nav" style="justify-content:flex-end">
      <button class="btn primary" id="startbtn" onclick="startForm()">Start diagnostic <i data-lucide="arrow-right"></i></button>
    </div>
  </div>`;
  lucide.createIcons();
  if(SC.captchaMode==='recaptcha') tryRenderRecaptcha();
}
function tryRenderRecaptcha(){
  const box=document.getElementById('recaptcha-box'); if(!box) return;
  if(window.grecaptcha && grecaptcha.render){
    try{ recaptchaId=grecaptcha.render(box,{sitekey:SC.recaptchaSiteKey}); }catch(e){}
  }else{ setTimeout(tryRenderRecaptcha,300); }
}
async function refreshCaptcha(){
  try{
    const d=await (await fetch('captcha.php')).json();
    SC.captchaQuestion=d.question;
    const lab=document.getElementById('cap-label'); if(lab) lab.textContent='Quick check — what is '+d.question+'? *';
    const inp=document.getElementById('f-captcha'); if(inp) inp.value='';
  }catch(e){}
}
function introErr(m){const e=document.getElementById('intro-err');e.textContent=m;e.style.display='block';}
function val(id){const el=document.getElementById(id);return el?el.value.trim():'';}

async function startForm(){
  const name=val('f-name'), email=val('f-email'), company=val('f-co');
  const phone=SC.requirePhone?val('f-phone'):'';
  if(!name) return introErr('Please enter your name.');
  if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return introErr('Please enter a valid email.');
  if(SC.requirePhone && phone.replace(/\D/g,'').length<7) return introErr('Please enter a valid phone number.');

  const payload={name,email,phone,company};
  if(SC.captchaMode==='builtin'){
    payload.captcha=val('f-captcha');
    if(!payload.captcha) return introErr('Please answer the quick check.');
  }else if(SC.captchaMode==='recaptcha'){
    payload.recaptcha=(window.grecaptcha && recaptchaId!==null)?grecaptcha.getResponse(recaptchaId):'';
    if(!payload.recaptcha) return introErr('Please complete the "I\'m not a robot" check.');
  }

  const btn=document.getElementById('startbtn'); btn.disabled=true;
  try{
    const data=await (await fetch('gate.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})).json();
    if(!data.ok){
      if(data.captcha){ SC.captchaQuestion=data.captcha;
        const lab=document.getElementById('cap-label'); if(lab) lab.textContent='Quick check — what is '+data.captcha+'? *';
        const inp=document.getElementById('f-captcha'); if(inp) inp.value='';
      }
      if(SC.captchaMode==='recaptcha' && window.grecaptcha && recaptchaId!==null) grecaptcha.reset(recaptchaId);
      btn.disabled=false;
      return introErr(data.error||'Please check your details.');
    }
    state.meta={name,email,phone,company};
    state.submitted=false;
    const p=data.progress;
    if(p && p.answers && Object.keys(p.answers).length &&
       confirm('We found saved progress for '+email+'.\n\nOK — resume where you left off\nCancel — start fresh')){
      state.answers=p.answers;
      step=Math.min(Math.max(1,p.step||1),SECTIONS.length+1);
    }else{
      state.answers={}; step=1;
    }
    save(); render();
  }catch(e){ btn.disabled=false; introErr('Network error — please try again.'); }
}

/* ---------- save progress (by email) ---------- */
async function saveProgress(silent){
  if(step<1) return;
  try{
    const r=await fetch('progress.php',{method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({action:'save',answers:state.answers,step,meta:state.meta})});
    const d=await r.json();
    if(!silent) toast(d.ok ? ('Progress saved — resume anytime with '+esc(state.meta.email)) : (d.error||'Could not save'));
  }catch(e){ if(!silent) toast('Could not save progress'); }
}
function toast(msg){
  let t=document.getElementById('sc-toast');
  if(!t){ t=document.createElement('div'); t.id='sc-toast'; document.body.appendChild(t); }
  t.innerHTML='<i data-lucide="check-circle"></i> '+msg;
  t.className='show'; lucide.createIcons();
  clearTimeout(window.__toastT); window.__toastT=setTimeout(()=>t.className='',3200);
}

/* ---------- section ---------- */
function renderSection(si){
  const S=SECTIONS[si];
  app.innerHTML=`<div class="sec-toolbar">
      <button class="btn tiny" onclick="saveProgress(false)"><i data-lucide="save"></i> Save progress</button>
      <button class="btn tiny ghost" onclick="resetAll()"><i data-lucide="rotate-ccw"></i> Reset</button>
    </div>
    <div class="card section">
    <div class="section-head">
      <div class="sec-ico"><i data-lucide="${S.icon}"></i></div>
      <div><div class="kicker">Section ${si+1} of ${SECTIONS.length}</div><h2>${S.cat}</h2></div>
    </div>
    <p class="section-desc">${S.desc}</p>
    <div id="qs"></div>
    <div class="secresult" id="secres"></div>
    <div class="nav">
      <button class="btn" onclick="goto(${si})">${si===0?'<i data-lucide=\"arrow-left\"></i> Intro':'<i data-lucide=\"arrow-left\"></i> Back'}</button>
      <button class="btn primary" id="nextbtn" onclick="goto(${si+2})" disabled>${si===SECTIONS.length-1?'See my results':'Next section'} <i data-lucide="arrow-right"></i></button>
    </div>
  </div>`;
  const qs=document.getElementById('qs');
  S.q.forEach((q,qi)=>{
    const key=si+'-'+qi, cur=state.answers[key];
    const el=document.createElement('div'); el.className='q';
    el.innerHTML=`<div class="q-top">
        <div><div class="q-title">${q.t}</div><div class="q-target">Aim for a <b>5</b>: ${q.a5}</div></div>
      </div>
      <div class="scale">${[1,2,3,4,5].map(v=>`<button data-v="${v}" class="${cur===v?'sel':''}" onclick="pick(${si},${qi},${v})">${v}<small>${['','No','','Partly','','Yes'][v]||''}</small></button>`).join('')}</div>
      <div class="anchor" id="anc-${key}"></div>`;
    qs.appendChild(el);
    if(cur) showAnchor(si,qi,cur);
  });
  lucide.createIcons();
  refreshSection(si);
}
function showAnchor(si,qi,v){
  const q=SECTIONS[si].q[qi], box=document.getElementById('anc-'+si+'-'+qi);
  const label={1:'Score 1',2:'Score 2',3:'Score 3',4:'Score 4',5:'Score 5'}[v];
  let txt = v<=1?q.a1 : v===2?`Between "${q.a1}" and "${q.a3}"` : v===3?q.a3 : v===4?`Close to best practice — nearly: ${q.a5}` : q.a5;
  box.innerHTML=`<b style="color:${SCOLORS[v]}">${label}.</b> ${txt}`;
  box.classList.add('show');
}
function pick(si,qi,v){
  state.answers[si+'-'+qi]=v; save();
  document.querySelectorAll('#qs .q')[qi].querySelectorAll('.scale button')
    .forEach(b=>b.classList.toggle('sel',+b.dataset.v===v));
  showAnchor(si,qi,v);
  refreshSection(si);
}
function refreshSection(si){
  const {sum,full}=sectionScore(si);
  const box=document.getElementById('secres');
  const nb=document.getElementById('nextbtn');
  if(full){
    const mx=secMax(si), pct=sum/mx, b=bandFor(pct);
    box.style.background=`linear-gradient(135deg,${b.c},${b.c})`;
    box.classList.add('show');
    box.innerHTML=`<div class="big">${sum}<span>/${mx}</span></div>
      <div><div class="band">${b.t}</div><div class="msg">${b.msg}</div></div>`;
    nb.disabled=false;
  }else{
    box.classList.remove('show'); nb.disabled=true;
  }
}

/* ---------- navigation ---------- */
function goto(n){
  if(n<0){n=0;}
  step=Math.max(0,Math.min(n,SECTIONS.length+1));
  save(); render(); window.scrollTo({top:0,behavior:'smooth'});
  if(step>=1 && step<=SECTIONS.length) saveProgress(true);   // silent autosave per section
}

/* ---------- results ---------- */
function renderResults(){
  const cats=SECTIONS.map((s,si)=>({name:s.cat,score:sectionScore(si).sum,max:secMax(si)}));
  const total=cats.reduce((a,c)=>a+c.score,0);
  const pct=total/MAX;
  const ratio=c=>c.max?c.score/c.max:0;
  let verdict;
  if(pct>=0.8) verdict={t:"Scalable Engine",c:"var(--s5)",p:"Your sales system is strong and repeatable. Focus on optimising and scaling — you can add people and volume with confidence."};
  else if(pct>=0.6) verdict={t:"Strong Foundation",c:"var(--s4)",p:"Solid fundamentals with clear pockets to tighten. Close the gaps below and you're ready to scale."};
  else if(pct>=0.4) verdict={t:"Needs Structure",c:"var(--s3)",p:"The pieces exist but inconsistency is costing you deals. Systemise your process and follow-up next."};
  else verdict={t:"High Risk",c:"var(--s1)",p:"Sales is running on heroics and luck. Prioritise the critical areas below before investing in more leads."};

  const sorted=[...cats].map((c,i)=>({...c,idx:i})).sort((a,b)=>ratio(a)-ratio(b));
  const weakest=sorted.slice(0,3);
  const strongest=[...sorted].reverse().slice(0,3).filter(c=>ratio(c)>=0.7);
  const R=circ(pct,verdict.c);

  app.innerHTML=`<div class="card results">
    <div class="gauge-wrap">
      <div class="gauge">${R}<div class="num"><b>${total}</b><span>out of ${MAX}</span></div></div>
      <div class="verdict">
        <span class="badge" style="background:${verdict.c}">${Math.round(pct*100)}% · ${verdict.t}</span>
        <h2>${state.meta.company?esc(state.meta.company)+"'s":"Your"} sales engine</h2>
        <p>${verdict.p}</p>
      </div>
    </div>

    <div class="submitbar" id="subbar"></div>

    <div class="radar-card card" style="box-shadow:none;border-color:var(--line)">
      <h3 style="justify-content:center;margin-bottom:14px"><i data-lucide="radar"></i> Balance across all ${cats.length} areas</h3>
      ${radar(cats)}
    </div>

    <div class="grid2">
      <div class="panel card" style="box-shadow:none">
        <h3><i data-lucide="bar-chart-3"></i> Score by area</h3>
        <p class="hint">Each area shown as your score out of its maximum.</p>
        ${cats.map(c=>{const p=ratio(c);return `<div class="catbar"><div class="lab"><b>${c.name}</b><span>${c.score}/${c.max}</span></div><div class="track"><i style="width:${p*100}%;background:${bandFor(p).c}"></i></div></div>`;}).join('')}
      </div>
      <div class="panel card" style="box-shadow:none">
        <h3><i data-lucide="flame"></i> Your priority fixes</h3>
        <p class="hint">Lowest-scoring areas — biggest, fastest wins.</p>
        ${weakest.map((c,i)=>`<div class="fix"><div class="rank r${i+1}">${i+1}</div><div><div class="t">${c.name} · ${c.score}/${c.max}</div><div class="d">${FIX[c.name]||''}</div></div></div>`).join('')}
        ${strongest.length?`<h3 style="margin-top:20px"><i data-lucide="award"></i> Strengths to leverage</h3>${strongest.map(c=>`<div class="fix"><div class="rank" style="background:var(--s5)"><i data-lucide="check" style="width:14px"></i></div><div><div class="t">${c.name} · ${c.score}/${c.max}</div></div></div>`).join('')}`:''}
      </div>
    </div>

    <div class="actions">
      <button class="btn" onclick="goto(${SECTIONS.length})"><i data-lucide="arrow-left"></i> Review answers</button>
      <button class="btn" onclick="window.print()"><i data-lucide="printer"></i> Print / Save PDF</button>
      <button class="btn primary" onclick="resetAll()"><i data-lucide="rotate-ccw"></i> Start over</button>
    </div>
    <p class="small">${state.meta.name?esc(state.meta.name)+" · ":""}${esc(state.meta.company||"")} — ${esc(SC.brand)} Sales Diagnostic.</p>
  </div>`;
  lucide.createIcons();
  window.__submitPayload={ answers:state.answers };
  updateSubmitBar();
  if(!state.submitted) submitResults();
}

/* ---------- send results to the consultant ---------- */
function updateSubmitBar(status){
  const bar=document.getElementById('subbar'); if(!bar)return;
  bar.className='submitbar '+(status||(state.submitted?'ok':'idle'));
  if(state.submitted||status==='ok'){
    bar.innerHTML=`<i data-lucide="check-circle"></i><div><b>Sent to your consultant.</b> They've been notified with your results.</div>`;
  }else if(status==='sending'){
    bar.innerHTML=`<i data-lucide="loader"></i><div>Sending your results to your consultant…</div>`;
  }else if(status==='error'){
    bar.innerHTML=`<i data-lucide="alert-triangle"></i><div><b>Couldn't send automatically.</b> <a href="#" onclick="submitResults();return false;">Try again</a></div>`;
  }else{
    bar.innerHTML=`<i data-lucide="send"></i><div>Ready to send your results to your consultant. <a href="#" onclick="submitResults();return false;">Send now</a></div>`;
  }
  lucide.createIcons();
}
async function submitResults(){
  if(state.submitted) return;
  updateSubmitBar('sending');
  try{
    const res=await fetch('submit.php',{method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify(window.__submitPayload)});
    const data=await res.json();
    if(!res.ok||!data.ok) throw new Error(data.error||'failed');
    state.submitted=true; save();
    updateSubmitBar('ok');
  }catch(e){ updateSubmitBar('error'); }
}

/* circular gauge */
function circ(pct,color){
  const r=88,c=2*Math.PI*r,off=c*(1-pct);
  const track=cssVar('--surface2')||'#eef1f5', arc=resolveColor(color);
  return `<svg width="200" height="200" viewBox="0 0 200 200">
    <circle cx="100" cy="100" r="${r}" fill="none" stroke="${track}" stroke-width="16"/>
    <circle cx="100" cy="100" r="${r}" fill="none" stroke="${arc}" stroke-width="16" stroke-linecap="round"
      stroke-dasharray="${c}" stroke-dashoffset="${c}" transform="rotate(-90 100 100)">
      <animate attributeName="stroke-dashoffset" from="${c}" to="${off}" dur="1s" fill="freeze" calcMode="spline" keySplines="0.2 0.8 0.2 1" keyTimes="0;1"/>
    </circle></svg>`;
}
/* radar chart */
function radar(cats){
  const n=cats.length,cx=230,cy=190,R=150;
  const line=cssVar('--line')||'#e7eaef', muted=cssVar('--muted')||'#7c8b99', brandCol=cssVar('--brand')||'#f5901e';
  const pt=(i,rad)=>{const a=-Math.PI/2+i*2*Math.PI/n;return[cx+rad*Math.cos(a),cy+rad*Math.sin(a)];};
  let rings='';
  [0.25,0.5,0.75,1].forEach(f=>{
    rings+=`<polygon points="${cats.map((_,i)=>pt(i,R*f).join(',')).join(' ')}" fill="none" stroke="${line}" stroke-width="1"/>`;
  });
  let axes='',labels='';
  cats.forEach((c,i)=>{
    const[x,y]=pt(i,R); axes+=`<line x1="${cx}" y1="${cy}" x2="${x}" y2="${y}" stroke="${line}"/>`;
    const[lx,ly]=pt(i,R+24);
    const anchor=Math.abs(lx-cx)<8?'middle':(lx>cx?'start':'end');
    labels+=`<text x="${lx}" y="${ly}" font-size="11" fill="${muted}" text-anchor="${anchor}" dominant-baseline="middle" font-weight="600">${c.name}</text>`;
  });
  const poly=cats.map((c,i)=>pt(i,R*(c.max?c.score/c.max:0)).join(',')).join(' ');
  return `<svg class="radar" viewBox="0 0 460 380">${rings}${axes}
    <polygon points="${poly}" fill="rgba(245,144,30,.20)" stroke="${brandCol}" stroke-width="2" stroke-linejoin="round"/>
    ${cats.map((c,i)=>{const[x,y]=pt(i,R*(c.max?c.score/c.max:0));return `<circle cx="${x}" cy="${y}" r="3.5" fill="${brandCol}"/>`;}).join('')}
    ${labels}</svg>`;
}

function resetAll(){
  if(!confirm('Clear all answers and start over?'))return;
  fetch('progress.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'reset'})}).catch(()=>{});
  localStorage.removeItem('salescraft');
  state={answers:{},meta:{name:'',email:'',phone:'',company:''}}; step=0; render();
}

function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));}

/* ---------- theme ---------- */
function cssVar(n){return getComputedStyle(document.documentElement).getPropertyValue(n).trim();}
function resolveColor(c){ if(typeof c==='string' && c.indexOf('var(')===0){ return cssVar(c.slice(4,-1).trim())||'#000'; } return c; }
function scToggleTheme(){
  const d=document.documentElement;
  const t=d.getAttribute('data-theme')==='dark'?'light':'dark';
  d.setAttribute('data-theme',t);
  try{localStorage.setItem('sc-theme',t);}catch(e){}
  const ic=document.querySelector('.theme-toggle i');
  if(ic){ic.setAttribute('data-lucide',t==='dark'?'moon':'sun');lucide.createIcons();}
  if(step>SECTIONS.length) render();   // re-render results so the SVG charts pick up new colours
}

/* ---------- router ---------- */
function render(){
  renderProgress();
  if(step===0) renderIntro();
  else if(step<=SECTIONS.length) renderSection(step-1);
  else renderResults();
}
render();
</script>
</body>
</html>
