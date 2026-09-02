# Portal Gampong Full-Stack — 100% Data-Driven

Frontend modern HTML/CSS/JavaScript + backend PHP 8+ + MySQL/MariaDB + Dashboard Admin.

Prinsip project ini:

> Tidak ada data gampong dummy/hard-coded di halaman publik. Semua konten yang bisa berubah dikelola Admin → MySQL → API → `index.html`.

Fresh install hanya membuat tabel dan akun admin. Jika sebuah modul belum diisi, halaman publik menampilkan empty-state dan tidak membuat data contoh.

## Fitur publik

- Hero dinamis: eyebrow, judul, subjudul, background, profil pimpinan/Keuchik.
- Identitas gampong, kecamatan, kota/kabupaten, provinsi, kode pos, jam pelayanan.
- Logo dinamis.
- Profil, sejarah/deskripsi, visi, misi, nilai pelayanan, komitmen.
- Perangkat gampong dan foto.
- Lembaga gampong.
- Layanan publik dinamis per kategori.
- Persyaratan layanan satu-per-baris dari admin.
- Alur dan estimasi layanan.
- Pengajuan layanan online hanya untuk layanan yang diaktifkan admin.
- Upload dokumen PDF/JPG/PNG maksimal 5 MB.
- NIK 16 digit divalidasi di browser dan server.
- Tiket pengajuan otomatis.
- Pengaduan/aspirasi + kategori dinamis dari admin.
- Tiket pengaduan otomatis.
- Cek status pengajuan/pengaduan.
- Data sensitif terenkripsi AES-256-GCM.
- Statistik penduduk dinamis.
- Statistik unggulan di Hero.
- Diagram laki-laki/perempuan otomatis jika admin membuat `stat_key` `male` dan `female`.
- APBG dinamis + perhitungan realisasi + export CSV.
- Data pembangunan/proyek + progres.
- Berita.
- Pengumuman + ticker otomatis.
- Agenda.
- UMKM.
- Galeri + lightbox.
- FAQ.
- Akses cepat.
- Kontak, Google Maps embed, petunjuk arah.
- Sosial media.
- Tautan eksternal.
- Sumber data.
- Pencarian client-side dari seluruh data publik yang sudah dimuat API.
- Dark mode.
- Kontrol ukuran teks.
- Responsive desktop/tablet/mobile.
- Reduced-motion accessibility.
- SEO teknis lengkap: title, description, keywords, canonical, Open Graph, Twitter Card, JSON-LD, robots.txt, sitemap.xml, dan halaman artikel crawlable.
- PWA manifest dinamis melalui `manifest.php`.
- Service worker dengan API/auth/admin dikecualikan dari cache.
- PWA icons 192/512 + favicon.
- Session idle timeout dan session ID rotation.
- Public settings menggunakan whitelist agar setting internal tidak ikut terekspos.

## Fitur backend/admin

- PHP 8+ menggunakan PDO MySQL.
- Login admin dengan `password_hash()` / `password_verify()`.
- Role `superadmin`, `admin`, `operator`.
- Session cookie HttpOnly + SameSite Strict.
- CSRF protection untuk mutation admin.
- Rate limiting login, pengajuan, pengaduan, dan status tiket.
- NIK, telepon, alamat, nama/kontak pengaduan, dan isi pengaduan terenkripsi AES-256-GCM.
- Dokumen pengajuan disimpan di folder privat.
- Media publik JPG/PNG/WEBP maksimal 4 MB.
- Folder upload memblokir eksekusi script melalui `.htaccess`.
- Audit log aktivitas admin.
- Export seluruh data publik ke JSON.
- CRUD:
  - Perangkat gampong
  - Lembaga
  - Statistik penduduk
  - Layanan + persyaratan
  - Berita/pengumuman
  - Agenda
  - FAQ
  - UMKM
  - Galeri
  - APBG
  - Pembangunan
  - Akses cepat
  - Sosial media
  - Tautan eksternal
  - Sumber data
- Workflow status pengajuan surat.
- Workflow status pengaduan.
- Pengaturan identitas, hero, profil, kontak, peta, footer, SEO.
- Manajemen pengguna admin untuk superadmin.
- Ubah password.

## Database

Fresh database mempunyai tabel:

```text
admins
users
settings
government_officials
institutions
services
service_requirements
population_statistics
posts
agendas
umkm
galleries
budget_items
development_projects
faqs
quick_links
social_links
external_links
data_sources
service_requests
complaints
rate_limits
audit_logs
```

`backend/database/seed.sql` sengaja kosong. Tidak ada konten dummy yang di-import.

## Struktur project

```text
gampong-lamgugob/
├── index.php              # homepage server-side SEO wrapper
├── index.html             # template UI publik
├── post.php               # halaman artikel SEO
├── robots.php / robots.txt
├── sitemap.php / sitemap.xml
├── api.php
├── manifest.php
├── sw.js
├── README.md
├── admin/
│   ├── index.php
│   └── assets/
│       ├── admin.css
│       └── admin.js
├── backend/
│   ├── .env.example
│   ├── .htaccess
│   ├── api.php
│   ├── bootstrap.php
│   ├── config.php
│   ├── install.php
│   ├── reset-content.php
│   ├── database/
│   │   ├── schema.sql
│   │   └── seed.sql
│   └── storage/private/
└── assets/
    ├── css/style.css
    ├── js/app.js
    ├── images/
    └── uploads/
```

# Instalasi XAMPP Windows

## 1. Letakkan project

```text
C:\xampp\htdocs\gampong-lamgugob
```

Nyalakan Apache dan MySQL dari XAMPP Control Panel.

Pastikan extension PHP tersedia:

```text
pdo_mysql
openssl
fileinfo
mbstring
```

## 2. Buat `.env`

Git Bash:

```bash
cd /c/xampp/htdocs/gampong-lamgugob
cp backend/.env.example backend/.env
```

Generate APP_KEY:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Isi `backend/.env`:

```env
APP_NAME="Portal Gampong"
APP_ENV=production
APP_KEY=PASTE_RANDOM_KEY_DI_SINI
BASE_URL=http://localhost/gampong-lamgugob
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=gampong_lamgugob
DB_USER=root
DB_PASS=
```

**Jangan mengganti `APP_KEY` setelah data warga tersimpan**, karena key tersebut digunakan untuk mengenkripsi/dekripsi data sensitif.

## 3. Install database

Dari root project:

```bash
php backend/install.php \
  --admin-name="Administrator" \
  --admin-email="admin@example.local" \
  --admin-password="GantiPasswordKuat123!"
```

Installer akan:

1. Membuat database jika belum ada.
2. Membuat seluruh tabel.
3. Membuat/memperbarui superadmin.
4. **Tidak mengisi data gampong atau konten contoh.**

## 4. Buka

Frontend (gunakan URL root agar SEO server-side aktif):

```text
http://localhost/gampong-lamgugob/
```

Admin:

```text
http://localhost/gampong-lamgugob/admin/
```

Public API:

```text
http://localhost/gampong-lamgugob/api.php?action=content
```

# Urutan pengisian admin yang disarankan

1. **Identitas & Tampilan**
   - nama gampong
   - kecamatan/kota/provinsi
   - alamat/kontak
   - hero
   - pimpinan/Keuchik
   - profil/visi/misi
   - peta
   - SEO
2. **Perangkat Gampong**
3. **Lembaga**
4. **Statistik Penduduk**
5. **Layanan Publik + Persyaratan**
6. **Berita / Pengumuman / Agenda**
7. **APBG / Pembangunan**
8. **UMKM / Galeri**
9. **FAQ / Akses Cepat / Sosial / Tautan / Sumber Data**

Setelah admin klik simpan/publish, frontend mengambil data melalui:

```text
GET api.php?action=content
```

Tidak perlu mengubah `index.html` untuk memperbarui data gampong.

# Statistik penduduk

Setiap statistik memiliki:

```text
stat_key
label
stat_value
unit
data_year
icon
is_featured
sort_order
is_published
```

Contoh **format kunci** (bukan data contoh):

```text
population_total
male
female
families
area_km2
```

Gunakan `male` dan `female` jika ingin diagram jenis kelamin otomatis muncul.

# Layanan publik

Admin mengatur:

```text
Nama
Kategori
Deskripsi
Icon
Estimasi
Persyaratan (satu per baris)
Alur
Urutan
Bisa diajukan online
Publik/tidak
```

Hanya layanan dengan `Bisa diajukan online = Ya` dan `Publik = Ya` yang masuk ke dropdown form pengajuan.

# Status workflow

Pengajuan:

```text
pending
verified
processing
completed
rejected
```

Pengaduan:

```text
new
reviewed
in_progress
resolved
closed
```

# API publik

```text
GET  api.php?action=content
POST api.php?action=service-request
POST api.php?action=complaint
GET  api.php?action=status&ticket=...
```

# API admin

```text
POST api.php?action=admin.login
GET  api.php?action=admin.me
POST api.php?action=admin.logout
GET  api.php?action=admin.dashboard

GET  api.php?action=admin.workflow-list&kind=requests
GET  api.php?action=admin.workflow-list&kind=complaints
POST api.php?action=admin.workflow-update

GET  api.php?action=admin.cms-list&type=services
POST api.php?action=admin.cms-save
POST api.php?action=admin.cms-delete

GET  api.php?action=admin.settings-get
POST api.php?action=admin.settings-save
POST api.php?action=admin.media-upload
GET  api.php?action=admin.download&id=...

GET  api.php?action=admin.users-list
POST api.php?action=admin.user-save
POST api.php?action=admin.user-delete
GET  api.php?action=admin.audit-list
GET  api.php?action=admin.export
```

# Upgrade dari versi sebelumnya

`install.php` menggunakan `CREATE TABLE IF NOT EXISTS`, jadi dapat dijalankan lagi untuk membuat tabel modul baru.

Jika database lama **pernah diisi seed/dummy** dan kamu memang ingin mulai benar-benar kosong, backup database terlebih dahulu lalu jalankan:

```bash
php backend/reset-content.php --yes
```

Perintah tersebut menghapus **semua konten publik, pengajuan, dan pengaduan**, tetapi mempertahankan akun admin. Jangan jalankan pada database produksi yang mempunyai data warga tanpa backup.

# Keamanan produksi

- Gunakan HTTPS.
- Ganti email admin lokal.
- Gunakan password unik dan kuat.
- Gunakan `APP_KEY` acak minimal 32 karakter dan simpan aman.
- Jangan commit `backend/.env`.
- Backup database dan `backend/storage/private` secara rutin.
- Batasi akses hosting/file manager.
- Jangan simpan data warga di JavaScript, HTML, localStorage, atau repository publik.
- Tinjau kebijakan retensi dokumen warga dan hak akses admin.
- Sesuaikan validasi layanan dengan SOP resmi pemerintah gampong.
\n\n# RBAC: Super Admin, Admin, dan Operator\n\nProject menerapkan Role-Based Access Control (RBAC) di **backend API** dan **dashboard UI**. Menyembunyikan menu bukan mekanisme keamanan utama; setiap endpoint memeriksa permission server-side.\n\n| Kemampuan | Super Admin | Admin | Operator |\n|---|:---:|:---:|:---:|\n| Dashboard | ✅ | ✅ | ✅ |\n| Pengajuan surat: lihat/proses/download | ✅ | ✅ | ✅ |\n| Pengaduan: lihat/proses | ✅ | ✅ | ✅ |\n| Kelola identitas/profil/kontak | ✅ | ✅ | ❌ |\n| Kelola perangkat/lembaga/statistik | ✅ | ✅ | ❌ |\n| Kelola layanan/berita/agenda/FAQ | ✅ | ✅ | ❌ |\n| Kelola UMKM/galeri/APBG/pembangunan | ✅ | ✅ | ❌ |\n| Kelola navigasi/sosial/sumber data | ✅ | ✅ | ❌ |\n| Upload media publik | ✅ | ✅ | ❌ |\n| Export JSON publik | ✅ | ✅ | ❌ |\n| Kelola pengguna dan role | ✅ | ❌ | ❌ |\n| Audit log | ✅ | ❌ | ❌ |\n| Ubah password sendiri | ✅ | ✅ | ✅ |\n\nPermission backend:\n\n```text\nsuperadmin: dashboard, workflow.*, cms.*, settings.*, media.upload, export.public, users.manage, audit.read, account.password\nadmin:      dashboard, workflow.*, cms.*, settings.*, media.upload, export.public, account.password\noperator:   dashboard, workflow.read, workflow.update, workflow.download, account.password\n```\n\n`requireAdmin()` sekarang mengambil ulang role dari tabel `admins` pada setiap request yang dilindungi. Jika superadmin mengubah role pengguna atau menghapus akun, session lama tidak mempertahankan privilege sebelumnya.\n\nPengelolaan konten memakai permission `cms.read` / `cms.write`, pengaturan portal memakai `settings.read` / `settings.write`, pelayanan warga memakai `workflow.*`, dan pengguna/audit hanya untuk superadmin.\n\nLihat `RBAC.md` untuk matriks endpoint lengkap.\n
## Verifikasi RBAC

```bash
php backend/rbac-selftest.php
```

Lihat juga `RBAC.md` dan `RBAC_AUDIT.md`.

---

# Update: Role Warga/User + Login/Register Publik

Versi ini menambahkan autentikasi publik tanpa mengubah sifat website sebagai portal yang dapat dibuka guest.

## Role

```text
superadmin  -> pemilik sistem
admin       -> pengelola konten/data
operator    -> pelayanan warga
user        -> akun warga yang registrasi dari navbar
guest       -> pengunjung tanpa login
```

Navbar `index.html` sekarang memiliki **Masuk** dan **Daftar**. Form Masuk adalah satu pintu untuk semua role. Login staf otomatis diarahkan ke `/admin/`; login warga tetap di halaman publik dan mendapat menu **Akun Saya**.

Registrasi publik selalu membuat `role=user` dan tidak dapat membuat akun admin/operator/superadmin.

Lihat `USER_AUTH.md` dan `RBAC.md` untuk alur lengkap.

## Upgrade dari ZIP RBAC sebelumnya

Setelah mengganti source code dan memastikan backup database tersedia, jalankan installer yang sama:

```bash
php backend/install.php \
  --admin-name="Administrator" \
  --admin-email="admin@example.local" \
  --admin-password="PasswordKuat123!"
```

Installer bersifat idempotent untuk struktur utama dan akan menambahkan tabel `users`, `service_requests.user_id`, `complaints.user_id`, index, dan foreign key bila database lama belum memilikinya.

Akun warga tidak diberi data dummy. Semua akun warga berasal dari registrasi nyata atau pengelolaan akun yang dilakukan pengguna/Super Admin.


## Redesign responsif & dark mode (v5)

Versi ini mempertahankan seluruh modul backend, RBAC, akun warga, guest access, dan alur data dinamis sebelumnya. Perubahan UI utama:

- Tema diterapkan sebelum render untuk mencegah flash light mode.
- Dark mode diperbaiki untuk input, select, modal, akun warga, kartu, source strip, form pengaduan, dan komponen dinamis.
- Dashboard admin sekarang juga memiliki dark mode dan menggunakan preferensi tema yang sama dengan website publik.
- Navbar, hero, kartu Keuchik, layanan, statistik, galeri, modal, akun warga, dan footer ditata ulang agar lebih adaptif.
- Sidebar admin mobile memiliki backdrop/scrim, body lock, dan dapat ditutup dengan klik di luar atau tombol Escape.
- Tabel admin tetap dapat digeser horizontal pada layar kecil tanpa merusak layout.
- Modal mobile menggunakan batas tinggi berbasis `100dvh` agar aman pada browser mobile.
- Service worker dinaikkan ke cache `portal-gampong-v5-redesign` dan memuat stylesheet redesign untuk mode offline.

Stylesheet redesign berada di:

```text
assets/css/redesign.css
admin/assets/redesign.css
```

File CSS lama tetap dipertahankan untuk kompatibilitas seluruh fitur lama; stylesheet redesign dimuat setelahnya sebagai lapisan UI terbaru.

## Auth & Profil sebagai Halaman Terpisah

Versi terbaru memisahkan akun warga dari `index.html`:

- `login.html` — login satu pintu untuk Super Admin, Admin, Operator, dan User/Warga.
- `register.html` — registrasi publik khusus role `user`.
- `profile.html` — profil warga, riwayat pengajuan, riwayat pengaduan, dan ubah password.

`index.html` tetap dapat diakses sebagai guest dan tidak lagi memuat modal login/register/profile. Navbar membaca session dengan `auth.me`: user menuju `profile.html`, sedangkan staf menuju `admin/`.

Light mode juga mendapat override eksplisit pada komponen legacy, input/autofill, modal, card, tabel admin, halaman auth, dan halaman profil untuk mencegah teks hilang pada latar terang.


# Checklist Produksi Final

1. Gunakan HTTPS dan set `BASE_URL` ke URL HTTPS final.
2. Gunakan `APP_KEY` acak minimal 32 karakter dan jangan pernah menggantinya setelah data terenkripsi tersimpan.
3. Gunakan password database khusus dengan hak minimum; jangan memakai akun MySQL `root` di hosting produksi.
4. Pastikan document root tidak memberi akses langsung ke `backend/`. `.htaccess` project sudah memblokirnya pada Apache.
5. Backup database dan `backend/.env` secara aman.
6. Isi SEO Title, Description, Keywords, dan SEO Image dari Dashboard.
7. Daftarkan `/sitemap.xml` pada search engine setelah domain produksi aktif.
8. Uji email/nomor layanan, peta, dan seluruh data publik sebelum rilis.
9. Pertahankan PHP dan MySQL/MariaDB pada versi yang masih menerima security update.
