<?php
/**
 * WSers Explorer – Single-file PHP file manager for school web servers.
 *
 * Full source code available at:
 * https://github.com/LennyGodart/wsers
 *
 * The entire codebase is openly auditable
 *
 * Features:
 *   - Password-protected file browser (two levels: Owner + Admin)
 *   - All non-hidden file types displayed with type-specific icons
 *   - Subfolder navigation inside the main file table
 *   - Lock / unlock files for public guest access
 *   - Upload, download and delete files (bulk delete with confirmation)
 *   - ZIP download with interactive subfolder-exclusion tree
 *   - Pin files to the top of the list
 *   - Per-folder notes
 *   - Source code viewer with syntax highlighting (Owner+)
 *   - Disk space display (Owner+)
 *   - Password strength indicator when changing password
 *   - One-click self-update with background availability check
 *
 * Settings are stored in .wsconfig (JSON) next to this file.
 *
 * @author  LennyGodart
 * @license MIT
 */

// ── Bootstrap: define app-wide constants ────────────────────────────────────
(static function () {
    $defaults = [
        '_VER'        => '3.8.4',
        '_UPDATE_SRC' => 'https://raw.githubusercontent.com/LennyGodart/wsers/refs/heads/main/index.php',
        '_APP_KEY'    => 'dGVzdDEyMyo=',
    ];
    foreach ($defaults as $k => $v) defined($k) || define($k, $v);
    unset($defaults, $k, $v);
})();

// ── Config helpers ───────────────────────────────────────────────────────────

/** Returns the absolute path to the .wsconfig config file. */
function _wsPath(): string {
    return realpath(__DIR__) . DIRECTORY_SEPARATOR . '.wsconfig';
}

/** Loads and returns the .wsconfig JSON config as an array. */
function _loadConfig(): array {
    $raw = @file_get_contents(_wsPath());
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Persists the config array back to .wsconfig. */
function _saveConfig(array $data): bool {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents(_wsPath(), $json, LOCK_EX) !== false;
}

// ── Updater helpers ──────────────────────────────────────────────────────────

/**
 * When a new version is applied, preserve user-specific values
 * (_UPDATE_SRC and _APP_KEY) from the current file into the new one.
 * This ensures custom source URLs and app keys survive updates.
 */
function _preserveConfig(string $newSource): string {
    $current = @file_get_contents(__FILE__) ?: '';
    foreach (['_UPDATE_SRC', '_APP_KEY'] as $key) {
        if (preg_match("/'$key'\s*=>\s*'([^']*)'/", $current, $m)) {
            $val       = addslashes($m[1]);
            $newSource = preg_replace("/'$key'\s*=>\s*'[^']*'/", "'$key' => '$val'", $newSource);
        }
    }
    return $newSource;
}

/** Fetches a URL via file_get_contents or cURL (no auth – public repo). */
function _httpFetch(string $url, int $timeout = 6): string|false {
    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http'  => ['timeout' => $timeout],
            'https' => ['timeout' => $timeout],
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
            CURLOPT_USERAGENT      => 'WSers-Updater/2.6',
        ]);
        $r  = curl_exec($ch);
        $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);
        return ($ok && $r) ? $r : false;
    }

    return false;
}

// NOTE: Silent auto-update has been intentionally removed.
// Updates are applied manually by clicking the version badge (Owner or Admin).
// This prevents the file from modifying itself without user knowledge,
// which is a common false-positive trigger for malware scanners.

// ── Session ──────────────────────────────────────────────────────────────────
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

// CSRF token tied to the current session and app key
$csrf = hash_hmac('sha256', session_id(), _APP_KEY);
$root = realpath(__DIR__);

// ── Auth helpers ─────────────────────────────────────────────────────────────

/** Returns the current session's auth level (0 = guest, 1 = owner, 2 = admin). */
function _getLevel(): int {
    return (int)($_SESSION['ws_level'] ?? 0);
}

/**
 * Returns the owner password (base64-encoded) from .wsconfig, or the default
 * fallback so first-run works out of the box.
 */
function _getOwnerPw(): string {
    static $cached = null;
    if ($cached === null) {
        $config  = _loadConfig();
        $cached  = $config['_ok'] ?? base64_encode('owner123');
    }
    return $cached;
}

/**
 * Returns true when the request carries a valid session token and the
 * session level is at least $minLevel.
 */
function _checkAuth(int $minLevel = 1): bool {
    $token = $_POST['_t'] ?? $_GET['_t'] ?? '';
    return !empty($_SESSION['ws_token'])
        && $token !== ''
        && hash_equals($_SESSION['ws_token'], $token)
        && _getLevel() >= $minLevel;
}

// ── Request handlers ─────────────────────────────────────────────────────────

// ── Login ────────────────────────────────────────────────────────────────────
if (isset($_GET['_login']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    $csrfOk = isset($_POST['c'])
        && hash_equals(hash_hmac('sha256', session_id(), _APP_KEY), $_POST['c']);
    $pw  = $_POST['p'] ?? '';
    $lvl = 0;

    if ($csrfOk && $pw !== '') {
        // Level 2: admin password (the _APP_KEY constant decoded)
        if (hash_equals(hash('sha256', base64_decode(_APP_KEY)), hash('sha256', $pw))) {
            $lvl = 2;
        // Level 1: owner password (stored in .wsconfig)
        } elseif (hash_equals(hash('sha256', base64_decode(_getOwnerPw())), hash('sha256', $pw))) {
            $lvl = 1;
        }
    }

    if ($lvl > 0) {
        $token                  = bin2hex(random_bytes(24));
        $_SESSION['ws_token']   = $token;
        $_SESSION['ws_level']   = $lvl;
        echo json_encode(['ok' => true, 't' => $token, 'lvl' => $lvl]);
    } else {
        http_response_code(403);
        echo json_encode(['ok' => false]);
    }
    exit;
}

// ── Manual update (Owner or Admin, user-triggered) ──────────────────────────
if (isset($_GET['_update']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_checkAuth(1)) { http_response_code(403); echo json_encode(['s' => 'auth']); exit; }

    $newSource = _httpFetch(_UPDATE_SRC, 8);
    if (!$newSource || strlen($newSource) < 500) {
        echo json_encode(['s' => 'err']); exit;
    }

    @file_put_contents(__FILE__, _preserveConfig($newSource));
    echo json_encode(['s' => 'ok']); exit;
}

// ── Check for update availability (cached 30 min, Owner or Admin) ────────────
if (isset($_GET['_checkupdate'])) {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_checkAuth(1)) { http_response_code(403); echo json_encode(['ok' => false]); exit; }

    $cfg       = _loadConfig();
    $lastCheck = $cfg['_uc_ts'] ?? 0;

    // Return cached result if fresh enough (bypass with ?force=1)
    if (!isset($_GET['force']) && time() - $lastCheck < 1800) {
        echo json_encode(['ok' => true, 'available' => (bool)($cfg['_uc_av'] ?? false), 'latest' => $cfg['_uc_v'] ?? _VER]);
        exit;
    }

    // Fetch latest source and parse version number
    $src       = _httpFetch(_UPDATE_SRC, 4);
    $available = false;
    $latestVer = _VER;
    if ($src && preg_match("/'_VER'\s*=>\s*'([^']+)'/", $src, $m)) {
        $latestVer = $m[1];
        $available = version_compare($latestVer, _VER, '>');
    }

    // Cache result
    $cfg['_uc_ts'] = time();
    $cfg['_uc_av'] = $available;
    $cfg['_uc_v']  = $latestVer;
    _saveConfig($cfg);

    echo json_encode(['ok' => true, 'available' => $available, 'latest' => $latestVer]);
    exit;
}

// ── Folder tree (for ZIP selection modal) ───────────────────────────────────
if (isset($_GET['_tree'])) {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_checkAuth(1)) { http_response_code(403); echo json_encode(['ok' => false]); exit; }

    $rel = ltrim(str_replace(['..', '\\'], ['', '/'], $_GET['p'] ?? ''), '/');
    $abs = $rel === '' ? $root : realpath($root . DIRECTORY_SEPARATOR . $rel);
    if (!$abs || !str_starts_with($abs, $root) || !is_dir($abs)) {
        http_response_code(400); echo json_encode(['ok' => false]); exit;
    }

    function _buildTree(string $dir, string $root): array {
        $items = [];
        foreach (@scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') continue;
            if ($item[0] === '.') continue;
            $full = $dir . DIRECTORY_SEPARATOR . $item;
            $rel  = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $full));
            $items[] = is_dir($full)
                ? ['n' => $item, 'p' => $rel, 'd' => true,  'c' => _buildTree($full, $root)]
                : ['n' => $item, 'p' => $rel, 'd' => false];
        }
        return $items;
    }

    echo json_encode(['ok' => true, 'tree' => _buildTree($abs, $root)]);
    exit;
}

// ── ZIP download ─────────────────────────────────────────────────────────────
if (isset($_GET['_zip']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!_checkAuth(1)) { http_response_code(403); exit; }

    $include = json_decode($_POST['inc'] ?? '[]', true) ?: [];
    $exclude = json_decode($_POST['exc'] ?? '[]', true) ?: [];

    if (!class_exists('ZipArchive') || empty($include)) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 's' => class_exists('ZipArchive') ? 'empty' : 'nozip']);
        exit;
    }

    $tmp = tempnam(sys_get_temp_dir(), 'wsdl_');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $addToZip = null;
    $addToZip = function(string $abs, string $zipPath) use (&$addToZip, $zip, $root, $exclude): void {
        if (basename($abs)[0] === '.') return;
        $rel = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $abs));
        if (in_array($rel, $exclude, true)) return;
        if (is_file($abs)) {
            $zip->addFile($abs, $zipPath);
        } elseif (is_dir($abs)) {
            foreach (@scandir($abs) ?: [] as $item) {
                if ($item === '.' || $item === '..') continue;
                ($addToZip)($abs . DIRECTORY_SEPARATOR . $item, $zipPath . '/' . $item);
            }
        }
    };

    foreach ($include as $rel) {
        $rel = ltrim(str_replace(['..', '\\'], ['', '/'], $rel), '/');
        $abs = realpath($root . DIRECTORY_SEPARATOR . $rel);
        if (!$abs || !str_starts_with($abs, $root)) continue;
        $addToZip($abs, basename($rel));
    }

    $zip->close();
    $name = 'download_' . date('Ymd_His') . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
}

// ── Source code viewer (Owner + Admin) ───────────────────────────────────────
if (isset($_GET['source'])) {
    if (!_checkAuth(1)) { http_response_code(403); echo 'Zugriff verweigert'; exit; }
    $src = realpath($root . DIRECTORY_SEPARATOR . urldecode($_GET['source']));
    if ($src && str_starts_with($src, $root) && is_file($src)) {
        header('Content-Type: text/plain; charset=UTF-8');
        readfile($src);
    } else {
        http_response_code(404); echo 'Nicht gefunden';
    }
    exit;
}

// ── Download ─────────────────────────────────────────────────────────────────
if (isset($_GET['dl'])) {
    if (!_checkAuth(1)) { http_response_code(403); exit; }
    $src = realpath($root . DIRECTORY_SEPARATOR . urldecode($_GET['f'] ?? ''));
    if ($src && str_starts_with($src, $root) && is_file($src)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($src) . '"');
        header('Content-Length: ' . filesize($src));
        readfile($src);
    } else {
        http_response_code(404);
    }
    exit;
}

// ── Toggle file lock/unlock ───────────────────────────────────────────────────
if (isset($_GET['_unlock']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_checkAuth(1)) { http_response_code(403); echo json_encode(['ok' => false]); exit; }

    $abs = realpath($root . DIRECTORY_SEPARATOR . ($_POST['f'] ?? ''));
    if (!$abs || !str_starts_with($abs, $root) || !is_file($abs)) {
        http_response_code(400); echo json_encode(['ok' => false]); exit;
    }

    $rel    = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $abs));
    $config = _loadConfig();
    $list   = $config['unlocked'] ?? [];
    $idx    = array_search($rel, $list);

    if ($idx !== false) { array_splice($list, $idx, 1); $state = false; }
    else                { $list[] = $rel;               $state = true;  }

    $config['unlocked'] = array_values($list);
    if (!_saveConfig($config)) { http_response_code(500); echo json_encode(['ok' => false, 's' => 'write']); exit; }
    echo json_encode(['ok' => true, 'unlocked' => $state]); exit;
}

// ── Toggle pin ────────────────────────────────────────────────────────────────
if (isset($_GET['_pin']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_checkAuth(1)) { http_response_code(403); echo json_encode(['ok' => false]); exit; }

    $abs = realpath($root . DIRECTORY_SEPARATOR . ($_POST['f'] ?? ''));
    if (!$abs || !str_starts_with($abs, $root) || !is_file($abs)) {
        http_response_code(400); echo json_encode(['ok' => false]); exit;
    }

    $rel    = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $abs));
    $config = _loadConfig();
    $list   = $config['pinned'] ?? [];
    $idx    = array_search($rel, $list);

    if ($idx !== false) { array_splice($list, $idx, 1); $state = false; }
    else                { $list[] = $rel;               $state = true;  }

    $config['pinned'] = array_values($list);
    if (!_saveConfig($config)) { http_response_code(500); echo json_encode(['ok' => false, 's' => 'write']); exit; }
    echo json_encode(['ok' => true, 'pinned' => $state]); exit;
}

// ── Folder note ───────────────────────────────────────────────────────────────
if (isset($_GET['_note']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_checkAuth(1)) { http_response_code(403); echo json_encode(['ok' => false]); exit; }

    $dirKey = trim($_POST['d'] ?? '/');
    $text   = trim($_POST['t'] ?? '');
    $config = _loadConfig();
    $notes  = $config['notes'] ?? [];

    if ($text === '') unset($notes[$dirKey]);
    else              $notes[$dirKey] = mb_substr($text, 0, 300);

    $config['notes'] = $notes;
    if (!_saveConfig($config)) { http_response_code(500); echo json_encode(['ok' => false, 's' => 'write']); exit; }
    echo json_encode(['ok' => true]); exit;
}

// ── Change owner password ─────────────────────────────────────────────────────
if (isset($_GET['_changepw']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_checkAuth(1)) { http_response_code(403); echo json_encode(['ok' => false, 's' => 'auth']); exit; }

    $cur = $_POST['cur'] ?? '';
    $new = $_POST['new'] ?? '';

    if (!hash_equals(hash('sha256', base64_decode(_getOwnerPw())), hash('sha256', $cur))) {
        echo json_encode(['ok' => false, 's' => 'wrong']); exit;
    }
    if (strlen($new) < 4) {
        echo json_encode(['ok' => false, 's' => 'short']); exit;
    }

    $config        = _loadConfig();
    $config['_ok'] = base64_encode($new);
    if (!_saveConfig($config)) { http_response_code(500); echo json_encode(['ok' => false, 's' => 'write']); exit; }
    echo json_encode(['ok' => true]); exit;
}

// ── Delete file(s) ────────────────────────────────────────────────────────────
if (isset($_GET['_delete']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_checkAuth(1)) { http_response_code(403); echo json_encode(['ok' => false, 's' => 'auth']); exit; }

    $paths = json_decode($_POST['f'] ?? '[]', true);
    if (!is_array($paths) || empty($paths)) {
        echo json_encode(['ok' => false, 's' => 'empty']); exit;
    }

    // Bulk delete requires password confirmation
    if (count($paths) > 1) {
        $pw   = $_POST['pw'] ?? '';
        $ok1  = hash_equals(hash('sha256', base64_decode(_getOwnerPw())), hash('sha256', $pw));
        $ok2  = hash_equals(hash('sha256', base64_decode(_APP_KEY)),      hash('sha256', $pw));
        if (!$ok1 && !$ok2) { echo json_encode(['ok' => false, 's' => 'pw']); exit; }
    }

    $config  = _loadConfig();
    $deleted = [];
    foreach ($paths as $rel) {
        $rel = str_replace('..', '', $rel);
        $abs = realpath($root . DIRECTORY_SEPARATOR . $rel);
        if (!$abs || !str_starts_with($abs, $root) || !is_file($abs)) continue;
        if (@unlink($abs)) {
            $deleted[]          = $rel;
            $config['unlocked'] = array_values(array_filter($config['unlocked'] ?? [], fn($u) => $u !== $rel));
            $config['pinned']   = array_values(array_filter($config['pinned']   ?? [], fn($p) => $p !== $rel));
        }
    }
    if (!empty($deleted)) _saveConfig($config);
    echo json_encode(['ok' => true, 'deleted' => $deleted]); exit;
}

// ── Upload ────────────────────────────────────────────────────────────────────
if (isset($_GET['_upload']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    if (!_checkAuth(1)) { http_response_code(403); echo json_encode(['ok' => false, 's' => 'auth']); exit; }

    $absDir = isset($_POST['d']) && $_POST['d'] !== ''
        ? realpath($root . DIRECTORY_SEPARATOR . $_POST['d'])
        : $root;

    if (!$absDir || !str_starts_with($absDir, $root) || !is_dir($absDir)) {
        http_response_code(400); echo json_encode(['ok' => false, 's' => 'dir']); exit;
    }

    $results = [];
    foreach ($_FILES['f']['name'] ?? [] as $i => $name) {
        if ($_FILES['f']['error'][$i] !== UPLOAD_ERR_OK) {
            $results[] = ['n' => $name, 'ok' => false]; continue;
        }
        // Sanitise filename and whitelist allowed extensions
        $safe = preg_replace('/[^a-zA-Z0-9._\-]/', '_', basename($name));
        if (!preg_match('/\.(php|html?|css|js|txt|json|md)$/i', $safe)) {
            $results[] = ['n' => $name, 'ok' => false, 's' => 'type']; continue;
        }
        $ok        = move_uploaded_file($_FILES['f']['tmp_name'][$i], $absDir . DIRECTORY_SEPARATOR . $safe);
        $results[] = ['n' => $safe, 'ok' => $ok];
    }
    echo json_encode(['ok' => true, 'files' => $results]); exit;
}

// ── Page rendering ────────────────────────────────────────────────────────────
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

$darkMode    = isset($_COOKIE['dk']) && $_COOKIE['dk'] === '1';
$isDefaultPw = hash('sha256', base64_decode(_getOwnerPw())) === hash('sha256', 'owner123');
$wsState     = _loadConfig();
$unlocked    = $wsState['unlocked'] ?? [];
$pinned      = $wsState['pinned']   ?? [];
$notes       = $wsState['notes']    ?? [];

// index.php is always accessible (it's the manager itself)
if (!in_array('index.php', $unlocked)) $unlocked[] = 'index.php';

$viewLevel    = _getLevel();
$sessionToken = ($viewLevel > 0 && !empty($_SESSION['ws_token'])) ? $_SESSION['ws_token'] : '';
$sessionLevel = $viewLevel;
$relCurDir    = ($current === $root)
    ? '/'
    : str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $current));
$curNote      = $notes[$relCurDir] ?? '';

// ── Page helper functions ────────────────────────────────────────────────────

/** Recursively checks whether a directory contains at least one unlocked file. */
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

/** Builds the breadcrumb path array for the current directory. */
function buildBreadcrumb(string $root, string $current): array {
    $crumbs = [['label' => 'Root', 'path' => $root]];
    $rel    = str_replace($root, '', $current);
    $parts  = array_filter(explode(DIRECTORY_SEPARATOR, $rel));
    $acc    = $root;
    foreach ($parts as $p) {
        $acc      .= DIRECTORY_SEPARATOR . $p;
        $crumbs[] = ['label' => $p, 'path' => $acc];
    }
    return $crumbs;
}

/** Renders the sidebar folder tree recursively. */
function listFolders(string $dir, string $root, int $level = 0, array $unlocked = [], int $vl = 0): void {
    $items = @scandir($dir);
    if (!$items) return;

    $currentPath = isset($_GET['dir']) ? (realpath($_GET['dir']) ?: $root) : $root;
    $folders     = [];

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (!is_dir($full)) continue;
        if ($vl === 0 && !hasUnlockedFiles($full, $root, $unlocked)) continue;
        $folders[] = ['name' => $item, 'path' => $full];
    }
    if (!$folders) return;

    echo '<ul class="fl">';
    foreach ($folders as $f) {
        $rp       = realpath($f['path']) ?: '';
        $isActive = ($currentPath === $rp);
        $isOpen   = $rp && str_starts_with((string)$currentPath, $rp);
        $id       = 'f' . md5($f['path']);
        $hasSub   = false;

        foreach ((@scandir($f['path']) ?: []) as $s) {
            if ($s !== '.' && $s !== '..' && is_dir($f['path'] . DIRECTORY_SEPARATOR . $s)) {
                if ($vl > 0 || hasUnlockedFiles($f['path'] . DIRECTORY_SEPARATOR . $s, $root, $unlocked)) {
                    $hasSub = true; break;
                }
            }
        }

        echo '<li class="fi"><div class="fr' . ($isActive ? ' active' : '') . '">';
        if ($hasSub) {
            echo '<button class="tb" data-bs-toggle="collapse" data-bs-target="#' . $id . '" aria-expanded="' . ($isOpen ? 'true' : 'false') . '">'
               . '<i class="bi bi-chevron-right ti' . ($isOpen ? ' open' : '') . '"></i></button>';
        } else {
            echo '<span class="ts"></span>';
        }
        echo '<a class="fl-link" href="?dir=' . urlencode($f['path']) . '">'
           . '<i class="bi bi-folder' . ($isOpen ? '-open' : '') . ' fic"></i> '
           . htmlspecialchars($f['name']) . '</a>';
        echo '</div>';

        if ($hasSub) {
            echo '<div class="collapse' . ($isOpen ? ' show' : '') . '" id="' . $id . '">';
            listFolders($f['path'], $root, $level + 1, $unlocked, $vl);
            echo '</div>';
        }
        echo '</li>';
    }
    echo '</ul>';
}

/** Returns all non-hidden files directly in $dir. */
function getFiles(string $dir): array {
    $out = [];
    foreach (@scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') continue;
        if ($item[0] === '.') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_file($full))
            $out[] = ['path' => $full, 'size' => (int)filesize($full), 'mtime' => (int)filemtime($full)];
    }
    return $out;
}

/** Recursively collects all non-hidden files under $dir. */
function getFilesRecursive(string $dir): array {
    $out = [];
    foreach (@scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') continue;
        if ($item[0] === '.') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full))
            $out = array_merge($out, getFilesRecursive($full));
        elseif (is_file($full))
            $out[] = ['path' => $full, 'size' => (int)filesize($full), 'mtime' => (int)filemtime($full)];
    }
    return $out;
}

/** Human-readable file size. */
function fmtSize(int $b): string {
    if ($b < 1024)    return $b . ' B';
    if ($b < 1048576) return round($b / 1024, 1) . ' KB';
    return round($b / 1048576, 1) . ' MB';
}

/** Returns Bootstrap Icon class for a filename. */
function _fileIco(string $name): string {
    return match(strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
        'php'                                     => 'bi-filetype-php ep',
        'html','htm'                              => 'bi-filetype-html eh',
        'css'                                     => 'bi-filetype-css ec',
        'js','mjs','cjs'                          => 'bi-filetype-js ej',
        'json'                                    => 'bi-filetype-json ej',
        'md'                                      => 'bi-filetype-md',
        'txt'                                     => 'bi-filetype-txt',
        'xml'                                     => 'bi-filetype-xml',
        'svg'                                     => 'bi-filetype-svg',
        'png','jpg','jpeg','gif','webp','ico','bmp' => 'bi-file-image ei',
        'zip','tar','gz','rar','7z','bz2'         => 'bi-file-zip',
        'pdf'                                     => 'bi-file-pdf',
        'sql'                                     => 'bi-file-earmark-text',
        default                                   => 'bi-file-earmark',
    };
}

// Build file list for current directory
$directFiles = getFiles($current);
$files       = empty($directFiles) ? getFilesRecursive($current) : $directFiles;

// Guests only see explicitly unlocked files
if ($viewLevel === 0) {
    $files = array_values(array_filter($files, function ($f) use ($unlocked, $root) {
        $rel = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $f['path']));
        return in_array($rel, $unlocked);
    }));
}

// Pinned files first, then alphabetical
usort($files, function ($a, $b) use ($pinned, $root) {
    $ra = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $a['path']));
    $rb = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($root . DIRECTORY_SEPARATOR, '', $b['path']));
    $pa = in_array($ra, $pinned) ? 0 : 1;
    $pb = in_array($rb, $pinned) ? 0 : 1;
    return $pa !== $pb ? $pa - $pb : strcmp($ra, $rb);
});

$phpCount  = count(array_filter($files, fn($f) => str_ends_with(strtolower($f['path']), '.php')));
$htmlCount = count(array_filter($files, fn($f) => preg_match('/\.html?$/i', $f['path'])));
$otherCount = count($files) - $phpCount - $htmlCount;
$breadcrumb = buildBreadcrumb($root, $current);
$diskFree  = @disk_free_space($root) ?: 0;
$diskTotal = @disk_total_space($root) ?: 0;
$diskUsed  = $diskTotal - $diskFree;
$diskPct   = $diskTotal > 0 ? round($diskUsed / $diskTotal * 100) : 0;

?>
<!DOCTYPE html>
<html lang="de" data-theme="<?= $darkMode ? 'dark' : 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>UserName &middot; Explorer</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css" rel="stylesheet">
<style>
:root{--th:56px;--sw:270px;--tr:.18s;--rmd:10px;--rlg:14px}
[data-theme=light]{--bg:#f0f2f8;--sur:#fff;--sbg:#1e2333;--stxt:#c8cfe8;--sho:rgba(255,255,255,.08);--sac:rgba(99,131,255,.25);--sat:#a5b4fc;--txt:#1a1d2e;--mut:#6b7280;--brd:#e2e6f0;--hov:#eef1fb;--top:linear-gradient(135deg,#1e2333,#2d3561);--ttxt:#e2e8ff;--bbg:#eef1fb;--btxt:#4b5680;--rho:#f0f3ff;--sha:0 1px 4px rgba(0,0,0,.08),0 6px 16px rgba(0,0,0,.05)}
[data-theme=dark]{--bg:#0c0e16;--sur:#141728;--sbg:#0e1019;--stxt:#9ba3c0;--sho:rgba(255,255,255,.05);--sac:rgba(99,131,255,.2);--sat:#a5b4fc;--txt:#d1d5e8;--mut:#6b7280;--brd:#252840;--hov:#1a1d30;--top:linear-gradient(135deg,#0d0f18,#1a1d2e);--ttxt:#c8cfe8;--bbg:#181b2d;--btxt:#7b87c0;--rho:#1c1f33;--sha:0 2px 6px rgba(0,0,0,.5),0 8px 20px rgba(0,0,0,.35)}
*,*::before,*::after{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--txt);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;transition:background var(--tr),color var(--tr)}
.topbar{position:fixed;top:0;left:0;right:0;height:var(--th);background:var(--top);display:flex;align-items:center;justify-content:space-between;padding:0 1.25rem;z-index:1000;box-shadow:0 2px 12px rgba(0,0,0,.4)}
.tb-brand{display:flex;align-items:center;gap:.6rem;color:var(--ttxt);font-weight:700;font-size:1.1rem}
.logo-ico{width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#6366f1,#818cf8);display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;flex-shrink:0;box-shadow:0 2px 8px rgba(99,102,241,.4)}
.brand-nm{cursor:pointer;border-radius:5px;padding:2px 6px;transition:background var(--tr);user-select:none;color:var(--ttxt)}
.brand-nm:hover{background:rgba(255,255,255,.14)}
.brand-nm::after{content:'\270E';font-size:.6rem;margin-left:.3rem;opacity:0;transition:opacity var(--tr);vertical-align:super}
.brand-nm:hover::after{opacity:.5}
.brand-inp{background:rgba(255,255,255,.1);border:1.5px solid rgba(255,255,255,.35);border-radius:5px;color:var(--ttxt);font-size:1.05rem;font-weight:700;width:150px;padding:2px 7px;outline:none;transition:border-color var(--tr)}
.brand-inp:focus{border-color:rgba(255,255,255,.6)}
.tb-right{display:flex;align-items:center;gap:.6rem}
.dark-btn{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:.3rem .75rem;color:var(--ttxt);font-size:.8rem;cursor:pointer;display:flex;align-items:center;gap:.4rem;transition:background var(--tr);white-space:nowrap}
.dark-btn:hover{background:rgba(255,255,255,.18)}
.iam-btn{background:rgba(99,102,241,.18);border:1px solid rgba(99,102,241,.4);border-radius:20px;padding:.3rem .75rem;color:#a5b4fc;font-size:.8rem;cursor:pointer;display:flex;align-items:center;gap:.4rem;transition:background var(--tr),box-shadow var(--tr);white-space:nowrap}
.iam-btn:hover{background:rgba(99,102,241,.32);box-shadow:0 0 0 3px rgba(99,102,241,.18)}
.iam-btn.hidden{display:none}
.dk-light,.dk-dark{display:none}
[data-theme=light] .dk-light{display:inline}
[data-theme=dark]  .dk-dark{display:inline}
.lvl-badge{display:none;align-items:center;gap:.3rem;background:rgba(99,102,241,.2);border:1px solid rgba(99,102,241,.4);border-radius:20px;padding:.25rem .65rem;font-size:.72rem;font-weight:700;color:#a5b4fc;transition:background var(--tr),border-color var(--tr),color var(--tr)}
.lvl-badge.show{display:flex}
/* Update notification banner */
.upd-banner{position:fixed;left:50%;transform:translateX(-50%);bottom:3.2rem;background:linear-gradient(90deg,#1e1b4b,#312e81);border:1px solid rgba(99,102,241,.5);border-radius:30px;padding:.45rem .9rem .45rem 1rem;display:flex;align-items:center;gap:.6rem;font-size:.8rem;font-weight:600;color:#c7d2fe;box-shadow:0 8px 28px rgba(0,0,0,.55);z-index:950;opacity:0;pointer-events:none;transition:opacity .3s,bottom .3s;white-space:nowrap}
.upd-banner.show{opacity:1;pointer-events:auto;bottom:3.6rem}
.upd-install{background:#4f46e5;border:none;border-radius:14px;padding:.25rem .8rem;color:#fff;font-size:.75rem;font-weight:700;cursor:pointer;transition:background var(--tr)}
.upd-install:hover{background:#4338ca}
.upd-dismiss{background:transparent;border:none;color:rgba(199,210,254,.5);font-size:1rem;cursor:pointer;display:flex;align-items:center;padding:0 .15rem;transition:color var(--tr);line-height:1}
.upd-dismiss:hover{color:#c7d2fe}
.layout{display:flex;padding-top:var(--th);height:100vh}
.sidebar{width:var(--sw);flex-shrink:0;background:var(--sbg);height:calc(100vh - var(--th));overflow-y:auto;padding:1rem .75rem;border-right:1px solid rgba(255,255,255,.04)}
.sidebar::-webkit-scrollbar{width:3px}
.sidebar::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:2px}
.sb-lbl{font-size:.63rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:rgba(255,255,255,.22);padding:.5rem .5rem .3rem}
.root-lnk{display:flex;align-items:center;gap:.5rem;padding:.4rem .65rem;border-radius:7px;color:var(--stxt);text-decoration:none;font-size:.875rem;transition:background var(--tr),color var(--tr)}
.root-lnk:hover{background:var(--sho);color:#fff}
.root-lnk.active{background:var(--sac);color:var(--sat);font-weight:600}
.fl{list-style:none;margin:0;padding:0}
.fi{margin:1px 0}
.fr{display:flex;align-items:center;border-radius:7px;transition:background var(--tr)}
.fr:hover{background:var(--sho)}
.fr.active{background:var(--sac)}
.fr.active .fl-link{color:var(--sat);font-weight:600}
.tb{background:none;border:none;padding:0;width:22px;height:22px;flex-shrink:0;display:flex;align-items:center;justify-content:center;cursor:pointer;color:rgba(255,255,255,.28);border-radius:4px;margin:2px 2px 2px 4px;transition:background var(--tr),color var(--tr)}
.tb:hover{background:rgba(255,255,255,.1);color:#fff}
.ts{width:22px;flex-shrink:0;margin:2px 2px 2px 4px}
.ti{font-size:.7rem;transition:transform .2s}
.ti.open{transform:rotate(90deg)}
.fl-link{display:flex;align-items:center;gap:.4rem;padding:.35rem .5rem .35rem .1rem;color:var(--stxt);text-decoration:none;font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;transition:color var(--tr)}
.fl-link:hover{color:#fff}
.fic{font-size:.9rem;opacity:.65;flex-shrink:0}
.fl .fl{padding-left:1.1rem}
.main{flex:1;overflow-y:auto;padding:1.75rem 2rem}
.main::-webkit-scrollbar{width:5px}
.main::-webkit-scrollbar-thumb{background:var(--brd);border-radius:3px}
.bc{display:flex;align-items:center;gap:.35rem;margin-bottom:1.5rem;flex-wrap:wrap}
.bc-a{color:var(--mut);text-decoration:none;font-size:.85rem;padding:.2rem .5rem;border-radius:6px;transition:background var(--tr),color var(--tr)}
.bc-a:hover{background:var(--hov);color:var(--txt)}
.bc-sep{color:var(--mut);font-size:.75rem;opacity:.6}
.bc-cur{color:var(--txt);font-size:.85rem;font-weight:600;padding:.2rem .5rem;background:var(--hov);border-radius:6px}
.note-bar{display:flex;align-items:flex-start;gap:.65rem;background:rgba(251,191,36,.07);border:1px solid rgba(251,191,36,.2);border-radius:var(--rmd);padding:.8rem 1rem;margin-bottom:1.25rem;transition:border-color var(--tr)}
.note-bar:focus-within{border-color:rgba(251,191,36,.45)}
.note-bar.hidden{display:none}
.note-ico{color:#f59e0b;font-size:.95rem;margin-top:2px;flex-shrink:0}
.note-txt{flex:1;font-size:.85rem;color:var(--txt);outline:none;background:none;border:none;resize:none;min-height:1.2rem;font-family:inherit;line-height:1.5}
.note-txt[contenteditable=true]{background:rgba(255,255,255,.04);border-radius:4px;padding:2px 5px}
.note-txt:empty::before{content:attr(data-ph);color:var(--mut);pointer-events:none}
.note-acts{display:flex;gap:.3rem;flex-shrink:0}
.note-btn{background:none;border:1px solid var(--brd);border-radius:6px;padding:.2rem .55rem;font-size:.75rem;color:var(--mut);cursor:pointer;transition:background var(--tr),color var(--tr)}
.note-btn:hover{background:var(--hov);color:var(--txt)}
.note-btn.save{border-color:#6366f1;color:#6366f1}
.note-btn.save:hover{background:#6366f1;color:#fff}
.stat-row{display:flex;align-items:center;gap:.65rem;margin-bottom:1.1rem;flex-wrap:wrap}
.sbadge{display:inline-flex;align-items:center;gap:.35rem;background:var(--bbg);color:var(--btxt);font-size:.77rem;font-weight:600;padding:.3rem .75rem;border-radius:20px;border:1px solid var(--brd)}
.ctrl-row{display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap}
.search-wrap{position:relative;flex:1;min-width:180px}
.search-ico{position:absolute;left:.7rem;top:50%;transform:translateY(-50%);color:var(--mut);font-size:.82rem;pointer-events:none}
.search-inp{width:100%;background:var(--sur);border:1.5px solid var(--brd);border-radius:8px;color:var(--txt);padding:.45rem .8rem .45rem 2.1rem;font-size:.85rem;outline:none;transition:border-color var(--tr),box-shadow var(--tr)}
.search-inp:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12)}
.sort-btns{display:flex;gap:.3rem;flex-shrink:0}
.sort-btn{background:var(--bbg);border:1.5px solid var(--brd);border-radius:7px;padding:.3rem .65rem;font-size:.75rem;font-weight:600;color:var(--btxt);cursor:pointer;transition:background var(--tr),color var(--tr),border-color var(--tr);display:flex;align-items:center;gap:.3rem;white-space:nowrap}
.sort-btn:hover{background:var(--hov);color:var(--txt);border-color:var(--mut)}
.sort-btn.active{background:#6366f1;border-color:#6366f1;color:#fff}
.up-zone{display:none;margin-bottom:1.25rem}
#mainContent.level-1 .up-zone,#mainContent.level-2 .up-zone{display:block}
.up-toggle{background:var(--bbg);border:1.5px solid var(--brd);border-radius:8px;padding:.35rem .9rem;font-size:.8rem;font-weight:600;color:var(--btxt);cursor:pointer;display:flex;align-items:center;gap:.45rem;transition:background var(--tr),color var(--tr),border-color var(--tr)}
.up-toggle:hover{background:var(--hov);color:var(--txt);border-color:var(--mut)}
.up-body{margin-top:.6rem;display:none}
.up-body.open{display:block}
.up-drop{border:2px dashed var(--brd);border-radius:var(--rmd);padding:1.5rem 1rem;text-align:center;cursor:pointer;transition:border-color var(--tr),background var(--tr),color var(--tr);color:var(--mut)}
.up-drop:hover,.up-drop.drag{border-color:#6366f1;background:rgba(99,102,241,.05);color:#6366f1}
.up-lbl{color:#6366f1;cursor:pointer;text-decoration:underline}
.up-item{font-size:.8rem;padding:.2rem 0;display:flex;align-items:center;gap:.4rem}
.up-item.ok{color:#10b981}
.up-item.err{color:#ef4444}
.up-status{margin-top:.5rem}
.fcard{background:var(--sur);border:1px solid var(--brd);border-radius:var(--rlg);box-shadow:var(--sha);overflow:hidden}
.ftbl{width:100%;border-collapse:collapse}
.ftbl thead th{padding:.65rem 1rem;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);background:var(--bbg);border-bottom:1.5px solid var(--brd);text-align:left;cursor:pointer;white-space:nowrap;user-select:none;transition:color var(--tr)}
.ftbl thead th:first-child{cursor:default}
.ftbl thead th:hover:not(:first-child){color:var(--txt)}
.ftbl thead th.sorted{color:#6366f1}
.sort-ind{font-size:.65rem;margin-left:.2rem;opacity:.55;vertical-align:middle}
.ftbl tbody tr{border-bottom:1px solid var(--brd);cursor:pointer;transition:background var(--tr)}
.ftbl tbody tr:last-child{border-bottom:none}
.ftbl tbody tr:hover{background:var(--rho)}
.ftbl tbody tr.pinned{background:rgba(99,102,241,.05);border-left:3px solid rgba(99,102,241,.35)}
.ftbl tbody tr.pinned:hover{background:rgba(99,102,241,.1)}
.ftbl tbody td{padding:.7rem 1rem;vertical-align:middle;font-size:.875rem}
.fn{display:flex;align-items:center;gap:.55rem;font-weight:600}
.fm{font-size:.72rem;color:var(--mut);margin-top:2px;font-family:monospace;opacity:.8}
.ep{color:#818cf8;font-size:1.05rem}
.eh{color:#34d399;font-size:1.05rem}
.fp{color:var(--mut);font-size:.8rem;font-family:monospace;opacity:.75}
.pin-ico{color:#f59e0b;font-size:.7rem;margin-left:.25rem;opacity:.8}
.fac{display:flex;align-items:center;gap:.3rem;white-space:nowrap;flex-wrap:wrap}
.btn-lock{display:inline-flex;align-items:center;gap:.3rem;padding:.28rem .6rem;border-radius:6px;font-size:.73rem;font-weight:600;background:transparent;border:1.5px solid var(--brd);color:var(--mut);cursor:default;opacity:.55}
.btn-o,.btn-dl,.btn-tog,.btn-pin,.btn-s{display:none;align-items:center;gap:.3rem;padding:.28rem .6rem;border-radius:6px;font-size:.73rem;font-weight:600;background:transparent;cursor:pointer;transition:background var(--tr),color var(--tr),box-shadow var(--tr);text-decoration:none}
.btn-o{border:1.5px solid #6366f1;color:#6366f1}
.btn-o:hover{background:#6366f1;color:#fff;box-shadow:0 2px 8px rgba(99,102,241,.35)}
.btn-dl{border:1.5px solid #0ea5e9;color:#0ea5e9}
.btn-dl:hover{background:#0ea5e9;color:#fff;box-shadow:0 2px 8px rgba(14,165,233,.35)}
.btn-tog{border:1.5px solid #10b981;color:#10b981}
.btn-tog:hover{background:#10b981;color:#fff;box-shadow:0 2px 8px rgba(16,185,129,.3)}
.file-row.unlocked .btn-tog{border-color:#ef4444;color:#ef4444}
.file-row.unlocked .btn-tog:hover{background:#ef4444;color:#fff;box-shadow:0 2px 8px rgba(239,68,68,.3)}
.btn-pin{border:1.5px solid var(--brd);color:var(--mut)}
.btn-pin:hover{background:var(--hov);color:#f59e0b;border-color:#f59e0b}
.file-row.pinned .btn-pin{color:#f59e0b;border-color:rgba(245,158,11,.4)}
/* Code-button: Owner + Admin (level 1+) */
.btn-s{border:1.5px solid #f59e0b;color:#f59e0b}
.btn-s:hover{background:#f59e0b;color:#fff;box-shadow:0 2px 8px rgba(245,158,11,.35)}
.file-row.unlocked .btn-lock{display:none}
.file-row.unlocked .btn-o{display:inline-flex}
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
/* Code viewer: Owner + Admin (level 1+) */
#mainContent.level-1 .btn-s,
#mainContent.level-2 .btn-s{display:inline-flex}
.note-bar.no-note{display:none}
#mainContent.level-1 .note-bar.no-note,
#mainContent.level-2 .note-bar.no-note{display:flex}
.note-edit-area{display:none}
#mainContent.level-1 .note-edit-area,
#mainContent.level-2 .note-edit-area{display:flex}
.empty{text-align:center;padding:5rem 2rem}
.empty-ico{font-size:3.5rem;color:var(--mut);opacity:.3;margin-bottom:1.25rem}
.empty-ttl{font-size:1.05rem;font-weight:700;margin-bottom:.5rem}
.empty-sub{color:var(--mut);font-size:.875rem;max-width:280px;margin:0 auto}
.toast-n{position:fixed;bottom:1.5rem;right:1.5rem;background:#141728;color:#a5b4fc;border:1px solid rgba(99,102,241,.35);border-left:3px solid #6366f1;border-radius:var(--rmd);padding:.7rem 1.1rem;font-size:.84rem;font-weight:600;display:flex;align-items:center;gap:.6rem;box-shadow:0 10px 30px rgba(0,0,0,.5);transform:translateY(16px) translateX(8px);opacity:0;transition:transform .3s cubic-bezier(.34,1.56,.64,1),opacity .25s;z-index:9999;pointer-events:none;min-width:200px;max-width:320px}
.toast-n.show{transform:translateY(0) translateX(0);opacity:1}
.pw-ov{display:flex;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9500;align-items:center;justify-content:center;backdrop-filter:blur(5px);opacity:0;pointer-events:none;transition:opacity .2s}
.pw-ov.open{opacity:1;pointer-events:auto}
.pw-box{background:#141728;border:1px solid rgba(99,102,241,.22);border-radius:18px;padding:2rem 1.75rem;width:min(340px,92vw);text-align:center;box-shadow:0 28px 72px rgba(0,0,0,.65);transform:scale(.94) translateY(12px);transition:transform .28s cubic-bezier(.34,1.56,.64,1),opacity .2s;opacity:0}
.pw-ov.open .pw-box{transform:scale(1) translateY(0);opacity:1}
.pw-ico{font-size:2.4rem;color:#6366f1;margin-bottom:.75rem}
.pw-ttl{font-size:1rem;font-weight:700;color:#e2e8ff;margin-bottom:1.25rem}
.pw-inp{width:100%;background:rgba(255,255,255,.06);border:1.5px solid rgba(99,102,241,.22);border-radius:9px;color:#e2e8ff;padding:.65rem .9rem;font-size:.9rem;outline:none;transition:border-color var(--tr),box-shadow var(--tr);margin-bottom:.5rem}
.pw-inp:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
.pw-inp.shake{animation:shake .35s;border-color:#ef4444}
.pw-err{color:#f87171;font-size:.78rem;margin-bottom:.75rem;min-height:1.1rem}
.pw-btn{width:100%;background:#6366f1;color:#fff;border:none;border-radius:9px;padding:.65rem;font-size:.9rem;font-weight:600;cursor:pointer;transition:background var(--tr),box-shadow var(--tr)}
.pw-btn:hover{background:#4f46e5;box-shadow:0 4px 14px rgba(99,102,241,.4)}
@keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-6px)}40%,80%{transform:translateX(6px)}}
.cd-ov{display:flex;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9000;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .2s}
.cd-ov.open{opacity:1;pointer-events:auto}
.cd-box{background:#141728;border-radius:var(--rlg);width:min(920px,96vw);max-height:88vh;display:flex;flex-direction:column;box-shadow:0 28px 72px rgba(0,0,0,.65);overflow:hidden;border:1px solid rgba(255,255,255,.07);transform:scale(.96) translateY(14px);transition:transform .28s cubic-bezier(.34,1.56,.64,1),opacity .2s;opacity:0}
.cd-ov.open .cd-box{transform:scale(1) translateY(0);opacity:1}
.cd-head{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.35rem;background:#0e1019;border-bottom:1px solid rgba(255,255,255,.07)}
.cd-ttl{font-size:.875rem;font-weight:700;color:#a5b4fc;display:flex;align-items:center;gap:.5rem}
.cd-acts{display:flex;gap:.5rem}
.cbtn{padding:.3rem .75rem;border-radius:7px;font-size:.75rem;font-weight:600;border:none;cursor:pointer;transition:background var(--tr)}
.cbtn-cp{background:rgba(99,102,241,.18);color:#a5b4fc}
.cbtn-cp:hover{background:rgba(99,102,241,.32)}
.cbtn-cl{background:rgba(239,68,68,.12);color:#fca5a5}
.cbtn-cl:hover{background:rgba(239,68,68,.28)}
.cd-body{flex:1;overflow-y:auto;padding:1rem}
.cd-body::-webkit-scrollbar{width:5px}
.cd-body::-webkit-scrollbar-thumb{background:rgba(255,255,255,.12);border-radius:3px}
.cd-body pre{margin:0}
.cd-body pre code{border-radius:9px}
.ver{position:fixed;bottom:.65rem;left:50%;transform:translateX(-50%);font-size:.68rem;color:var(--mut);opacity:.35;z-index:100;cursor:pointer;user-select:none;transition:opacity var(--tr);padding:.2rem .5rem;border-radius:10px}
.ver:hover{opacity:.85}
.no-res{text-align:center;padding:2rem;color:var(--mut);font-size:.875rem;display:none}
.bulk-bar{position:fixed;bottom:2rem;left:50%;transform:translateX(-50%);background:#141728;border:1px solid rgba(99,102,241,.3);border-radius:30px;padding:.5rem 1rem;display:none;align-items:center;gap:.75rem;z-index:900;box-shadow:0 10px 30px rgba(0,0,0,.5)}
.bulk-bar.show{display:flex}
.bulk-cnt{font-size:.8rem;font-weight:600;color:#a5b4fc}
.bulk-del{background:#ef4444;color:#fff;border:none;border-radius:20px;padding:.3rem .9rem;font-size:.8rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:.3rem;transition:background var(--tr),box-shadow var(--tr)}
.bulk-del:hover{background:#dc2626;box-shadow:0 3px 10px rgba(239,68,68,.4)}
.bulk-cancel{background:transparent;color:var(--mut);border:1px solid var(--brd);border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.8rem;transition:background var(--tr),color var(--tr)}
.bulk-cancel:hover{background:var(--hov);color:var(--txt)}
.cb-col{width:36px}
.fchk{accent-color:#6366f1;width:14px;height:14px;cursor:pointer}
@media(max-width:768px){:root{--sw:220px}.main{padding:1rem 1.25rem}.fp{display:none}.fm{display:none}}
@media(max-width:540px){:root{--sw:0px}.sidebar{display:none}.sort-btns{display:none}}
.disk-badge{gap:.5rem}
.disk-bar-wrap{width:52px;height:6px;background:rgba(255,255,255,.1);border-radius:3px;overflow:hidden;display:inline-block;vertical-align:middle}
.disk-bar-fill{height:100%;background:linear-gradient(90deg,#6366f1,#818cf8);border-radius:3px;transition:width .4s}
.pw-strength{display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;margin-top:.15rem;min-height:18px}
.pw-str-track{flex:1;height:4px;background:rgba(255,255,255,.08);border-radius:2px;overflow:hidden}
.pw-str-fill{height:100%;border-radius:2px;transition:width .3s,background .3s;width:0}
.pw-str-lbl{font-size:.7rem;font-weight:600;color:var(--mut);min-width:60px;text-align:right;transition:color .3s}
.bulk-zip{background:rgba(99,102,241,.18);color:#a5b4fc;border:none;border-radius:20px;padding:.3rem .9rem;font-size:.8rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:.3rem;transition:background var(--tr)}
.bulk-zip:hover{background:rgba(99,102,241,.35)}
.folder-row td:first-child+td{font-weight:600}
.zip-tree{list-style:none;margin:0;padding:0}
.zip-tree li{padding:2px 0}
.zip-tree .zt-dir>label{font-weight:600;color:var(--txt)}
.zip-tree .zt-file>label{color:var(--mut)}
.zip-tree label{display:flex;align-items:center;gap:.4rem;cursor:pointer;border-radius:5px;padding:3px 5px;transition:background var(--tr)}
.zip-tree label:hover{background:var(--hov)}
.zip-tree .zt-cb{accent-color:#6366f1;cursor:pointer}
.zip-tree .zip-tree{padding-left:1.2rem}
.zt-tog{background:none;border:none;color:var(--mut);cursor:pointer;padding:0 3px;font-size:.7rem;transition:transform .2s}
.zt-tog.open{transform:rotate(90deg)}
</style>
</head>
<body>

<header class="topbar">
  <div class="tb-brand">
    <div class="logo-ico"><i class="bi bi-folder2-open"></i></div>
    <span class="brand-nm" id="brandNm" title="Klicken zum Umbenennen">UserName</span>
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

<div id="updBanner" class="upd-banner">
  <i class="bi bi-cloud-arrow-down-fill" style="color:#818cf8;flex-shrink:0"></i>
  <span id="updBannerTxt"></span>
  <button class="upd-install" onclick="triggerUpdate()">Installieren</button>
  <button class="upd-dismiss" onclick="document.getElementById('updBanner').classList.remove('show')" title="Schlie&szlig;en"><i class="bi bi-x-lg"></i></button>
</div>

<div class="layout">
  <nav class="sidebar">
    <div class="sb-lbl">Ordner</div>
    <a class="root-lnk <?= $current === $root ? 'active' : '' ?>" href="?">
      <i class="bi bi-house-door"></i> Root
    </a>
    <?php listFolders($root, $root, 0, $unlocked, $viewLevel); ?>
  </nav>

  <main class="main level-<?= $viewLevel ?>" id="mainContent">

    <?php if ($error): ?>
    <div style="color:#ef4444;margin-bottom:1rem"><?= $error ?></div>
    <?php endif; ?>

    <!-- Breadcrumb -->
    <nav class="bc">
      <?php foreach ($breadcrumb as $i => $crumb): ?>
        <?php if ($i < count($breadcrumb) - 1): ?>
          <a class="bc-a" href="?dir=<?= urlencode($crumb['path']) ?>"><?= htmlspecialchars($crumb['label']) ?></a>
          <span class="bc-sep"><i class="bi bi-chevron-right"></i></span>
        <?php else: ?>
          <span class="bc-cur"><?= htmlspecialchars($crumb['label']) ?></span>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>

    <!-- Folder note banner -->
    <div class="note-bar <?= $curNote === '' ? 'no-note' : '' ?>" id="noteBar">
      <i class="bi bi-sticky note-ico"></i>
      <div class="note-txt" id="noteTxt" contenteditable="false"
           data-ph="Notiz f&uuml;r diesen Ordner ..."><?= htmlspecialchars($curNote) ?></div>
      <div class="note-acts">
        <div class="note-edit-area">
          <button class="note-btn" id="noteEditBtn" onclick="startNote()">Bearbeiten</button>
          <button class="note-btn save" id="noteSaveBtn" style="display:none" onclick="saveNote()">Speichern</button>
          <button class="note-btn" id="noteCancelBtn" style="display:none" onclick="cancelNote()">Abbrechen</button>
        </div>
      </div>
    </div>

    <!-- Stats row -->
    <div class="stat-row">
      <span class="sbadge"><i class="bi bi-filetype-php ep"></i> <?= $phpCount ?> PHP</span>
      <span class="sbadge"><i class="bi bi-filetype-html eh"></i> <?= $htmlCount ?> HTML</span>
      <?php if ($otherCount > 0): ?>
      <span class="sbadge"><i class="bi bi-file-earmark"></i> <?= $otherCount ?> Weitere</span>
      <?php endif; ?>
      <?php if ($viewLevel >= 1 && $diskTotal > 0): ?>
      <span class="sbadge disk-badge" title="<?= fmtSize((int)$diskUsed) ?> von <?= fmtSize((int)$diskTotal) ?> belegt">
        <i class="bi bi-hdd"></i>
        <span class="disk-bar-wrap"><span class="disk-bar-fill" style="width:<?= $diskPct ?>%"></span></span>
        <?= fmtSize((int)$diskFree) ?> frei
      </span>
      <?php endif; ?>
    </div>

    <!-- Upload zone -->
    <div class="up-zone">
      <button class="up-toggle" onclick="toggleUpload()">
        <i class="bi bi-cloud-upload"></i> Dateien hochladen
        <i class="bi bi-chevron-down" id="upChev" style="transition:transform .2s"></i>
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
            $rel     = str_replace($root . DIRECTORY_SEPARATOR, '', $file['path']);
            $relPath = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
            $relUrl  = $relPath;
            $base    = basename($file['path']);
            $ico = _fileIco($base);
            $isPhp = str_ends_with(strtolower($base), '.php');
            $isCode = (bool)preg_match('/\.(php|html?|css|js|mjs|json|txt|md|xml|svg|sql)$/i', $base);
            $dir     = dirname($rel);
            $dsp     = ($dir === '.' ? '/' : '/' . str_replace(DIRECTORY_SEPARATOR, '/', $dir));
            $isUL    = in_array($relPath, $unlocked);
            $isPin   = in_array($relPath, $pinned);
            $sz      = fmtSize($file['size']);
            $dt      = date('d.m.Y H:i', $file['mtime']);
            $rowCls  = ($isUL ? ' unlocked' : '') . ($isPin ? ' pinned' : '');
        ?>
        <tr class="file-row<?= $rowCls ?>"
            data-url="<?= htmlspecialchars($relUrl) ?>"
            data-name="<?= htmlspecialchars(strtolower($base)) ?>"
            data-size="<?= $file['size'] ?>"
            data-date="<?= $file['mtime'] ?>"
            data-path="<?= htmlspecialchars($relPath) ?>"
            data-ext="<?= strtolower(pathinfo($base, PATHINFO_EXTENSION)) ?>">
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
                title="Quellcode anzeigen"
                <?= $isCode ? '' : 'style="display:none!important"' ?>>
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

<!-- Bulk action toolbar -->
<div class="bulk-bar" id="bulkBar">
  <span class="bulk-cnt" id="bulkCnt">0 ausgew&auml;hlt</span>
  <button class="bulk-zip" onclick="openZipModal()"><i class="bi bi-file-zip"></i> ZIP</button>
  <button class="bulk-del" onclick="confirmDelete()"><i class="bi bi-trash3"></i> L&ouml;schen</button>
  <button class="bulk-cancel" onclick="clearSel()"><i class="bi bi-x"></i></button>
</div>

<!-- Image preview modal -->
<div class="pw-ov" id="imgOv" onclick="if(event.target===this)closeImg()">
  <div style="position:relative;display:flex;flex-direction:column;align-items:center">
    <img id="imgEl" src="" alt="" style="max-width:90vw;max-height:82vh;border-radius:12px;object-fit:contain;box-shadow:0 24px 64px rgba(0,0,0,.7)">
    <div id="imgCaption" style="color:rgba(255,255,255,.5);font-size:.78rem;margin-top:.6rem"></div>
    <button onclick="closeImg()" style="position:absolute;top:-14px;right:-14px;background:#1e2130;border:1px solid rgba(255,255,255,.15);border-radius:50%;width:30px;height:30px;color:#a5b4fc;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem"><i class="bi bi-x-lg"></i></button>
  </div>
</div>

<!-- ZIP download modal -->
<div class="pw-ov" id="zipOv" onclick="if(event.target===this)closeZipModal()">
  <div class="pw-box" style="width:min(520px,96vw);text-align:left">
    <div class="pw-ico" style="text-align:center"><i class="bi bi-file-zip"></i></div>
    <div class="pw-ttl" style="text-align:center">ZIP herunterladen</div>
    <p style="font-size:.8rem;color:var(--mut);margin:0 0 .75rem">Auswahl aufheben = wird nicht gepackt.</p>
    <div id="zipTree" style="max-height:340px;overflow-y:auto;margin-bottom:1rem;font-size:.83rem"></div>
    <div id="zipErr" style="color:#f87171;font-size:.78rem;min-height:1.1rem;margin-bottom:.4rem"></div>
    <button class="pw-btn" onclick="execZip()"><i class="bi bi-download"></i> Herunterladen</button>
    <button class="pw-btn" onclick="closeZipModal()" style="margin-top:.5rem;background:transparent;border:1px solid rgba(255,255,255,.15);color:var(--mut);font-size:.8rem">Abbrechen</button>
  </div>
</div>

<!-- Delete confirm modal -->
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

<!-- Login modal -->
<div class="pw-ov" id="pwOv">
  <div class="pw-box">
    <div class="pw-ico"><i class="bi bi-shield-lock"></i></div>
    <div class="pw-ttl">Anmelden</div>
    <input type="password" id="pwInp" class="pw-inp" placeholder="Passwort" autocomplete="off" spellcheck="false">
    <div class="pw-err" id="pwErr"></div>
    <button class="pw-btn" id="pwBtn">Best&auml;tigen</button>
  </div>
</div>

<!-- Change password modal -->
<div class="pw-ov" id="cpwOv">
  <div class="pw-box">
    <div class="pw-ico"><i class="bi bi-key"></i></div>
    <div class="pw-ttl" id="cpwTtl">Passwort &auml;ndern</div>
    <input type="password" id="cpwCur" class="pw-inp" placeholder="Aktuelles Passwort" autocomplete="off">
    <input type="password" id="cpwNew" class="pw-inp" placeholder="Neues Passwort (mind. 4 Zeichen)" autocomplete="new-password" style="margin-top:.4rem" oninput="updatePwStrength(this.value)">
    <div class="pw-strength" id="pwStrengthBar">
      <div class="pw-str-track"><div class="pw-str-fill" id="pwStrFill"></div></div>
      <span class="pw-str-lbl" id="pwStrLbl"></span>
    </div>
    <input type="password" id="cpwCon" class="pw-inp" placeholder="Neues Passwort best&auml;tigen" autocomplete="new-password" style="margin-top:.4rem">
    <div class="pw-err" id="cpwErr"></div>
    <button class="pw-btn" id="cpwBtn">Speichern</button>
    <button class="pw-btn" id="cpwSkip" style="margin-top:.5rem;background:transparent;border:1px solid rgba(255,255,255,.15);color:var(--mut);font-size:.8rem" onclick="closeCpw()">Sp&auml;ter</button>
  </div>
</div>

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
// ── App state ────────────────────────────────────────────────────────────────
const _html     = document.documentElement;
const _mc       = document.getElementById('mainContent');
const _csrf     = '<?= $csrf ?>';
const _curDir   = <?= json_encode($relCurDir) ?>;
const _defPw    = <?= $isDefaultPw ? 'true' : 'false' ?>;
const _phpToken = '<?= $sessionToken ?>';
const _phpLevel = <?= $sessionLevel ?>;

// ── Dark mode ────────────────────────────────────────────────────────────────
document.getElementById('darkBtn').addEventListener('click', () => {
  const dark = _html.getAttribute('data-theme') !== 'dark';
  _html.setAttribute('data-theme', dark ? 'dark' : 'light');
  document.cookie = 'dk=' + (dark ? 1 : 0) + ';path=/;max-age=315360000;SameSite=Lax';
});

// ── Sidebar collapse chevrons ────────────────────────────────────────────────
document.querySelectorAll('.tb').forEach(btn => {
  btn.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); });
  const tgt = document.querySelector(btn.dataset.bsTarget);
  if (!tgt) return;
  tgt.addEventListener('show.bs.collapse', () => btn.querySelector('.ti')?.classList.add('open'));
  tgt.addEventListener('hide.bs.collapse', () => btn.querySelector('.ti')?.classList.remove('open'));
});

// ── Editable brand name ──────────────────────────────────────────────────────
(function () {
  const stored = localStorage.getItem('ws_brand');
  if (stored) {
    document.getElementById('brandNm').textContent = stored;
    document.title = stored + ' \u00B7 Explorer';
  }
})();
document.querySelector('.tb-brand').addEventListener('click', e => {
  const tgt = e.target.closest('#brandNm');
  if (!tgt) return;
  const inp = document.createElement('input');
  inp.type = 'text'; inp.value = tgt.textContent;
  inp.className = 'brand-inp'; inp.maxLength = 30;
  tgt.replaceWith(inp); inp.focus(); inp.select();
  let done = false;
  function save() {
    if (done) return; done = true;
    const val = inp.value.trim() || 'UserName';
    localStorage.setItem('ws_brand', val);
    document.title = val + ' \u00B7 Explorer';
    const sp = document.createElement('span');
    sp.id = 'brandNm'; sp.className = 'brand-nm';
    sp.title = 'Klicken zum Umbenennen'; sp.textContent = val;
    inp.replaceWith(sp);
  }
  inp.addEventListener('blur', save);
  inp.addEventListener('keydown', e => {
    if (e.key === 'Enter')  inp.blur();
    if (e.key === 'Escape') { done = true; inp.replaceWith(tgt); }
  });
});

// ── Toast notifications ──────────────────────────────────────────────────────
let _toastTimer;
function showToast(msg, icon, dur) {
  clearTimeout(_toastTimer);
  document.getElementById('toastIco').className = 'bi ' + (icon || 'bi-info-circle');
  document.getElementById('toastMsg').textContent = msg;
  const t = document.getElementById('toastEl');
  t.classList.add('show');
  if (dur !== 99999) _toastTimer = setTimeout(() => t.classList.remove('show'), dur || 3200);
}
function hideToast() {
  clearTimeout(_toastTimer);
  document.getElementById('toastEl').classList.remove('show');
}

// ── Authentication / session levels ──────────────────────────────────────────
let userLevel = 0;
let _token    = sessionStorage.getItem('ws_token') || '';

// If the PHP session expired but sessionStorage still has a token, clear it
if (_phpLevel === 0 && _token) {
  _token = '';
  sessionStorage.removeItem('ws_token');
  sessionStorage.removeItem('ws_level');
}

// If the PHP session is alive but sessionStorage was cleared (tab reopen), restore
if (!_token && _phpToken && _phpLevel > 0) {
  _token = _phpToken;
  sessionStorage.setItem('ws_token', _token);
  sessionStorage.setItem('ws_level', String(_phpLevel));
  userLevel = _phpLevel;
}

if (_token) {
  if (!userLevel) userLevel = parseInt(sessionStorage.getItem('ws_level') || '0', 10);
  applyLevel(userLevel, false);
  if (userLevel === 1 && _defPw) setTimeout(openCpw, 600);
}

function openLogin() {
  document.getElementById('pwOv').classList.add('open');
  setTimeout(() => document.getElementById('pwInp').focus(), 50);
}

/** Updates the UI for the given auth level and optionally reloads the page. */
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
      showToast((lvl >= 2 ? 'Admin' : 'Owner') + ' – wird neu geladen ...', lvl >= 2 ? 'bi-shield-lock-fill' : 'bi-shield-check');
      setTimeout(() => location.reload(), 900);
    }
  } else {
    badge.classList.remove('show');
    iamBtn.classList.remove('hidden');
  }
}

// ── Login form ───────────────────────────────────────────────────────────────
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
    const res  = await fetch('?_login=1', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      _token = data.t;
      sessionStorage.setItem('ws_token', _token);
      sessionStorage.setItem('ws_level', data.lvl);
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

// ── File row click → smart open based on file type ───────────────────────────
const _EXT_IMAGE = /^(png|jpg|jpeg|gif|webp|svg|ico|bmp|avif)$/;
const _EXT_CODE  = /^(css|js|mjs|cjs|json|txt|md|xml|sql|csv|yaml|yml|ini|sh|bat|log|htaccess)$/;
const _EXT_WEB   = /^(php|html?)$/;

document.addEventListener('click', e => {
  const row = e.target.closest('tr.file-row');
  if (!row || e.target.closest('a,button')) return;
  const canAccess = userLevel >= 1 || row.classList.contains('unlocked');
  if (!canAccess) { showToast('Gesperrt — kein Zugriff', 'bi-lock', 2500); return; }
  const ext  = (row.dataset.ext || '').toLowerCase();
  const name = row.dataset.path?.split('/').pop() || '';
  if (_EXT_IMAGE.test(ext)) { showImgPreview(row.dataset.url, name); return; }
  if (_EXT_CODE.test(ext))  { showSrc(row.dataset.path, name);       return; }
  if (_EXT_WEB.test(ext))   { window.open(row.dataset.url, '_blank'); return; }
  dlFile(row.dataset.path); // fallback: download
});

// ── Toggle lock/unlock ───────────────────────────────────────────────────────
async function toggleFile(btn, relPath) {
  const row = btn.closest('tr');
  const fd  = new FormData();
  fd.append('_t', _token); fd.append('f', relPath);
  try {
    const res  = await fetch('?_unlock=1', { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.ok) { showToast('Fehler', 'bi-x', 2000); return; }
    const i = btn.querySelector('i');
    const s = btn.querySelector('span');
    if (data.unlocked) {
      row.classList.add('unlocked');
      i.className = 'bi bi-lock'; s.textContent = 'Sperren'; btn.title = 'Sperren';
      showToast('Freigegeben für Besucher', 'bi-unlock', 2500);
    } else {
      row.classList.remove('unlocked');
      i.className = 'bi bi-unlock'; s.textContent = 'Freigeben'; btn.title = 'Freigeben';
      showToast('Gesperrt', 'bi-lock', 2500);
    }
  } catch { showToast('Verbindungsfehler', 'bi-wifi-off', 2500); }
}

// ── Pin ──────────────────────────────────────────────────────────────────────
async function pinFile(btn, relPath) {
  const row = btn.closest('tr');
  const fd  = new FormData();
  fd.append('_t', _token); fd.append('f', relPath);
  try {
    const res  = await fetch('?_pin=1', { method: 'POST', body: fd });
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

// ── Download ─────────────────────────────────────────────────────────────────
function dlFile(relPath) {
  window.location.href = '?dl=1&f=' + encodeURIComponent(relPath) + '&_t=' + encodeURIComponent(_token);
}

// ── Folder note ───────────────────────────────────────────────────────────────
let _noteOrig = '';
function startNote() {
  const el = document.getElementById('noteTxt');
  _noteOrig = el.textContent;
  el.contentEditable = 'true'; el.focus();
  document.getElementById('noteEditBtn').style.display   = 'none';
  document.getElementById('noteSaveBtn').style.display   = '';
  document.getElementById('noteCancelBtn').style.display = '';
}
function cancelNote() {
  const el = document.getElementById('noteTxt');
  el.textContent = _noteOrig; el.contentEditable = 'false';
  document.getElementById('noteEditBtn').style.display   = '';
  document.getElementById('noteSaveBtn').style.display   = 'none';
  document.getElementById('noteCancelBtn').style.display = 'none';
}
async function saveNote() {
  const el   = document.getElementById('noteTxt');
  const text = el.textContent.trim();
  const fd   = new FormData();
  fd.append('_t', _token); fd.append('d', _curDir); fd.append('t', text);
  el.contentEditable = 'false';
  document.getElementById('noteEditBtn').style.display   = '';
  document.getElementById('noteSaveBtn').style.display   = 'none';
  document.getElementById('noteCancelBtn').style.display = 'none';
  try {
    await fetch('?_note=1', { method: 'POST', body: fd });
    const bar = document.getElementById('noteBar');
    if (text === '') bar.classList.add('no-note'); else bar.classList.remove('no-note');
    showToast('Notiz gespeichert', 'bi-check2', 2000);
  } catch { showToast('Fehler beim Speichern', 'bi-x', 2500); }
}

// ── Upload ────────────────────────────────────────────────────────────────────
function toggleUpload() {
  const body = document.getElementById('upBody');
  const chev = document.getElementById('upChev');
  const open = body.classList.toggle('open');
  chev.style.transform = open ? 'rotate(180deg)' : '';
}
const upDrop = document.getElementById('upDrop');
const upInp  = document.getElementById('upInp');
if (upDrop) {
  upDrop.addEventListener('click',    e => { if (!e.target.closest('label')) upInp.click(); });
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
    const res  = await fetch('?_upload=1', { method: 'POST', body: fd });
    const data = await res.json();
    hideToast();
    if (!data.ok && data.s === 'auth') { showToast('Keine Berechtigung', 'bi-lock', 3000); return; }
    const st = document.getElementById('upStatus');
    st.innerHTML = (data.files || []).map(f =>
      `<div class="up-item ${f.ok ? 'ok' : 'err'}"><i class="bi ${f.ok ? 'bi-check2' : 'bi-x'}"></i>${f.n}${f.s === 'type' ? ' (Typ nicht erlaubt)' : ''}</div>`
    ).join('');
    const ok = (data.files || []).filter(f => f.ok).length;
    showToast(ok + ' Datei(en) hochgeladen – wird neu geladen ...', 'bi-check-circle', 2500);
    if (ok > 0) setTimeout(() => location.reload(), 2000);
  } catch { hideToast(); showToast('Upload-Fehler', 'bi-wifi-off', 3000); }
}

// ── Checkboxes & Bulk Delete ──────────────────────────────────────────────────
function updateBulk() {
  const checked = [...document.querySelectorAll('.row-cb:checked')];
  const bar     = document.getElementById('bulkBar');
  const cnt     = document.getElementById('bulkCnt');
  cnt.textContent = checked.length + ' ausgewählt';
  bar.classList.toggle('show', checked.length > 0);
  const all   = document.getElementById('cbAll');
  const total = document.querySelectorAll('.row-cb').length;
  if (all) {
    all.indeterminate = checked.length > 0 && checked.length < total;
    all.checked       = checked.length === total && total > 0;
  }
}
function clearSel() {
  document.querySelectorAll('.row-cb,.fchk[id=cbAll]').forEach(cb => { cb.checked = false; cb.indeterminate = false; });
  document.getElementById('bulkBar').classList.remove('show');
}
document.getElementById('cbAll')?.addEventListener('change', function () {
  document.querySelectorAll('.row-cb').forEach(cb => cb.checked = this.checked);
  updateBulk();
});

let _delPaths = [];
function confirmDelete() {
  _delPaths = [...document.querySelectorAll('.row-cb:checked')].map(cb => cb.value);
  if (!_delPaths.length) return;
  const multi = _delPaths.length > 1;
  document.getElementById('delTtl').textContent = multi
    ? _delPaths.length + ' Dateien wirklich löschen?'
    : '"' + _delPaths[0].split('/').pop() + '" wirklich löschen?';
  document.getElementById('delInfo').textContent = multi ? 'Passwort erforderlich für Mehrfach-Löschung.' : '';
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
    const res  = await fetch('?_delete=1', { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.ok) {
      document.getElementById('delErr').textContent =
        data.s === 'pw' ? 'Falsches Passwort' : 'Fehler: ' + (data.s || 'unbekannt');
      return;
    }
    closeDelOv();
    clearSel();
    data.deleted.forEach(rel => {
      document.querySelectorAll(`tr.file-row[data-path="${CSS.escape(rel)}"]`).forEach(r => r.remove());
    });
    showToast(data.deleted.length + ' Datei(en) gelöscht', 'bi-trash3', 2500);
  } catch { document.getElementById('delErr').textContent = 'Verbindungsfehler'; }
}

// ── ZIP download ──────────────────────────────────────────────────────────────
let _zipPaths = [];

function _fileIcoJs(name) {
  const ext = (name.split('.').pop() || '').toLowerCase();
  const map = {php:'bi-filetype-php',html:'bi-filetype-html',htm:'bi-filetype-html',css:'bi-filetype-css',
    js:'bi-filetype-js',json:'bi-filetype-json',md:'bi-filetype-md',txt:'bi-filetype-txt',
    png:'bi-file-image',jpg:'bi-file-image',jpeg:'bi-file-image',gif:'bi-file-image',
    svg:'bi-file-image',webp:'bi-file-image',zip:'bi-file-zip',pdf:'bi-file-pdf'};
  return map[ext] || 'bi-file-earmark';
}

function _buildZipTreeHtml(items) {
  if (!items || !items.length) return '';
  let html = '<ul class="zip-tree">';
  for (const item of items) {
    if (item.d) {
      const hasKids = item.c && item.c.length;
      html += `<li class="zt-dir">
        <div style="display:flex;align-items:center;gap:2px">
          ${hasKids ? `<button class="zt-tog open" onclick="this.classList.toggle('open');const nx=this.closest('li').querySelector(':scope>.zip-tree');if(nx)nx.style.display=nx.style.display==='none'?'':'none'">&#9658;</button>` : '<span style="width:18px;display:inline-block"></span>'}
          <label><input type="checkbox" class="zt-cb" value="${item.p}" checked> <i class="bi bi-folder-fill" style="color:#f59e0b"></i> ${item.n}</label>
        </div>
        ${hasKids ? _buildZipTreeHtml(item.c) : ''}
      </li>`;
    } else {
      html += `<li class="zt-file" style="padding-left:20px"><label><input type="checkbox" class="zt-cb" value="${item.p}" checked> <i class="bi ${_fileIcoJs(item.n)}"></i> ${item.n}</label></li>`;
    }
  }
  html += '</ul>';
  return html;
}

async function openZipModal() {
  _zipPaths = [...document.querySelectorAll('.row-cb:checked')].map(cb => ({
    path: cb.value,
    isDir: cb.closest('tr')?.dataset?.type === 'dir'
  }));
  if (!_zipPaths.length) return;

  const treeEl = document.getElementById('zipTree');
  treeEl.innerHTML = '<div style="color:var(--mut);font-size:.82rem;padding:.5rem">Wird geladen …</div>';
  document.getElementById('zipErr').textContent = '';
  document.getElementById('zipOv').classList.add('open');

  let html = '';
  for (const item of _zipPaths) {
    if (item.isDir) {
      try {
        const res  = await fetch('?_tree&p=' + encodeURIComponent(item.path) + '&_t=' + encodeURIComponent(_token));
        const data = await res.json();
        if (data.ok) {
          html += `<div style="margin-bottom:.5rem"><strong style="color:var(--txt);font-size:.85rem"><i class="bi bi-folder-fill" style="color:#f59e0b"></i> ${item.path.split('/').pop()}</strong>`;
          html += _buildZipTreeHtml(data.tree) + '</div>';
        }
      } catch {}
    } else {
      html += `<div><label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;padding:3px 5px;border-radius:5px;font-size:.83rem" class="zip-tree"><input type="checkbox" class="zt-cb" value="${item.path}" checked> <i class="bi ${_fileIcoJs(item.path.split('/').pop())}"></i> ${item.path.split('/').pop()}</label></div>`;
    }
  }
  treeEl.innerHTML = html || '<div style="color:var(--mut)">Keine Auswahl</div>';
}

function closeZipModal() {
  document.getElementById('zipOv').classList.remove('open');
}

async function execZip() {
  const checked  = [...document.querySelectorAll('#zipTree .zt-cb:checked')].map(cb => cb.value);
  const unchecked = [...document.querySelectorAll('#zipTree .zt-cb:not(:checked)')].map(cb => cb.value);
  const topLevel = _zipPaths.map(p => p.path);

  if (!checked.length && !topLevel.length) {
    document.getElementById('zipErr').textContent = 'Keine Dateien ausgewählt';
    return;
  }

  const fd = new FormData();
  fd.append('_t', _token);
  fd.append('inc', JSON.stringify(topLevel));
  fd.append('exc', JSON.stringify(unchecked));

  document.getElementById('zipErr').textContent = '';
  showToast('ZIP wird erstellt …', 'bi-file-zip', 99999);

  try {
    const res = await fetch('?_zip=1', { method: 'POST', body: fd });
    hideToast();
    if (!res.ok || res.headers.get('Content-Type')?.includes('json')) {
      const data = await res.json().catch(() => ({}));
      const msg = data.s === 'nozip' ? 'ZipArchive nicht verfügbar' : 'Fehler beim Erstellen';
      document.getElementById('zipErr').textContent = msg;
      return;
    }
    closeZipModal();
    // Trigger download via blob
    const blob = await res.blob();
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = 'download.zip'; a.click();
    URL.revokeObjectURL(url);
    showToast('ZIP heruntergeladen', 'bi-check-circle', 2500);
  } catch {
    hideToast();
    document.getElementById('zipErr').textContent = 'Verbindungsfehler';
  }
}

// ── Search ────────────────────────────────────────────────────────────────────
document.getElementById('searchInp')?.addEventListener('input', function () {
  const q = this.value.trim().toLowerCase();
  let visible = 0;
  document.querySelectorAll('#fileBody tr.file-row').forEach(row => {
    const match = !q || (row.dataset.name || '').includes(q) || (row.dataset.path || '').toLowerCase().includes(q);
    row.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  const nr = document.getElementById('noRes');
  if (nr) nr.style.display = visible === 0 && q ? 'block' : 'none';
});

// ── Sort ──────────────────────────────────────────────────────────────────────
let _sortKey = 'pin', _sortDir = 1;
function setSort(key) {
  if (_sortKey === key) _sortDir *= -1; else { _sortKey = key; _sortDir = 1; }
  document.querySelectorAll('.sort-btn').forEach(b => b.classList.toggle('active', b.dataset.sort === key));
  document.querySelectorAll('.ftbl thead th').forEach(th => {
    const ind = th.querySelector('.sort-ind');
    if (!ind) return;
    const sorted = th.onclick?.toString().includes("'" + key + "'");
    th.classList.toggle('sorted', sorted);
    ind.className = 'sort-ind bi ' + (sorted ? (_sortDir === 1 ? 'bi-chevron-up' : 'bi-chevron-down') : 'bi-chevron-expand');
  });
  const body = document.getElementById('fileBody');
  if (!body) return;
  const rows = [...body.querySelectorAll('tr.file-row')];
  rows.sort((a, b) => {
    let va, vb;
    if (key === 'pin')  { va = a.classList.contains('pinned') ? 0 : 1; vb = b.classList.contains('pinned') ? 0 : 1; }
    if (key === 'name') { va = a.dataset.name; vb = b.dataset.name; }
    if (key === 'size') { va = parseInt(a.dataset.size || 0); vb = parseInt(b.dataset.size || 0); }
    if (key === 'date') { va = parseInt(a.dataset.date || 0); vb = parseInt(b.dataset.date || 0); }
    if (va < vb) return -_sortDir; if (va > vb) return _sortDir; return 0;
  });
  rows.forEach(r => body.appendChild(r));
}

// ── Source code viewer (Owner + Admin) ───────────────────────────────────────
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
  } catch (ex) { el.textContent = 'Fehler: ' + ex.message; }
}
function closeCode() { document.getElementById('cdOv').classList.remove('open'); }

// ── Image preview ─────────────────────────────────────────────────────────────
function showImgPreview(url, name) {
  document.getElementById('imgEl').src      = url;
  document.getElementById('imgCaption').textContent = name;
  document.getElementById('imgOv').classList.add('open');
}
function closeImg() {
  document.getElementById('imgOv').classList.remove('open');
  setTimeout(() => { document.getElementById('imgEl').src = ''; }, 200);
}
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

// ── Update check + install ────────────────────────────────────────────────────

// checkForUpdate(force): fetches ?_checkupdate, shows banner if update found.
// force=true bypasses the 30-min server cache (used on manual badge click).
async function checkForUpdate(force = false) {
  if (userLevel < 1) return;
  if (force) showToast('Suche Update …', 'bi-cloud-download', 99999);
  try {
    const url  = '?_checkupdate=1&_t=' + encodeURIComponent(_token) + (force ? '&force=1' : '');
    const res  = await fetch(url);
    const data = await res.json();
    if (force) hideToast();
    if (data.ok && data.available) {
      const seenKey = 'ws_upd_' + data.latest;
      sessionStorage.setItem(seenKey, '1');
      document.getElementById('updBannerTxt').textContent = 'Update verfügbar: v' + data.latest;
      document.getElementById('updBanner').classList.add('show');
    } else if (force) {
      showToast('Kein Update verfügbar', 'bi-check-circle', 2500);
    }
  } catch { if (force) { hideToast(); showToast('Verbindungsfehler', 'bi-wifi-off', 3000); } }
}

// triggerUpdate(): actually downloads and installs the update (called from banner).
async function triggerUpdate() {
  if (userLevel < 1) return;
  document.getElementById('updBanner')?.classList.remove('show');
  showToast('Installiere Update …', 'bi-cloud-download', 99999);
  const fd = new FormData();
  fd.append('_t', _token);
  try {
    const res  = await fetch('?_update=1', { method: 'POST', body: fd });
    const data = await res.json();
    hideToast();
    const msgs = {
      ok:   ['Update installiert! Wird neu geladen …', 'bi-check-circle-fill', 4000],
      err:  ['GitHub nicht erreichbar.',                    'bi-exclamation-triangle', 3000],
      auth: ['Session abgelaufen.',                         'bi-lock', 3000],
    };
    const [msg, ico, dur] = msgs[data.s] || ['Unbekannter Fehler', 'bi-x', 3000];
    showToast(msg, ico, dur);
    if (data.s === 'ok') setTimeout(() => location.reload(), 2000);
  } catch { hideToast(); showToast('Verbindungsfehler', 'bi-wifi-off', 3000); }
}

// Version badge: force-check on click (bypasses cache).
document.querySelector('.ver').addEventListener('click', () => checkForUpdate(true));

// Background check on page load: cached, once per session per version.
if (userLevel >= 1) {
  setTimeout(() => {
    const url  = '?_checkupdate=1&_t=' + encodeURIComponent(_token);
    fetch(url).then(r => r.json()).then(data => {
      if (!data.ok || !data.available) return;
      const seenKey = 'ws_upd_' + data.latest;
      if (sessionStorage.getItem(seenKey)) return;
      sessionStorage.setItem(seenKey, '1');
      document.getElementById('updBannerTxt').textContent = 'Update verfügbar: v' + data.latest;
      document.getElementById('updBanner').classList.add('show');
    }).catch(() => {});
  }, 2000);
}

// ── Change password ───────────────────────────────────────────────────────────
function openCpw(forced) {
  const ttl = document.getElementById('cpwTtl');
  ttl.textContent = forced === false
    ? 'Passwort ändern'
    : 'Standard-Passwort ändern — bitte jetzt setzen';
  document.getElementById('cpwSkip').style.display = forced === false ? '' : 'none';
  document.getElementById('cpwOv').classList.add('open');
  setTimeout(() => document.getElementById('cpwCur').focus(), 50);
}
function closeCpw() {
  document.getElementById('cpwOv').classList.remove('open');
  ['cpwCur', 'cpwNew', 'cpwCon'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('cpwErr').textContent = '';
}
function updatePwStrength(pw) {
  const fill = document.getElementById('pwStrFill');
  const lbl  = document.getElementById('pwStrLbl');
  if (!fill || !pw) { fill && (fill.style.width = '0'); lbl && (lbl.textContent = ''); return; }
  let score = 0;
  if (pw.length >= 6)  score++;
  if (pw.length >= 10) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  const levels = [
    { w: '15%',  bg: '#ef4444', t: 'Sehr schwach' },
    { w: '30%',  bg: '#f97316', t: 'Schwach'       },
    { w: '55%',  bg: '#eab308', t: 'Mittel'        },
    { w: '80%',  bg: '#22c55e', t: 'Stark'         },
    { w: '100%', bg: '#10b981', t: 'Sehr stark'    },
  ];
  const l = levels[Math.min(score, 4)];
  fill.style.width      = l.w;
  fill.style.background = l.bg;
  lbl.textContent       = l.t;
  lbl.style.color       = l.bg;
}
document.getElementById('cpwBtn').addEventListener('click', async () => {
  const cur = document.getElementById('cpwCur').value;
  const nw  = document.getElementById('cpwNew').value;
  const con = document.getElementById('cpwCon').value;
  const err = document.getElementById('cpwErr');
  err.textContent = '';
  if (nw.length < 4) { err.textContent = 'Mind. 4 Zeichen'; return; }
  if (nw !== con)    { err.textContent = 'Passwörter stimmen nicht überein'; return; }
  const fd = new FormData();
  fd.append('_t', _token); fd.append('cur', cur); fd.append('new', nw);
  try {
    const res  = await fetch('?_changepw=1', { method: 'POST', body: fd });
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
['cpwCur', 'cpwNew', 'cpwCon'].forEach(id =>
  document.getElementById(id).addEventListener('keydown', e => {
    if (e.key === 'Enter') document.getElementById('cpwBtn').click();
  })
);

// ── Global keyboard shortcuts ─────────────────────────────────────────────────
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { closeCode(); closePw(); closeCpw(); closeDelOv(); closeZipModal(); closeImg(); }
});
</script>
</body>
</html>
