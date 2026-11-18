<?php
declare(strict_types=1);

session_name('xenox_admin');
session_start();
header('Content-Type: text/html; charset=UTF-8');

$config = require __DIR__ . '/config.php';
$ROOT = dirname(__DIR__, 5);
$UPLOAD_DIR = $ROOT . '/assets/uploads';
$CONTENT_JSON = $ROOT . '/content.json';
$CONTENT_JS = $ROOT . '/content.js';
$BASE = '../../../../../';

$DEFAULTS = [
    'images' => [
        'logo' => 'assets/img/logo-mark.png',
        'hero_logo' => 'assets/img/logo-mark.png',
        'card_valorant' => 'assets/img/valorant.png',
        'card_cs2' => 'assets/img/cs2-light.png',
        'card_spoof' => 'assets/img/spoof-preview.png',
        'detail_valorant' => 'assets/img/valorant-preview.png',
        'detail_cs2' => 'assets/img/cs2-preview.png',
        'detail_spoof' => 'assets/img/spoof-preview.png',
    ],
    'announcements' => [
        "XENOX SOFTWARE'a hoş geldiniz — tüm ürünlerde anında teslimat.",
        "VALORANT EXTERNAL ve CS2 EXTERNAL stokta. Discord üzerinden hemen sipariş verin.",
        "ONECLICK SPOOF stokta — HWID sorunlarına tek tıkla çözüm.",
        "Yeni sezon indirimi: Discord'a özel %20 indirim kodu.",
        "discord.gg/vUMqUtwMyd",
    ],
    'ticker_speed' => 48,
    'theme_default' => 'mono',
    'discord' => 'https://discord.gg/vUMqUtwMyd',
];

$SLOTS = [
    'logo' => [
        'label' => 'Üst Bar Logosu',
        'hint' => 'Şeffaf PNG önerilir. Sitenin sol üstünde görünür.',
        'box' => 'wide',
    ],
    'hero_logo' => [
        'label' => 'Ana Sayfa Büyük Logo',
        'hint' => 'Ana sayfadaki büyük logo. Şeffaf PNG en iyi sonucu verir.',
        'box' => 'wide',
    ],
    'card_valorant' => [
        'label' => 'Valorant — Kart Görseli',
        'hint' => 'Ürünler sayfasındaki Valorant kartında görünür.',
        'box' => 'square',
    ],
    'card_cs2' => [
        'label' => 'CS2 — Kart Görseli',
        'hint' => 'Ürünler sayfasındaki CS2 kartında görünür. Açık renk önerilir.',
        'box' => 'square',
    ],
    'card_spoof' => [
        'label' => 'Serials — Kart Görseli',
        'hint' => 'Ürünler sayfasındaki SERIALS kartında görünür.',
        'box' => 'square',
    ],
    'detail_valorant' => [
        'label' => 'Valorant — Detay Görseli',
        'hint' => 'Valorant detay sayfasındaki büyük önizleme.',
        'box' => 'wide',
    ],
    'detail_cs2' => [
        'label' => 'CS2 — Detay Görseli',
        'hint' => 'CS2 detay sayfasındaki büyük önizleme.',
        'box' => 'wide',
    ],
    'detail_spoof' => [
        'label' => 'Spoofer — Detay Görseli',
        'hint' => 'OneClick Spoof detay sayfasındaki büyük önizleme.',
        'box' => 'wide',
    ],
];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    return '../../../../../';
}

function asset_url(string $path): string
{
    $path = ltrim($path, '/');
    $abs = dirname(__DIR__, 5) . '/' . $path;
    $version = is_file($abs) ? '?v=' . filemtime($abs) : '';
    return '../../../../../' . e($path) . $version;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['xenox_flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['xenox_flash'])) {
        return null;
    }
    $flash = $_SESSION['xenox_flash'];
    unset($_SESSION['xenox_flash']);
    return $flash;
}

function redirect_self(): void
{
    header('Location: admin.php');
    exit;
}

function load_data(string $jsonPath, array $defaults, ?string $jsPath = null): array
{
    $data = $defaults;
    $decoded = null;

    if (is_file($jsonPath)) {
        $raw = @file_get_contents($jsonPath);
        $attempt = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($attempt)) {
            $decoded = $attempt;
        }
    }

    if ($decoded === null && $jsPath !== null && is_file($jsPath)) {
        $js = @file_get_contents($jsPath);
        if (is_string($js) && preg_match('/window\.SITE_CONTENT\s*=\s*(\{.*\})\s*;/s', $js, $matches)) {
            $attempt = json_decode($matches[1], true);
            if (is_array($attempt)) {
                $decoded = $attempt;
            }
        }
    }

    if ($decoded === null) {
        return $data;
    }

    $data = array_merge($defaults, $decoded);
    $data['images'] = is_array($decoded['images'] ?? null)
        ? array_merge($defaults['images'], $decoded['images'])
        : $defaults['images'];
    $data['announcements'] = is_array($decoded['announcements'] ?? null)
        ? array_values(array_filter(array_map('strval', $decoded['announcements']), static fn($item) => trim($item) !== ''))
        : $defaults['announcements'];
    $data['ticker_speed'] = max(10, min(120, (int)($decoded['ticker_speed'] ?? $defaults['ticker_speed'])));
    $data['theme_default'] = ($decoded['theme_default'] ?? '') === 'classic' ? 'classic' : 'mono';
    $data['discord'] = is_string($decoded['discord'] ?? null) && $decoded['discord'] !== ''
        ? $decoded['discord']
        : $defaults['discord'];

    return $data;
}

function save_data(string $jsonPath, string $jsPath, array $data): bool
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }
    $js = "/* XENOX SOFTWARE — site içeriği (admin panel tarafından otomatik oluşturulur) */\nwindow.SITE_CONTENT = " . $json . ";\n";
    $first = @file_put_contents($jsonPath, $json, LOCK_EX);
    $second = @file_put_contents($jsPath, $js, LOCK_EX);
    return $first !== false && $second !== false;
}

function handle_upload(string $field, string $slot, string $uploadDir): ?string
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }
    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Dosya yüklenemedi (hata kodu: ' . (int)$file['error'] . ').');
    }
    if (($file['size'] ?? 0) > 6 * 1024 * 1024) {
        throw new RuntimeException('Dosya çok büyük (en fazla 6 MB).');
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        throw new RuntimeException('Geçerli bir görsel dosyası değil.');
    }
    $allowed = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
    ];
    $mime = $info['mime'] ?? '';
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Desteklenmeyen format. PNG, JPG, WEBP veya GIF kullanın.');
    }
    if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Yükleme klasörü oluşturulamadı.');
    }
    $name = $slot . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $allowed[$mime];
    $target = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $name;
    if (!@move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Dosya kaydedilemedi. klasör yazma iznini kontrol edin.');
    }
    return 'assets/uploads/' . $name;
}

function short_text(string $value, int $limit): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $limit, 'UTF-8');
    }
    return substr($value, 0, $limit);
}

if (empty($_SESSION['xenox_csrf'])) {
    $_SESSION['xenox_csrf'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['xenox_csrf'];
$loggedIn = !empty($_SESSION['xenox_auth']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals((string)($_SESSION['xenox_csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'login') {
        $lockedUntil = (int)($_SESSION['xenox_lock_until'] ?? 0);
        if (time() < $lockedUntil) {
            set_flash('error', 'Çok fazla hatalı deneme. Lütfen 1 dakika sonra tekrar deneyin.');
            redirect_self();
        }

        $password = (string)($_POST['password'] ?? '');
        $stored = (string)($config['password'] ?? '');
        $valid = false;

        if (strpos($stored, '$2y$') === 0 || strpos($stored, '$argon2') === 0) {
            $valid = password_verify($password, $stored);
        } else {
            $valid = hash_equals($stored, $password);
        }

        if ($valid) {
            session_regenerate_id(true);
            $_SESSION['xenox_auth'] = true;
            $_SESSION['xenox_fails'] = 0;
            unset($_SESSION['xenox_lock_until']);
            set_flash('success', 'Giriş başarılı. Hoş geldin.');
            redirect_self();
        }

        $_SESSION['xenox_fails'] = (int)($_SESSION['xenox_fails'] ?? 0) + 1;
        if ($_SESSION['xenox_fails'] >= 5) {
            $_SESSION['xenox_lock_until'] = time() + 60;
        }
        usleep(400000);
        set_flash('error', 'Şifre hatalı. Tekrar deneyin.');
        redirect_self();
    }

    if (!$loggedIn) {
        http_response_code(403);
        exit('Yetkisiz istek.');
    }

    if ($action === 'logout') {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
        header('Location: admin.php');
        exit;
    }

    if ($action === 'save') {
        $data = load_data($CONTENT_JSON, $DEFAULTS);

        $lines = preg_split('/\r\n|\r|\n/', (string)($_POST['announcements'] ?? '')) ?: [];
        $announcements = [];
        foreach ($lines as $line) {
            $line = trim((string)preg_replace('/\s+/u', ' ', $line));
            if ($line === '') {
                continue;
            }
            $announcements[] = short_text($line, 240);
            if (count($announcements) >= 12) {
                break;
            }
        }
        $data['announcements'] = $announcements;

        $data['ticker_speed'] = max(10, min(120, (int)($_POST['ticker_speed'] ?? 48)));
        $data['theme_default'] = (($_POST['theme_default'] ?? 'mono') === 'classic') ? 'classic' : 'mono';

        $discord = trim((string)($_POST['discord'] ?? ''));
        if ($discord === '' || filter_var($discord, FILTER_VALIDATE_URL) === false) {
            $discord = $DEFAULTS['discord'];
        }
        $data['discord'] = $discord;

        $errors = [];
        foreach ($SLOTS as $key => $slot) {
            try {
                $newPath = handle_upload('img_' . $key, $key, $UPLOAD_DIR);
                if ($newPath !== null) {
                    $data['images'][$key] = $newPath;
                }
            } catch (Throwable $ex) {
                $errors[] = $slot['label'] . ': ' . $ex->getMessage();
            }
        }

        $reset = (string)($_POST['reset_image'] ?? '');
        if ($reset !== '' && isset($SLOTS[$reset])) {
            $data['images'][$reset] = $DEFAULTS['images'][$reset];
        }

        if ($errors) {
            set_flash('error', 'Kaydedilemedi — ' . implode(' | ', $errors));
            redirect_self();
        }

        if (save_data($CONTENT_JSON, $CONTENT_JS, $data)) {
            set_flash('success', 'Değişiklikler kaydedildi. Siteyi yenilediğinizde görünecek.');
        } else {
            set_flash('error', 'content.js / content.json yazılamadı. Klasör yazma iznini kontrol edin.');
        }
        redirect_self();
    }
}

$data = load_data($CONTENT_JSON, $DEFAULTS);
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>XENOX — Yönetim Paneli</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    :root {
        --bg: #060607;
        --panel: #0d0d10;
        --panel-2: #121216;
        --line: rgba(255, 255, 255, 0.09);
        --line-strong: rgba(255, 255, 255, 0.22);
        --text: #f5f5f7;
        --muted: rgba(245, 245, 247, 0.55);
        --muted-2: rgba(245, 245, 247, 0.32);
        --accent: #ffffff;
        --accent-ink: #070708;
        --ok: #22c55e;
        --err: #f87171;
        --font-display: 'Montserrat', sans-serif;
        --font-body: 'Inter', sans-serif;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        min-height: 100vh;
        background:
            radial-gradient(ellipse at 12% 0%, rgba(255,255,255,0.055), transparent 55%),
            radial-gradient(ellipse at 88% 10%, rgba(255,255,255,0.03), transparent 50%),
            var(--bg);
        color: var(--text);
        font-family: var(--font-body);
        -webkit-font-smoothing: antialiased;
        padding: 28px;
    }
    a { color: inherit; }
    button, input, textarea, select { font-family: inherit; }
    ::selection { background: #fff; color: #000; }
    ::-webkit-scrollbar { width: 9px; height: 9px; }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.18); border-radius: 99px; }
    ::-webkit-scrollbar-track { background: transparent; }

    .shell { width: min(1160px, 100%); margin: 0 auto; }

    /* ======= LOGIN ======= */
    .login-wrap {
        min-height: calc(100vh - 56px);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .login-card {
        width: min(420px, 100%);
        padding: 34px;
        background: rgba(16, 16, 20, 0.82);
        border: 1px solid var(--line);
        border-radius: 20px;
        box-shadow: 0 30px 90px rgba(0, 0, 0, 0.55);
        backdrop-filter: blur(18px);
    }
    .login-logo {
        display: flex;
        align-items: baseline;
        gap: 10px;
        font-family: var(--font-display);
        font-size: 26px;
        font-weight: 900;
        letter-spacing: 5px;
        margin-bottom: 26px;
    }
    .login-logo span {
        font-size: 8px;
        font-weight: 700;
        letter-spacing: 2.5px;
        color: var(--muted);
        border: 1px solid var(--line);
        border-radius: 999px;
        padding: 4px 8px;
    }
    .login-card h1 {
        font-family: var(--font-display);
        font-size: 19px;
        font-weight: 800;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    .login-card > p {
        font-size: 12.5px;
        line-height: 1.6;
        color: var(--muted);
        margin-bottom: 22px;
    }
    .login-note {
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid var(--line);
        font-size: 10.5px;
        line-height: 1.6;
        color: var(--muted-2);
    }

    /* ======= FORM ELEMENTS ======= */
    .field { margin-bottom: 16px; }
    .field label {
        display: block;
        margin-bottom: 7px;
        font-family: var(--font-display);
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 1.8px;
        text-transform: uppercase;
        color: var(--muted);
    }
    .input, textarea.input, select.input {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.035);
        color: var(--text);
        font-size: 13px;
        outline: none;
        transition: 0.2s;
    }
    .input:focus, textarea.input:focus, select.input:focus {
        border-color: var(--line-strong);
        background: rgba(255, 255, 255, 0.06);
    }
    textarea.input { resize: vertical; min-height: 180px; line-height: 1.65; }
    select.input { appearance: none; cursor: pointer; }
    select.input option { background: #111; color: #fff; }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        height: 42px;
        padding: 0 18px;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: transparent;
        color: var(--text);
        font-family: var(--font-display);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 1.4px;
        cursor: pointer;
        text-decoration: none;
        transition: 0.2s;
        white-space: nowrap;
    }
    .btn:hover { background: rgba(255,255,255,0.07); border-color: var(--line-strong); }
    .btn-primary {
        background: var(--accent);
        color: var(--accent-ink);
        border-color: var(--accent);
        box-shadow: 0 8px 28px rgba(255, 255, 255, 0.12);
    }
    .btn-primary:hover { background: #fff; transform: translateY(-1px); }
    .btn-block { width: 100%; }
    .btn-sm { height: 32px; padding: 0 11px; font-size: 9px; letter-spacing: 1px; border-radius: 8px; }

    .flash {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        margin-bottom: 20px;
        padding: 13px 15px;
        border-radius: 10px;
        font-size: 12.5px;
        line-height: 1.5;
        border: 1px solid;
    }
    .flash.success { color: #bbf7d0; background: rgba(34, 197, 94, 0.08); border-color: rgba(34, 197, 94, 0.35); }
    .flash.error { color: #fecaca; background: rgba(248, 113, 113, 0.08); border-color: rgba(248, 113, 113, 0.35); }
    .flash b { font-family: var(--font-display); font-size: 9px; letter-spacing: 1.5px; }

    /* ======= DASHBOARD ======= */
    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 34px;
        padding-bottom: 22px;
        border-bottom: 1px solid var(--line);
    }
    .brand { display: flex; align-items: center; gap: 13px; }
    .brand-mark {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--line-strong);
        border-radius: 11px;
        font-family: var(--font-display);
        font-size: 17px;
        font-weight: 900;
        background: rgba(255,255,255,0.04);
    }
    .brand-text b {
        display: block;
        font-family: var(--font-display);
        font-size: 16px;
        font-weight: 900;
        letter-spacing: 4px;
        line-height: 1.2;
    }
    .brand-text span {
        display: block;
        font-family: var(--font-display);
        font-size: 8px;
        font-weight: 700;
        letter-spacing: 2.6px;
        color: var(--muted-2);
    }
    .top-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
    .top-actions form { display: inline-flex; }

    .page-head { margin-bottom: 26px; }
    .page-head h1 {
        font-family: var(--font-display);
        font-size: clamp(26px, 4vw, 38px);
        font-weight: 900;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }
    .page-head p { font-size: 12.5px; line-height: 1.6; color: var(--muted); max-width: 640px; }

    .tabs {
        display: flex;
        gap: 4px;
        padding: 5px;
        margin-bottom: 22px;
        border: 1px solid var(--line);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.025);
        width: fit-content;
        max-width: 100%;
        overflow-x: auto;
    }
    .tab {
        padding: 9px 18px;
        border: none;
        border-radius: 8px;
        background: transparent;
        color: var(--muted);
        font-family: var(--font-display);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 1.3px;
        cursor: pointer;
        white-space: nowrap;
        transition: 0.2s;
    }
    .tab:hover { color: var(--text); }
    .tab.active { color: var(--accent-ink); background: var(--accent); }

    .tab-panel { display: none; }
    .tab-panel.active { display: block; animation: fadeUp 0.3s ease both; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

    .panel-note {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        margin-bottom: 18px;
        padding: 12px 14px;
        border: 1px dashed var(--line);
        border-radius: 10px;
        font-size: 11.5px;
        line-height: 1.6;
        color: var(--muted);
    }
    .panel-note b { color: var(--text); font-weight: 600; }

    .images-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }
    .img-card {
        display: flex;
        flex-direction: column;
        border: 1px solid var(--line);
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.025);
        overflow: hidden;
        transition: 0.2s;
    }
    .img-card:hover { border-color: var(--line-strong); }
    .img-preview {
        height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 14px;
        background:
            linear-gradient(45deg, rgba(255,255,255,0.035) 25%, transparent 25%, transparent 75%, rgba(255,255,255,0.035) 75%) 0 0 / 18px 18px,
            linear-gradient(45deg, rgba(255,255,255,0.035) 25%, transparent 25%, transparent 75%, rgba(255,255,255,0.035) 75%) 9px 9px / 18px 18px,
            #08080a;
        border-bottom: 1px solid var(--line);
    }
    .img-preview img {
        max-width: 100%;
        max-height: 120px;
        object-fit: contain;
    }
    .img-body { padding: 14px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
    .img-title {
        font-family: var(--font-display);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.6px;
    }
    .img-hint { font-size: 10.5px; line-height: 1.5; color: var(--muted-2); }
    .file-row input[type="file"] {
        width: 100%;
        font-size: 10.5px;
        color: var(--muted);
    }
    .file-row input[type="file"]::file-selector-button {
        margin-right: 10px;
        padding: 7px 10px;
        border: 1px solid var(--line);
        border-radius: 7px;
        background: rgba(255,255,255,0.05);
        color: var(--text);
        font-family: var(--font-display);
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 0.8px;
        cursor: pointer;
        transition: 0.2s;
    }
    .file-row input[type="file"]::file-selector-button:hover { background: rgba(255,255,255,0.1); border-color: var(--line-strong); }
    .img-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: auto;
        padding-top: 4px;
    }
    .img-path {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-family: ui-monospace, Consolas, monospace;
        font-size: 9px;
        color: var(--muted-2);
    }

    .two-col { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .settings-box {
        padding: 20px;
        border: 1px solid var(--line);
        border-radius: 14px;
        background: rgba(255,255,255,0.025);
    }
    .settings-box h2 {
        font-family: var(--font-display);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1.6px;
        margin-bottom: 16px;
        color: var(--muted);
        text-transform: uppercase;
    }
    .small-hint { margin-top: -8px; margin-bottom: 14px; font-size: 10.5px; line-height: 1.5; color: var(--muted-2); }

    .save-bar {
        position: sticky;
        bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-top: 24px;
        padding: 12px 14px;
        border: 1px solid var(--line);
        border-radius: 14px;
        background: rgba(10, 10, 13, 0.88);
        backdrop-filter: blur(18px);
        box-shadow: 0 18px 50px rgba(0, 0, 0, 0.45);
        z-index: 10;
    }
    .save-bar span { font-size: 10.5px; color: var(--muted-2); }

    .admin-footer {
        margin-top: 26px;
        padding-top: 18px;
        border-top: 1px solid var(--line);
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 8px;
        font-size: 10px;
        letter-spacing: 0.4px;
        color: var(--muted-2);
    }

    @media (max-width: 820px) {
        body { padding: 18px; }
        .images-grid { grid-template-columns: 1fr; }
        .two-col { grid-template-columns: 1fr; }
        .topbar { flex-direction: column; align-items: flex-start; }
        .top-actions { justify-content: flex-start; }
        .save-bar { flex-direction: column; align-items: stretch; }
        .save-bar .btn { width: 100%; }
    }
</style>
</head>
<body>
<?php if (!$loggedIn): ?>
    <div class="login-wrap">
        <div class="login-card">
            <div class="login-logo">XENOX <span>ADMIN</span></div>
            <h1>Yönetim Girişi</h1>
            <p>Ürün görsellerini ve duyuruları düzenlemek için yönetici şifrenizi girin.</p>
            <?php if ($flash): ?>
                <div class="flash <?= e($flash['type']) ?>"><b><?= $flash['type'] === 'success' ? 'BİLGİ' : 'HATA' ?></b><span><?= e($flash['message']) ?></span></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="login">
                <div class="field">
                    <label for="password">Şifre</label>
                    <input class="input" type="password" id="password" name="password" placeholder="••••••••••" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary btn-block">GİRİŞ YAP</button>
            </form>
            <div class="login-note">Bu sayfa arama motorlarına kapalıdır. Şifrenizi kimseyle paylaşmayın.</div>
        </div>
    </div>
<?php else: ?>
    <div class="shell">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">X</div>
                <div class="brand-text">
                    <b>XENOX</b>
                    <span>ADMIN PANEL</span>
                </div>
            </div>
            <div class="top-actions">
                <a class="btn btn-sm" href="<?= e($BASE) ?>index.html" target="_blank" rel="noopener">SİTEYİ AÇ ↗</a>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn btn-sm">ÇIKIŞ</button>
                </form>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><b><?= $flash['type'] === 'success' ? 'BAŞARILI' : 'HATA' ?></b><span><?= e($flash['message']) ?></span></div>
        <?php endif; ?>

        <div class="page-head">
            <h1>Site Yönetimi</h1>
            <p>Ürün fotoğraflarını değiştir, kayan duyuruları düzenle ve site ayarlarını güncelle. Kaydettiğin anda site içeriği güncellenir.</p>
        </div>

        <form method="post" enctype="multipart/form-data" id="adminForm">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="save">

            <div class="tabs" role="tablist">
                <button type="button" class="tab active" data-tab="images">GÖRSELLER</button>
                <button type="button" class="tab" data-tab="announcements">DUYURULAR</button>
                <button type="button" class="tab" data-tab="settings">AYARLAR</button>
            </div>

            <section class="tab-panel active" data-panel="images">
                <div class="panel-note"><span>🖼</span><span><b>Ürün görselleri:</b> Değiştirmek istediğin görselin altından yeni dosyayı seç, ardından en alttaki <b>Kaydet</b> butonuna bas. PNG / JPG / WEBP desteklenir, en fazla 6 MB.</span></div>
                <div class="images-grid">
                    <?php foreach ($SLOTS as $key => $slot): ?>
                        <div class="img-card">
                            <div class="img-preview">
                                <img src="<?= asset_url((string)$data['images'][$key]) ?>" alt="<?= e($slot['label']) ?>">
                            </div>
                            <div class="img-body">
                                <div class="img-title"><?= e($slot['label']) ?></div>
                                <div class="img-hint"><?= e($slot['hint']) ?></div>
                                <div class="file-row">
                                    <input type="file" name="img_<?= e($key) ?>" accept="image/png,image/jpeg,image/webp,image/gif">
                                </div>
                                <div class="img-foot">
                                    <span class="img-path" title="<?= e((string)$data['images'][$key]) ?>"><?= e((string)$data['images'][$key]) ?></span>
                                    <button type="submit" class="btn btn-sm" name="reset_image" value="<?= e($key) ?>" onclick="return confirm('Bu görsel varsayılan görsele döndürülsün mü?');">SIFIRLA</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="tab-panel" data-panel="announcements">
                <div class="two-col">
                    <div class="settings-box">
                        <h2>Kayan Duyuru Yazısı</h2>
                        <div class="field">
                            <label for="announcements">Duyurular (her satır ayrı duyuru)</label>
                            <textarea class="input" id="announcements" name="announcements" placeholder="Duyuru metni..."><?= e(implode("\n", (array)$data['announcements'])) ?></textarea>
                        </div>
                        <div class="small-hint">Her satır sitenin en üstündeki kayan yazıda ayrı bir duyuru olarak gösterilir. En fazla 12 satır kullanılır.</div>
                    </div>
                    <div class="settings-box">
                        <h2>Önizleme</h2>
                        <p class="small-hint">Duyurular kaydedildikten sonra sitenin en üstünde soldan sağa doğru kayar. Üzerine gelindiğinde yazı durur.</p>
                        <div class="field">
                            <label for="ticker_speed">Kayma Hızı (saniye — büyük değer = yavaş)</label>
                            <input class="input" type="number" id="ticker_speed" name="ticker_speed" min="10" max="120" value="<?= (int)$data['ticker_speed'] ?>">
                        </div>
                        <div class="small-hint">Önerilen değer: 40–60 saniye.</div>
                    </div>
                </div>
            </section>

            <section class="tab-panel" data-panel="settings">
                <div class="two-col">
                    <div class="settings-box">
                        <h2>Görünüm</h2>
                        <div class="field">
                            <label for="theme_default">Varsayılan Tema</label>
                            <select class="input" id="theme_default" name="theme_default">
                                <option value="mono" <?= $data['theme_default'] === 'mono' ? 'selected' : '' ?>>Siyah &amp; Beyaz (Modern)</option>
                                <option value="classic" <?= $data['theme_default'] === 'classic' ? 'selected' : '' ?>>Klasik (Mor / Mavi)</option>
                            </select>
                        </div>
                        <div class="small-hint">Site açıldığında bu tema kullanılır. Ziyaretçi üst menüdeki tema butonuyla anında değiştirebilir; seçimi tarayıcısında saklanır.</div>
                    </div>
                    <div class="settings-box">
                        <h2>Bağlantılar</h2>
                        <div class="field">
                            <label for="discord">Discord Davet Linki</label>
                            <input class="input" type="url" id="discord" name="discord" value="<?= e((string)$data['discord']) ?>" placeholder="https://discord.gg/...">
                        </div>
                        <div class="small-hint">Sitedeki tüm Discord butonları bu adrese yönlenir.</div>
                    </div>
                </div>
            </section>

            <div class="save-bar">
                <span>Değişiklikler content.js dosyasına yazılır. Kaydettikten sonra siteyi yenileyin.</span>
                <button type="submit" class="btn btn-primary">DEĞİŞİKLİKLERİ KAYDET</button>
            </div>
        </form>

        <footer class="admin-footer">
            <span>XENOX ADMIN · Bu alan yalnızca yönetici içindir.</span>
            <span>Şifre değiştirmek için: admin/config.php</span>
        </footer>
    </div>

    <script>
        document.querySelectorAll('.tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.tab').forEach(function (t) { t.classList.remove('active'); });
                document.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
                tab.classList.add('active');
                var panel = document.querySelector('.tab-panel[data-panel="' + tab.dataset.tab + '"]');
                if (panel) panel.classList.add('active');
            });
        });
    </script>
<?php endif; ?>
</body>
</html>
