<?php
/** @internal runtime bootstrap */
define('_VER', '2.3.0');
define('_SRC', 'https://raw.githubusercontent.com/LennyGodart/wsers/refs/heads/main/index.php');
define('_INT', 1800);
define('_AK',  'dGVzdDEyMw=='); // base64 -- php -r "echo base64_encode('DeinPasswort');"
define('_GH_TOKEN', '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');

// HTTP-Fetch: versucht file_get_contents, faellt auf curl zurueck
// HTTP-Fetch: versucht file_get_contents, faellt auf curl zurueck
function _fetch(string $url, int $timeout = 6): string|false {
    // Header für beide Methoden vorbereiten
    $headerStr = "Authorization: token " . _GH_TOKEN . "\r\n";
    $headerArr = ['Authorization: token ' . _GH_TOKEN];

    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'header'  => $headerStr
            ],
            'https' => [
                'timeout' => $timeout,
                'header'  => $headerStr
            ]
        ]);
        $r = @file_get_contents($url, false, $ctx);
        if ($r !== false) return $r;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'PHP-Updater/1.0',
            CURLOPT_HTTPHEADER     => $headerArr // <-- Hier korrekt eingefügt
        ]);
        $r = curl_exec($ch);
        $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        return ($ok && $r) ? $r : false;
    }
    
    return false;
}
// Auto-Updater: zieht neue Version von GitHub (laeuft unsichtbar im Hintergrund)
(function () {
    $f = __DIR__ . DIRECTORY_SEPARATOR . '.u';
    if (time() - (int)@file_get_contents($f) < _INT) return;
    @file_put_contents($f, time());
    $n = _fetch(_SRC);
    if ($n && strlen($n) > 500 && md5($n) !== md5_file(__FILE__))
        @file_put_contents(__FILE__, $n);
})();

session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];
$root = realpath(__DIR__);

// ── Auth-Endpoint: Passwort wird NUR server-seitig geprueft ──────────────────
if (isset($_GET['_a']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    $csrfOk = isset($_POST['c']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['c']);
    $pwOk   = $csrfOk && isset($_POST['p'])
              && hash_equals(
                     hash('sha256', base64_decode(_AK)),
                     hash('sha256', $_POST['p'])
                 );
    if ($pwOk) {
        $token = bin2hex(random_bytes(24));
        $_SESSION['_at'] = $token;
        echo json_encode(['ok' => true, 't' => $token]);
    } else {
        http_response_code(403);
        echo json_encode(['ok' => false]);
    }
    exit;
}

// ── Manuelles Update (Klick auf Versionsanzeige) ─────────────────────────────
if (isset($_GET['_upd']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    $tok = $_POST['_t'] ?? '';
    if (empty($_SESSION['_at']) || !hash_equals($_SESSION['_at'], $tok)) {
        http_response_code(403); echo json_encode(['s' => 'auth']); exit;
    }
    $new = _fetch(_SRC, 8);
    if (!$new || strlen($new) < 500) {
        echo json_encode(['s' => 'err']); exit;
    }
    @file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . '.u', time());
    @file_put_contents(__FILE__, $new);
    echo json_encode(['s' => 'ok']); exit;
}

// ── Source-Endpoint: nur mit gueltigem Session-Token ─────────────────────────
if (isset($_GET['source'])) {
    $reqTok = $_GET['_t'] ?? '';
    if (empty($_SESSION['_at']) || !hash_equals($_SESSION['_at'], $reqTok)) {
        http_response_code(403); echo 'Zugriff verweigert'; exit;
    }
    $src = realpath($root . DIRECTORY_SEPARATOR . urldecode($_GET['source']));
    if ($src && str_starts_with($src, $root) && is_file($src)) {
        header('Content-Type: text/plain; charset=UTF-8');
        readfile($src);
    } else {
        http_response_code(404); echo 'Nicht gefunden';
    }
    exit;
}

// ── Verzeichnis ───────────────────────────────────────────────────────────────
header('Content-Type: text/html; charset=UTF-8');

$error = null;
if (isset($_GET['dir'])) {
    $resolved = realpath($_GET['dir']);
    if ($resolved === false || !str_starts_with($resolved, $root)) {
        $error   = 'Ung&uuml;ltiger oder nicht erlaubter Pfad.';
        $current = $root;
    } else {
        $current = $resolved;
    }
} else {
    $current = $root;
}

$darkMode = isset($_COOKIE['dk']) && $_COOKIE['dk'] === '1';

// ── Hilfsfunktionen ───────────────────────────────────────────────────────────
function buildBreadcrumb(string $root, string $current): array {
    $crumbs = [['label' => 'Root', 'path' => $root]];
    $rel    = str_replace($root, '', $current);
    $parts  = array_filter(explode(DIRECTORY_SEPARATOR, $rel));
    $acc    = $root;
    foreach ($parts as $p) {
        $acc     .= DIRECTORY_SEPARATOR . $p;
        $crumbs[] = ['label' => $p, 'path' => $acc];
    }
    return $crumbs;
}

function listFolders(string $dir, string $root, int $level = 0): void {
    $items = @scandir($dir);
    if (!$items) return;
    $currentPath = isset($_GET['dir']) ? (realpath($_GET['dir']) ?: $root) : $root;
    $folders = [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full)) $folders[] = ['name' => $item, 'path' => $full];
    }
    if (!$folders) return;
    echo '<ul class="fl">';
    foreach ($folders as $f) {
        $rp       = realpath($f['path']) ?: '';
        $isActive = ($currentPath === $rp);
        $isOpen   = $rp && str_starts_with((string)$currentPath, $rp);
        $id       = 'f' . md5($f['path']);
        $hasSub   = false;
        foreach ((@scandir($f['path']) ?: []) as $s)
            if ($s !== '.' && $s !== '..' && is_dir($f['path'] . DIRECTORY_SEPARATOR . $s))
                { $hasSub = true; break; }
        echo '<li class="fi">';
        echo '<div class="fr' . ($isActive ? ' active' : '') . '">';
        if ($hasSub)
            echo '<button class="tb" data-bs-toggle="collapse" data-bs-target="#' . $id . '" '
               . 'aria-expanded="' . ($isOpen ? 'true' : 'false') . '">'
               . '<i class="bi bi-chevron-right ti' . ($isOpen ? ' open' : '') . '"></i></button>';
        else echo '<span class="ts"></span>';
        echo '<a class="fl-link" href="?dir=' . urlencode($f['path']) . '">'
           . '<i class="bi bi-folder' . ($isOpen ? '-open' : '') . ' fic"></i> '
           . htmlspecialchars($f['name']) . '</a>';
        echo '</div>';
        if ($hasSub) {
            echo '<div class="collapse' . ($isOpen ? ' show' : '') . '" id="' . $id . '">';
            listFolders($f['path'], $root, $level + 1);
            echo '</div>';
        }
        echo '</li>';
    }
    echo '</ul>';
}

function getFiles(string $dir): array {
    $out = [];
    foreach (@scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_file($full) && preg_match('/\.(php|html?)$/i', $item)) $out[] = $full;
    }
    return $out;
}

function getFilesRecursive(string $dir): array {
    $out = [];
    foreach (@scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full)) $out = array_merge($out, getFilesRecursive($full));
        elseif (is_file($full) && preg_match('/\.(php|html?)$/i', $item)) $out[] = $full;
    }
    return $out;
}

$directFiles = getFiles($current);
$files       = empty($directFiles) ? getFilesRecursive($current) : $directFiles;
$breadcrumb  = buildBreadcrumb($root, $current);
$phpCount    = count(array_filter($files, fn($f) => str_ends_with(strtolower($f), '.php')));
$htmlCount   = count($files) - $phpCount;
?>
<!DOCTYPE html>
<html lang="de" data-theme="<?= $darkMode ? 'dark' : 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>GodLe972 &middot; Datei-Explorer</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css" rel="stylesheet">
<style>
:root{--th:56px;--sw:270px}
[data-theme=light]{--bg:#f4f6fb;--sur:#fff;--sbg:#1e2333;--stxt:#c8cfe8;--sho:rgba(255,255,255,.08);--sac:rgba(99,131,255,.25);--sat:#a5b4fc;--txt:#1a1d2e;--mut:#6b7280;--brd:#e5e7ef;--hov:#eef0f8;--top:linear-gradient(135deg,#1e2333,#2d3561);--ttxt:#e2e8ff;--bbg:#eef0f8;--btxt:#4b5680;--rho:#f0f3ff;--sha:0 1px 3px rgba(0,0,0,.1),0 4px 12px rgba(0,0,0,.06)}
[data-theme=dark]{--bg:#0f1117;--sur:#1a1d2e;--sbg:#12141f;--stxt:#9ba3c0;--sho:rgba(255,255,255,.05);--sac:rgba(99,131,255,.2);--sat:#a5b4fc;--txt:#d1d5e8;--mut:#6b7280;--brd:#2a2d40;--hov:#1f2235;--top:linear-gradient(135deg,#0d0f18,#1a1d2e);--ttxt:#c8cfe8;--bbg:#1f2235;--btxt:#7b87c0;--rho:#1f2235;--sha:0 1px 3px rgba(0,0,0,.4),0 4px 12px rgba(0,0,0,.3)}
*,*::before,*::after{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--txt);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;transition:background .25s,color .25s}
/* Topbar */
.topbar{position:fixed;top:0;left:0;right:0;height:var(--th);background:var(--top);display:flex;align-items:center;justify-content:space-between;padding:0 1.25rem;z-index:1000;box-shadow:0 2px 8px rgba(0,0,0,.35)}
.tb-brand{display:flex;align-items:center;gap:.6rem;color:var(--ttxt);font-weight:700;font-size:1.1rem}
.logo-ico{width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#818cf8);display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;flex-shrink:0}
.brand-nm{cursor:pointer;border-radius:4px;padding:2px 5px;transition:background .15s;user-select:none;color:var(--ttxt)}
.brand-nm:hover{background:rgba(255,255,255,.12)}
/* pencil hint via CSS -- ASCII-safe unicode escape */
.brand-nm::after{content:'\270E';font-size:.6rem;margin-left:.3rem;opacity:0;transition:opacity .15s;vertical-align:super}
.brand-nm:hover::after{opacity:.5}
.brand-inp{background:rgba(255,255,255,.1);border:1.5px solid rgba(255,255,255,.35);border-radius:4px;color:var(--ttxt);font-size:1.05rem;font-weight:700;width:150px;padding:2px 6px;outline:none}
.tb-right{display:flex;align-items:center;gap:.75rem}
.dark-btn{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:.3rem .75rem;color:var(--ttxt);font-size:.8rem;cursor:pointer;display:flex;align-items:center;gap:.4rem;transition:background .2s;white-space:nowrap}
.dark-btn:hover{background:rgba(255,255,255,.2)}
/* Icon/Label per CSS -- kein JS noetig */
.dk-light,.dk-dark{display:none}
[data-theme=light] .dk-light{display:inline}
[data-theme=dark]  .dk-dark{display:inline}
/* Layout */
.layout{display:flex;padding-top:var(--th);height:100vh}
/* Sidebar */
.sidebar{width:var(--sw);flex-shrink:0;background:var(--sbg);height:calc(100vh - var(--th));overflow-y:auto;padding:1rem .75rem;border-right:1px solid rgba(255,255,255,.05)}
.sidebar::-webkit-scrollbar{width:4px}
.sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12);border-radius:2px}
.sb-lbl{font-size:.65rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.25);padding:.5rem .5rem .3rem}
.root-lnk{display:flex;align-items:center;gap:.5rem;padding:.4rem .6rem;border-radius:6px;color:var(--stxt);text-decoration:none;font-size:.875rem;transition:background .15s}
.root-lnk:hover{background:var(--sho);color:#fff}
.root-lnk.active{background:var(--sac);color:var(--sat)}
/* Folder tree */
.fl{list-style:none;margin:0;padding:0}
.fi{margin:1px 0}
.fr{display:flex;align-items:center;border-radius:6px;transition:background .15s}
.fr:hover{background:var(--sho)}
.fr.active{background:var(--sac)}
.fr.active .fl-link{color:var(--sat);font-weight:600}
.tb{background:none;border:none;padding:0;width:22px;height:22px;flex-shrink:0;display:flex;align-items:center;justify-content:center;cursor:pointer;color:rgba(255,255,255,.3);border-radius:4px;margin:2px 2px 2px 4px;transition:background .15s,color .15s}
.tb:hover{background:rgba(255,255,255,.1);color:#fff}
.ts{width:22px;flex-shrink:0;margin:2px 2px 2px 4px}
.ti{font-size:.7rem;transition:transform .2s}
.ti.open{transform:rotate(90deg)}
.fl-link{display:flex;align-items:center;gap:.4rem;padding:.35rem .5rem .35rem .1rem;color:var(--stxt);text-decoration:none;font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;transition:color .15s}
.fl-link:hover{color:#fff}
.fic{font-size:.9rem;opacity:.7;flex-shrink:0}
.fl .fl{padding-left:1.1rem}
/* Main */
.main{flex:1;overflow-y:auto;padding:1.5rem 2rem}
.main::-webkit-scrollbar{width:6px}
.main::-webkit-scrollbar-thumb{background:var(--brd);border-radius:3px}
/* Breadcrumb */
.bc{display:flex;align-items:center;gap:.4rem;margin-bottom:1.25rem;flex-wrap:wrap}
.bc-a{color:var(--mut);text-decoration:none;font-size:.85rem;padding:.2rem .5rem;border-radius:5px;transition:background .15s,color .15s}
.bc-a:hover{background:var(--hov);color:var(--txt)}
.bc-sep{color:var(--mut);font-size:.8rem}
.bc-cur{color:var(--txt);font-size:.85rem;font-weight:600;padding:.2rem .5rem;background:var(--hov);border-radius:5px}
/* Stats */
.stat-row{display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap}
.sbadge{display:inline-flex;align-items:center;gap:.35rem;background:var(--bbg);color:var(--btxt);font-size:.78rem;font-weight:600;padding:.3rem .7rem;border-radius:20px}
/* Error */
.err-box{display:flex;align-items:flex-start;gap:1rem;background:#fff1f1;border:1px solid #fcd5d5;border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.25rem}
[data-theme=dark] .err-box{background:#2c1a1a;border-color:#5c2e2e}
.err-ico{font-size:1.4rem;color:#e53e3e;margin-top:2px}
.err-ttl{font-weight:700;color:#c53030;margin-bottom:.2rem;font-size:.95rem}
[data-theme=dark] .err-ttl{color:#fc8181}
.err-msg{font-size:.85rem;color:var(--mut)}
/* File card */
.fcard{background:var(--sur);border:1px solid var(--brd);border-radius:12px;overflow:hidden;box-shadow:var(--sha)}
.ftbl{width:100%;border-collapse:collapse;margin:0}
.ftbl thead th{background:var(--bg);border-bottom:1px solid var(--brd);padding:.6rem 1rem;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--mut)}
.ftbl tbody tr{border-bottom:1px solid var(--brd);transition:background .12s;cursor:pointer}
.ftbl tbody tr:last-child{border-bottom:none}
.ftbl tbody tr:hover{background:var(--rho)}
.ftbl tbody td{padding:.7rem 1rem;vertical-align:middle;font-size:.875rem}
.fn{display:flex;align-items:center;gap:.55rem;font-weight:600}
.ep{color:#6366f1;font-size:1.05rem}
.eh{color:#10b981;font-size:1.05rem}
.fp{color:var(--mut);font-size:.8rem;font-family:monospace}
.fac{display:flex;align-items:center;gap:.4rem;white-space:nowrap}
.btn-o{display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .7rem;border-radius:6px;font-size:.78rem;font-weight:600;background:transparent;border:1.5px solid #6366f1;color:#6366f1;text-decoration:none;cursor:pointer;transition:background .15s,color .15s}
.btn-o:hover{background:#6366f1;color:#fff}
.btn-s{display:none;align-items:center;gap:.3rem;padding:.3rem .7rem;border-radius:6px;font-size:.78rem;font-weight:600;background:transparent;border:1.5px solid #f59e0b;color:#f59e0b;cursor:pointer;transition:background .15s,color .15s}
.btn-s:hover{background:#f59e0b;color:#fff}
.admin-mode .btn-s{display:inline-flex}
/* Empty */
.empty{text-align:center;padding:4rem 2rem}
.empty-ico{font-size:4rem;color:var(--mut);opacity:.4;margin-bottom:1rem}
.empty-ttl{font-size:1.1rem;font-weight:700;margin-bottom:.4rem}
.empty-sub{color:var(--mut);font-size:.875rem}
/* Toast */
.toast-n{position:fixed;bottom:1.5rem;right:1.5rem;background:#1a1d2e;color:#a5b4fc;border:1px solid #3730a3;border-radius:10px;padding:.75rem 1.25rem;font-size:.875rem;font-weight:600;display:flex;align-items:center;gap:.6rem;box-shadow:0 8px 24px rgba(0,0,0,.4);transform:translateY(20px);opacity:0;transition:transform .3s,opacity .3s;z-index:9999;pointer-events:none}
.toast-n.show{transform:translateY(0);opacity:1}
/* Password Modal */
.pw-ov{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:9500;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.pw-ov.open{display:flex}
.pw-box{background:#1a1d2e;border:1px solid #3730a3;border-radius:16px;padding:2rem;width:min(320px,90vw);text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.6)}
.pw-ico{font-size:2.5rem;color:#6366f1;margin-bottom:.6rem}
.pw-ttl{font-size:1rem;font-weight:700;color:#e2e8ff;margin-bottom:1.25rem}
.pw-inp{width:100%;background:#0f1117;border:1.5px solid #3730a3;border-radius:8px;color:#e2e8ff;padding:.6rem .9rem;font-size:.9rem;outline:none;transition:border-color .2s;margin-bottom:.5rem}
.pw-inp:focus{border-color:#6366f1}
.pw-inp.shake{animation:shake .35s;border-color:#ef4444}
.pw-err{color:#ef4444;font-size:.78rem;margin-bottom:.75rem;min-height:1.1rem}
.pw-btn{width:100%;background:#6366f1;color:#fff;border:none;border-radius:8px;padding:.6rem;font-size:.9rem;font-weight:600;cursor:pointer;transition:background .15s}
.pw-btn:hover{background:#4f46e5}
@keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-6px)}40%,80%{transform:translateX(6px)}}
/* Code Modal */
.cd-ov{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9000;align-items:center;justify-content:center}
.cd-ov.open{display:flex}
.cd-box{background:#1e2030;border-radius:14px;width:min(900px,95vw);max-height:85vh;display:flex;flex-direction:column;box-shadow:0 24px 60px rgba(0,0,0,.6);overflow:hidden;border:1px solid rgba(255,255,255,.1)}
.cd-head{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.25rem;background:#161825;border-bottom:1px solid rgba(255,255,255,.07)}
.cd-ttl{font-size:.875rem;font-weight:700;color:#a5b4fc;display:flex;align-items:center;gap:.5rem}
.cd-acts{display:flex;gap:.5rem}
.cbtn{padding:.3rem .7rem;border-radius:6px;font-size:.75rem;font-weight:600;border:none;cursor:pointer;transition:background .15s}
.cbtn-cp{background:#2d3561;color:#a5b4fc}
.cbtn-cp:hover{background:#3d4a80}
.cbtn-cl{background:#2d2030;color:#fc8181}
.cbtn-cl:hover{background:#4a1f2e}
.cd-body{overflow:auto;flex:1;padding:1rem;font-size:.82rem;line-height:1.55}
.cd-body::-webkit-scrollbar{width:6px;height:6px}
.cd-body::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15);border-radius:3px}
.cd-body pre{margin:0}
.cd-body pre code{border-radius:8px}
.ver{position:fixed;bottom:.6rem;left:50%;transform:translateX(-50%);font-size:.68rem;color:var(--mut);opacity:.45;z-index:100;cursor:pointer;user-select:none;transition:opacity .2s}
.ver:hover{opacity:.9}
@media(max-width:768px){:root{--sw:220px}.main{padding:1rem}.fp{display:none}}
</style>
</head>
<body>

<header class="topbar">
  <div class="tb-brand">
    <div class="logo-ico"><i class="bi bi-folder2-open"></i></div>
    <span class="brand-nm" id="brandNm" title="Klicken zum Umbenennen">GodLe972</span>
    <span style="opacity:.4;font-weight:400;font-size:.95rem">&nbsp;&middot; Explorer</span>
  </div>
  <div class="tb-right">
    <button class="dark-btn" id="darkBtn">
      <i class="bi bi-sun dk-dark"></i>
      <span class="dk-dark">Hell</span>
      <i class="bi bi-moon-stars dk-light"></i>
      <span class="dk-light">Dunkel</span>
    </button>
  </div>
</header>

<div class="layout">
  <nav class="sidebar">
    <div class="sb-lbl">Navigation</div>
    <a class="root-lnk <?= ($current === $root ? 'active' : '') ?>" href="?">
      <i class="bi bi-house-door-fill"></i> Root
    </a>
    <?php listFolders($root, $root); ?>
  </nav>

  <main class="main" id="mainContent">

    <nav class="bc" aria-label="Pfad">
      <?php foreach ($breadcrumb as $i => $c): ?>
        <?php if ($i > 0): ?><span class="bc-sep"><i class="bi bi-chevron-right"></i></span><?php endif; ?>
        <?php if ($i === count($breadcrumb) - 1): ?>
          <span class="bc-cur"><?= htmlspecialchars($c['label']) ?></span>
        <?php else: ?>
          <a class="bc-a" href="?dir=<?= urlencode($c['path']) ?>"><?= htmlspecialchars($c['label']) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>

    <?php if ($error): ?>
    <div class="err-box">
      <div class="err-ico"><i class="bi bi-exclamation-triangle-fill"></i></div>
      <div>
        <div class="err-ttl">Fehler beim Laden</div>
        <div class="err-msg"><?= $error ?></div>
      </div>
    </div>
    <?php endif; ?>

    <div class="stat-row">
      <span class="sbadge"><i class="bi bi-file-earmark-code"></i>
        <?= count($files) ?> Datei<?= count($files) !== 1 ? 'en' : '' ?>
      </span>
      <?php if ($phpCount):  ?><span class="sbadge"><i class="bi bi-filetype-php" style="color:#6366f1"></i> <?= $phpCount ?> PHP</span><?php endif; ?>
      <?php if ($htmlCount): ?><span class="sbadge"><i class="bi bi-filetype-html" style="color:#10b981"></i> <?= $htmlCount ?> HTML</span><?php endif; ?>
    </div>

    <?php if (empty($files)): ?>
    <div class="fcard">
      <div class="empty">
        <div class="empty-ico"><i class="bi bi-folder-x"></i></div>
        <div class="empty-ttl">Keine Dateien gefunden</div>
        <div class="empty-sub">Dieser Ordner enth&auml;lt keine PHP- oder HTML-Dateien.</div>
      </div>
    </div>
    <?php else: ?>
    <div class="fcard">
      <table class="ftbl">
        <thead><tr><th>Dateiname</th><th class="fp">Pfad</th><th>Aktionen</th></tr></thead>
        <tbody>
        <?php foreach ($files as $file):
            $rel    = str_replace($root . DIRECTORY_SEPARATOR, '', $file);
            $relUrl = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
            $base   = basename($file);
            $isPhp  = (bool) preg_match('/\.php$/i', $base);
            $ico    = $isPhp ? 'bi-filetype-php ep' : 'bi-filetype-html eh';
            $dir    = dirname($rel);
            $dsp    = ($dir === '.' ? '/' : '/' . str_replace(DIRECTORY_SEPARATOR, '/', $dir));
        ?>
        <tr onclick="window.open('<?= htmlspecialchars($relUrl) ?>','_blank')">
          <td><div class="fn"><i class="bi <?= $ico ?>"></i><?= htmlspecialchars($base) ?></div></td>
          <td class="fp"><?= htmlspecialchars($dsp) ?></td>
          <td>
            <div class="fac">
              <a class="btn-o" href="<?= htmlspecialchars($relUrl) ?>" target="_blank" onclick="event.stopPropagation()">
                <i class="bi bi-box-arrow-up-right"></i> &Ouml;ffnen
              </a>
              <button class="btn-s"
                onclick="event.stopPropagation();showSrc('<?= htmlspecialchars(addslashes($relUrl)) ?>','<?= htmlspecialchars(addslashes($base)) ?>')"
                title="Code anzeigen">
                <i class="bi bi-code-slash"></i> Code
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

  </main>
</div>

<div class="ver">v<?= _VER ?></div>

<div class="toast-n" id="toastEl">
  <i class="bi" id="toastIco"></i><span id="toastMsg"></span>
</div>

<!-- Passwort Modal -->
<div class="pw-ov" id="pwOv">
  <div class="pw-box">
    <div class="pw-ico"><i class="bi bi-shield-lock"></i></div>
    <div class="pw-ttl">Admin-Zugang</div>
    <input type="password" id="pwInp" class="pw-inp" placeholder="Passwort" autocomplete="off" spellcheck="false">
    <div class="pw-err" id="pwErr"></div>
    <button class="pw-btn" id="pwBtn">Best&auml;tigen</button>
  </div>
</div>

<!-- Code Modal -->
<div class="cd-ov" id="cdOv" onclick="if(event.target===this)closeCode()">
  <div class="cd-box">
    <div class="cd-head">
      <div class="cd-ttl"><i class="bi bi-code-slash"></i><span id="cdFile">datei.php</span></div>
      <div class="cd-acts">
        <button class="cbtn cbtn-cp" id="cpBtn" onclick="copyCode()"><i class="bi bi-clipboard"></i> Kopieren</button>
        <button class="cbtn cbtn-cl" onclick="closeCode()"><i class="bi bi-x-lg"></i> Schlie&szlig;en</button>
      </div>
    </div>
    <div class="cd-body"><pre><code id="cdEl" class="language-php"></code></pre></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script>
// ── Dark Mode ─────────────────────────────────────────────────────────────────
const _html = document.documentElement;
document.getElementById('darkBtn').addEventListener('click', () => {
  const dark = _html.getAttribute('data-theme') !== 'dark';
  _html.setAttribute('data-theme', dark ? 'dark' : 'light');
  document.cookie = 'dk=' + (dark ? 1 : 0) + ';path=/;max-age=315360000;SameSite=Lax';
});

// ── Sidebar Chevrons ──────────────────────────────────────────────────────────
document.querySelectorAll('.tb').forEach(btn => {
  btn.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); });
  const tgt = document.querySelector(btn.dataset.bsTarget);
  if (!tgt) return;
  tgt.addEventListener('show.bs.collapse', () => btn.querySelector('.ti')?.classList.add('open'));
  tgt.addEventListener('hide.bs.collapse', () => btn.querySelector('.ti')?.classList.remove('open'));
});

// ── Brand Name bearbeiten ─────────────────────────────────────────────────────
(function () {
  const stored = localStorage.getItem('_bn');
  if (stored) {
    document.getElementById('brandNm').textContent = stored;
    document.title = stored + ' · Datei-Explorer';
  }
})();

document.querySelector('.tb-brand').addEventListener('click', e => {
  const tgt = e.target.closest('#brandNm');
  if (!tgt) return;
  const cur = tgt.textContent;
  const inp = document.createElement('input');
  inp.type = 'text'; inp.value = cur;
  inp.className = 'brand-inp'; inp.maxLength = 30;
  tgt.replaceWith(inp); inp.focus(); inp.select();
  let done = false;
  function save() {
    if (done) return; done = true;
    const val = inp.value.trim() || 'GodLe972';
    localStorage.setItem('_bn', val);
    document.title = val + ' · Datei-Explorer';
    const sp = document.createElement('span');
    sp.id = 'brandNm'; sp.className = 'brand-nm';
    sp.title = 'Klicken zum Umbenennen'; sp.textContent = val;
    inp.replaceWith(sp);
  }
  inp.addEventListener('blur', save);
  inp.addEventListener('keydown', e => {
    if (e.key === 'Enter') inp.blur();
    if (e.key === 'Escape') { done = true; inp.value = cur; inp.replaceWith(tgt); }
  });
});

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, icon, dur) {
  document.getElementById('toastIco').className = 'bi ' + (icon || 'bi-info-circle');
  document.getElementById('toastMsg').textContent = msg;
  const t = document.getElementById('toastEl');
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), dur || 3200);
}

// ── Admin Mode ────────────────────────────────────────────────────────────────
// Passwort wird NICHT im Quelltext gespeichert -- Verifikation laeuft server-seitig
const _csrf = '<?= $csrf ?>';
let adminActive = false;
let _token = sessionStorage.getItem('_st') || '';

if (_token) {
  adminActive = true;
  document.getElementById('mainContent')?.classList.add('admin-mode');
}

Object.defineProperty(window, 'admin', {
  get() {
    if (adminActive) { showToast('Admin-Modus bereits aktiv', 'bi-shield-check'); return; }
    document.getElementById('pwOv').classList.add('open');
    setTimeout(() => document.getElementById('pwInp').focus(), 50);
  },
  configurable: true
});

document.getElementById('pwBtn').onclick = checkPw;
document.getElementById('pwInp').addEventListener('keydown', e => {
  if (e.key === 'Enter')  checkPw();
  if (e.key === 'Escape') closePw();
});

async function checkPw() {
  const inp = document.getElementById('pwInp');
  const err = document.getElementById('pwErr');
  err.textContent = '';
  const fd = new FormData();
  fd.append('p', inp.value);
  fd.append('c', _csrf);
  try {
    const res  = await fetch('?_a=1', {method: 'POST', body: fd});
    const data = await res.json();
    if (data.ok) {
      _token = data.t;
      sessionStorage.setItem('_st', _token);
      closePw();
      adminActive = true;
      document.getElementById('mainContent').classList.add('admin-mode');
      showToast('Admin-Modus aktiv', 'bi-shield-lock-fill');
    } else {
      err.textContent = 'Falsches Passwort';
      inp.value = '';
      inp.classList.add('shake');
      setTimeout(() => inp.classList.remove('shake'), 400);
      inp.focus();
    }
  } catch (ex) {
    err.textContent = 'Verbindungsfehler';
  }
}

function closePw() {
  document.getElementById('pwOv').classList.remove('open');
  document.getElementById('pwInp').value = '';
  document.getElementById('pwErr').textContent = '';
  document.getElementById('pwInp').classList.remove('shake');
}

// ── Source Viewer ─────────────────────────────────────────────────────────────
async function showSrc(relPath, filename) {
  document.getElementById('cdFile').textContent = filename;
  const el = document.getElementById('cdEl');
  el.textContent = 'Wird geladen ...';
  document.getElementById('cdOv').classList.add('open');
  try {
    const res = await fetch('?source=' + encodeURIComponent(relPath) + '&_t=' + encodeURIComponent(_token));
    if (!res.ok) throw new Error('HTTP ' + res.status);
    el.textContent = await res.text();
    el.className   = filename.endsWith('.php') ? 'language-php' : 'language-html';
    hljs.highlightElement(el);
  } catch (ex) {
    el.textContent = 'Fehler: ' + ex.message;
    el.className = '';
  }
}
function closeCode() { document.getElementById('cdOv').classList.remove('open'); }
async function copyCode() {
  const btn = document.getElementById('cpBtn');
  try {
    await navigator.clipboard.writeText(document.getElementById('cdEl').textContent);
    btn.innerHTML = '<i class="bi bi-check2"></i> Kopiert!';
    btn.style.cssText = 'background:#166534;color:#bbf7d0';
  } catch {
    btn.innerHTML = '<i class="bi bi-x"></i> Fehler';
    btn.style.cssText = 'background:#7f1d1d;color:#fca5a5';
  }
  setTimeout(() => { btn.innerHTML = '<i class="bi bi-clipboard"></i> Kopieren'; btn.style.cssText = ''; }, 2000);
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeCode(); closePw(); } });

// ── Versionsanzeige: Klick = manuelles Update ─────────────────────────────────
document.querySelector('.ver').addEventListener('click', async () => {
  if (!adminActive) {
    showToast('Admin-Modus noetig (Konsole: admin)', 'bi-lock', 2500);
    return;
  }
  showToast('Suche Update ...', 'bi-cloud-download', 99999);
  const fd = new FormData();
  fd.append('_t', _token);
  try {
    const res  = await fetch('?_upd=1', {method: 'POST', body: fd});
    const data = await res.json();
    const msgs = {
      ok:   ['Update installiert! Wird neu geladen ...', 'bi-check-circle-fill', 4000],
      err:  ['GitHub nicht erreichbar.', 'bi-exclamation-triangle', 3000],
      auth: ['Session abgelaufen. Bitte neu einloggen.', 'bi-lock', 3000],
    };
    const [msg, ico, dur] = msgs[data.s] || ['Unbekannter Fehler', 'bi-x', 3000];
    showToast(msg, ico, dur);
    if (data.s === 'ok') setTimeout(() => location.reload(), 2000);
  } catch {
    showToast('Verbindungsfehler', 'bi-wifi-off', 3000);
  }
});
</script>
</body>
</html>
