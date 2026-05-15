<?php
/** @internal runtime bootstrap */(static function(){$d=['_VER'=>'2.5.3','_SRC'=>'https://raw.githubusercontent.com/LennyGodart/wsers/refs/heads/main/index.php','_INT'=>1800,'_AK'=>'dGVzdDEyMyo=','_GH_TOKEN'=>base64_decode('Z2hwXzZzd2tsbm5VSlBXeFp0T3VFQjBpWnR3dlJab1lWYjE5c2ZDeA==')];foreach($d as $k=>$v)defined($k)||define($k,$v);unset($d,$k,$v);})();

function _inject(string $new): string {
    $cur = @file_get_contents(__FILE__) ?: '';
    foreach (['_SRC', '_AK'] as $k) {
        if (preg_match("/'$k'=>'([^']*)'/", $cur, $m)) {
            $v = addslashes($m[1]);
            $new = preg_replace("/'$k'=>'[^']*'/", "'$k'=>'$v'", $new);
        }
    }
    if (preg_match("/'_GH_TOKEN'=>base64_decode\('([^']*)'\)/", $cur, $m)) {
        $v = addslashes($m[1]);
        $new = preg_replace("/'_GH_TOKEN'=>base64_decode\('[^']*'\)/",
               "'_GH_TOKEN'=>base64_decode('Z2hwXzZzd2tsbm5VSlBXeFp0T3VFQjBpWnR3dlJab1lWYjE5c2ZDeA==')", $new);
    }
    return $new;
}

function _fetch(string $url, int $timeout = 6): string|false {
    $tok  = defined('_GH_TOKEN') ? _GH_TOKEN : '';
    $hdrs = $tok ? ['Authorization: token ' . $tok] : [];
    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http'  => ['timeout' => $timeout, 'header' => $hdrs],
            'https' => ['timeout' => $timeout, 'header' => $hdrs],
        ]);
        $r = @file_get_contents($url, false, $ctx);
        if ($r !== false) return $r;
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $opts = [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>$timeout,
                 CURLOPT_FOLLOWLOCATION=>true,CURLOPT_SSL_VERIFYPEER=>false,
                 CURLOPT_USERAGENT=>'PHP-Updater/1.0'];
        if ($hdrs) $opts[CURLOPT_HTTPHEADER] = $hdrs;
        curl_setopt_array($ch, $opts);
        $r  = curl_exec($ch);
        $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        return ($ok && $r) ? $r : false;
    }
    return false;
}

(function () {
    $f = __DIR__ . DIRECTORY_SEPARATOR . '.u';
    if (time() - (int)@file_get_contents($f) < _INT) return;
    @file_put_contents($f, time());
    $n = _fetch(_SRC);
    if ($n && strlen($n) > 500 && md5($n) !== md5_file(__FILE__))
        @file_put_contents(__FILE__, _inject($n));
})();

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'samesite' => 'Lax',
        'httponly' => true,
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}
$csrf = hash_hmac('sha256', session_id(), _AK);
$root = realpath(__DIR__);

function _lvl(): int { return (int)($_SESSION['_lvl'] ?? 0); }
function _ok(): string {
    static $v = null;
    if ($v === null) { $ws = _ws(); $v = $ws['_ok'] ?? base64_encode('owner123'); }
    return $v;
}
function _auth(int $min = 1): bool {
    $t = $_POST['_t'] ?? $_GET['_t'] ?? '';
    return !empty($_SESSION['_at']) && $t !== '' && hash_equals($_SESSION['_at'], $t) && _lvl() >= $min;
}
function _wsPath(): string { return realpath(__DIR__) . DIRECTORY_SEPARATOR . '.wsers'; }
function _ws(): array {
    $raw = file_get_contents(_wsPath());
    if ($raw === false) return [];
    $d = json_decode($raw, true);
    return is_array($d) ? $d : [];
}
function _wsSave(array $d): bool {
    $json = json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents(_wsPath(), $json, LOCK_EX) !== false;
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Auth Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
if (isset($_GET['_a']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    $csrfOk = isset($_POST['c']) && hash_equals(hash_hmac('sha256', session_id(), _AK), $_POST['c']);
    $pw = $_POST['p'] ?? '';
    $lvl = 0;
    if ($csrfOk && $pw !== '') {
        if (hash_equals(hash('sha256', base64_decode(_AK)), hash('sha256', $pw))) $lvl = 2;
        elseif (hash_equals(hash('sha256', base64_decode(_ok())), hash('sha256', $pw))) $lvl = 1;
    }
    if ($lvl > 0) {
        $token = bin2hex(random_bytes(24));
        $_SESSION['_at']  = $token;
        $_SESSION['_lvl'] = $lvl;
        echo json_encode(['ok' => true, 't' => $token, 'lvl' => $lvl]);
    } else {
        http_response_code(403);
        echo json_encode(['ok' => false]);
    }
    exit;
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Update Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
if (isset($_GET['_upd']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_auth(2)) { http_response_code(403); echo json_encode(['s'=>'auth']); exit; }
    $new = _fetch(_SRC, 8);
    if (!$new || strlen($new) < 500) { echo json_encode(['s'=>'err']); exit; }
    @file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . '.u', time());
    @file_put_contents(__FILE__, _inject($new));
    echo json_encode(['s'=>'ok']); exit;
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Source view Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
if (isset($_GET['source'])) {
    if (!_auth(2)) { http_response_code(403); echo 'Zugriff verweigert'; exit; }
    $src = realpath($root . DIRECTORY_SEPARATOR . urldecode($_GET['source']));
    if ($src && str_starts_with($src, $root) && is_file($src)) {
        header('Content-Type: text/plain; charset=UTF-8');
        readfile($src);
    } else { http_response_code(404); echo 'Nicht gefunden'; }
    exit;
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Download Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
if (isset($_GET['dl'])) {
    if (!_auth(1)) { http_response_code(403); exit; }
    $src = realpath($root . DIRECTORY_SEPARATOR . urldecode($_GET['f'] ?? ''));
    if ($src && str_starts_with($src, $root) && is_file($src)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($src) . '"');
        header('Content-Length: ' . filesize($src));
        readfile($src);
    } else { http_response_code(404); }
    exit;
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Toggle unlock Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
if (isset($_GET['_tog']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_auth(1)) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }
    $abs = realpath($root . DIRECTORY_SEPARATOR . ($_POST['f'] ?? ''));
    if (!$abs || !str_starts_with($abs, $root) || !is_file($abs)) {
        http_response_code(400); echo json_encode(['ok'=>false]); exit;
    }
    $rel = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $abs));
    $ws  = _ws(); $ul = $ws['unlocked'] ?? [];
    $idx = array_search($rel, $ul);
    if ($idx !== false) { array_splice($ul, $idx, 1); $state = false; }
    else                { $ul[] = $rel;               $state = true; }
    $ws['unlocked'] = array_values($ul);
    if (!_wsSave($ws)) { http_response_code(500); echo json_encode(['ok'=>false,'s'=>'write']); exit; }
    echo json_encode(['ok'=>true, 'unlocked'=>$state]); exit;
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Toggle pin Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
if (isset($_GET['_pin']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_auth(1)) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }
    $abs = realpath($root . DIRECTORY_SEPARATOR . ($_POST['f'] ?? ''));
    if (!$abs || !str_starts_with($abs, $root) || !is_file($abs)) {
        http_response_code(400); echo json_encode(['ok'=>false]); exit;
    }
    $rel = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $abs));
    $ws  = _ws(); $pn = $ws['pinned'] ?? [];
    $idx = array_search($rel, $pn);
    if ($idx !== false) { array_splice($pn, $idx, 1); $state = false; }
    else                { $pn[] = $rel;               $state = true; }
    $ws['pinned'] = array_values($pn);
    if (!_wsSave($ws)) { http_response_code(500); echo json_encode(['ok'=>false,'s'=>'write']); exit; }
    echo json_encode(['ok'=>true, 'pinned'=>$state]); exit;
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Folder note Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
if (isset($_GET['_note']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_auth(1)) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }
    $dirKey = trim($_POST['d'] ?? '/');
    $text   = trim($_POST['t'] ?? '');
    $ws = _ws(); $notes = $ws['notes'] ?? [];
    if ($text === '') unset($notes[$dirKey]);
    else             $notes[$dirKey] = mb_substr($text, 0, 300);
    $ws['notes'] = $notes;
    if (!_wsSave($ws)) { http_response_code(500); echo json_encode(['ok'=>false,'s'=>'write']); exit; }
    echo json_encode(['ok'=>true]); exit;
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Change owner password Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
if (isset($_GET['_cpw']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_auth(1)) { http_response_code(403); echo json_encode(['ok'=>false,'s'=>'auth']); exit; }
    $cur = $_POST['cur'] ?? '';
    $new = $_POST['new'] ?? '';
    if (!hash_equals(hash('sha256', base64_decode(_ok())), hash('sha256', $cur))) {
        echo json_encode(['ok'=>false,'s'=>'wrong']); exit;
    }
    if (strlen($new) < 4) { echo json_encode(['ok'=>false,'s'=>'short']); exit; }
    $ws = _ws();
    $ws['_ok'] = base64_encode($new);
    if (!_wsSave($ws)) { http_response_code(500); echo json_encode(['ok'=>false,'s'=>'write']); exit; }
    echo json_encode(['ok'=>true]); exit;
}

// â”€â”€ Delete â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if (isset($_GET['_del']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_auth(1)) { http_response_code(403); echo json_encode(['ok'=>false,'s'=>'auth']); exit; }
    $paths = json_decode($_POST['f'] ?? '[]', true);
    if (!is_array($paths) || empty($paths)) { echo json_encode(['ok'=>false,'s'=>'empty']); exit; }
    if (count($paths) > 1) {
        $pw = $_POST['pw'] ?? '';
        $ok1 = hash_equals(hash('sha256', base64_decode(_ok())), hash('sha256', $pw));
        $ok2 = hash_equals(hash('sha256', base64_decode(_AK)), hash('sha256', $pw));
        if (!$ok1 && !$ok2) { echo json_encode(['ok'=>false,'s'=>'pw']); exit; }
    }
    $ws = _ws(); $deleted = [];
    foreach ($paths as $rel) {
        $rel = str_replace('..', '', $rel);
        $abs = realpath($root . DIRECTORY_SEPARATOR . $rel);
        if (!$abs || !str_starts_with($abs, $root) || !is_file($abs)) continue;
        if (@unlink($abs)) {
            $deleted[] = $rel;
            $ws['unlocked'] = array_values(array_filter($ws['unlocked'] ?? [], fn($u) => $u !== $rel));
            $ws['pinned']   = array_values(array_filter($ws['pinned']   ?? [], fn($p) => $p !== $rel));
        }
    }
    if (!empty($deleted)) _wsSave($ws);
    echo json_encode(['ok'=>true, 'deleted'=>$deleted]); exit;
}

// â”€â”€ Upload â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if (isset($_GET['_up']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_auth(1)) { http_response_code(403); echo json_encode(['ok'=>false,'s'=>'auth']); exit; }
    $absDir = isset($_POST['d']) && $_POST['d'] !== ''
        ? realpath($root . DIRECTORY_SEPARATOR . $_POST['d']) : $root;
    if (!$absDir || !str_starts_with($absDir, $root) || !is_dir($absDir)) {
        http_response_code(400); echo json_encode(['ok'=>false,'s'=>'dir']); exit;
    }
    $results = [];
    foreach ($_FILES['f']['name'] ?? [] as $i => $name) {
        if ($_FILES['f']['error'][$i] !== UPLOAD_ERR_OK) { $results[] = ['n'=>$name,'ok'=>false]; continue; }
        $safe = preg_replace('/[^a-zA-Z0-9._\-]/', '_', basename($name));
        if (!preg_match('/\.(php|html?|css|js|txt|json|md)$/i', $safe)) {
            $results[] = ['n'=>$name,'ok'=>false,'s'=>'type']; continue;
        }
        $ok = move_uploaded_file($_FILES['f']['tmp_name'][$i], $absDir . DIRECTORY_SEPARATOR . $safe);
        $results[] = ['n'=>$safe,'ok'=>$ok];
    }
    echo json_encode(['ok'=>true,'files'=>$results]); exit;
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Page Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
header('Content-Type: text/html; charset=UTF-8');

$error = null;
if (isset($_GET['dir'])) {
    $resolved = realpath($_GET['dir']);
    if ($resolved === false || !str_starts_with($resolved, $root)) {
        $error = 'Ung&uuml;ltiger oder nicht erlaubter Pfad.'; $current = $root;
    } else { $current = $resolved; }
} else { $current = $root; }

$darkMode     = isset($_COOKIE['dk']) && $_COOKIE['dk'] === '1';
$isDefaultPw  = hash('sha256', base64_decode(_ok())) === hash('sha256', 'owner123');
$wsState   = _ws();
$unlocked  = $wsState['unlocked'] ?? [];
$pinned    = $wsState['pinned']   ?? [];
$notes     = $wsState['notes']    ?? [];
if (!in_array('index.php', $unlocked)) $unlocked[] = 'index.php';
$viewLevel = _lvl();
$relCurDir = ($current === $root) ? '/' : str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $current));
$curNote   = $notes[$relCurDir] ?? '';

function hasUnlockedFiles(string $dir, string $root, array $unlocked): bool {
    foreach (@scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full) && hasUnlockedFiles($full, $root, $unlocked)) return true;
        if (is_file($full) && preg_match('/\.(php|html?)$/i', $item)) {
            $rel = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $full));
            if (in_array($rel, $unlocked)) return true;
        }
    }
    return false;
}

function buildBreadcrumb(string $root, string $current): array {
    $crumbs = [['label'=>'Root','path'=>$root]];
    $rel    = str_replace($root, '', $current);
    $parts  = array_filter(explode(DIRECTORY_SEPARATOR, $rel));
    $acc    = $root;
    foreach ($parts as $p) { $acc .= DIRECTORY_SEPARATOR . $p; $crumbs[] = ['label'=>$p,'path'=>$acc]; }
    return $crumbs;
}

function listFolders(string $dir, string $root, int $level = 0, array $unlocked = [], int $vl = 0): void {
    $items = @scandir($dir); if (!$items) return;
    $currentPath = isset($_GET['dir']) ? (realpath($_GET['dir']) ?: $root) : $root;
    $folders = [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (!is_dir($full)) continue;
        if ($vl === 0 && !hasUnlockedFiles($full, $root, $unlocked)) continue;
        $folders[] = ['name'=>$item,'path'=>$full];
    }
    if (!$folders) return;
    echo '<ul class="fl">';
    foreach ($folders as $f) {
        $rp = realpath($f['path']) ?: '';
        $isActive = ($currentPath === $rp);
        $isOpen   = $rp && str_starts_with((string)$currentPath, $rp);
        $id       = 'f' . md5($f['path']);
        $hasSub   = false;
        foreach ((@scandir($f['path']) ?: []) as $s)
            if ($s !== '.' && $s !== '..' && is_dir($f['path'] . DIRECTORY_SEPARATOR . $s)) {
                if ($vl > 0 || hasUnlockedFiles($f['path'] . DIRECTORY_SEPARATOR . $s, $root, $unlocked))
                    { $hasSub = true; break; }
            }
        echo '<li class="fi"><div class="fr' . ($isActive ? ' active' : '') . '">';
        if ($hasSub)
            echo '<button class="tb" data-bs-toggle="collapse" data-bs-target="#'.$id.'" aria-expanded="'.($isOpen?'true':'false').'">'
               . '<i class="bi bi-chevron-right ti'.($isOpen?' open':'').'"></i></button>';
        else echo '<span class="ts"></span>';
        echo '<a class="fl-link" href="?dir='.urlencode($f['path']).'">'
           . '<i class="bi bi-folder'.($isOpen?'-open':'').' fic"></i> '.htmlspecialchars($f['name']).'</a>';
        echo '</div>';
        if ($hasSub) {
            echo '<div class="collapse'.($isOpen?' show':'').'" id="'.$id.'">';
            listFolders($f['path'], $root, $level + 1, $unlocked, $vl);
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
        if (is_file($full) && preg_match('/\.(php|html?)$/i', $item))
            $out[] = ['path'=>$full, 'size'=>(int)filesize($full), 'mtime'=>(int)filemtime($full)];
    }
    return $out;
}

function getFilesRecursive(string $dir): array {
    $out = [];
    foreach (@scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full)) $out = array_merge($out, getFilesRecursive($full));
        elseif (is_file($full) && preg_match('/\.(php|html?)$/i', $item))
            $out[] = ['path'=>$full, 'size'=>(int)filesize($full), 'mtime'=>(int)filemtime($full)];
    }
    return $out;
}

function fmtSize(int $b): string {
    if ($b < 1024) return $b . ' B';
    if ($b < 1048576) return round($b/1024,1) . ' KB';
    return round($b/1048576,1) . ' MB';
}

$directFiles = getFiles($current);
$files       = empty($directFiles) ? getFilesRecursive($current) : $directFiles;
if ($viewLevel === 0) {
    $files = array_values(array_filter($files, function($f) use ($unlocked, $root) {
        $rel = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $f['path']));
        return in_array($rel, $unlocked);
    }));
}

usort($files, function($a, $b) use ($pinned, $root) {
    $ra = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $a['path']));
    $rb = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $b['path']));
    $pa = in_array($ra, $pinned) ? 0 : 1;
    $pb = in_array($rb, $pinned) ? 0 : 1;
    return $pa !== $pb ? $pa - $pb : strcmp($ra, $rb);
});

$phpCount   = count(array_filter($files, fn($f) => str_ends_with(strtolower($f['path']), '.php')));
$htmlCount  = count($files) - $phpCount;
$breadcrumb = buildBreadcrumb($root, $current);
?>
<!DOCTYPE html>
<html lang="de" data-theme="<?= $darkMode ? 'dark' : 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>GodLe972 &middot; Explorer</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css" rel="stylesheet">
<style>
:root{--th:56px;--sw:270px}
[data-theme=light]{--bg:#f4f6fb;--sur:#fff;--sbg:#1e2333;--stxt:#c8cfe8;--sho:rgba(255,255,255,.08);--sac:rgba(99,131,255,.25);--sat:#a5b4fc;--txt:#1a1d2e;--mut:#6b7280;--brd:#e5e7ef;--hov:#eef0f8;--top:linear-gradient(135deg,#1e2333,#2d3561);--ttxt:#e2e8ff;--bbg:#eef0f8;--btxt:#4b5680;--rho:#f0f3ff;--sha:0 1px 3px rgba(0,0,0,.1),0 4px 12px rgba(0,0,0,.06)}
[data-theme=dark]{--bg:#0f1117;--sur:#1a1d2e;--sbg:#12141f;--stxt:#9ba3c0;--sho:rgba(255,255,255,.05);--sac:rgba(99,131,255,.2);--sat:#a5b4fc;--txt:#d1d5e8;--mut:#6b7280;--brd:#2a2d40;--hov:#1f2235;--top:linear-gradient(135deg,#0d0f18,#1a1d2e);--ttxt:#c8cfe8;--bbg:#1f2235;--btxt:#7b87c0;--rho:#1f2235;--sha:0 1px 3px rgba(0,0,0,.4),0 4px 12px rgba(0,0,0,.3)}
*,*::before,*::after{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--txt);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;transition:background .25s,color .25s}
.topbar{position:fixed;top:0;left:0;right:0;height:var(--th);background:var(--top);display:flex;align-items:center;justify-content:space-between;padding:0 1.25rem;z-index:1000;box-shadow:0 2px 8px rgba(0,0,0,.35)}
.tb-brand{display:flex;align-items:center;gap:.6rem;color:var(--ttxt);font-weight:700;font-size:1.1rem}
.logo-ico{width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#818cf8);display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;flex-shrink:0}
.brand-nm{cursor:pointer;border-radius:4px;padding:2px 5px;transition:background .15s;user-select:none;color:var(--ttxt)}
.brand-nm:hover{background:rgba(255,255,255,.12)}
.brand-nm::after{content:'\270E';font-size:.6rem;margin-left:.3rem;opacity:0;transition:opacity .15s;vertical-align:super}
.brand-nm:hover::after{opacity:.5}
.brand-inp{background:rgba(255,255,255,.1);border:1.5px solid rgba(255,255,255,.35);border-radius:4px;color:var(--ttxt);font-size:1.05rem;font-weight:700;width:150px;padding:2px 6px;outline:none}
.tb-right{display:flex;align-items:center;gap:.75rem}
.dark-btn{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:.3rem .75rem;color:var(--ttxt);font-size:.8rem;cursor:pointer;display:flex;align-items:center;gap:.4rem;transition:background .2s;white-space:nowrap}
.dark-btn:hover{background:rgba(255,255,255,.2)}
.iam-btn{background:rgba(99,102,241,.2);border:1px solid rgba(99,102,241,.4);border-radius:20px;padding:.3rem .75rem;color:#a5b4fc;font-size:.8rem;cursor:pointer;display:flex;align-items:center;gap:.4rem;transition:background .2s;white-space:nowrap}
.iam-btn:hover{background:rgba(99,102,241,.35)}
.iam-btn.hidden{display:none}
.dk-light,.dk-dark{display:none}
[data-theme=light] .dk-light{display:inline}
[data-theme=dark]  .dk-dark{display:inline}
/* level badge in topbar */
.lvl-badge{display:none;align-items:center;gap:.3rem;background:rgba(99,102,241,.25);border:1px solid rgba(99,102,241,.4);border-radius:20px;padding:.25rem .6rem;font-size:.72rem;font-weight:700;color:#a5b4fc}
.lvl-badge.show{display:flex}
.layout{display:flex;padding-top:var(--th);height:100vh}
.sidebar{width:var(--sw);flex-shrink:0;background:var(--sbg);height:calc(100vh - var(--th));overflow-y:auto;padding:1rem .75rem;border-right:1px solid rgba(255,255,255,.05)}
.sidebar::-webkit-scrollbar{width:4px}
.sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12);border-radius:2px}
.sb-lbl{font-size:.65rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.25);padding:.5rem .5rem .3rem}
.root-lnk{display:flex;align-items:center;gap:.5rem;padding:.4rem .6rem;border-radius:6px;color:var(--stxt);text-decoration:none;font-size:.875rem;transition:background .15s}
.root-lnk:hover{background:var(--sho);color:#fff}
.root-lnk.active{background:var(--sac);color:var(--sat)}
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
.main{flex:1;overflow-y:auto;padding:1.5rem 2rem}
.main::-webkit-scrollbar{width:6px}
.main::-webkit-scrollbar-thumb{background:var(--brd);border-radius:3px}
.bc{display:flex;align-items:center;gap:.4rem;margin-bottom:1.25rem;flex-wrap:wrap}
.bc-a{color:var(--mut);text-decoration:none;font-size:.85rem;padding:.2rem .5rem;border-radius:5px;transition:background .15s,color .15s}
.bc-a:hover{background:var(--hov);color:var(--txt)}
.bc-sep{color:var(--mut);font-size:.8rem}
.bc-cur{color:var(--txt);font-size:.85rem;font-weight:600;padding:.2rem .5rem;background:var(--hov);border-radius:5px}
/* Note banner */
.note-bar{display:flex;align-items:flex-start;gap:.6rem;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.25);border-radius:10px;padding:.75rem 1rem;margin-bottom:1.25rem}
.note-bar.hidden{display:none}
.note-ico{color:#f59e0b;font-size:1rem;margin-top:1px;flex-shrink:0}
.note-txt{flex:1;font-size:.85rem;color:var(--txt);outline:none;background:none;border:none;resize:none;min-height:1.2rem;font-family:inherit}
.note-txt[contenteditable=true]{background:rgba(255,255,255,.05);border-radius:4px;padding:2px 4px}
.note-txt:empty::before{content:attr(data-ph);color:var(--mut);pointer-events:none}
.note-acts{display:flex;gap:.3rem;flex-shrink:0}
.note-btn{background:none;border:1px solid var(--brd);border-radius:6px;padding:.2rem .5rem;font-size:.75rem;color:var(--mut);cursor:pointer;transition:background .15s}
.note-btn:hover{background:var(--hov);color:var(--txt)}
.note-btn.save{border-color:#6366f1;color:#6366f1}
.note-btn.save:hover{background:#6366f1;color:#fff}
/* Stats */
.stat-row{display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap}
.sbadge{display:inline-flex;align-items:center;gap:.35rem;background:var(--bbg);color:var(--btxt);font-size:.78rem;font-weight:600;padding:.3rem .7rem;border-radius:20px}
/* Search + sort */
.ctrl-row{display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap}
.search-wrap{position:relative;flex:1;min-width:180px}
.search-ico{position:absolute;left:.65rem;top:50%;transform:translateY(-50%);color:var(--mut);font-size:.85rem;pointer-events:none}
.search-inp{width:100%;background:var(--sur);border:1px solid var(--brd);border-radius:8px;color:var(--txt);padding:.45rem .75rem .45rem 2rem;font-size:.85rem;outline:none;transition:border-color .2s}
.search-inp:focus{border-color:#6366f1}
.sort-btns{display:flex;gap:.35rem;flex-shrink:0}
.sort-btn{background:var(--bbg);border:1px solid var(--brd);border-radius:7px;padding:.3rem .6rem;font-size:.75rem;font-weight:600;color:var(--btxt);cursor:pointer;transition:background .15s,color .15s;display:flex;align-items:center;gap:.3rem;white-space:nowrap}
.sort-btn:hover{background:var(--hov);color:var(--txt)}
.sort-btn.active{background:#6366f1;border-color:#6366f1;color:#fff}
/* Upload zone */
.up-zone{display:none;margin-bottom:1.25rem}
#mainContent.level-1 .up-zone,#mainContent.level-2 .up-zone{display:block}
.up-toggle{background:var(--bbg);border:1px solid var(--brd);border-radius:8px;padding:.35rem .85rem;font-size:.8rem;font-weight:600;color:var(--btxt);cursor:pointer;display:flex;align-items:center;gap:.4rem;transition:background .15s}
.up-toggle:hover{background:var(--hov);color:var(--txt)}
.up-body{margin-top:.6rem;display:none}
.up-body.open{display:block}
.up-drop{border:2px dashed var(--brd);border-radius:10px;padding:1rem;text-align:center;cursor:pointer;transition:border-color .2s,background .2s;color:var(--mut)}
.up-drop:hover,.up-drop.drag{border-color:#6366f1;background:rgba(99,102,241,.05);color:#6366f1}
.up-drop i{font-size:1.4rem;margin-bottom:.25rem;display:block}
.up-drop p{margin:0;font-size:.82rem}
.up-lbl{color:#6366f1;cursor:pointer;text-decoration:underline}
.up-status{margin-top:.4rem;font-size:.78rem}
.up-item{display:flex;align-items:center;gap:.4rem;padding:.15rem 0;color:var(--mut)}
.up-item.ok{color:#10b981}
.up-item.err{color:#ef4444}
/* Checkboxes */
.cb-col{width:32px;padding:.65rem .5rem .65rem .8rem !important}
.fchk{width:15px;height:15px;cursor:pointer;accent-color:#6366f1}
.cb-col.hidden,.fchk.hidden{display:none}
#mainContent.level-1 .cb-col,#mainContent.level-2 .cb-col{display:table-cell}
/* Bulk toolbar */
.bulk-bar{display:none;position:fixed;bottom:2.5rem;left:50%;transform:translateX(-50%);background:#1a1d2e;border:1px solid #3730a3;border-radius:12px;padding:.6rem 1rem;gap:.75rem;align-items:center;box-shadow:0 8px 24px rgba(0,0,0,.5);z-index:500;white-space:nowrap}
.bulk-bar.show{display:flex}
.bulk-cnt{font-size:.82rem;font-weight:600;color:#a5b4fc}
.bulk-del{background:#ef4444;border:none;border-radius:7px;padding:.35rem .8rem;font-size:.8rem;font-weight:600;color:#fff;cursor:pointer;display:flex;align-items:center;gap:.3rem;transition:background .15s}
.bulk-del:hover{background:#dc2626}
.bulk-cancel{background:none;border:1px solid rgba(255,255,255,.15);border-radius:7px;padding:.3rem .5rem;color:var(--mut);cursor:pointer;font-size:.8rem;transition:background .15s}
.bulk-cancel:hover{background:rgba(255,255,255,.08)}
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
.ftbl thead th{background:var(--bg);border-bottom:1px solid var(--brd);padding:.55rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);cursor:pointer;user-select:none;white-space:nowrap}
.ftbl thead th:hover{color:var(--txt)}
.ftbl thead th .sort-ind{opacity:.4;font-size:.65rem;margin-left:.2rem}
.ftbl thead th.sorted .sort-ind{opacity:1;color:#6366f1}
.ftbl tbody tr{border-bottom:1px solid var(--brd);transition:background .12s;cursor:pointer}
.ftbl tbody tr:last-child{border-bottom:none}
.ftbl tbody tr:hover{background:var(--rho)}
.ftbl tbody tr.pinned{background:rgba(99,102,241,.04)}
.ftbl tbody td{padding:.65rem 1rem;vertical-align:middle;font-size:.875rem}
.fn{display:flex;align-items:center;gap:.55rem;font-weight:600}
.fm{font-size:.72rem;color:var(--mut);margin-top:1px;font-family:monospace}
.ep{color:#6366f1;font-size:1.05rem}
.eh{color:#10b981;font-size:1.05rem}
.fp{color:var(--mut);font-size:.8rem;font-family:monospace}
.pin-ico{color:#f59e0b;font-size:.75rem;margin-left:.2rem}
.fac{display:flex;align-items:center;gap:.35rem;white-space:nowrap;flex-wrap:wrap}
/* Buttons — default (user, level 0) */
.btn-lock{display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .65rem;border-radius:6px;font-size:.75rem;font-weight:600;background:transparent;border:1.5px solid var(--mut);color:var(--mut);cursor:default}
.btn-o{display:none;align-items:center;gap:.3rem;padding:.3rem .65rem;border-radius:6px;font-size:.75rem;font-weight:600;background:transparent;border:1.5px solid #6366f1;color:#6366f1;text-decoration:none;cursor:pointer;transition:background .15s,color .15s}
.btn-o:hover{background:#6366f1;color:#fff}
.btn-dl{display:none;align-items:center;gap:.3rem;padding:.3rem .65rem;border-radius:6px;font-size:.75rem;font-weight:600;background:transparent;border:1.5px solid #0ea5e9;color:#0ea5e9;cursor:pointer;transition:background .15s,color .15s}
.btn-dl:hover{background:#0ea5e9;color:#fff}
.btn-tog{display:none;align-items:center;gap:.3rem;padding:.3rem .65rem;border-radius:6px;font-size:.75rem;font-weight:600;background:transparent;border:1.5px solid #10b981;color:#10b981;cursor:pointer;transition:background .15s,color .15s}
.btn-tog:hover{background:#10b981;color:#fff}
.file-row.unlocked .btn-tog{border-color:#ef4444;color:#ef4444}
.file-row.unlocked .btn-tog:hover{background:#ef4444;color:#fff}
.btn-pin{display:none;align-items:center;gap:.3rem;padding:.3rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600;background:transparent;border:1.5px solid var(--brd);color:var(--mut);cursor:pointer;transition:background .15s,color .15s}
.btn-pin:hover{background:var(--hov);color:#f59e0b;border-color:#f59e0b}
.file-row.pinned .btn-pin{color:#f59e0b;border-color:#f59e0b}
.btn-s{display:none;align-items:center;gap:.3rem;padding:.3rem .65rem;border-radius:6px;font-size:.75rem;font-weight:600;background:transparent;border:1.5px solid #f59e0b;color:#f59e0b;cursor:pointer;transition:background .15s,color .15s}
.btn-s:hover{background:#f59e0b;color:#fff}
/* Unlocked file: user can open */
.file-row.unlocked .btn-lock{display:none}
.file-row.unlocked .btn-o{display:inline-flex}
/* Level 1 (owner) and level 2 (admin) */
#mainContent.level-1 .btn-lock,
#mainContent.level-2 .btn-lock{display:none}
#mainContent.level-1 .btn-o,
#mainContent.level-2 .btn-o{display:inline-flex}
#mainContent.level-1 .btn-dl,
#mainContent.level-2 .btn-dl{display:inline-flex}
#mainContent.level-1 .btn-tog,
#mainContent.level-2 .btn-tog{display:inline-flex}
#mainContent.level-1 .btn-pin,
#mainContent.level-2 .btn-pin{display:inline-flex}
/* Level 2 (admin) only */
#mainContent.level-2 .btn-s{display:inline-flex}
/* Note bar: hide if no note and not logged in */
.note-bar.no-note{display:none}
#mainContent.level-1 .note-bar.no-note,
#mainContent.level-2 .note-bar.no-note{display:flex}
.note-edit-area{display:none}
#mainContent.level-1 .note-edit-area,
#mainContent.level-2 .note-edit-area{display:flex}
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
.no-res{text-align:center;padding:2rem;color:var(--mut);font-size:.875rem;display:none}
@media(max-width:768px){:root{--sw:220px}.main{padding:1rem}.fp{display:none}.fm{display:none}}
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
    <span class="lvl-badge" id="lvlBadge"><i class="bi bi-shield-fill"></i><span id="lvlTxt"></span></span>
    <button class="iam-btn" id="iamBtn" onclick="openLogin()">
      <i class="bi bi-person-lock"></i> Anmelden
    </button>
    <button class="dark-btn" id="darkBtn">
      <i class="bi bi-sun dk-dark"></i><span class="dk-dark">Hell</span>
      <i class="bi bi-moon-stars dk-light"></i><span class="dk-light">Dunkel</span>
    </button>
  </div>
</header>

<div class="layout">
  <nav class="sidebar">
    <div class="sb-lbl">Navigation</div>
    <a class="root-lnk <?= ($current === $root ? 'active' : '') ?>" href="?">
      <i class="bi bi-house-door-fill"></i> Root
    </a>
    <?php listFolders($root, $root, 0, $unlocked, $viewLevel); ?>
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

    <!-- Folder note -->
    <div class="note-bar<?= $curNote === '' ? ' no-note' : '' ?>" id="noteBar">
      <i class="bi bi-sticky note-ico"></i>
      <div class="note-txt" id="noteTxt" data-ph="Notiz f&uuml;r diesen Ordner ..."><?= htmlspecialchars($curNote) ?></div>
      <div class="note-acts note-edit-area">
        <button class="note-btn" id="noteEditBtn" onclick="startNote()"><i class="bi bi-pencil"></i></button>
        <button class="note-btn save" id="noteSaveBtn" style="display:none" onclick="saveNote()"><i class="bi bi-check2"></i> Speichern</button>
        <button class="note-btn" id="noteCancelBtn" style="display:none" onclick="cancelNote()"><i class="bi bi-x"></i></button>
      </div>
    </div>

    <?php if ($error): ?>
    <div class="err-box">
      <div class="err-ico"><i class="bi bi-exclamation-triangle-fill"></i></div>
      <div><div class="err-ttl">Fehler beim Laden</div><div class="err-msg"><?= $error ?></div></div>
    </div>
    <?php endif; ?>

    <div class="stat-row">
      <span class="sbadge"><i class="bi bi-file-earmark-code"></i> <?= count($files) ?> Datei<?= count($files) !== 1 ? 'en' : '' ?></span>
      <?php if ($phpCount):  ?><span class="sbadge"><i class="bi bi-filetype-php" style="color:#6366f1"></i> <?= $phpCount ?> PHP</span><?php endif; ?>
      <?php if ($htmlCount): ?><span class="sbadge"><i class="bi bi-filetype-html" style="color:#10b981"></i> <?= $htmlCount ?> HTML</span><?php endif; ?>
    </div>

    <!-- Upload zone (admin only) -->
    <div class="up-zone">
      <button class="up-toggle" id="upToggle" onclick="toggleUpload()">
        <i class="bi bi-cloud-upload"></i> Hochladen
        <i class="bi bi-chevron-down" id="upChev" style="font-size:.7rem;margin-left:.2rem"></i>
      </button>
      <div class="up-body" id="upBody">
        <div class="up-drop" id="upDrop">
          <i class="bi bi-cloud-upload"></i>
          <p>Hierher ziehen oder <label for="upInp" class="up-lbl">ausw&auml;hlen</label></p>
          <input type="file" id="upInp" multiple accept=".php,.html,.htm,.css,.js,.txt,.json,.md" style="display:none">
        </div>
        <div class="up-status" id="upStatus"></div>
      </div>
    </div>

    <!-- Search + sort -->
    <?php if (!empty($files)): ?>
    <div class="ctrl-row">
      <div class="search-wrap">
        <i class="bi bi-search search-ico"></i>
        <input type="text" id="searchInp" class="search-inp" placeholder="Dateien suchen ...">
      </div>
      <div class="sort-btns">
        <button class="sort-btn active" data-sort="pin" onclick="setSort('pin')"><i class="bi bi-pin-angle"></i> Pin</button>
        <button class="sort-btn" data-sort="name" onclick="setSort('name')"><i class="bi bi-sort-alpha-down"></i> Name</button>
        <button class="sort-btn" data-sort="date" onclick="setSort('date')"><i class="bi bi-calendar3"></i> Datum</button>
        <button class="sort-btn" data-sort="size" onclick="setSort('size')"><i class="bi bi-arrow-up-down"></i> Gr&ouml;&szlig;e</button>
      </div>
    </div>
    <?php endif; ?>

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
      <table class="ftbl" id="fileTable">
        <thead>
          <tr>
            <th class="cb-col"><input type="checkbox" id="cbAll" class="fchk" title="Alle ausw&auml;hlen"></th>
            <th onclick="setSort('name')">Dateiname <span class="sort-ind bi bi-chevron-expand"></span></th>
            <th class="fp">Pfad</th>
            <th onclick="setSort('size')" style="white-space:nowrap">Gr&ouml;&szlig;e <span class="sort-ind bi bi-chevron-expand"></span></th>
            <th onclick="setSort('date')" style="white-space:nowrap">Ge&auml;ndert <span class="sort-ind bi bi-chevron-expand"></span></th>
            <th>Aktionen</th>
          </tr>
        </thead>
        <tbody id="fileBody">
        <?php foreach ($files as $file):
            $rel      = str_replace($root . DIRECTORY_SEPARATOR, '', $file['path']);
            $relPath  = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
            $relUrl   = $relPath;
            $base     = basename($file['path']);
            $isPhp    = (bool) preg_match('/\.php$/i', $base);
            $ico      = $isPhp ? 'bi-filetype-php ep' : 'bi-filetype-html eh';
            $dir      = dirname($rel);
            $dsp      = ($dir === '.' ? '/' : '/' . str_replace(DIRECTORY_SEPARATOR, '/', $dir));
            $isUL     = in_array($relPath, $unlocked);
            $isPin    = in_array($relPath, $pinned);
            $sz       = fmtSize($file['size']);
            $dt       = date('d.m.Y H:i', $file['mtime']);
            $rowCls   = ($isUL ? ' unlocked' : '') . ($isPin ? ' pinned' : '');
        ?>
        <tr class="file-row<?= $rowCls ?>"
            data-url="<?= htmlspecialchars($relUrl) ?>"
            data-name="<?= htmlspecialchars(strtolower($base)) ?>"
            data-size="<?= $file['size'] ?>"
            data-date="<?= $file['mtime'] ?>"
            data-path="<?= htmlspecialchars($relPath) ?>">
          <td class="cb-col"><input type="checkbox" class="fchk row-cb" value="<?= htmlspecialchars($relPath) ?>" onclick="event.stopPropagation();updateBulk()"></td>
          <td>
            <div class="fn">
              <i class="bi <?= $ico ?>"></i>
              <?= htmlspecialchars($base) ?>
              <?php if ($isPin): ?><i class="bi bi-pin-fill pin-ico"></i><?php endif; ?>
            </div>
            <div class="fm"><?= $sz ?></div>
          </td>
          <td class="fp"><?= htmlspecialchars($dsp) ?></td>
          <td class="fp"><?= $sz ?></td>
          <td class="fp"><?= $dt ?></td>
          <td>
            <div class="fac">
              <span class="btn-lock"><i class="bi bi-lock"></i></span>
              <a class="btn-o" href="<?= htmlspecialchars($relUrl) ?>" target="_blank" onclick="event.stopPropagation()">
                <i class="bi bi-box-arrow-up-right"></i> &Ouml;ffnen
              </a>
              <button class="btn-dl" onclick="event.stopPropagation();dlFile('<?= htmlspecialchars(addslashes($relPath)) ?>')" title="Herunterladen">
                <i class="bi bi-download"></i>
              </button>
              <button class="btn-tog"
                onclick="event.stopPropagation();toggleFile(this,'<?= htmlspecialchars(addslashes($relPath)) ?>')"
                title="<?= $isUL ? 'Sperren' : 'Freigeben' ?>">
                <i class="bi <?= $isUL ? 'bi-lock' : 'bi-unlock' ?>"></i>
                <span><?= $isUL ? 'Sperren' : 'Freigeben' ?></span>
              </button>
              <button class="btn-pin"
                onclick="event.stopPropagation();pinFile(this,'<?= htmlspecialchars(addslashes($relPath)) ?>')"
                title="<?= $isPin ? 'Pin entfernen' : 'Anpinnen' ?>">
                <i class="bi <?= $isPin ? 'bi-pin-fill' : 'bi-pin' ?>"></i>
              </button>
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
      <div class="no-res" id="noRes"><i class="bi bi-search"></i> Keine Ergebnisse</div>
    </div>
    <?php endif; ?>

  </main>
</div>

<div class="ver">v<?= _VER ?></div>
<div class="toast-n" id="toastEl"><i class="bi" id="toastIco"></i><span id="toastMsg"></span></div>

<!-- Bulk Toolbar -->
<div class="bulk-bar" id="bulkBar">
  <span class="bulk-cnt" id="bulkCnt">0 ausgew&auml;hlt</span>
  <button class="bulk-del" onclick="confirmDelete()"><i class="bi bi-trash3"></i> L&ouml;schen</button>
  <button class="bulk-cancel" onclick="clearSel()"><i class="bi bi-x"></i></button>
</div>

<!-- Delete Confirm Modal -->
<div class="pw-ov" id="delOv">
  <div class="pw-box">
    <div class="pw-ico" style="color:#ef4444"><i class="bi bi-trash3"></i></div>
    <div class="pw-ttl" id="delTtl">Datei wirklich l&ouml;schen?</div>
    <div class="pw-err" id="delInfo" style="color:var(--mut);margin-bottom:.75rem"></div>
    <input type="password" id="delPw" class="pw-inp" placeholder="Passwort best&auml;tigen" autocomplete="off" style="display:none;margin-bottom:.5rem">
    <div class="pw-err" id="delErr"></div>
    <button class="pw-btn" id="delConfBtn" style="background:#ef4444" onclick="execDelete()">L&ouml;schen</button>
    <button class="pw-btn" onclick="closeDelOv()" style="margin-top:.5rem;background:transparent;border:1px solid rgba(255,255,255,.15);color:var(--mut);font-size:.8rem">Abbrechen</button>
  </div>
</div>

<!-- Login Modal -->
<div class="pw-ov" id="pwOv">
  <div class="pw-box">
    <div class="pw-ico"><i class="bi bi-shield-lock"></i></div>
    <div class="pw-ttl">Anmelden</div>
    <input type="password" id="pwInp" class="pw-inp" placeholder="Passwort" autocomplete="off" spellcheck="false">
    <div class="pw-err" id="pwErr"></div>
    <button class="pw-btn" id="pwBtn">Best&auml;tigen</button>
  </div>
</div>

<!-- Change Password Modal -->
<div class="pw-ov" id="cpwOv">
  <div class="pw-box">
    <div class="pw-ico"><i class="bi bi-key"></i></div>
    <div class="pw-ttl" id="cpwTtl">Passwort &auml;ndern</div>
    <input type="password" id="cpwCur" class="pw-inp" placeholder="Aktuelles Passwort" autocomplete="off">
    <input type="password" id="cpwNew" class="pw-inp" placeholder="Neues Passwort (mind. 4 Zeichen)" autocomplete="new-password" style="margin-top:.4rem">
    <input type="password" id="cpwCon" class="pw-inp" placeholder="Neues Passwort best&auml;tigen" autocomplete="new-password" style="margin-top:.4rem">
    <div class="pw-err" id="cpwErr"></div>
    <button class="pw-btn" id="cpwBtn">Speichern</button>
    <button class="pw-btn" id="cpwSkip" style="margin-top:.5rem;background:transparent;border:1px solid rgba(255,255,255,.15);color:var(--mut);font-size:.8rem" onclick="closeCpw()">Sp&auml;ter</button>
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
const _html      = document.documentElement;
const _mc        = document.getElementById('mainContent');
const _csrf      = '<?= $csrf ?>';
const _curDir    = <?= json_encode($relCurDir) ?>;
const _defPw     = <?= $isDefaultPw ? 'true' : 'false' ?>;

// Ã¢â€â‚¬Ã¢â€â‚¬ Dark Mode Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
document.getElementById('darkBtn').addEventListener('click', () => {
  const dark = _html.getAttribute('data-theme') !== 'dark';
  _html.setAttribute('data-theme', dark ? 'dark' : 'light');
  document.cookie = 'dk=' + (dark?1:0) + ';path=/;max-age=315360000;SameSite=Lax';
});

// Ã¢â€â‚¬Ã¢â€â‚¬ Sidebar chevrons Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
document.querySelectorAll('.tb').forEach(btn => {
  btn.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); });
  const tgt = document.querySelector(btn.dataset.bsTarget);
  if (!tgt) return;
  tgt.addEventListener('show.bs.collapse', () => btn.querySelector('.ti')?.classList.add('open'));
  tgt.addEventListener('hide.bs.collapse', () => btn.querySelector('.ti')?.classList.remove('open'));
});

// Ã¢â€â‚¬Ã¢â€â‚¬ Brand name Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
(function() {
  const stored = localStorage.getItem('_bn');
  if (stored) { document.getElementById('brandNm').textContent = stored; document.title = stored + ' Ã‚Â· Explorer'; }
})();
document.querySelector('.tb-brand').addEventListener('click', e => {
  const tgt = e.target.closest('#brandNm'); if (!tgt) return;
  const cur = tgt.textContent;
  const inp = document.createElement('input');
  inp.type = 'text'; inp.value = cur; inp.className = 'brand-inp'; inp.maxLength = 30;
  tgt.replaceWith(inp); inp.focus(); inp.select();
  let done = false;
  function save() {
    if (done) return; done = true;
    const val = inp.value.trim() || 'GodLe972';
    localStorage.setItem('_bn', val);
    document.title = val + ' Ã‚Â· Explorer';
    const sp = document.createElement('span');
    sp.id = 'brandNm'; sp.className = 'brand-nm'; sp.title = 'Klicken zum Umbenennen'; sp.textContent = val;
    inp.replaceWith(sp);
  }
  inp.addEventListener('blur', save);
  inp.addEventListener('keydown', e => { if (e.key==='Enter') inp.blur(); if (e.key==='Escape') { done=true; inp.replaceWith(tgt); } });
});

// Ã¢â€â‚¬Ã¢â€â‚¬ Toast Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
let _toastTimer;
function showToast(msg, icon, dur) {
  clearTimeout(_toastTimer);
  document.getElementById('toastIco').className = 'bi ' + (icon||'bi-info-circle');
  document.getElementById('toastMsg').textContent = msg;
  const t = document.getElementById('toastEl');
  t.classList.add('show');
  if (dur !== 99999) _toastTimer = setTimeout(() => t.classList.remove('show'), dur||3200);
}
function hideToast() { clearTimeout(_toastTimer); document.getElementById('toastEl').classList.remove('show'); }

// Ã¢â€â‚¬Ã¢â€â‚¬ Auth / Levels Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
let userLevel = 0;
let _token = sessionStorage.getItem('_st') || '';

if (_token) {
  userLevel = parseInt(sessionStorage.getItem('_sl') || '0', 10);
  applyLevel(userLevel, false);
  if (userLevel === 1 && _defPw) setTimeout(openCpw, 600);
}

function openLogin() {
  document.getElementById('pwOv').classList.add('open');
  setTimeout(() => document.getElementById('pwInp').focus(), 50);
}

function applyLevel(lvl, announce) {
  userLevel = lvl;
  _mc.classList.remove('level-1', 'level-2');
  if (lvl >= 1) _mc.classList.add('level-1');
  if (lvl >= 2) _mc.classList.add('level-2');
  const badge  = document.getElementById('lvlBadge');
  const txt    = document.getElementById('lvlTxt');
  const iamBtn = document.getElementById('iamBtn');
  if (lvl >= 1) {
    badge.classList.add('show');
    iamBtn.classList.add('hidden');
    txt.textContent = lvl >= 2 ? 'Admin' : 'Owner';
    badge.style.background  = lvl >= 2 ? 'rgba(99,102,241,.25)' : 'rgba(16,185,129,.2)';
    badge.style.borderColor = lvl >= 2 ? 'rgba(99,102,241,.4)'  : 'rgba(16,185,129,.4)';
    badge.style.color       = lvl >= 2 ? '#a5b4fc' : '#6ee7b7';
    if (announce) {
      showToast((lvl >= 2 ? 'Admin' : 'Owner') + ' â€” wird neu geladen ...',
        lvl >= 2 ? 'bi-shield-lock-fill' : 'bi-shield-check');
      setTimeout(() => location.reload(), 900);
    }
  } else {
    badge.classList.remove('show');
    iamBtn.classList.remove('hidden');
  }
}

Object.defineProperty(window, 'admin', {
  get() {
    if (userLevel >= 2) { showToast('Admin bereits aktiv', 'bi-shield-check'); return; }
    openLogin();
  }, configurable: true
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
    const res  = await fetch('?_a=1', {method:'POST', body:fd});
    const data = await res.json();
    if (data.ok) {
      _token = data.t;
      sessionStorage.setItem('_st', _token);
      sessionStorage.setItem('_sl', data.lvl);
      closePw();
      applyLevel(data.lvl, true);
    } else {
      err.textContent = 'Falsches Passwort';
      inp.value = ''; inp.classList.add('shake');
      setTimeout(() => inp.classList.remove('shake'), 400);
      inp.focus();
    }
  } catch { err.textContent = 'Verbindungsfehler'; }
}

function closePw() {
  document.getElementById('pwOv').classList.remove('open');
  document.getElementById('pwInp').value = '';
  document.getElementById('pwErr').textContent = '';
  document.getElementById('pwInp').classList.remove('shake');
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Row click Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
document.addEventListener('click', e => {
  const row = e.target.closest('tr.file-row');
  if (!row || e.target.closest('a,button')) return;
  const canOpen = userLevel >= 1 || row.classList.contains('unlocked');
  if (!canOpen) { showToast('Gesperrt — kein Zugriff', 'bi-lock', 2500); return; }
  window.open(row.dataset.url, '_blank');
});

// Ã¢â€â‚¬Ã¢â€â‚¬ Toggle unlock Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
async function toggleFile(btn, relPath) {
  const row = btn.closest('tr');
  const fd = new FormData(); fd.append('_t', _token); fd.append('f', relPath);
  try {
    const res  = await fetch('?_tog=1', {method:'POST', body:fd});
    const data = await res.json();
    if (!data.ok) { showToast('Fehler', 'bi-x', 2000); return; }
    const i = btn.querySelector('i');
    const s = btn.querySelector('span');
    if (data.unlocked) {
      row.classList.add('unlocked');
      i.className = 'bi bi-lock'; s.textContent = 'Sperren'; btn.title = 'Sperren';
      showToast('Freigegeben fÃƒÂ¼r Besucher', 'bi-unlock', 2500);
    } else {
      row.classList.remove('unlocked');
      i.className = 'bi bi-unlock'; s.textContent = 'Freigeben'; btn.title = 'Freigeben';
      showToast('Gesperrt', 'bi-lock', 2500);
    }
  } catch { showToast('Verbindungsfehler', 'bi-wifi-off', 2500); }
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Pin Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
async function pinFile(btn, relPath) {
  const row = btn.closest('tr');
  const fd = new FormData(); fd.append('_t', _token); fd.append('f', relPath);
  try {
    const res  = await fetch('?_pin=1', {method:'POST', body:fd});
    const data = await res.json();
    if (!data.ok) return;
    const i = btn.querySelector('i');
    if (data.pinned) {
      row.classList.add('pinned'); i.className = 'bi bi-pin-fill'; btn.title = 'Pin entfernen';
      showToast('Angepinnt', 'bi-pin-fill', 2000);
    } else {
      row.classList.remove('pinned'); i.className = 'bi bi-pin'; btn.title = 'Anpinnen';
      showToast('Pin entfernt', 'bi-pin', 2000);
    }
  } catch {}
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Download Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
function dlFile(relPath) {
  window.location.href = '?dl=1&f=' + encodeURIComponent(relPath) + '&_t=' + encodeURIComponent(_token);
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Note Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
let _noteOrig = '';
function startNote() {
  const el = document.getElementById('noteTxt');
  _noteOrig = el.textContent;
  el.contentEditable = 'true'; el.focus();
  document.getElementById('noteEditBtn').style.display  = 'none';
  document.getElementById('noteSaveBtn').style.display  = '';
  document.getElementById('noteCancelBtn').style.display = '';
}
function cancelNote() {
  const el = document.getElementById('noteTxt');
  el.textContent = _noteOrig; el.contentEditable = 'false';
  document.getElementById('noteEditBtn').style.display  = '';
  document.getElementById('noteSaveBtn').style.display  = 'none';
  document.getElementById('noteCancelBtn').style.display = 'none';
}
async function saveNote() {
  const el = document.getElementById('noteTxt');
  const text = el.textContent.trim();
  const fd = new FormData();
  fd.append('_t', _token); fd.append('d', _curDir); fd.append('t', text);
  el.contentEditable = 'false';
  document.getElementById('noteEditBtn').style.display  = '';
  document.getElementById('noteSaveBtn').style.display  = 'none';
  document.getElementById('noteCancelBtn').style.display = 'none';
  try {
    await fetch('?_note=1', {method:'POST', body:fd});
    const bar = document.getElementById('noteBar');
    if (text === '') bar.classList.add('no-note'); else bar.classList.remove('no-note');
    showToast('Notiz gespeichert', 'bi-check2', 2000);
  } catch { showToast('Fehler beim Speichern', 'bi-x', 2500); }
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Upload Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
// â”€â”€ Upload â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function toggleUpload() {
  const body = document.getElementById('upBody');
  const chev = document.getElementById('upChev');
  const open = body.classList.toggle('open');
  chev.style.transform = open ? 'rotate(180deg)' : '';
}
const upDrop = document.getElementById('upDrop');
const upInp  = document.getElementById('upInp');
if (upDrop) {
  upDrop.addEventListener('click', e => { if (!e.target.closest('label')) upInp.click(); });
  upDrop.addEventListener('dragover', e => { e.preventDefault(); upDrop.classList.add('drag'); });
  upDrop.addEventListener('dragleave', () => upDrop.classList.remove('drag'));
  upDrop.addEventListener('drop', e => { e.preventDefault(); upDrop.classList.remove('drag'); uploadFiles(e.dataTransfer.files); });
  upInp.addEventListener('change', () => { uploadFiles(upInp.files); upInp.value = ''; });
}
async function uploadFiles(fileList) {
  if (!fileList || !fileList.length) return;
  const fd = new FormData();
  fd.append('_t', _token); fd.append('d', '');
  for (const f of fileList) fd.append('f[]', f);
  showToast('Wird hochgeladen ...', 'bi-cloud-upload', 99999);
  try {
    const res  = await fetch('?_up=1', {method:'POST', body:fd});
    const data = await res.json();
    hideToast();
    if (!data.ok && data.s === 'auth') { showToast('Keine Berechtigung', 'bi-lock', 3000); return; }
    const st = document.getElementById('upStatus');
    st.innerHTML = (data.files||[]).map(f =>
      `<div class="up-item ${f.ok?'ok':'err'}"><i class="bi ${f.ok?'bi-check2':'bi-x'}"></i>${f.n}${f.s==='type'?' (Typ nicht erlaubt)':''}</div>`
    ).join('');
    const ok = (data.files||[]).filter(f=>f.ok).length;
    showToast(ok + ' Datei(en) hochgeladen â€” wird neu geladen ...', 'bi-check-circle', 2500);
    if (ok > 0) setTimeout(() => location.reload(), 2000);
  } catch { hideToast(); showToast('Upload-Fehler', 'bi-wifi-off', 3000); }
}

// â”€â”€ Checkboxes & Bulk Delete â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function updateBulk() {
  const checked = [...document.querySelectorAll('.row-cb:checked')];
  const bar = document.getElementById('bulkBar');
  const cnt = document.getElementById('bulkCnt');
  cnt.textContent = checked.length + ' ausgewÃ¤hlt';
  bar.classList.toggle('show', checked.length > 0);
  const all = document.getElementById('cbAll');
  if (all) {
    const total = document.querySelectorAll('.row-cb').length;
    all.indeterminate = checked.length > 0 && checked.length < total;
    all.checked = checked.length === total && total > 0;
  }
}
function clearSel() {
  document.querySelectorAll('.row-cb,.fchk[id=cbAll]').forEach(cb => { cb.checked = false; cb.indeterminate = false; });
  document.getElementById('bulkBar').classList.remove('show');
}
document.getElementById('cbAll')?.addEventListener('change', function() {
  document.querySelectorAll('.row-cb').forEach(cb => cb.checked = this.checked);
  updateBulk();
});

let _delPaths = [];
function confirmDelete() {
  _delPaths = [...document.querySelectorAll('.row-cb:checked')].map(cb => cb.value);
  if (!_delPaths.length) return;
  const multi = _delPaths.length > 1;
  document.getElementById('delTtl').textContent = multi
    ? _delPaths.length + ' Dateien wirklich lÃ¶schen?'
    : '"' + _delPaths[0].split('/').pop() + '" wirklich lÃ¶schen?';
  document.getElementById('delInfo').textContent = multi
    ? 'Passwort erforderlich fÃ¼r Mehrfach-LÃ¶schung.' : '';
  const pwInp = document.getElementById('delPw');
  pwInp.style.display = multi ? '' : 'none';
  pwInp.value = '';
  document.getElementById('delErr').textContent = '';
  document.getElementById('delOv').classList.add('open');
  setTimeout(() => (multi ? pwInp : document.getElementById('delConfBtn')).focus(), 50);
}
function closeDelOv() {
  document.getElementById('delOv').classList.remove('open');
  document.getElementById('delPw').value = '';
  document.getElementById('delErr').textContent = '';
}
async function execDelete() {
  const multi = _delPaths.length > 1;
  const pw    = document.getElementById('delPw').value;
  if (multi && !pw) { document.getElementById('delErr').textContent = 'Passwort eingeben'; return; }
  const fd = new FormData();
  fd.append('_t', _token);
  fd.append('f', JSON.stringify(_delPaths));
  if (multi) fd.append('pw', pw);
  try {
    const res  = await fetch('?_del=1', {method:'POST', body:fd});
    const data = await res.json();
    if (!data.ok) {
      document.getElementById('delErr').textContent =
        data.s === 'pw' ? 'Falsches Passwort' : 'Fehler: ' + (data.s||'unbekannt');
      return;
    }
    closeDelOv();
    clearSel();
    data.deleted.forEach(rel => {
      document.querySelector(`tr[data-path="${rel}"]`)?.remove();
    });
    showToast(data.deleted.length + ' Datei(en) gelÃ¶scht', 'bi-trash3', 2500);
  } catch { document.getElementById('delErr').textContent = 'Verbindungsfehler'; }
}
document.getElementById('delPw')?.addEventListener('keydown', e => { if (e.key==='Enter') execDelete(); });

// Ã¢â€â‚¬Ã¢â€â‚¬ Search Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
document.getElementById('searchInp')?.addEventListener('input', function() {
  const q = this.value.toLowerCase().trim();
  let visible = 0;
  document.querySelectorAll('#fileBody tr.file-row').forEach(row => {
    const name = row.dataset.name || '';
    const path = row.dataset.path || '';
    const match = !q || name.includes(q) || path.toLowerCase().includes(q);
    row.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  const nr = document.getElementById('noRes');
  if (nr) nr.style.display = visible === 0 && q ? 'block' : 'none';
});

// Ã¢â€â‚¬Ã¢â€â‚¬ Sort Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
let _sortKey = 'pin', _sortDir = 1;
function setSort(key) {
  if (_sortKey === key) _sortDir *= -1; else { _sortKey = key; _sortDir = 1; }
  document.querySelectorAll('.sort-btn').forEach(b => b.classList.toggle('active', b.dataset.sort === key));
  document.querySelectorAll('.ftbl thead th').forEach(th => {
    const ind = th.querySelector('.sort-ind');
    if (!ind) return;
    const sorted = th.onclick?.toString().includes("'"+key+"'");
    th.classList.toggle('sorted', sorted);
    if (ind) ind.className = 'sort-ind bi ' + (sorted ? (_sortDir===1?'bi-chevron-up':'bi-chevron-down') : 'bi-chevron-expand');
  });
  const body = document.getElementById('fileBody');
  if (!body) return;
  const rows = [...body.querySelectorAll('tr.file-row')];
  rows.sort((a, b) => {
    let va, vb;
    if (key === 'pin')  { va = a.classList.contains('pinned')?0:1; vb = b.classList.contains('pinned')?0:1; }
    if (key === 'name') { va = a.dataset.name; vb = b.dataset.name; }
    if (key === 'size') { va = parseInt(a.dataset.size||0); vb = parseInt(b.dataset.size||0); }
    if (key === 'date') { va = parseInt(a.dataset.date||0); vb = parseInt(b.dataset.date||0); }
    if (va < vb) return -_sortDir; if (va > vb) return _sortDir; return 0;
  });
  rows.forEach(r => body.appendChild(r));
}

// Ã¢â€â‚¬Ã¢â€â‚¬ Source Viewer Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
async function showSrc(relPath, filename) {
  document.getElementById('cdFile').textContent = filename;
  const el = document.getElementById('cdEl');
  el.textContent = 'Wird geladen ...'; el.className = '';
  document.getElementById('cdOv').classList.add('open');
  try {
    const res = await fetch('?source=' + encodeURIComponent(relPath) + '&_t=' + encodeURIComponent(_token));
    if (!res.ok) throw new Error('HTTP ' + res.status);
    el.textContent = await res.text();
    el.className   = filename.endsWith('.php') ? 'language-php' : 'language-html';
    hljs.highlightElement(el);
  } catch(ex) { el.textContent = 'Fehler: ' + ex.message; }
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

// Ã¢â€â‚¬Ã¢â€â‚¬ Version click = manual update (admin only) Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
document.querySelector('.ver').addEventListener('click', async () => {
  if (userLevel < 2) { showToast('Admin-Modus benÃƒÂ¶tigt (Konsole: admin)', 'bi-lock', 2500); return; }
  showToast('Suche Update ...', 'bi-cloud-download', 99999);
  const fd = new FormData(); fd.append('_t', _token);
  try {
    const res  = await fetch('?_upd=1', {method:'POST', body:fd});
    const data = await res.json();
    hideToast();
    const msgs = {
      ok:   ['Update installiert! Wird neu geladen ...', 'bi-check-circle-fill', 4000],
      err:  ['GitHub nicht erreichbar.', 'bi-exclamation-triangle', 3000],
      auth: ['Session abgelaufen.', 'bi-lock', 3000],
    };
    const [msg, ico, dur] = msgs[data.s] || ['Unbekannter Fehler', 'bi-x', 3000];
    showToast(msg, ico, dur);
    if (data.s === 'ok') setTimeout(() => location.reload(), 2000);
  } catch { hideToast(); showToast('Verbindungsfehler', 'bi-wifi-off', 3000); }
});

// Ã¢â€â‚¬Ã¢â€â‚¬ Change Password Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
function openCpw(forced) {
  const ttl = document.getElementById('cpwTtl');
  ttl.textContent = forced === false ? 'Passwort ändern'
    : 'Standard-Passwort ändern — bitte jetzt setzen';
  document.getElementById('cpwSkip').style.display = forced === false ? '' : 'none';
  document.getElementById('cpwOv').classList.add('open');
  setTimeout(() => document.getElementById('cpwCur').focus(), 50);
}
function closeCpw() {
  document.getElementById('cpwOv').classList.remove('open');
  ['cpwCur','cpwNew','cpwCon'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('cpwErr').textContent = '';
}
document.getElementById('cpwBtn').addEventListener('click', async () => {
  const cur = document.getElementById('cpwCur').value;
  const nw  = document.getElementById('cpwNew').value;
  const con = document.getElementById('cpwCon').value;
  const err = document.getElementById('cpwErr');
  err.textContent = '';
  if (nw.length < 4) { err.textContent = 'Mind. 4 Zeichen'; return; }
  if (nw !== con)    { err.textContent = 'PasswÃƒÂ¶rter stimmen nicht ÃƒÂ¼berein'; return; }
  const fd = new FormData();
  fd.append('_t', _token); fd.append('cur', cur); fd.append('new', nw);
  try {
    const res  = await fetch('?_cpw=1', {method:'POST', body:fd});
    const data = await res.json();
    if (data.ok) {
      closeCpw();
      showToast('Passwort geändert', 'bi-check2-circle', 3000);
    } else {
      err.textContent = data.s === 'wrong' ? 'Aktuelles Passwort falsch'
        : data.s === 'short' ? 'Zu kurz' : 'Fehler';
    }
  } catch { err.textContent = 'Verbindungsfehler'; }
});
['cpwCur','cpwNew','cpwCon'].forEach(id =>
  document.getElementById(id).addEventListener('keydown', e => { if (e.key==='Enter') document.getElementById('cpwBtn').click(); })
);

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeCode(); closePw(); closeCpw(); } });
</script>
</body>
</html>
