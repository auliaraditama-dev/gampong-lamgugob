<?php

declare(strict_types=1);

if (!defined('PORTAL_ENTRY')) {
    http_response_code(404);
    exit;
}

require __DIR__ . '/bootstrap.php';

$action = cleanText($_GET['action'] ?? $_POST['action'] ?? '', 80);
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function slugify(string $value): string
{
    $value = mb_strtolower(trim($value));
    $value = preg_replace('/[^\pL\pN]+/u', '-', $value) ?? '';
    $value = trim($value, '-');
    return $value !== '' ? $value : bin2hex(random_bytes(5));
}

function boolInt(mixed $value, int $default = 0): int
{
    if ($value === null || $value === '') return $default;
    return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
}

function settingsArray(): array
{
    $data = [];
    foreach (db()->query('SELECT setting_key,setting_value FROM settings ORDER BY setting_key')->fetchAll() as $row) {
        $data[$row['setting_key']] = $row['setting_value'];
    }
    return $data;
}

function publicQueryRows(PDO $pdo, string $sql, array $params = [], string $label = ''): array
{
    try {
        if ($params) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }
        return $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        if (in_array($driverCode, [1054, 1146], true)) {
            error_log('[Portal public data] Modul dilewati karena schema belum lengkap' . ($label !== '' ? " ({$label})" : '') . ': ' . $e->getMessage());
            return [];
        }
        throw $e;
    }
}

function publicContentData(): array
{
    $pdo = db();
    $settings = publicSettingsMap();
    $officials = publicQueryRows($pdo, "SELECT id,name,position,description,photo_url,sort_order FROM government_officials WHERE is_published=1 ORDER BY sort_order,id", [], 'officials');
    $institutions = publicQueryRows($pdo, "SELECT id,name,short_name,description,icon,sort_order FROM institutions WHERE is_published=1 ORDER BY sort_order,id", [], 'institutions');
    $services = publicQueryRows($pdo, "SELECT id,name,category,description,icon,estimated_time,flow_text,sort_order,is_online FROM services WHERE is_published=1 ORDER BY sort_order,id", [], 'services');
    foreach ($services as &$service) {
        $service['requirements'] = publicQueryRows($pdo, 'SELECT id,requirement_text,sort_order FROM service_requirements WHERE service_id=? ORDER BY sort_order,id', [(int) $service['id']], 'service_requirements');
    }
    unset($service);
    $population = publicQueryRows($pdo, "SELECT id,stat_key,label,stat_value,unit,data_year,icon,is_featured,sort_order FROM population_statistics WHERE is_published=1 ORDER BY sort_order,id", [], 'population');
    $posts = publicQueryRows($pdo, "SELECT id,type,title,slug,excerpt,content,category,image_url,source_url,published_at FROM posts WHERE is_published=1 AND (published_at IS NULL OR published_at<=NOW()) ORDER BY COALESCE(published_at,created_at) DESC LIMIT 100", [], 'posts');
    $agendas = publicQueryRows($pdo, "SELECT id,title,category,description,location,start_at,end_at FROM agendas WHERE is_published=1 AND start_at>=DATE_SUB(NOW(),INTERVAL 1 DAY) ORDER BY start_at ASC LIMIT 100", [], 'agendas');
    $umkm = publicQueryRows($pdo, "SELECT id,name,category,description,owner_name,contact,address,image_url,is_verified FROM umkm WHERE is_published=1 ORDER BY is_verified DESC,updated_at DESC LIMIT 100", [], 'umkm');
    $galleries = publicQueryRows($pdo, "SELECT id,title,caption,image_url,event_date FROM galleries WHERE is_published=1 ORDER BY COALESCE(event_date,DATE(created_at)) DESC,id DESC LIMIT 100", [], 'galleries');
    $budget = publicQueryRows($pdo, "SELECT id,budget_year,field_name,budget_amount,realization_amount,description FROM budget_items WHERE is_published=1 ORDER BY budget_year DESC,id", [], 'budget');
    $projects = publicQueryRows($pdo, "SELECT id,title,category,description,location,budget_amount,progress_percent,status,start_date,end_date,image_url,sort_order FROM development_projects WHERE is_published=1 ORDER BY sort_order,id DESC", [], 'projects');
    $faqs = publicQueryRows($pdo, "SELECT id,question,answer,sort_order FROM faqs WHERE is_published=1 ORDER BY sort_order,id", [], 'faqs');
    $quickLinks = publicQueryRows($pdo, "SELECT id,title,subtitle,url,icon,sort_order FROM quick_links WHERE is_published=1 ORDER BY sort_order,id", [], 'quickLinks');
    $socialLinks = publicQueryRows($pdo, "SELECT id,platform,label,url,icon,sort_order FROM social_links WHERE is_published=1 ORDER BY sort_order,id", [], 'socialLinks');
    $externalLinks = publicQueryRows($pdo, "SELECT id,label,url,sort_order FROM external_links WHERE is_published=1 ORDER BY sort_order,id", [], 'externalLinks');
    $sources = publicQueryRows($pdo, "SELECT id,title,description,url,sort_order FROM data_sources WHERE is_published=1 ORDER BY sort_order,id", [], 'sources');
    $areas = publicQueryRows($pdo, "SELECT id,name,area_type,parent_name,population,data_year,description,verification_status,source_url,sort_order FROM village_areas WHERE is_published=1 ORDER BY area_type,sort_order,id", [], 'areas');
    $boundaries = publicQueryRows($pdo, "SELECT id,direction,neighbor,description,verification_status,source_url,sort_order FROM village_boundaries WHERE is_published=1 ORDER BY sort_order,id", [], 'boundaries');
    $facilities = publicQueryRows($pdo, "SELECT id,name,category,address,description,verification_status,source_url,sort_order FROM public_facilities WHERE is_published=1 ORDER BY sort_order,id", [], 'facilities');
    $milestones = publicQueryRows($pdo, "SELECT id,slug,event_year,event_date,title,description,verification_status,source_url,sort_order FROM village_milestones WHERE is_published=1 ORDER BY event_year DESC,event_date DESC,sort_order,id", [], 'milestones');
    $mosqueManagement = publicQueryRows($pdo, "SELECT id,position,name,period_label,last_verified_at,description,verification_status,source_url,sort_order FROM mosque_management WHERE is_published=1 ORDER BY sort_order,id", [], 'mosqueManagement');
    $mosquePrograms = publicQueryRows($pdo, "SELECT id,title,category,schedule_text,description,last_verified_at,verification_status,source_url,sort_order FROM mosque_programs WHERE is_published=1 ORDER BY sort_order,id", [], 'mosquePrograms');
    $mosqueFacilities = publicQueryRows($pdo, "SELECT id,name,description,last_verified_at,verification_status,source_url,sort_order FROM mosque_facilities WHERE is_published=1 ORDER BY sort_order,id", [], 'mosqueFacilities');

    return compact(
        'settings','officials','institutions','services','population','posts','agendas','umkm','galleries',
        'budget','projects','faqs','quickLinks','socialLinks','externalLinks','sources','areas','boundaries','facilities','milestones',
        'mosqueManagement','mosquePrograms','mosqueFacilities'
    );
}

function publicContent(): never
{
    jsonResponse(['success' => true, 'data' => publicContentData()]);
}

function createServiceRequest(): never
{
    rateLimit('service-request', 8, 3600);
    $name = cleanText($_POST['name'] ?? '', 150);
    $nik = preg_replace('/\D/', '', (string) ($_POST['nik'] ?? '')) ?? '';
    $service = cleanText($_POST['service'] ?? '', 180);
    $phone = cleanText($_POST['phone'] ?? '', 60);
    $address = cleanText($_POST['address'] ?? '', 1200);
    $notes = cleanText($_POST['notes'] ?? '', 3000);

    if ($name === '' || $service === '' || $phone === '' || $address === '' || !preg_match('/^\d{16}$/', $nik)) {
        jsonResponse(['success' => false, 'message' => 'Data pengajuan belum lengkap atau NIK tidak valid.'], 422);
    }

    $serviceCheck = db()->prepare('SELECT name,is_online FROM services WHERE name=? AND is_published=1 LIMIT 1');
    $serviceCheck->execute([$service]);
    $serviceRow = $serviceCheck->fetch();
    if (!$serviceRow || !(int) $serviceRow['is_online']) {
        jsonResponse(['success' => false, 'message' => 'Layanan tidak tersedia untuk pengajuan online.'], 422);
    }

    $upload = isset($_FILES['file']) ? saveUpload($_FILES['file']) : null;
    $identity = currentPortalIdentity();
    $userId = (($identity['account_type'] ?? '') === 'user') ? (int) $identity['id'] : null;
    $ticket = makeTicket('service');
    $stmt = db()->prepare('INSERT INTO service_requests (user_id,ticket,name,nik_cipher,nik_last4,service,phone_cipher,address_cipher,notes_cipher,file_stored_name,file_original_name,file_mime,file_size) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $userId, $ticket, $name, encryptField($nik), substr($nik, -4), $service, encryptField($phone), encryptField($address), encryptField($notes),
        $upload['stored_name'] ?? null, $upload['original_name'] ?? null, $upload['mime'] ?? null, $upload['size'] ?? null,
    ]);
    jsonResponse(['success' => true, 'message' => 'Pengajuan berhasil diterima.', 'ticket' => $ticket], 201);
}

function createComplaint(): never
{
    rateLimit('complaint', 8, 3600);
    $data = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') ? bodyJson() : $_POST;
    $name = cleanText($data['name'] ?? '', 150);
    $contact = cleanText($data['contact'] ?? '', 120);
    $category = cleanText($data['category'] ?? '', 100);
    $message = cleanText($data['message'] ?? '', 5000);
    $private = boolInt($data['private'] ?? false);

    if ($name === '' || $contact === '' || $category === '' || (function_exists('mb_strlen') ? mb_strlen($message) : strlen($message)) < 10) {
        jsonResponse(['success' => false, 'message' => 'Data pengaduan belum lengkap.'], 422);
    }

    $configured = array_values(array_filter(array_map('trim', preg_split('/\R/', setting('complaint_categories', '')) ?: [])));
    if ($configured && !in_array($category, $configured, true)) jsonResponse(['success' => false, 'message' => 'Kategori pengaduan tidak valid.'], 422);

    $identity = currentPortalIdentity();
    $userId = (($identity['account_type'] ?? '') === 'user') ? (int) $identity['id'] : null;
    $ticket = makeTicket('complaint');
    $stmt = db()->prepare('INSERT INTO complaints (user_id,ticket,name_cipher,contact_cipher,category,message_cipher,is_private) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$userId, $ticket, encryptField($name), encryptField($contact), $category, encryptField($message), $private]);
    jsonResponse(['success' => true, 'message' => 'Pengaduan berhasil diterima.', 'ticket' => $ticket], 201);
}

function ticketStatus(): never
{
    rateLimit('status-check', 30, 3600);
    $ticket = strtoupper(cleanText($_GET['ticket'] ?? '', 40));
    if (!preg_match('/^[A-Z0-9]{2,7}-\d{4}-[A-F0-9]{8}$/', $ticket)) jsonResponse(['success' => false, 'message' => 'Format nomor tiket tidak valid.'], 422);

    $stmt = db()->prepare('SELECT ticket,service,status,admin_note,created_at,updated_at FROM service_requests WHERE ticket=? LIMIT 1');
    $stmt->execute([$ticket]);
    $row = $stmt->fetch();
    if ($row) jsonResponse(['success' => true, 'type' => 'service', 'data' => $row]);

    $stmt = db()->prepare('SELECT ticket,category,status,admin_note,created_at,updated_at FROM complaints WHERE ticket=? LIMIT 1');
    $stmt->execute([$ticket]);
    $row = $stmt->fetch();
    if ($row) jsonResponse(['success' => true, 'type' => 'complaint', 'data' => $row]);
    jsonResponse(['success' => false, 'message' => 'Nomor tiket tidak ditemukan.'], 404);
}

function authRegister(): never
{
    ensureSession();
    rateLimit('user-register', 5, 3600);
    $data = bodyJson();
    $name = cleanText($data['name'] ?? '', 150);
    $email = strtolower(cleanText($data['email'] ?? '', 190));
    $password = (string) ($data['password'] ?? '');
    $confirmation = (string) ($data['password_confirmation'] ?? '');
    $nik = preg_replace('/\D/', '', (string) ($data['nik'] ?? '')) ?? '';
    $phone = cleanText($data['phone'] ?? '', 80);
    $address = cleanText($data['address'] ?? '', 1500);

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Nama dan email yang valid wajib diisi.'], 422);
    }
    if (($passwordError = passwordPolicyError($password)) !== null) jsonResponse(['success' => false, 'message' => $passwordError], 422);
    if ($password !== $confirmation) jsonResponse(['success' => false, 'message' => 'Konfirmasi password tidak sama.'], 422);
    if ($nik !== '' && !preg_match('/^\d{16}$/', $nik)) jsonResponse(['success' => false, 'message' => 'NIK harus 16 digit atau dikosongkan.'], 422);

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetchColumn()) jsonResponse(['success' => false, 'message' => 'Email tidak tersedia.'], 409);

    try {
        $stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,nik_cipher,nik_hash,nik_last4,phone_cipher,address_cipher,status) VALUES (?,?,?,\'user\',?,?,?,?,?,\'active\')');
        $stmt->execute([
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $nik !== '' ? encryptField($nik) : null,
            $nik !== '' ? nikHash($nik) : null,
            $nik !== '' ? substr($nik, -4) : null,
            $phone !== '' ? encryptField($phone) : null,
            $address !== '' ? encryptField($address) : null,
        ]);
        $id = (int) $pdo->lastInsertId();
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) jsonResponse(['success' => false, 'message' => 'Email atau NIK sudah terdaftar.'], 409);
        throw $e;
    }

    session_regenerate_id(true);
    unset($_SESSION['admin']);
    $_SESSION['user'] = ['id' => $id, 'name' => $name, 'email' => $email, 'role' => 'user', 'is_verified' => 0];
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
    $profile = portalUserProfile($id);
    jsonResponse(['success' => true, 'message' => 'Registrasi berhasil.', 'identity' => publicPortalIdentity(), 'profile' => $profile, 'csrf' => $_SESSION['csrf']], 201);
}

function authLogin(): never
{
    ensureSession();
    rateLimit('portal-login', 12, 900);
    $data = bodyJson();
    $email = strtolower(cleanText($data['email'] ?? '', 190));
    $password = (string) ($data['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') jsonResponse(['success' => false, 'message' => 'Email dan password wajib diisi.'], 422);

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id,name,email,password_hash,role FROM admins WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, (string) $admin['password_hash'])) {
        session_regenerate_id(true);
        unset($_SESSION['user']);
        $_SESSION['admin'] = ['id' => (int) $admin['id'], 'name' => $admin['name'], 'email' => $admin['email'], 'role' => $admin['role']];
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
        $pdo->prepare('UPDATE admins SET last_login_at=NOW() WHERE id=?')->execute([$admin['id']]);
        adminAudit('login', 'admin', (int) $admin['id']);
        jsonResponse(['success' => true, 'identity' => publicPortalIdentity(), 'csrf' => $_SESSION['csrf'], 'redirect' => 'admin/']);
    }

    $stmt = $pdo->prepare('SELECT id,name,email,password_hash,role,is_verified,status FROM users WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, (string) $user['password_hash'])) jsonResponse(['success' => false, 'message' => 'Email atau password salah.'], 401);
    if (($user['status'] ?? '') !== 'active') jsonResponse(['success' => false, 'message' => 'Akun warga sedang diblokir. Hubungi pengelola portal.'], 403);

    session_regenerate_id(true);
    unset($_SESSION['admin']);
    $_SESSION['user'] = ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => 'user', 'is_verified' => (int) $user['is_verified']];
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
    $pdo->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([$user['id']]);
    jsonResponse(['success' => true, 'identity' => publicPortalIdentity(), 'profile' => portalUserProfile((int) $user['id']), 'csrf' => $_SESSION['csrf'], 'redirect' => null]);
}

function authMe(): never
{
    $identity = currentPortalIdentity();
    if (!$identity) jsonResponse(['success' => true, 'identity' => null, 'csrf' => null]);
    $payload = ['success' => true, 'identity' => publicPortalIdentity($identity), 'csrf' => csrfToken()];
    if (($identity['account_type'] ?? '') === 'user') $payload['profile'] = portalUserProfile((int) $identity['id']);
    jsonResponse($payload);
}

function authLogout(): never
{
    ensureSession();
    $identity = currentPortalIdentity();
    if ($identity) requireCsrf();
    if (($identity['account_type'] ?? '') === 'staff') adminAudit('logout', 'admin', (int) $identity['id']);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
    jsonResponse(['success' => true]);
}

function userAccount(): never
{
    $user = requirePortalUser();
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id,ticket,service,status,admin_note,created_at,updated_at FROM service_requests WHERE user_id=? ORDER BY id DESC LIMIT 200');
    $stmt->execute([$user['id']]);
    $requests = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT id,ticket,category,status,admin_note,created_at,updated_at FROM complaints WHERE user_id=? ORDER BY id DESC LIMIT 200');
    $stmt->execute([$user['id']]);
    $complaints = $stmt->fetchAll();
    jsonResponse(['success' => true, 'profile' => portalUserProfile((int) $user['id']), 'requests' => $requests, 'complaints' => $complaints, 'csrf' => csrfToken()]);
}

function userProfileSave(): never
{
    $user = requirePortalUser();
    requireCsrf();
    $data = bodyJson();
    $name = cleanText($data['name'] ?? '', 150);
    $email = strtolower(cleanText($data['email'] ?? '', 190));
    $nik = preg_replace('/\D/', '', (string) ($data['nik'] ?? '')) ?? '';
    $phone = cleanText($data['phone'] ?? '', 80);
    $address = cleanText($data['address'] ?? '', 1500);
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['success' => false, 'message' => 'Nama dan email valid wajib diisi.'], 422);
    if ($nik !== '' && !preg_match('/^\d{16}$/', $nik)) jsonResponse(['success' => false, 'message' => 'NIK harus 16 digit atau dikosongkan.'], 422);

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetchColumn()) jsonResponse(['success' => false, 'message' => 'Email tidak tersedia.'], 409);

    $identityStmt = $pdo->prepare('SELECT name,email,nik_hash,is_verified FROM users WHERE id=? LIMIT 1');
    $identityStmt->execute([$user['id']]);
    $oldIdentity = $identityStmt->fetch() ?: [];
    $newNikHash = $nik !== '' ? nikHash($nik) : null;
    $verification = (int) ($oldIdentity['is_verified'] ?? 0);
    if (
        (string) ($oldIdentity['name'] ?? '') !== $name ||
        strtolower((string) ($oldIdentity['email'] ?? '')) !== $email ||
        (string) ($oldIdentity['nik_hash'] ?? '') !== (string) ($newNikHash ?? '')
    ) $verification = 0;

    try {
        $stmt = $pdo->prepare('UPDATE users SET name=?,email=?,nik_cipher=?,nik_hash=?,nik_last4=?,phone_cipher=?,address_cipher=?,is_verified=?,updated_at=NOW() WHERE id=?');
        $stmt->execute([
            $name, $email,
            $nik !== '' ? encryptField($nik) : null,
            $newNikHash,
            $nik !== '' ? substr($nik, -4) : null,
            $phone !== '' ? encryptField($phone) : null,
            $address !== '' ? encryptField($address) : null,
            $verification,
            $user['id'],
        ]);
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) jsonResponse(['success' => false, 'message' => 'Email atau NIK sudah digunakan akun lain.'], 409);
        throw $e;
    }
    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['email'] = $email;
    $_SESSION['user']['is_verified'] = $verification;
    jsonResponse(['success' => true, 'message' => 'Profil berhasil diperbarui.', 'profile' => portalUserProfile((int) $user['id']), 'identity' => publicPortalIdentity()]);
}

function userChangePassword(): never
{
    $user = requirePortalUser();
    requireCsrf();
    $data = bodyJson();
    $current = (string) ($data['current_password'] ?? '');
    $new = (string) ($data['new_password'] ?? '');
    $confirmation = (string) ($data['password_confirmation'] ?? '');
    if (($passwordError = passwordPolicyError($new)) !== null) jsonResponse(['success' => false, 'message' => $passwordError], 422);
    if ($new !== $confirmation) jsonResponse(['success' => false, 'message' => 'Konfirmasi password tidak sama.'], 422);
    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id=? LIMIT 1');
    $stmt->execute([$user['id']]);
    if (!password_verify($current, (string) $stmt->fetchColumn())) jsonResponse(['success' => false, 'message' => 'Password saat ini salah.'], 422);
    db()->prepare('UPDATE users SET password_hash=?,updated_at=NOW() WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
    jsonResponse(['success' => true, 'message' => 'Password berhasil diubah.']);
}


function adminLogin(): never
{
    ensureSession();
    rateLimit('admin-login', 10, 900);
    $data = bodyJson();
    $email = strtolower(cleanText($data['email'] ?? '', 190));
    $password = (string) ($data['password'] ?? '');
    $stmt = db()->prepare('SELECT id,name,email,password_hash,role FROM admins WHERE email=? LIMIT 1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if (!$admin || !password_verify($password, $admin['password_hash'])) jsonResponse(['success' => false, 'message' => 'Email atau password salah.'], 401);
    session_regenerate_id(true);
    $_SESSION['admin'] = ['id' => (int) $admin['id'], 'name' => $admin['name'], 'email' => $admin['email'], 'role' => $admin['role']];
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
    db()->prepare('UPDATE admins SET last_login_at=NOW() WHERE id=?')->execute([$admin['id']]);
    adminAudit('login', 'admin', (int) $admin['id']);
    jsonResponse(['success' => true, 'admin' => $_SESSION['admin'], 'permissions' => rolePermissions((string) $_SESSION['admin']['role']), 'csrf' => $_SESSION['csrf']]);
}

function adminMe(): never
{
    $admin = requireAdmin();
    jsonResponse(['success' => true, 'admin' => $admin, 'permissions' => rolePermissions((string) $admin['role']), 'csrf' => csrfToken()]);
}

function adminLogout(): never
{
    $admin = requireAdmin();
    requireCsrf();
    adminAudit('logout', 'admin', (int) $admin['id']);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
    jsonResponse(['success' => true]);
}

function requireSuperAdmin(): array
{
    return requireRole(['superadmin']);
}

function adminDashboard(): never
{
    $admin = requirePermission('dashboard');
    $pdo = db();
    $counts = [
        'requests_pending' => (int) $pdo->query("SELECT COUNT(*) FROM service_requests WHERE status IN ('pending','verified','processing')")->fetchColumn(),
        'complaints_open' => (int) $pdo->query("SELECT COUNT(*) FROM complaints WHERE status IN ('new','reviewed','in_progress')")->fetchColumn(),
    ];

    $setup = [];
    if (($admin['role'] ?? '') !== 'operator') {
        $counts += [
            'services' => (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn(),
            'posts' => (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
            'agendas' => (int) $pdo->query('SELECT COUNT(*) FROM agendas')->fetchColumn(),
            'umkm' => (int) $pdo->query('SELECT COUNT(*) FROM umkm')->fetchColumn(),
            'gallery' => (int) $pdo->query('SELECT COUNT(*) FROM galleries')->fetchColumn(),
            'officials' => (int) $pdo->query('SELECT COUNT(*) FROM government_officials')->fetchColumn(),
            'areas' => (int) $pdo->query('SELECT COUNT(*) FROM village_areas')->fetchColumn(),
            'facilities' => (int) $pdo->query('SELECT COUNT(*) FROM public_facilities')->fetchColumn(),
            'mosque_programs' => (int) $pdo->query('SELECT COUNT(*) FROM mosque_programs')->fetchColumn(),
            'sources' => (int) $pdo->query('SELECT COUNT(*) FROM data_sources')->fetchColumn(),
        ];
        if (($admin['role'] ?? '') === 'superadmin') $counts['citizens'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $settings = settingsArray();
        $setup = [
            'Identitas gampong' => !empty($settings['village_name']) && !empty($settings['district']) && !empty($settings['city']),
            'Hero & profil' => !empty($settings['hero_title']) && !empty($settings['profile_history']),
            'Pimpinan' => !empty($settings['keuchik_name']),
            'Kontak & peta' => !empty($settings['office_address']) && (!empty($settings['office_phone']) || !empty($settings['office_email'])),
            'Perangkat gampong' => $counts['officials'] > 0,
            'Wilayah & dusun' => $counts['areas'] > 0,
            'Fasilitas publik' => $counts['facilities'] > 0,
            'Data Masjid Syuhada' => !empty($settings['mosque_name']) && $counts['mosque_programs'] > 0,
            'Sumber data' => $counts['sources'] > 0,
            'Layanan publik' => $counts['services'] > 0,
            'Statistik penduduk' => (int) $pdo->query('SELECT COUNT(*) FROM population_statistics')->fetchColumn() > 0,
            'Konten informasi' => ($counts['posts'] + $counts['agendas']) > 0,
        ];
    }

    $recentRequests = $pdo->query('SELECT id,ticket,name,nik_last4,service,status,created_at FROM service_requests ORDER BY id DESC LIMIT 7')->fetchAll();
    $recentComplaints = $pdo->query('SELECT id,ticket,category,status,created_at FROM complaints ORDER BY id DESC LIMIT 7')->fetchAll();
    jsonResponse([
        'success' => true,
        'data' => compact('counts','recentRequests','recentComplaints','setup'),
        'role' => $admin['role'],
        'permissions' => rolePermissions((string) $admin['role']),
    ]);
}

function listRequests(): never
{
    requirePermission('workflow.read');
    $kind = cleanText($_GET['kind'] ?? 'requests', 30);
    $pdo = db();
    if ($kind === 'complaints') {
        $rows = $pdo->query('SELECT * FROM complaints ORDER BY id DESC LIMIT 500')->fetchAll();
        foreach ($rows as &$row) {
            $row['name'] = decryptField($row['name_cipher']);
            $row['contact'] = decryptField($row['contact_cipher']);
            $row['message'] = decryptField($row['message_cipher']);
            unset($row['name_cipher'], $row['contact_cipher'], $row['message_cipher']);
        }
        jsonResponse(['success' => true, 'data' => $rows]);
    }
    $rows = $pdo->query('SELECT * FROM service_requests ORDER BY id DESC LIMIT 500')->fetchAll();
    foreach ($rows as &$row) {
        $row['nik'] = decryptField($row['nik_cipher']);
        $row['phone'] = decryptField($row['phone_cipher']);
        $row['address'] = decryptField($row['address_cipher']);
        $row['notes'] = decryptField($row['notes_cipher']);
        unset($row['nik_cipher'], $row['phone_cipher'], $row['address_cipher'], $row['notes_cipher']);
    }
    jsonResponse(['success' => true, 'data' => $rows]);
}

function updateWorkflow(): never
{
    requirePermission('workflow.update');
    requireCsrf();
    $data = bodyJson();
    $kind = cleanText($data['kind'] ?? '', 30);
    $id = (int) ($data['id'] ?? 0);
    $status = cleanText($data['status'] ?? '', 30);
    $note = cleanText($data['admin_note'] ?? '', 3000);
    $allowed = $kind === 'complaints' ? ['new','reviewed','in_progress','resolved','closed'] : ['pending','verified','processing','completed','rejected'];
    $table = $kind === 'complaints' ? 'complaints' : 'service_requests';
    if ($id < 1 || !in_array($status, $allowed, true)) jsonResponse(['success' => false, 'message' => 'Data status tidak valid.'], 422);
    db()->prepare("UPDATE {$table} SET status=?,admin_note=?,updated_at=NOW() WHERE id=?")->execute([$status, $note, $id]);
    adminAudit('update-status', $table, $id, ['status' => $status]);
    jsonResponse(['success' => true]);
}

function cmsMap(): array
{
    return [
        'posts' => ['table'=>'posts','fields'=>['type','title','slug','excerpt','content','category','image_url','source_url','published_at','is_published'],'boolean'=>['is_published'],'order'=>'COALESCE(published_at,created_at) DESC','long'=>['excerpt','content']],
        'agendas' => ['table'=>'agendas','fields'=>['title','category','description','location','start_at','end_at','is_published'],'boolean'=>['is_published'],'order'=>'start_at DESC','long'=>['description']],
        'umkm' => ['table'=>'umkm','fields'=>['name','category','description','owner_name','contact','address','image_url','is_verified','is_published'],'boolean'=>['is_verified','is_published'],'order'=>'id DESC','long'=>['description','address']],
        'galleries' => ['table'=>'galleries','fields'=>['title','caption','image_url','event_date','is_published'],'boolean'=>['is_published'],'order'=>'id DESC','long'=>['caption']],
        'budget' => ['table'=>'budget_items','fields'=>['budget_year','field_name','budget_amount','realization_amount','description','is_published'],'boolean'=>['is_published'],'order'=>'budget_year DESC,id ASC','long'=>['description']],
        'officials' => ['table'=>'government_officials','fields'=>['name','position','description','photo_url','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'sort_order,id','long'=>['description']],
        'institutions' => ['table'=>'institutions','fields'=>['name','short_name','description','icon','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'sort_order,id','long'=>['description']],
        'services' => ['table'=>'services','fields'=>['name','category','description','icon','estimated_time','flow_text','sort_order','is_online','is_published'],'boolean'=>['is_online','is_published'],'order'=>'sort_order,id','long'=>['description','flow_text']],
        'population' => ['table'=>'population_statistics','fields'=>['stat_key','label','stat_value','unit','data_year','icon','is_featured','sort_order','is_published'],'boolean'=>['is_featured','is_published'],'order'=>'sort_order,id','long'=>[]],
        'projects' => ['table'=>'development_projects','fields'=>['title','category','description','location','budget_amount','progress_percent','status','start_date','end_date','image_url','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'sort_order,id DESC','long'=>['description']],
        'faqs' => ['table'=>'faqs','fields'=>['question','answer','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'sort_order,id','long'=>['answer']],
        'quicklinks' => ['table'=>'quick_links','fields'=>['title','subtitle','url','icon','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'sort_order,id','long'=>[]],
        'social' => ['table'=>'social_links','fields'=>['platform','label','url','icon','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'sort_order,id','long'=>[]],
        'external' => ['table'=>'external_links','fields'=>['label','url','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'sort_order,id','long'=>[]],
        'sources' => ['table'=>'data_sources','fields'=>['title','description','url','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'sort_order,id','long'=>['description']],
        'areas' => ['table'=>'village_areas','fields'=>['name','area_type','parent_name','population','data_year','description','verification_status','source_url','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'area_type,sort_order,id','long'=>['description']],
        'boundaries' => ['table'=>'village_boundaries','fields'=>['direction','neighbor','description','verification_status','source_url','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'sort_order,id','long'=>['neighbor','description']],
        'facilities' => ['table'=>'public_facilities','fields'=>['name','category','address','description','verification_status','source_url','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'sort_order,id','long'=>['address','description']],
        'milestones' => ['table'=>'village_milestones','fields'=>['slug','event_year','event_date','title','description','verification_status','source_url','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'event_year DESC,event_date DESC,sort_order,id','long'=>['description']],
        'mosque_management' => ['table'=>'mosque_management','fields'=>['position','name','period_label','last_verified_at','description','verification_status','source_url','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'sort_order,id','long'=>['description']],
        'mosque_programs' => ['table'=>'mosque_programs','fields'=>['title','category','schedule_text','description','last_verified_at','verification_status','source_url','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'sort_order,id','long'=>['description']],
        'mosque_facilities' => ['table'=>'mosque_facilities','fields'=>['name','description','last_verified_at','verification_status','source_url','sort_order','is_published'],'boolean'=>['is_published'],'order'=>'sort_order,id','long'=>['description']],
    ];
}

function cmsList(): never
{
    requirePermission('cms.read');
    $type = cleanText($_GET['type'] ?? '', 30);
    $map = cmsMap();
    if (!isset($map[$type])) jsonResponse(['success' => false, 'message' => 'Tipe data tidak valid.'], 422);
    $cfg = $map[$type];
    $rows = db()->query("SELECT * FROM {$cfg['table']} ORDER BY {$cfg['order']} LIMIT 1000")->fetchAll();
    if ($type === 'services') {
        $stmt = db()->prepare('SELECT requirement_text FROM service_requirements WHERE service_id=? ORDER BY sort_order,id');
        foreach ($rows as &$row) {
            $stmt->execute([$row['id']]);
            $row['requirements_text'] = implode("\n", array_column($stmt->fetchAll(), 'requirement_text'));
        }
    }
    jsonResponse(['success' => true, 'data' => $rows]);
}

function cmsSave(): never
{
    requirePermission('cms.write');
    requireCsrf();
    $payload = bodyJson();
    $type = cleanText($payload['type'] ?? '', 30);
    $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
    $map = cmsMap();
    if (!isset($map[$type])) jsonResponse(['success' => false, 'message' => 'Tipe data tidak valid.'], 422);
    $cfg = $map[$type];
    $id = (int) ($data['id'] ?? 0);
    $values = [];

    foreach ($cfg['fields'] as $field) {
        if (!array_key_exists($field, $data)) continue;
        $value = in_array($field, $cfg['boolean'], true) ? boolInt($data[$field]) : cleanText($data[$field], in_array($field, $cfg['long'] ?? [], true) ? 30000 : 2500);
        if ($field === 'slug' && $value === '') $value = slugify((string) ($data['title'] ?? '')) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        if (in_array($field, ['published_at','start_at','end_at','event_date','start_date','end_date','last_verified_at'], true) && $value === '') $value = null;
        if (in_array($field, ['sort_order','data_year','budget_year','event_year','population'], true)) $value = $value === '' ? 0 : (int) $value;
        if (in_array($field, ['stat_value','budget_amount','realization_amount','progress_percent'], true)) $value = $value === '' ? 0 : (float) $value;
        $values[$field] = $value;
    }

    if ($type === 'services' && empty($values['name'])) jsonResponse(['success'=>false,'message'=>'Nama layanan wajib diisi.'],422);
    if ($type === 'population' && empty($values['stat_key'])) jsonResponse(['success'=>false,'message'=>'Kunci statistik wajib diisi.'],422);
    if (!$values) jsonResponse(['success' => false, 'message' => 'Tidak ada data untuk disimpan.'], 422);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ($id > 0) {
            $set = implode(',', array_map(fn($field) => "{$field}=?", array_keys($values)));
            $stmt = $pdo->prepare("UPDATE {$cfg['table']} SET {$set},updated_at=NOW() WHERE id=?");
            $stmt->execute([...array_values($values), $id]);
            $entityId = $id;
            $actionName = 'update';
        } else {
            $fields = array_keys($values);
            $placeholders = implode(',', array_fill(0, count($fields), '?'));
            $stmt = $pdo->prepare("INSERT INTO {$cfg['table']} (" . implode(',', $fields) . ") VALUES ({$placeholders})");
            $stmt->execute(array_values($values));
            $entityId = (int) $pdo->lastInsertId();
            $actionName = 'create';
        }

        if ($type === 'services') {
            $pdo->prepare('DELETE FROM service_requirements WHERE service_id=?')->execute([$entityId]);
            $requirements = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($data['requirements_text'] ?? '')) ?: [])));
            $reqStmt = $pdo->prepare('INSERT INTO service_requirements (service_id,requirement_text,sort_order) VALUES (?,?,?)');
            foreach ($requirements as $i => $requirement) $reqStmt->execute([$entityId, cleanText($requirement, 2000), $i]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($e instanceof PDOException && (int) ($e->errorInfo[1] ?? 0) === 1062) jsonResponse(['success' => false, 'message' => 'Data unik sudah digunakan.'], 409);
        throw $e;
    }

    adminAudit($actionName, $cfg['table'], $entityId);
    jsonResponse(['success' => true, 'id' => $entityId]);
}

function cmsDelete(): never
{
    requirePermission('cms.write');
    requireCsrf();
    $payload = bodyJson();
    $type = cleanText($payload['type'] ?? '', 30);
    $id = (int) ($payload['id'] ?? 0);
    $map = cmsMap();
    if (!isset($map[$type]) || $id < 1) jsonResponse(['success' => false, 'message' => 'Data tidak valid.'], 422);
    $table = $map[$type]['table'];
    db()->prepare("DELETE FROM {$table} WHERE id=?")->execute([$id]);
    adminAudit('delete', $table, $id);
    jsonResponse(['success' => true]);
}

function settingKeys(): array
{
    return [
        'village_name','village_short_name','village_code','district','city','province','postal_code','mukim_name','timezone_name','area_km2','district_area_percent','village_data_year','ticket_prefix','office_address','office_phone','office_email','office_hours',
        'logo_url','profile_image_url','hero_eyebrow','hero_title','hero_subtitle','hero_background_url','keuchik_name','keuchik_title','keuchik_message','keuchik_photo_url',
        'profile_heading','profile_summary','profile_history','vision','mission','profile_values','profile_commitment',
        'government_heading','government_summary','services_heading','services_summary','data_heading','data_summary','transparency_heading','transparency_summary',
        'news_heading','news_summary','umkm_heading','umkm_summary','gallery_heading','gallery_summary','complaint_heading','complaint_summary','complaint_categories',
        'faq_heading','faq_summary','territory_heading','territory_summary','facilities_heading','facilities_summary','timeline_heading','timeline_summary',
        'mosque_heading','mosque_summary','mosque_name','mosque_address','mosque_location_code','mosque_history','mosque_data_note','data_disclaimer',
        'contact_heading','contact_summary','map_embed_url','map_direction_url','administrative_map_image_url','administrative_map_title','administrative_map_source','administrative_map_note','footer_description','seo_title','seo_description','seo_keywords','seo_image_url'
    ];
}

function settingsGet(): never
{
    requirePermission('settings.read');
    jsonResponse(['success' => true, 'data' => settingsArray()]);
}

function settingsSave(): never
{
    requirePermission('settings.write');
    requireCsrf();
    $payload = bodyJson();
    $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
    $allowed = settingKeys();
    $stmt = db()->prepare('INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    foreach ($settings as $key => $value) {
        if (!in_array($key, $allowed, true)) continue;
        $max = in_array($key, ['profile_history','vision','mission','profile_values','profile_commitment','complaint_categories','mosque_history','mosque_data_note','data_disclaimer','administrative_map_note'], true) ? 30000 : 5000;
        $stmt->execute([$key, cleanText($value, $max)]);
    }
    adminAudit('update', 'settings');
    jsonResponse(['success' => true]);
}

function changePassword(): never
{
    $admin = requirePermission('account.password');
    requireCsrf();
    $data = bodyJson();
    $current = (string) ($data['current_password'] ?? '');
    $new = (string) ($data['new_password'] ?? '');
    if (($passwordError = passwordPolicyError($new)) !== null) jsonResponse(['success' => false, 'message' => $passwordError], 422);
    $stmt = db()->prepare('SELECT password_hash FROM admins WHERE id=?');
    $stmt->execute([$admin['id']]);
    if (!password_verify($current, (string) $stmt->fetchColumn())) jsonResponse(['success' => false, 'message' => 'Password saat ini salah.'], 422);
    db()->prepare('UPDATE admins SET password_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
    adminAudit('change-password', 'admin', (int) $admin['id']);
    jsonResponse(['success' => true]);
}

function uploadPublicMedia(): never
{
    requirePermission('media.upload');
    requireCsrf();
    global $config;
    if (!isset($_FILES['file'])) jsonResponse(['success' => false, 'message' => 'File gambar belum dipilih.'], 422);
    $file = $_FILES['file'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) jsonResponse(['success' => false, 'message' => 'Upload gambar gagal.'], 422);
    if (($file['size'] ?? 0) > $config['public_image_max']) jsonResponse(['success' => false, 'message' => 'Ukuran gambar maksimal 4 MB.'], 422);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $ext = $config['allowed_public_images'][$mime] ?? null;
    if (!$ext) jsonResponse(['success' => false, 'message' => 'Gambar harus JPG, PNG, atau WEBP.'], 422);
    $info = @getimagesize($file['tmp_name']);
    if (!$info || ($info[0] ?? 0) < 100 || ($info[1] ?? 0) < 100 || ((int) $info[0] * (int) $info[1]) > (int) $config['public_image_max_pixels']) jsonResponse(['success' => false, 'message' => 'File gambar tidak valid atau dimensinya terlalu besar.'], 422);
    $folder = dirname(__DIR__) . '/assets/uploads';
    if (!is_dir($folder)) mkdir($folder, 0755, true);
    $name = date('Ymd') . '-' . bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $folder . '/' . $name)) jsonResponse(['success' => false, 'message' => 'Gagal menyimpan gambar.'], 500);
    adminAudit('upload', 'media', null, ['file' => $name]);
    jsonResponse(['success' => true, 'url' => 'assets/uploads/' . $name], 201);
}

function downloadPrivateFile(): never
{
    requirePermission('workflow.download');
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = db()->prepare('SELECT file_stored_name,file_original_name,file_mime FROM service_requests WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row || !$row['file_stored_name']) { http_response_code(404); exit('File tidak ditemukan'); }
    $path = __DIR__ . '/storage/private/' . basename($row['file_stored_name']);
    if (!is_file($path)) { http_response_code(404); exit('File tidak ditemukan'); }
    header('Content-Type: ' . ($row['file_mime'] ?: 'application/octet-stream'));
        $downloadName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) ($row['file_original_name'] ?: 'dokumen')) ?: 'dokumen';
    header("Content-Disposition: attachment; filename=\"{$downloadName}\"; filename*=UTF-8''" . rawurlencode((string) ($row['file_original_name'] ?: 'dokumen')));
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

function adminUsersList(): never
{
    requirePermission('users.manage');
    $rows = db()->query('SELECT id,name,email,role,last_login_at,created_at,updated_at FROM admins ORDER BY id')->fetchAll();
    jsonResponse(['success'=>true,'data'=>$rows]);
}

function adminUserSave(): never
{
    $current = requirePermission('users.manage');
    requireCsrf();
    $data = bodyJson();
    $id = (int) ($data['id'] ?? 0);
    $name = cleanText($data['name'] ?? '', 120);
    $email = strtolower(cleanText($data['email'] ?? '', 190));
    $role = cleanText($data['role'] ?? 'admin', 20);
    $password = (string) ($data['password'] ?? '');
    if ($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || !in_array($role,['superadmin','admin','operator'],true)) jsonResponse(['success'=>false,'message'=>'Data admin tidak valid.'],422);
    $emailCheck = db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1'); $emailCheck->execute([$email]);
    if ($emailCheck->fetchColumn()) jsonResponse(['success'=>false,'message'=>'Email sudah digunakan akun warga/user.'],409);
    if ($id === 0 && ($passwordError = passwordPolicyError($password)) !== null) jsonResponse(['success'=>false,'message'=>$passwordError],422);

    if ($id > 0 && $role !== 'superadmin') {
        $roleStmt = db()->prepare('SELECT role FROM admins WHERE id=? LIMIT 1');
        $roleStmt->execute([$id]);
        $oldRole = $roleStmt->fetchColumn();
        if ($oldRole === false) jsonResponse(['success'=>false,'message'=>'Akun admin tidak ditemukan.'],404);
        if ($oldRole === 'superadmin') {
            $superCount = (int) db()->query("SELECT COUNT(*) FROM admins WHERE role='superadmin'")->fetchColumn();
            if ($superCount <= 1) jsonResponse(['success'=>false,'message'=>'Minimal satu superadmin harus tetap tersedia.'],422);
        }
    }

    try {
        if ($id > 0) {
            if ($password !== '' && ($passwordError = passwordPolicyError($password)) !== null) jsonResponse(['success'=>false,'message'=>$passwordError],422);
            if ($password !== '') db()->prepare('UPDATE admins SET name=?,email=?,role=?,password_hash=? WHERE id=?')->execute([$name,$email,$role,password_hash($password,PASSWORD_DEFAULT),$id]);
            else db()->prepare('UPDATE admins SET name=?,email=?,role=? WHERE id=?')->execute([$name,$email,$role,$id]);
            if ($id === (int)$current['id']) $_SESSION['admin'] = ['id'=>$id,'name'=>$name,'email'=>$email,'role'=>$role];
            adminAudit('update','admin',$id);
        } else {
            db()->prepare('INSERT INTO admins (name,email,password_hash,role) VALUES (?,?,?,?)')->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);
            $id = (int) db()->lastInsertId();
            adminAudit('create','admin',$id);
        }
    } catch (PDOException $e) {
        if ((int)($e->errorInfo[1]??0)===1062) jsonResponse(['success'=>false,'message'=>'Email admin sudah digunakan.'],409);
        throw $e;
    }
    jsonResponse(['success'=>true,'id'=>$id]);
}

function adminUserDelete(): never
{
    $current = requirePermission('users.manage');
    requireCsrf();
    $data = bodyJson();
    $id = (int) ($data['id'] ?? 0);
    if ($id < 1 || $id === (int) $current['id']) jsonResponse(['success'=>false,'message'=>'Akun ini tidak dapat dihapus.'],422);
    $superCount = (int) db()->query("SELECT COUNT(*) FROM admins WHERE role='superadmin'")->fetchColumn();
    $stmt = db()->prepare('SELECT role FROM admins WHERE id=?'); $stmt->execute([$id]);
    if ($stmt->fetchColumn()==='superadmin' && $superCount <= 1) jsonResponse(['success'=>false,'message'=>'Minimal satu superadmin harus tersedia.'],422);
    db()->prepare('DELETE FROM admins WHERE id=?')->execute([$id]);
    adminAudit('delete','admin',$id);
    jsonResponse(['success'=>true]);
}

function adminCitizensList(): never
{
    requirePermission('citizens.manage');
    $rows = db()->query('SELECT id,name,email,role,nik_last4,is_verified,status,last_login_at,created_at,updated_at FROM users ORDER BY id DESC LIMIT 1000')->fetchAll();
    jsonResponse(['success' => true, 'data' => $rows]);
}

function adminCitizenSave(): never
{
    requirePermission('citizens.manage');
    requireCsrf();
    $data = bodyJson();
    $id = (int) ($data['id'] ?? 0);
    $status = cleanText($data['status'] ?? '', 20);
    $verified = boolInt($data['is_verified'] ?? false);
    if ($id < 1 || !in_array($status, ['active','blocked'], true)) jsonResponse(['success' => false, 'message' => 'Data warga tidak valid.'], 422);
    $stmt = db()->prepare('UPDATE users SET status=?,is_verified=?,updated_at=NOW() WHERE id=?');
    $stmt->execute([$status, $verified, $id]);
    if ($stmt->rowCount() < 1) {
        $check = db()->prepare('SELECT id FROM users WHERE id=?'); $check->execute([$id]);
        if (!$check->fetchColumn()) jsonResponse(['success' => false, 'message' => 'Akun warga tidak ditemukan.'], 404);
    }
    if ($status === 'blocked' && !empty($_SESSION['user']['id']) && (int) $_SESSION['user']['id'] === $id) unset($_SESSION['user']);
    adminAudit('update', 'user', $id, ['status' => $status, 'is_verified' => $verified]);
    jsonResponse(['success' => true]);
}

function adminCitizenResetPassword(): never
{
    requirePermission('citizens.manage');
    requireCsrf();
    $data = bodyJson();
    $id = (int) ($data['id'] ?? 0);
    $password = (string) ($data['password'] ?? '');
    if ($id < 1) jsonResponse(['success' => false, 'message' => 'Akun warga tidak valid.'], 422);
    if (($passwordError = passwordPolicyError($password)) !== null) jsonResponse(['success' => false, 'message' => $passwordError], 422);
    $stmt = db()->prepare('UPDATE users SET password_hash=?,updated_at=NOW() WHERE id=?');
    $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    if ($stmt->rowCount() < 1) jsonResponse(['success' => false, 'message' => 'Akun warga tidak ditemukan.'], 404);
    adminAudit('reset-password', 'user', $id);
    jsonResponse(['success' => true]);
}

function adminCitizenDelete(): never
{
    requirePermission('citizens.manage');
    requireCsrf();
    $data = bodyJson();
    $id = (int) ($data['id'] ?? 0);
    if ($id < 1) jsonResponse(['success' => false, 'message' => 'Akun warga tidak valid.'], 422);
    db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
    adminAudit('delete', 'user', $id);
    jsonResponse(['success' => true]);
}


function auditList(): never
{
    requirePermission('audit.read');
    $rows = db()->query('SELECT l.id,l.action_name,l.entity_type,l.entity_id,l.meta_json,l.created_at,a.name AS admin_name,a.email AS admin_email FROM audit_logs l LEFT JOIN admins a ON a.id=l.admin_id ORDER BY l.id DESC LIMIT 500')->fetchAll();
    jsonResponse(['success'=>true,'data'=>$rows]);
}

function exportPublicJson(): never
{
    requirePermission('export.public');
    $data = publicContentData();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="portal-gampong-export-' . date('Ymd-His') . '.json"');
    echo json_encode(['exported_at'=>date(DATE_ATOM),'data'=>$data],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    switch ($action) {
        case 'content': if ($method === 'GET') publicContent(); break;
        case 'service-request': if ($method === 'POST') createServiceRequest(); break;
        case 'complaint': if ($method === 'POST') createComplaint(); break;
        case 'status': if ($method === 'GET') ticketStatus(); break;
        case 'auth.register': if ($method === 'POST') authRegister(); break;
        case 'auth.login': if ($method === 'POST') authLogin(); break;
        case 'auth.me': if ($method === 'GET') authMe(); break;
        case 'auth.logout': if ($method === 'POST') authLogout(); break;
        case 'user.account': if ($method === 'GET') userAccount(); break;
        case 'user.profile-save': if ($method === 'POST') userProfileSave(); break;
        case 'user.change-password': if ($method === 'POST') userChangePassword(); break;
        case 'admin.login': if ($method === 'POST') adminLogin(); break;
        case 'admin.me': if ($method === 'GET') adminMe(); break;
        case 'admin.logout': if ($method === 'POST') adminLogout(); break;
        case 'admin.dashboard': if ($method === 'GET') adminDashboard(); break;
        case 'admin.workflow-list': if ($method === 'GET') listRequests(); break;
        case 'admin.workflow-update': if ($method === 'POST') updateWorkflow(); break;
        case 'admin.cms-list': if ($method === 'GET') cmsList(); break;
        case 'admin.cms-save': if ($method === 'POST') cmsSave(); break;
        case 'admin.cms-delete': if ($method === 'POST') cmsDelete(); break;
        case 'admin.settings-get': if ($method === 'GET') settingsGet(); break;
        case 'admin.settings-save': if ($method === 'POST') settingsSave(); break;
        case 'admin.change-password': if ($method === 'POST') changePassword(); break;
        case 'admin.media-upload': if ($method === 'POST') uploadPublicMedia(); break;
        case 'admin.download': if ($method === 'GET') downloadPrivateFile(); break;
        case 'admin.users-list': if ($method === 'GET') adminUsersList(); break;
        case 'admin.user-save': if ($method === 'POST') adminUserSave(); break;
        case 'admin.user-delete': if ($method === 'POST') adminUserDelete(); break;
        case 'admin.citizens-list': if ($method === 'GET') adminCitizensList(); break;
        case 'admin.citizen-save': if ($method === 'POST') adminCitizenSave(); break;
        case 'admin.citizen-reset-password': if ($method === 'POST') adminCitizenResetPassword(); break;
        case 'admin.citizen-delete': if ($method === 'POST') adminCitizenDelete(); break;
        case 'admin.audit-list': if ($method === 'GET') auditList(); break;
        case 'admin.export': if ($method === 'GET') exportPublicJson(); break;
    }
    jsonResponse(['success' => false, 'message' => 'Endpoint tidak ditemukan atau method tidak diizinkan.'], 404);
} catch (PDOException $e) {
    $message = ($config['app_env'] ?? 'production') === 'local' ? $e->getMessage() : 'Terjadi kesalahan database.';
    jsonResponse(['success' => false, 'message' => $message], 500);
} catch (Throwable $e) {
    $message = ($config['app_env'] ?? 'production') === 'local' ? $e->getMessage() : 'Terjadi kesalahan pada server.';
    jsonResponse(['success' => false, 'message' => $message], 500);
}
