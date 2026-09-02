<?php

declare(strict_types=1);

$config = require __DIR__ . '/config.php';

function ensureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    global $config;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    session_name('gampong_portal');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Strict',
        'path' => '/',
    ]);
    session_start();

    $now = time();
    $hasIdentity = !empty($_SESSION['admin']['id']) || !empty($_SESSION['user']['id']);
    $lastActivity = (int) ($_SESSION['__last_activity'] ?? 0);
    if ($hasIdentity && $lastActivity > 0 && ($now - $lastActivity) > (int) $config['session_idle_seconds']) {
        $_SESSION = [];
        session_regenerate_id(true);
        $hasIdentity = false;
    }
    if ($hasIdentity) {
        $_SESSION['__last_activity'] = $now;
        $lastRotate = (int) ($_SESSION['__last_rotate'] ?? 0);
        if ($lastRotate === 0 || ($now - $lastRotate) >= (int) $config['session_rotate_seconds']) {
            session_regenerate_id(true);
            $_SESSION['__last_rotate'] = $now;
        }
    }
}

function db(): PDO
{
    static $pdo = null;
    global $config;
    if ($pdo instanceof PDO) return $pdo;
    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function bodyJson(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') return [];
    $data = json_decode($raw, true);
    if (!is_array($data)) jsonResponse(['success' => false, 'message' => 'JSON tidak valid.'], 400);
    return $data;
}

function cleanText(mixed $value, int $max = 5000): string
{
    $value = trim((string) $value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max);
}

function setting(string $key, string $default = ''): string
{
    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false || $value === null ? $default : (string) $value;
}

function clientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function rateLimit(string $action, int $limit = 10, int $windowSeconds = 3600): void
{
    global $config;
    $pdo = db();
    $ipHash = hash_hmac('sha256', clientIp(), $config['app_key']);
    $stmt = $pdo->prepare('SELECT id,hits,updated_at FROM rate_limits WHERE ip_hash=? AND action_name=? LIMIT 1');
    $stmt->execute([$ipHash, $action]);
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->prepare('INSERT INTO rate_limits (ip_hash,action_name,hits,updated_at) VALUES (?,?,1,NOW())')->execute([$ipHash, $action]);
        return;
    }

    $last = strtotime((string) $row['updated_at']) ?: 0;
    if ((time() - $last) >= $windowSeconds) {
        $pdo->prepare('UPDATE rate_limits SET hits=1,updated_at=NOW() WHERE id=?')->execute([$row['id']]);
        return;
    }
    if ((int) $row['hits'] >= $limit) jsonResponse(['success' => false, 'message' => 'Terlalu banyak permintaan. Coba lagi nanti.'], 429);
    $pdo->prepare('UPDATE rate_limits SET hits=hits+1,updated_at=NOW() WHERE id=?')->execute([$row['id']]);
}


function publicSettingKeys(): array
{
    return [
        'village_name','village_short_name','district','city','province','postal_code','office_address','office_phone','office_email','office_hours',
        'logo_url','profile_image_url','hero_eyebrow','hero_title','hero_subtitle','hero_background_url','keuchik_name','keuchik_title','keuchik_message','keuchik_photo_url',
        'profile_heading','profile_summary','profile_history','vision','mission','profile_values','profile_commitment',
        'government_heading','government_summary','services_heading','services_summary','data_heading','data_summary','transparency_heading','transparency_summary',
        'news_heading','news_summary','umkm_heading','umkm_summary','gallery_heading','gallery_summary','complaint_heading','complaint_summary','complaint_categories',
        'faq_heading','faq_summary','contact_heading','contact_summary','map_embed_url','map_direction_url','footer_description','seo_title','seo_description','seo_keywords','seo_image_url'
    ];
}

function publicSettingsMap(): array
{
    $allowed = array_flip(publicSettingKeys());
    $data = [];
    foreach (db()->query('SELECT setting_key,setting_value FROM settings ORDER BY setting_key')->fetchAll() as $row) {
        if (isset($allowed[$row['setting_key']])) $data[$row['setting_key']] = $row['setting_value'];
    }
    return $data;
}

function portalBaseUrl(): string
{
    global $config;
    if (!empty($config['base_url'])) return rtrim((string) $config['base_url'], '/');
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost')) ?: 'localhost';
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    return $scheme . '://' . $host . ($dir === '/' || $dir === '.' ? '' : $dir);
}

function portalUrl(string $path = ''): string
{
    $base = portalBaseUrl();
    $path = ltrim($path, '/');
    return $path === '' ? $base . '/' : $base . '/' . $path;
}

function htmlEscape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function passwordPolicyError(string $password): ?string
{
    global $config;
    $min = (int) $config['password_min_length'];
    $length = function_exists('mb_strlen') ? mb_strlen($password) : strlen($password);
    if ($length < $min) return "Password minimal {$min} karakter.";
    if ($length > 128) return 'Password maksimal 128 karakter.';
    $normalized = strtolower(trim($password));
    $blocked = ['password','password123','123456789012','qwerty123456','admin123456','administrator'];
    if (in_array($normalized, $blocked, true)) return 'Gunakan password yang lebih kuat dan tidak mudah ditebak.';
    return null;
}

function sendHtmlSecurityHeaders(bool $noIndex = false): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    if ($noIndex) header('X-Robots-Tag: noindex, nofollow, noarchive');
}

function ticketPrefix(): string
{
    $value = strtoupper(preg_replace('/[^A-Z0-9]/i', '', setting('ticket_prefix', 'GMP')) ?? 'GMP');
    return substr($value !== '' ? $value : 'GMP', 0, 6);
}

function makeTicket(string $kind): string
{
    $prefix = ticketPrefix();
    if ($kind === 'complaint') $prefix .= 'A';
    return sprintf('%s-%s-%s', $prefix, date('Y'), strtoupper(bin2hex(random_bytes(4))));
}

function encryptionKey(): string
{
    global $config;
    return hash('sha256', $config['app_key'], true);
}

function encryptField(string $plain): string
{
    if ($plain === '') return '';
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', encryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false) throw new RuntimeException('Enkripsi gagal.');
    return base64_encode($iv . $tag . $cipher);
}

function decryptField(?string $encoded): string
{
    if (!$encoded) return '';
    $raw = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) < 29) return '';
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $cipher = substr($raw, 28);
    $plain = openssl_decrypt($cipher, 'aes-256-gcm', encryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
    return $plain === false ? '' : $plain;
}

function csrfToken(): string
{
    ensureSession();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}

function rolePermissions(string $role): array
{
    $matrix = [
        'superadmin' => [
            'dashboard',
            'workflow.read', 'workflow.update', 'workflow.download',
            'cms.read', 'cms.write',
            'settings.read', 'settings.write',
            'media.upload', 'export.public',
            'users.manage', 'citizens.manage', 'audit.read',
            'account.password',
        ],
        'admin' => [
            'dashboard',
            'workflow.read', 'workflow.update', 'workflow.download',
            'cms.read', 'cms.write',
            'settings.read', 'settings.write',
            'media.upload', 'export.public',
            'account.password',
        ],
        'operator' => [
            'dashboard',
            'workflow.read', 'workflow.update', 'workflow.download',
            'account.password',
        ],
        'user' => [
            'portal.account', 'portal.profile', 'portal.history',
            'portal.request', 'portal.complaint',
        ],
    ];
    return $matrix[$role] ?? [];
}


function nikHash(string $nik): ?string
{
    $nik = preg_replace('/\D/', '', $nik) ?? '';
    if ($nik === '') return null;
    global $config;
    return hash_hmac('sha256', $nik, $config['app_key']);
}

function currentPortalIdentity(): ?array
{
    ensureSession();

    if (!empty($_SESSION['admin']['id'])) {
        $id = (int) $_SESSION['admin']['id'];
        $stmt = db()->prepare('SELECT id,name,email,role FROM admins WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            unset($_SESSION['admin']);
        } else {
            $identity = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'email' => (string) $row['email'],
                'role' => (string) $row['role'],
                'account_type' => 'staff',
            ];
            $_SESSION['admin'] = array_intersect_key($identity, array_flip(['id','name','email','role']));
            return $identity;
        }
    }

    if (!empty($_SESSION['user']['id'])) {
        $id = (int) $_SESSION['user']['id'];
        $stmt = db()->prepare('SELECT id,name,email,role,is_verified,status FROM users WHERE id=? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row || ($row['status'] ?? '') !== 'active') {
            unset($_SESSION['user']);
            return null;
        }
        $identity = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'email' => (string) $row['email'],
            'role' => 'user',
            'account_type' => 'user',
            'is_verified' => (int) $row['is_verified'],
        ];
        $_SESSION['user'] = [
            'id' => $identity['id'],
            'name' => $identity['name'],
            'email' => $identity['email'],
            'role' => 'user',
            'is_verified' => $identity['is_verified'],
        ];
        return $identity;
    }

    return null;
}


function publicPortalIdentity(?array $identity = null): ?array
{
    $identity ??= currentPortalIdentity();
    if (!$identity) return null;
    return [
        'id' => (int) $identity['id'],
        'name' => (string) $identity['name'],
        'email' => (string) $identity['email'],
        'home_url' => (($identity['account_type'] ?? '') === 'user') ? 'profile.html' : 'admin/',
    ];
}

function requirePortalUser(): array
{
    $identity = currentPortalIdentity();
    if (!$identity || ($identity['account_type'] ?? '') !== 'user') {
        jsonResponse(['success' => false, 'message' => 'Silakan masuk untuk membuka akun.'], 401);
    }
    return $identity;
}

function portalUserProfile(int $id): ?array
{
    $stmt = db()->prepare('SELECT id,name,email,role,nik_cipher,nik_last4,phone_cipher,address_cipher,is_verified,status,last_login_at,created_at,updated_at FROM users WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return null;
    return [
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'email' => (string) $row['email'],
        'nik' => decryptField($row['nik_cipher']),
        'nik_last4' => (string) ($row['nik_last4'] ?? ''),
        'phone' => decryptField($row['phone_cipher']),
        'address' => decryptField($row['address_cipher']),
        'is_verified' => (int) $row['is_verified'],
        'status' => (string) $row['status'],
        'last_login_at' => $row['last_login_at'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
    ];
}

function requireAdmin(): array
{
    ensureSession();
    $sessionAdmin = $_SESSION['admin'] ?? null;
    $id = (int) ($sessionAdmin['id'] ?? 0);
    if ($id < 1) jsonResponse(['success' => false, 'message' => 'Belum login.'], 401);

    $stmt = db()->prepare('SELECT id,name,email,role FROM admins WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $fresh = $stmt->fetch();
    if (!$fresh) {
        $_SESSION = [];
        jsonResponse(['success' => false, 'message' => 'Sesi tidak lagi valid. Silakan login kembali.'], 401);
    }

    $admin = [
        'id' => (int) $fresh['id'],
        'name' => (string) $fresh['name'],
        'email' => (string) $fresh['email'],
        'role' => (string) $fresh['role'],
    ];
    $_SESSION['admin'] = $admin;
    return $admin;
}

function requireRole(array $roles): array
{
    $admin = requireAdmin();
    if (!in_array((string) ($admin['role'] ?? ''), $roles, true)) {
        jsonResponse(['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk fitur ini.'], 403);
    }
    return $admin;
}

function requirePermission(string $permission): array
{
    $admin = requireAdmin();
    if (!in_array($permission, rolePermissions((string) ($admin['role'] ?? '')), true)) {
        jsonResponse(['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk fitur ini.'], 403);
    }
    return $admin;
}

function requireCsrf(): void
{
    ensureSession();
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), $token)) jsonResponse(['success' => false, 'message' => 'CSRF token tidak valid.'], 419);
}

function saveUpload(array $file): ?array
{
    global $config;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) jsonResponse(['success' => false, 'message' => 'Upload dokumen gagal.'], 422);
    if (($file['size'] ?? 0) > $config['upload_max']) jsonResponse(['success' => false, 'message' => 'Ukuran file maksimal 5 MB.'], 422);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $ext = $config['allowed_uploads'][$mime] ?? null;
    if (!$ext) jsonResponse(['success' => false, 'message' => 'Format file harus PDF/JPG/PNG.'], 422);
    if (str_starts_with((string) $mime, 'image/')) {
        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo || (int) $imageInfo[0] < 1 || (int) $imageInfo[1] < 1 || ((int) $imageInfo[0] * (int) $imageInfo[1]) > (int) $config['public_image_max_pixels']) {
            jsonResponse(['success' => false, 'message' => 'Dimensi gambar tidak valid atau terlalu besar.'], 422);
        }
    }
    $dir = __DIR__ . '/storage/private';
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $stored)) jsonResponse(['success' => false, 'message' => 'Gagal menyimpan dokumen.'], 500);
    return [
        'stored_name' => $stored,
        'original_name' => cleanText(basename((string) $file['name']), 180),
        'mime' => $mime,
        'size' => (int) $file['size'],
    ];
}

function adminAudit(string $action, string $entity, ?int $entityId = null, array $meta = []): void
{
    ensureSession();
    $admin = $_SESSION['admin'] ?? null;
    if (!$admin) return;
    db()->prepare('INSERT INTO audit_logs (admin_id,action_name,entity_type,entity_id,meta_json,created_at) VALUES (?,?,?,?,?,NOW())')
        ->execute([$admin['id'], $action, $entity, $entityId, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
}
