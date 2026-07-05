<?php
/**
 * The default SalesCraft scorecard definition. Used as the seed / fallback when
 * the admin hasn't customised it. The admin edits a copy stored in settings
 * (key 'scorecard_schema'); see sc_scorecard() in bootstrap.php.
 *
 * Shape: ['categories' => [ ['name','icon','desc','fix','questions'=>[ ['t','a1','a3','a5'] ] ] ]]
 */
return [
  'categories' => [
    ['name' => 'Sales Strategy', 'icon' => 'target', 'desc' => 'Do you have a clear, deliberate plan for who you sell to and how you hit your numbers?',
     'fix' => 'Write a one-page ICP and cascade a revenue target down to monthly numbers per rep.',
     'questions' => [
       ['t' => 'Clear sales goals', 'a1' => 'No targets — the goal is "sell as much as possible."', 'a3' => "An annual revenue target exists, but it isn't broken down by quarter, month, rep or product.", 'a5' => 'Specific, time-bound targets cascaded to quarter/month/rep/product, reviewed regularly — everyone knows their number.'],
       ['t' => 'Defined target customers', 'a1' => "You'll sell to anyone with a pulse.", 'a3' => 'A general idea of the ideal customer, but nothing documented.', 'a5' => 'A documented ICP (industry, size, buyer, pain, budget) — reps can name it and confidently disqualify poor-fit leads.'],
       ['t' => 'Clear sales channels', 'a1' => 'You rely on random referrals and walk-ins.', 'a3' => 'One or two channels work, but none are deliberately managed.', 'a5' => 'A deliberate channel mix (inbound, outbound, partners…), each with known cost and yield, actively prioritised.'],
       ['t' => 'Sales & marketing alignment', 'a1' => 'Sales and marketing work in silos and blame each other.', 'a3' => 'Occasional coordination, but no shared definitions.', 'a5' => 'Shared lead definitions (MQL/SQL), response SLAs, joint pipeline reviews and closed-loop feedback.'],
     ]],
    ['name' => 'Sales Process', 'icon' => 'git-branch', 'desc' => 'Is there one repeatable way deals move forward — or does every rep improvise?',
     'fix' => 'Map your deal stages with entry/exit criteria and assign an owner to each.',
     'questions' => [
       ['t' => 'Documented sales process', 'a1' => 'Every rep does it their own way; nothing is written down.', 'a3' => 'Steps are loosely understood and only partly documented.', 'a5' => 'The end-to-end process is documented, taught and followed — a new hire could learn it from the doc.'],
       ['t' => 'Consistent lead handling', 'a1' => 'Leads are handled ad hoc and often dropped.', 'a3' => 'Most leads get handled, but inconsistently.', 'a5' => 'Every lead follows the same intake, qualification and routing rules, regardless of which rep gets it.'],
       ['t' => 'Defined pipeline stages', 'a1' => 'No stages — deals are just "in progress."', 'a3' => 'Stages exist but their definitions are fuzzy.', 'a5' => 'Clear stages with entry/exit criteria — a deal only advances when the criteria are genuinely met.'],
       ['t' => 'Stage ownership clarity', 'a1' => "It's unclear who owns a deal at any given point.", 'a3' => 'Ownership is generally known but hand-offs are messy.', 'a5' => 'Every stage has a named owner with clean hand-off rules — nothing falls through the cracks.'],
     ]],
    ['name' => 'Lead Management', 'icon' => 'filter', 'desc' => 'How predictably do leads arrive, and how well do you work them?',
     'fix' => 'Set a response-time rule (e.g. under 1 hour) and a fixed multi-touch follow-up cadence.',
     'questions' => [
       ['t' => 'Reliable lead sources', 'a1' => 'Lead flow is unpredictable — feast or famine.', 'a3' => 'A source or two, but volume swings a lot.', 'a5' => 'Multiple predictable sources producing a steady, forecastable volume of leads.'],
       ['t' => 'Speed of response', 'a1' => 'It takes days to respond, or you never do.', 'a3' => 'Leads are contacted within a day.', 'a5' => "Inbound leads are contacted within minutes / the first hour, consistently — and it's tracked."],
       ['t' => 'Follow-up consistency', 'a1' => 'One touch, then you give up.', 'a3' => 'A few follow-ups, but no set cadence.', 'a5' => 'A defined multi-touch cadence over days/weeks, executed for every lead, with automated reminders.'],
       ['t' => 'Conversion tracking', 'a1' => 'You have no idea of your conversion rates.', 'a3' => 'A rough sense of the overall close rate.', 'a5' => 'Conversion is measured at every stage — you know lead→close rates by source and by rep.'],
     ]],
    ['name' => 'Sales Team', 'icon' => 'users', 'desc' => 'Can your people actually sell — knowledge, benefits, objections, discovery?',
     'fix' => 'Run role-plays on discovery and objection handling; build a benefit/ROI cheat-sheet.',
     'questions' => [
       ['t' => 'Product knowledge', 'a1' => 'Reps are unsure of product details.', 'a3' => "Reps know the product's features.", 'a5' => 'Deep command of product, use cases and competitor comparisons — reps answer any question confidently.'],
       ['t' => 'Benefit-based selling', 'a1' => 'Reps just list features.', 'a3' => 'Reps mention some benefits.', 'a5' => 'Reps consistently translate features into quantified customer outcomes and ROI.'],
       ['t' => 'Objection handling', 'a1' => "Reps freeze or discount the moment they're challenged.", 'a3' => 'Common objections are handled adequately.', 'a5' => 'Reps anticipate objections and respond with a proven framework plus proof, keeping deals moving.'],
       ['t' => 'Consultative skills', 'a1' => "Reps pitch immediately and don't listen.", 'a3' => 'Reps ask some questions.', 'a5' => 'Reps diagnose before prescribing — strong discovery questions and a tailored solution.'],
     ]],
    ['name' => 'Messaging', 'icon' => 'message-square', 'desc' => 'Is your value clear, differentiated and told the same way by everyone?',
     'fix' => 'Lock a single value proposition and rewrite pitch + website to say it the same way.',
     'questions' => [
       ['t' => 'Clear value proposition', 'a1' => "You can't clearly articulate why someone should buy.", 'a3' => "A value statement exists, but it's generic.", 'a5' => 'A sharp, specific value prop tied to customer outcomes — everyone delivers it the same way.'],
       ['t' => 'Customer pain clarity', 'a1' => "You don't really know the customer's real pains.", 'a3' => 'A general understanding of customer pains.', 'a5' => 'You can state the customer\'s top pains in their own words, and messaging leads with them.'],
       ['t' => 'Differentiation', 'a1' => 'Just "we\'re cheaper/better," with no proof.', 'a3' => 'Some differentiators are named.', 'a5' => 'Clear, defensible differentiation vs named competitors, backed by proof.'],
       ['t' => 'Consistent messaging', 'a1' => 'Every rep says something different.', 'a3' => 'Messaging is broadly similar.', 'a5' => 'Unified messaging across every rep, the website and all materials.'],
     ]],
    ['name' => 'Objections', 'icon' => 'shield', 'desc' => 'Are objections anticipated, answered with proof, and price defended on value?',
     'fix' => 'Document your top 10 objections with a tested response and a proof point for each.',
     'questions' => [
       ['t' => 'Objection identification', 'a1' => "You're regularly surprised by objections.", 'a3' => 'You know the common ones.', 'a5' => 'A documented list of every common objection with root causes, updated over time.'],
       ['t' => 'Structured responses', 'a1' => 'You improvise a response every time.', 'a3' => 'You have a few go-to lines.', 'a5' => 'A tested response framework for each objection — trained and rehearsed by the team.'],
       ['t' => 'Use of proof', 'a1' => 'No evidence is offered.', 'a3' => 'You occasionally cite a case or result.', 'a5' => 'A ready library of case studies, testimonials, data and guarantees used at the right moment.'],
       ['t' => 'Price confidence', 'a1' => 'You cave to discounts quickly.', 'a3' => 'You hold price some of the time.', 'a5' => 'You defend price on value, rarely discount, and can justify the ROI clearly.'],
     ]],
    ['name' => 'Sales Tools', 'icon' => 'wrench', 'desc' => 'Do reps have the scripts, materials and onboarding to sell well from day one?',
     'fix' => 'Ship a starter kit: call script, updated one-pager, FAQ and a 30-day onboarding plan.',
     'questions' => [
       ['t' => 'Sales scripts', 'a1' => 'There are none.', 'a3' => 'A rough script exists.', 'a5' => 'Proven call/discovery/demo scripts and talk tracks that the team actually uses.'],
       ['t' => 'Brochures / materials', 'a1' => 'None, or badly outdated.', 'a3' => 'Some materials exist.', 'a5' => 'Professional, current collateral mapped to each stage of the buying journey.'],
       ['t' => 'FAQs', 'a1' => 'No FAQ resource.', 'a3' => 'Answers are handled informally.', 'a5' => 'A maintained FAQ covering common questions and objections, accessible to every rep.'],
       ['t' => 'Onboarding tools', 'a1' => 'New reps sink or swim.', 'a3' => 'Some onboarding docs exist.', 'a5' => 'A structured onboarding program (playbook, training, ramp plan) that gets reps productive fast.'],
     ]],
    ['name' => 'CRM & Data', 'icon' => 'database', 'desc' => 'Is every lead captured, visible, followed up and reported on — reliably?',
     'fix' => 'Get every live lead into the CRM with source + stage, and turn on follow-up reminders.',
     'questions' => [
       ['t' => 'Lead tracking', 'a1' => 'Leads live in people\'s heads, notebooks or scattered sheets.', 'a3' => 'A CRM exists but is under-used.', 'a5' => 'Every lead is captured in the CRM with source and status, and kept current.'],
       ['t' => 'Pipeline visibility', 'a1' => 'No visibility into where deals stand.', 'a3' => "Some pipeline view exists, but it isn't trusted.", 'a5' => 'A real-time, trusted pipeline by stage, value and owner.'],
       ['t' => 'Follow-up reminders', 'a1' => 'You rely on memory.', 'a3' => 'Reminders are set manually.', 'a5' => 'Automated tasks and reminders ensure no follow-up is ever missed.'],
       ['t' => 'Reporting', 'a1' => 'No reports.', 'a3' => 'Basic reports pulled manually.', 'a5' => 'Automated dashboards on activity, conversion and forecast, reviewed regularly.'],
     ]],
    ['name' => 'Management', 'icon' => 'clipboard-check', 'desc' => 'Are reps reviewed, coached and held accountable to the right KPIs?',
     'fix' => 'Start a weekly pipeline review and monthly 1:1 coaching against a short KPI set.',
     'questions' => [
       ['t' => 'Sales reviews', 'a1' => 'They never really happen.', 'a3' => 'Occasional check-ins.', 'a5' => 'Regular, structured pipeline and deal reviews on a set cadence.'],
       ['t' => 'Coaching', 'a1' => 'No coaching at all.', 'a3' => 'Ad hoc feedback.', 'a5' => 'Ongoing 1:1 coaching against skills and real calls, with development plans.'],
       ['t' => 'KPI usage', 'a1' => 'No KPIs are tracked.', 'a3' => 'You track revenue only.', 'a5' => 'Balanced KPIs (activity, conversion, pipeline, revenue) actually drive decisions.'],
       ['t' => 'Accountability', 'a1' => 'No consequences for missed targets.', 'a3' => 'Some accountability.', 'a5' => 'Clear ownership of numbers, a transparent scoreboard and consistent follow-through.'],
     ]],
    ['name' => 'Scalability', 'icon' => 'trending-up', 'desc' => 'Could you double the volume without the whole thing breaking?',
     'fix' => 'Remove single points of failure — document the playbook so anyone can run it.',
     'questions' => [
       ['t' => 'Process independence', 'a1' => 'Everything depends on the owner or one star rep.', 'a3' => "There's some independence.", 'a5' => "Sales runs on process, not heroics — it doesn't depend on any single person."],
       ['t' => 'Onboarding ease', 'a1' => 'Adding reps takes forever, if it works at all.', 'a3' => 'You can onboard, but slowly.', 'a5' => 'New reps ramp quickly and predictably through a repeatable system.'],
       ['t' => 'Revenue predictability', 'a1' => "You can't predict next month.", 'a3' => 'You have a rough forecast.', 'a5' => 'An accurate, repeatable forecast you can plan and invest against.'],
       ['t' => 'Scalability readiness', 'a1' => 'Growth would break the system.', 'a3' => 'You could grow, but with real strain.', 'a5' => 'People, process and systems are ready to double volume without breaking.'],
     ]],
  ],
];
