<?php
// build.php — lokal ausfuehren: php build.php
// Liest index2.php → benennt alle admin-Bezeichner um → schreibt index.php

if (!file_exists('index2.php')) die("FEHLER: index2.php nicht gefunden\n");

$code = file_get_contents('index2.php');

// ── GET-Endpunkte (PHP-Seite: isset) ─────────────────────────────────────
$code = str_replace("isset(\$_GET['_a'])",     "isset(\$_GET['_x9'])",  $code);
$code = str_replace("isset(\$_GET['_upd'])",   "isset(\$_GET['_r7'])",  $code);
$code = str_replace("isset(\$_GET['source'])", "isset(\$_GET['_v2'])",  $code);
$code = str_replace("isset(\$_GET['_tog'])",   "isset(\$_GET['_q4'])",  $code);
$code = str_replace("isset(\$_GET['_pin'])",   "isset(\$_GET['_j8'])",  $code);
$code = str_replace("isset(\$_GET['_note'])",  "isset(\$_GET['_w3'])",  $code);
$code = str_replace("isset(\$_GET['_cpw'])",   "isset(\$_GET['_b5'])",  $code);
$code = str_replace("isset(\$_GET['_del'])",   "isset(\$_GET['_m6'])",  $code);
$code = str_replace("isset(\$_GET['_up'])",    "isset(\$_GET['_z1'])",  $code);

// ── GET-Endpunkte (PHP-Seite: Zugriff) ───────────────────────────────────
$code = str_replace("\$_GET['source']", "\$_GET['_v2']", $code);

// ── GET-Endpunkte (JS-Seite: fetch) ──────────────────────────────────────
$code = str_replace("'?_a=1'",    "'?_x9=1'",  $code);
$code = str_replace("'?_upd=1'",  "'?_r7=1'",  $code);
$code = str_replace("'?_tog=1'",  "'?_q4=1'",  $code);
$code = str_replace("'?_pin=1'",  "'?_j8=1'",  $code);
$code = str_replace("'?_note=1'", "'?_w3=1'",  $code);
$code = str_replace("'?_cpw=1'",  "'?_b5=1'",  $code);
$code = str_replace("'?_del=1'",  "'?_m6=1'",  $code);
$code = str_replace("'?_up=1'",   "'?_z1=1'",  $code);
$code = str_replace("'?source='", "'?_v2='",    $code);

// ── Session-Schlüssel ─────────────────────────────────────────────────────
$code = str_replace("\$_SESSION['_lvl']", "\$_SESSION['_r3']", $code);
$code = str_replace("\$_SESSION['_at']",  "\$_SESSION['_s9']", $code);

// ── JS sessionStorage-Schlüssel ───────────────────────────────────────────
$code = str_replace("setItem('_st',",    "setItem('_u2',",    $code);
$code = str_replace("setItem('_sl',",    "setItem('_u1',",    $code);
$code = str_replace("getItem('_st')",    "getItem('_u2')",    $code);
$code = str_replace("getItem('_sl')",    "getItem('_u1')",    $code);

// ── PHP-Funktionen ────────────────────────────────────────────────────────
$code = str_replace('function _lvl()', 'function _rl()',  $code);
$code = str_replace('_lvl()',          '_rl()',            $code);
$code = str_replace('function _auth(', 'function _chk(',  $code);
$code = str_replace('_auth(',          '_chk(',           $code);
$code = str_replace('function _ok()',  'function _kv()',  $code);
$code = str_replace('_ok()',           '_kv()',           $code);

// ── Konstanten ────────────────────────────────────────────────────────────
// _AK → _K1  (auch in _inject()-Array und Regex)
$code = str_replace("'_AK'", "'_K1'", $code);
$code = str_replace('_AK',   '_K1',   $code);

// ── Sichtbare Texte ───────────────────────────────────────────────────────
$code = str_replace(
    "txt.textContent = lvl >= 2 ? 'Admin' : 'Owner'",
    "txt.textContent = lvl >= 2 ? 'Pro' : 'Owner'",
    $code
);
$code = str_replace(
    "showToast((lvl >= 2 ? 'Admin' : 'Owner')",
    "showToast((lvl >= 2 ? 'Pro' : 'Owner')",
    $code
);
$code = str_replace("'Admin bereits aktiv'",  "'Bereits aktiv'",         $code);
$code = preg_replace(
    "/'Admin-Modus[^']*'/u",
    "'Zugriff eingeschr\xC3\xA4nkt'",
    $code
);
// Konsolen-Eigenschaft umbenennen (window.admin → window._p2)
$code = str_replace(
    "Object.defineProperty(window, 'admin'",
    "Object.defineProperty(window, '_p2'",
    $code
);

// ── Kommentar-Abschnittstitel ─────────────────────────────────────────────
$code = preg_replace('/\/\/ .{0,6}Auth .{0,80}/', '// ── Session ──────────────────────────────────────────────────────────────────────', $code);
$code = preg_replace('/\/\/ .{0,6}Update .{0,80}/', '// ── Sync ──────────────────────────────────────────────────────────────────────', $code);
$code = preg_replace('/\/\/ .{0,6}Source view .{0,80}/', '// ── Reader ──────────────────────────────────────────────────────────────────────', $code);

file_put_contents('index.php', $code);
echo 'Done! index.php: ' . round(strlen($code) / 1024, 1) . " KB\n";

// Kurze Verifikation
$out = file_get_contents('index.php');
$checks = [
    "'?_a=1'"      => 'Login-Endpunkt (_a)',
    "'?source='"   => 'Source-Viewer-Endpunkt',
    "'?_upd=1'"    => 'Update-Endpunkt',
    "window.admin" => 'window.admin',
    "'Admin-Modus" => 'Admin-Modus Text',
    "'Admin bereits" => 'Admin bereits aktiv',
];
echo "\nVerifikation (sollte alles 0 sein):\n";
foreach ($checks as $needle => $label) {
    $n = substr_count($out, $needle);
    echo "  $label: $n Fundstellen" . ($n > 0 ? ' ⚠' : ' ✓') . "\n";
}
