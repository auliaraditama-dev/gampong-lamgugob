<?php
declare(strict_types=1);
require __DIR__ . '/../backend/bootstrap.php';
sendHtmlSecurityHeaders(true);
header('Cache-Control: private, no-store, max-age=0');
$portalIdentity = currentPortalIdentity();
if (!$portalIdentity || ($portalIdentity['account_type'] ?? '') !== 'staff') {
    header('Location: ../login.html?return=admin/');
    exit;
}
?><!doctype html>
<html lang="id" data-theme="light">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#087352">
  <script>(()=>{try{const s=localStorage.getItem('gampong-theme');const t=s==='dark'||s==='light'?s:(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.dataset.theme=t;document.querySelector('meta[name="theme-color"]').setAttribute('content',t==='dark'?'#09130f':'#087352')}catch(_){}})();</script>
  <title>Dashboard Admin · Portal Gampong</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="asset.php?file=admin.css"><link rel="stylesheet" href="asset.php?file=redesign.css">
</head>
<body>
  <div id="appView" class="app-shell">
    <div id="sidebarScrim" class="sidebar-scrim" aria-hidden="true"></div>
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-brand"><span class="brand-mark" id="adminBrandMark">PG</span><div><strong id="adminBrandName">Portal Gampong</strong><small>Admin Portal</small></div></div>
      <nav id="adminNav">
        <span class="nav-label">Utama</span>
        <button class="nav-item active" data-view="dashboard">Dashboard</button>
        <button class="nav-item" data-view="requests">Pengajuan Surat</button><button class="nav-item" data-view="complaints">Pengaduan</button>
        <span class="nav-label">Identitas</span>
        <button class="nav-item" data-view="settings">Identitas & Tampilan</button><button class="nav-item" data-view="officials">Perangkat Gampong</button><button class="nav-item" data-view="institutions">Lembaga</button><button class="nav-item" data-view="population">Statistik Penduduk</button>
        <span class="nav-label">Layanan & Informasi</span>
        <button class="nav-item" data-view="services">Layanan Publik</button><button class="nav-item" data-view="posts">Berita & Pengumuman</button><button class="nav-item" data-view="agendas">Agenda</button><button class="nav-item" data-view="faqs">FAQ</button>
        <span class="nav-label">Potensi & Transparansi</span>
        <button class="nav-item" data-view="umkm">UMKM</button><button class="nav-item" data-view="galleries">Galeri</button><button class="nav-item" data-view="budget">APBG</button><button class="nav-item" data-view="projects">Pembangunan</button>
        <span class="nav-label">Navigasi Portal</span>
        <button class="nav-item" data-view="quicklinks">Akses Cepat</button><button class="nav-item" data-view="social">Sosial Media</button><button class="nav-item" data-view="external">Tautan Eksternal</button><button class="nav-item" data-view="sources">Sumber Data</button>
        <span class="nav-label">Sistem</span>
        <button class="nav-item super-only" data-view="users">Pengguna Admin</button><button class="nav-item super-only" data-view="citizens">Pengguna Warga</button><button class="nav-item super-only" data-view="audit">Audit Log</button><button class="nav-item" data-view="security">Keamanan</button>
      </nav>
      <div class="sidebar-bottom"><a href="../" target="_blank" rel="noopener">Lihat Website</a><a id="exportPublicLink" href="../api.php?action=admin.export" target="_blank" rel="noopener">Export JSON Publik</a><button id="logoutButton" class="link-button danger">Keluar</button></div>
    </aside>

    <main class="admin-main">
      <header class="admin-header"><button id="sidebarToggle" class="icon-button" aria-label="Buka menu">☰</button><div><p class="eyebrow">Dashboard Pengelola</p><h2 id="pageTitle">Dashboard</h2></div><div class="admin-header-actions"><button id="adminThemeButton" class="icon-button admin-theme-button" type="button" aria-label="Ubah tema">☾</button><div class="admin-user"><span id="adminName">Administrator</span><small id="adminRole"></small></div></div></header>

      <section id="view-dashboard" class="view active"><div id="summaryCards" class="summary-grid"></div><article class="panel setup-panel" id="setupPanel"><div class="panel-head"><div><p class="eyebrow">Kesiapan Portal</p><h3>Checklist data publik</h3></div></div><div id="setupChecklist" class="setup-grid"></div></article><div class="panel-grid"><article class="panel"><div class="panel-head"><h3>Pengajuan Terbaru</h3><button class="text-button" data-go="requests">Lihat semua</button></div><div id="recentRequests"></div></article><article class="panel"><div class="panel-head"><h3>Pengaduan Terbaru</h3><button class="text-button" data-go="complaints">Lihat semua</button></div><div id="recentComplaints"></div></article></div></section>
      <section id="view-requests" class="view"><div class="panel"><div class="panel-head"><div><p class="eyebrow">Pelayanan</p><h3>Pengajuan Surat</h3></div><input id="requestSearch" class="search-input" placeholder="Cari tiket, nama, layanan..."></div><div id="requestsTable"></div></div></section>
      <section id="view-complaints" class="view"><div class="panel"><div class="panel-head"><div><p class="eyebrow">Aspirasi</p><h3>Pengaduan Warga</h3></div><input id="complaintSearch" class="search-input" placeholder="Cari tiket, kategori, nama..."></div><div id="complaintsTable"></div></div></section>

      <?php
      $views = [
        'officials'=>['Pemerintahan','Perangkat Gampong','Tambah Perangkat'], 'institutions'=>['Pemerintahan','Lembaga Gampong','Tambah Lembaga'],
        'population'=>['Data','Statistik Penduduk','Tambah Statistik'], 'services'=>['Pelayanan','Layanan Publik','Tambah Layanan'], 'posts'=>['Konten','Berita & Pengumuman','Tambah Konten'],
        'agendas'=>['Kegiatan','Agenda','Tambah Agenda'], 'faqs'=>['Informasi','FAQ','Tambah FAQ'], 'umkm'=>['Potensi','UMKM','Tambah UMKM'], 'galleries'=>['Dokumentasi','Galeri','Tambah Foto'],
        'budget'=>['Transparansi','APBG','Tambah Bidang'], 'projects'=>['Transparansi','Pembangunan','Tambah Proyek'], 'quicklinks'=>['Navigasi','Akses Cepat','Tambah Akses'],
        'social'=>['Navigasi','Sosial Media','Tambah Sosial'], 'external'=>['Navigasi','Tautan Eksternal','Tambah Tautan'], 'sources'=>['Data','Sumber Data','Tambah Sumber']
      ];
      foreach ($views as $key=>$v): ?>
      <section id="view-<?=htmlspecialchars($key)?>" class="view cms-view" data-type="<?=htmlspecialchars($key)?>"><div class="panel"><div class="panel-head"><div><p class="eyebrow"><?=htmlspecialchars($v[0])?></p><h3><?=htmlspecialchars($v[1])?></h3></div><button class="btn primary cms-add"><?=htmlspecialchars($v[2])?></button></div><div class="cms-table"></div></div></section>
      <?php endforeach; ?>

      <section id="view-settings" class="view"><form id="settingsForm" class="panel form-panel"><div class="panel-head"><div><p class="eyebrow">Sumber Konten Utama</p><h3>Identitas, Hero, Profil, Kontak & SEO</h3></div><button class="btn primary" type="submit">Simpan Semua</button></div>
        <div class="settings-group"><h4>Identitas Gampong</h4><div class="form-grid"><label>Nama Gampong<input name="village_name"></label><label>Nama Singkat<input name="village_short_name"></label><label>Kecamatan<input name="district"></label><label>Kota/Kabupaten<input name="city"></label><label>Provinsi<input name="province"></label><label>Kode Pos<input name="postal_code"></label><label>Prefix Tiket<input name="ticket_prefix" maxlength="6" placeholder="Contoh: GMP"></label><label>Jam Pelayanan<input name="office_hours"></label><label>Email Kantor<input name="office_email" type="email"></label><label>Telepon/WhatsApp<input name="office_phone"></label><label class="span-2">Alamat Kantor<textarea name="office_address" rows="3"></textarea></label><label class="span-2">Logo URL<input name="logo_url"><input class="setting-upload" data-target="logo_url" type="file" accept="image/jpeg,image/png,image/webp"></label></div></div>
        <div class="settings-group"><h4>Hero & Pimpinan</h4><div class="form-grid"><label>Eyebrow Hero<input name="hero_eyebrow"></label><label class="span-2">Judul Hero<textarea name="hero_title" rows="2"></textarea></label><label class="span-2">Subjudul Hero<textarea name="hero_subtitle" rows="3"></textarea></label><label class="span-2">Background Hero URL<input name="hero_background_url"><input class="setting-upload" data-target="hero_background_url" type="file" accept="image/jpeg,image/png,image/webp"></label><label>Nama Pimpinan/Keuchik<input name="keuchik_name"></label><label>Jabatan<input name="keuchik_title"></label><label class="span-2">Pesan Pimpinan<textarea name="keuchik_message" rows="3"></textarea></label><label class="span-2">Foto Pimpinan URL<input name="keuchik_photo_url"><input class="setting-upload" data-target="keuchik_photo_url" type="file" accept="image/jpeg,image/png,image/webp"></label></div></div>
        <div class="settings-group"><h4>Profil</h4><div class="form-grid"><label>Judul Profil<input name="profile_heading"></label><label class="span-2">Ringkasan Profil<textarea name="profile_summary" rows="3"></textarea></label><label class="span-2">Foto Profil URL<input name="profile_image_url"><input class="setting-upload" data-target="profile_image_url" type="file" accept="image/jpeg,image/png,image/webp"></label><label class="span-2">Sejarah/Deskripsi Gampong<textarea name="profile_history" rows="7"></textarea></label><label class="span-2">Visi<textarea name="vision" rows="4"></textarea></label><label class="span-2">Misi<textarea name="mission" rows="5"></textarea></label><label class="span-2">Nilai Pelayanan<textarea name="profile_values" rows="4"></textarea></label><label class="span-2">Komitmen<textarea name="profile_commitment" rows="4"></textarea></label></div></div>
        <div class="settings-group"><h4>Judul & Ringkasan Section</h4><div class="form-grid"><label>Judul Pemerintahan<input name="government_heading"></label><label>Ringkasan Pemerintahan<textarea name="government_summary" rows="2"></textarea></label><label>Judul Layanan<input name="services_heading"></label><label>Ringkasan Layanan<textarea name="services_summary" rows="2"></textarea></label><label>Judul Data<input name="data_heading"></label><label>Ringkasan Data<textarea name="data_summary" rows="2"></textarea></label><label>Judul Transparansi<input name="transparency_heading"></label><label>Ringkasan Transparansi<textarea name="transparency_summary" rows="2"></textarea></label><label>Judul Berita<input name="news_heading"></label><label>Ringkasan Berita<textarea name="news_summary" rows="2"></textarea></label><label>Judul UMKM<input name="umkm_heading"></label><label>Ringkasan UMKM<textarea name="umkm_summary" rows="2"></textarea></label><label>Judul Galeri<input name="gallery_heading"></label><label>Ringkasan Galeri<textarea name="gallery_summary" rows="2"></textarea></label><label>Judul Pengaduan<input name="complaint_heading"></label><label>Ringkasan Pengaduan<textarea name="complaint_summary" rows="2"></textarea></label><label>Judul FAQ<input name="faq_heading"></label><label>Ringkasan FAQ<textarea name="faq_summary" rows="2"></textarea></label><label>Judul Kontak<input name="contact_heading"></label><label>Ringkasan Kontak<textarea name="contact_summary" rows="2"></textarea></label></div></div>
        <div class="settings-group"><h4>Pengaduan, Peta, Footer & SEO</h4><div class="form-grid"><label class="span-2">Kategori Pengaduan (satu per baris)<textarea name="complaint_categories" rows="6"></textarea></label><label class="span-2">Google Maps Embed URL<input name="map_embed_url"></label><label class="span-2">URL Petunjuk Arah<input name="map_direction_url"></label><label class="span-2">Deskripsi Footer<textarea name="footer_description" rows="3"></textarea></label><label class="span-2">SEO Title<input name="seo_title"></label><label class="span-2">SEO Description<textarea name="seo_description" rows="3" maxlength="320"></textarea></label><label class="span-2">SEO Keywords<input name="seo_keywords" maxlength="500" placeholder="kata kunci dipisahkan koma"></label><label class="span-2">SEO / Social Image URL<input name="seo_image_url"><input class="setting-upload" data-target="seo_image_url" type="file" accept="image/jpeg,image/png,image/webp"></label></div></div>
      </form></section>

      <section id="view-users" class="view"><div class="panel"><div class="panel-head"><div><p class="eyebrow">Superadmin</p><h3>Pengguna Admin</h3></div><button id="addAdminButton" class="btn primary">Tambah Admin</button></div><div id="usersTable"></div></div></section>
      <section id="view-citizens" class="view"><div class="panel"><div class="panel-head"><div><p class="eyebrow">Superadmin</p><h3>Pengguna Warga / User</h3><p class="muted">Akun dari registrasi halaman publik. Superadmin dapat verifikasi, blokir, reset password, atau hapus akun.</p></div><button id="reloadCitizens" class="btn ghost">Muat Ulang</button></div><div id="citizensTable"></div></div></section>
      <section id="view-audit" class="view"><div class="panel"><div class="panel-head"><div><p class="eyebrow">Keamanan</p><h3>Audit Log</h3></div><button id="reloadAudit" class="btn ghost">Muat Ulang</button></div><div id="auditTable"></div></div></section>
      <section id="view-security" class="view"><form id="passwordForm" class="panel form-panel narrow-panel"><div class="panel-head"><div><p class="eyebrow">Akun Admin</p><h3>Ubah Password</h3></div></div><label>Password Saat Ini<input type="password" name="current_password" required autocomplete="current-password"></label><label>Password Baru<input type="password" name="new_password" required minlength="12" autocomplete="new-password"></label><label>Ulangi Password Baru<input type="password" name="confirm_password" required minlength="12" autocomplete="new-password"></label><button class="btn primary" type="submit">Ubah Password</button></form></section>
    </main>
  </div>

  <div id="drawer" class="drawer hidden" aria-hidden="true"><div class="drawer-backdrop" data-drawer-close></div><div class="drawer-card"><button class="drawer-close" data-drawer-close>×</button><div id="drawerContent"></div></div></div>
  <div id="toastRegion" class="toast-region"></div><script src="asset.php?file=admin.js"></script>
</body></html>
