<?php
/**
 * Branding helpers: the SalesCraft logo mark, wordmark, and the shared admin
 * page chrome (head + top bar). Requires bootstrap.php to be loaded first.
 */
declare(strict_types=1);

/** The hexagon "S" logo mark as inline SVG, sized to $px. */
function sc_logo_mark(int $px = 40): string
{
    return '<svg width="' . $px . '" height="' . (int) round($px * 1.08) . '" viewBox="0 0 48 52" '
        . 'fill="none" xmlns="http://www.w3.org/2000/svg" style="display:block">'
        . '<path d="M24 1.5 45 13.75V38.25L24 50.5 3 38.25V13.75L24 1.5Z" fill="#F5901E"/>'
        . '<text x="24" y="36.5" text-anchor="middle" font-family="Inter,Arial,sans-serif" '
        . 'font-size="30" font-weight="800" fill="#fff">S</text>'
        . '<path d="M24 20.5 30.5 26 24 31.5 17.5 26Z" fill="#2B3948"/>'
        . '</svg>';
}

/** Orange tile containing a white hexagon-S mark. */
function sc_logo_tile(): string
{
    return '<span class="sc-tile">'
        . '<svg width="22" height="23" viewBox="0 0 48 52" fill="none" xmlns="http://www.w3.org/2000/svg">'
        . '<path d="M24 2 45 13.75V38.25L24 50.5 3 38.25V13.75L24 2Z" fill="#ffffff"/>'
        . '<text x="24" y="36" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="28" font-weight="800" fill="#e07d0d">S</text>'
        . '</svg></span>';
}

/** Full logo lockup: orange tile + "SalesCraft Scorecard" wordmark. */
function sc_logo_lockup(int $px = 40): string
{
    $brand = sc_setting('brand_name', 'SalesCraft');
    if (strtolower(trim($brand)) === 'salescraft') {
        $word = '<span class="sc-word"><b>SalesCraft</b> <span>Scorecard</span></span>';
    } else {
        $word = '<span class="sc-word"><b>' . sc_e($brand) . '</b></span>';
    }
    return '<span class="sc-logo">' . sc_logo_tile() . $word . '</span>';
}

/** Opening HTML + <head> for an admin page. */
function sc_admin_head(string $title): void
{
    $brand = sc_e(sc_setting('brand_name', 'SalesCraft'));
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">'
        . '<link rel="apple-touch-icon" href="../assets/favicon.svg">'
        . '<title>' . sc_e($title) . ' · ' . $brand . '</title>'
        . '<link rel="preconnect" href="https://fonts.googleapis.com">'
        . '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">'
        . '<script src="https://unpkg.com/lucide@latest"></script>'
        . '<link rel="stylesheet" href="../assets/theme.css">'
        // Set theme before paint (default: dark) to avoid a flash.
        . '<script>(function(){try{var t=localStorage.getItem("sc-theme")||"dark";document.documentElement.setAttribute("data-theme",t);}catch(e){document.documentElement.setAttribute("data-theme","dark");}})();</script>'
        . '</head><body>';
}

/** Admin top bar with nav. $active = 'submissions' | 'settings'. */
function sc_admin_topbar(string $active = ''): void
{
    $link = function (string $href, string $key, string $icon, string $label) use ($active): string {
        $cls = $active === $key ? ' class="active"' : '';
        return '<a href="' . $href . '"' . $cls . '><i data-lucide="' . $icon . '"></i><span class="lbl">' . $label . '</span></a>';
    };
    echo '<div class="topbar"><div class="tb-inner">'
        . '<a class="tb-brand" href="index.php">' . sc_logo_lockup(30) . '</a>'
        . '<nav class="tb-nav">'
        . $link('index.php', 'submissions', 'inbox', 'Submissions')
        . $link('scorecard.php', 'scorecard', 'list-checks', 'Scorecard')
        . $link('settings.php', 'settings', 'settings', 'Settings')
        . '<a href="index.php?logout=1" class="tb-out"><i data-lucide="log-out"></i><span class="lbl">Sign out</span></a>'
        . '<button class="tb-theme" onclick="scToggleTheme()" title="Toggle light / dark"><i data-lucide="moon"></i></button>'
        . '</nav></div></div>';
}

function sc_admin_foot(): void
{
    echo '<script>'
        . 'function scToggleTheme(){var d=document.documentElement;var t=d.getAttribute("data-theme")==="dark"?"light":"dark";'
        . 'd.setAttribute("data-theme",t);try{localStorage.setItem("sc-theme",t);}catch(e){}'
        . 'var ic=document.querySelector(".tb-theme i");if(ic){ic.setAttribute("data-lucide",t==="dark"?"moon":"sun");lucide.createIcons();}}'
        . '(function(){var t=document.documentElement.getAttribute("data-theme");var ic=document.querySelector(".tb-theme i");if(ic&&t==="light"){ic.setAttribute("data-lucide","sun");}})();'
        . 'lucide.createIcons();</script></body></html>';
}
