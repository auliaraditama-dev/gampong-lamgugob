# Eye Comfort UI

Versi ini mempertahankan seluruh modul publik, autentikasi warga, RBAC, CMS, SEO, PWA, dan backend sebelumnya. Perubahan UI difokuskan pada kenyamanan penggunaan jangka panjang.

## Perubahan visual

- Gradient besar dan bertumpuk dikurangi secara signifikan.
- Warna solid hijau tua, hijau lembut, putih hangat, dan abu-hijau dipakai sebagai warna utama.
- Hero menggunakan overlay tunggal agar foto tetap terbaca tanpa kontras berlebihan.
- Orb, shimmer, dan dekorasi bergerak dikurangi.
- Shadow dipertipis dan radius kartu diseragamkan.
- Hover dibuat lebih kecil agar antarmuka tidak terasa terlalu aktif.
- Line-height dan ruang antar-section diperbesar agar nyaman untuk membaca lama.
- Dark mode memakai hitam-hijau lembut, bukan hitam murni.
- Light mode memakai latar putih-hijau lembut untuk mengurangi silau.
- Kontrol A− / A / A+ tetap memiliki kontras tinggi di light dan dark mode.

## Responsif

Optimasi tambahan diterapkan pada breakpoint 1080px, 900px, 720px, 520px, dan 390px untuk desktop, laptop, tablet, mobile, dan mobile kecil.

## Data

`backend/database/seed.sql` mengisi master data Lamgugob yang sudah memiliki dasar sumber. Data yang belum dapat diverifikasi, seperti APBG terbaru, daftar UMKM rinci, galeri resmi, jam pelayanan, telepon/email kantor gampong, dan agenda mendatang, tidak dibuat-buat dan tetap dapat diisi dari Dashboard Admin.
