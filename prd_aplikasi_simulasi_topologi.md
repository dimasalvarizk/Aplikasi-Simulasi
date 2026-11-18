# Product Requirements Document (PRD)
**Aplikasi Pembelajaran & Simulasi Topologi Berbasis Gamifikasi**

## 1. Ringkasan Eksekutif
Dokumen ini menguraikan spesifikasi produk untuk platform pembelajaran interaktif yang berfokus pada materi jaringan dan simulasi topologi. Platform ini memisahkan antarmuka berdasarkan peran pengguna untuk optimalisasi: aplikasi *mobile* untuk Siswa yang dirancang interaktif dengan elemen gamifikasi (XP, Level, Lencana), serta portal *web* untuk Guru guna mengelola konten dan memantau evaluasi. Seluruh sistem dirancang agar kompatibel dan optimal dijalankan pada infrastruktur *shared hosting* (cPanel).

## 2. Tujuan Produk
*   **Meningkatkan Keterlibatan Siswa:** Menerapkan sistem *reward* (XP, level, lencana) agar siswa termotivasi menyelesaikan modul dan kuis secara mandiri melalui *smartphone*.
*   **Pembelajaran Berbasis Praktik (Simulasi):** Menghadirkan *canvas* interaktif di aplikasi *mobile* bagi siswa untuk merangkai dan menguji topologi jaringan.
*   **Manajemen Data yang Efisien bagi Guru:** Menyediakan *dashboard* web yang responsif untuk memudahkan guru dalam membuat soal, memantau kemajuan tiap siswa, dan merekap nilai tanpa memerlukan keahlian teknis server.

## 3. Profil Pengguna (User Personas)
*   **Siswa:** Menggunakan aplikasi **Mobile (Android/iOS)**. Membutuhkan antarmuka yang cepat, menarik secara visual, dan hemat kuota data. Berfokus pada konsumsi materi, simulasi, dan pengerjaan kuis.
*   **Guru / Administrator:** Menggunakan browser **Web (Desktop/Tablet)**. Membutuhkan antarmuka yang fungsional untuk manajemen data besar (CRUD soal, tabel nilai siswa, pelaporan).

---

## 4. Alur Pengguna (User Flows)

### 4.1. Alur Siswa (Aplikasi Mobile)
1.  **Autentikasi:** Masuk menggunakan kredensial akun yang telah didaftarkan.
2.  **Beranda Utama:** Melihat status profil (Level saat ini, Total XP, Lencana terakhir, dan *progress bar* materi).
3.  **Eksplorasi Materi:** Memilih modul aktif -> membaca materi teori (teks/gambar).
4.  **Simulasi Topologi:** Masuk ke area *sandbox* -> melakukan *drag-and-drop* perangkat jaringan (*router*, *switch*, PC) -> menghubungkan kabel sesuai instruksi teori.
5.  **Tantangan & Kuis:** Menjawab soal pilihan ganda atau menyelesaikan studi kasus berdasarkan simulasi.
6.  **Layar Hasil (Reward):** Melihat kalkulasi skor akhir -> menerima animasi penambahan XP -> notifikasi kenaikan level atau perolehan lencana baru.
7.  **Tindak Lanjut:** Melanjutkan ke modul berikutnya atau mengulang kuis untuk memperbaiki perolehan XP.

### 4.2. Alur Guru (Portal Web)
1.  **Autentikasi:** Masuk ke *dashboard* admin melalui *web browser*.
2.  **Dashboard Utama:** Melihat statistik kelas (total siswa, rata-rata nilai, daftar materi yang paling sulit diselesaikan).
3.  **Manajemen Materi & Soal:** Menambah/mengedit teks materi, mengunggah gambar pendukung, dan membuat set soal kuis (menentukan jawaban benar dan bobot XP).
4.  **Pemantauan Siswa:** Membuka tabel rekap -> melihat detail progres per siswa (skor setiap kuis, total XP, dan kapan terakhir kali *login*).
5.  **Ekspor Laporan:** Mengunduh tabel rekap nilai dalam format PDF atau Excel/CSV untuk diserahkan ke pihak sekolah.

---

## 5. Spesifikasi Kebutuhan Fungsional (Functional Requirements)

| Fitur | Deskripsi Kebutuhan | Aktor | Platform |
| :--- | :--- | :--- | :--- |
| **Autentikasi & Otorisasi** | Sistem *login* berbasis kredensial dengan validasi *role* (Siswa/Guru). | Semua | Web & Mobile |
| **Engine Gamifikasi** | Sistem *backend* yang mengkalkulasi penambahan XP dari aktivitas membaca dan skor kuis, serta memicu transisi *level* dan *badges*. | Siswa | Mobile |
| **Manajemen Konten (CMS)** | Form input dengan editor *Rich Text* (WYSIWYG) untuk menyusun materi dan mengunggah aset gambar secara lokal. | Guru | Web |
| **Simulasi Interaktif** | *Canvas 2D* yang mendukung gestur *drag, drop, pan*, dan *zoom*. Memiliki logika validasi koneksi antar-node (misal: topologi *Star* vs *Bus*). | Siswa | Mobile |
| **Bank Soal & Kuis** | Penyajian soal secara acak, *timer* ujian, dan penguncian navigasi saat kuis berlangsung. Guru dapat mengatur *passing grade*. | Siswa, Guru | Web & Mobile |
| **Tabel Analitik & Rekap** | Tabel *datatable* dengan fitur *search, sort*, dan *pagination* untuk menampilkan ratusan baris data nilai secara ringan. | Guru | Web |
| **Ekspor Data** | Pembuatan berkas laporan CSV/Excel secara instan dari data tabel yang sedang difilter. | Guru | Web |

---

## 6. Tumpukan Teknologi (Tech Stack)

### 6.1. Backend API & Database (Di-host di cPanel)
*   **Framework:** **Laravel (PHP)**. Sangat tangguh untuk memproses logika bisnis, mengelola relasi data pendidikan, dan didukung penuh oleh semua *shared hosting*.
*   **Database:** **MySQL** atau **MariaDB**.
*   **Autentikasi API:** **Laravel Sanctum**. Digunakan untuk menerbitkan *token* akses yang aman bagi aplikasi *mobile*.
*   **Penyimpanan Media:** Local Storage (`/storage/app/public`), memanfaatkan kapasitas *disk space* dari paket *hosting* cPanel.

### 6.2. Portal Web Guru (Di-host di cPanel bersama Backend)
*   **Pendekatan:** **Monolitik Laravel** untuk kemudahan *deployment* di cPanel.
*   **Frontend UI:** **Laravel Livewire** dipadukan dengan **Alpine.js**, ATAU **Inertia.js** (dengan React/Vue yang di-build statis).
*   **Styling:** **Tailwind CSS** untuk desain *dashboard* yang responsif dan rapi.

### 6.3. Aplikasi Mobile Siswa
*   **Framework:** **Flutter (Dart)**.
*   **State Management:** BLoC atau Riverpod.
*   **Engine Simulasi:** `CustomPaint` bawaan Flutter untuk rendering komponen jaringan, atau **Flame Engine** jika interaksi fisika dibutuhkan.
*   **Networking:** Pustaka `dio` atau `http` untuk mengonsumsi REST API dari *server* Laravel.
*   **Penyimpanan Lokal:** `shared_preferences` untuk menyimpan token sesi *login*.

---

## 7. Strategi Deployment & Infrastruktur (Shared Hosting)

1.  **Persiapan Database:** Membuat database dan user MySQL melalui cPanel.
2.  **Struktur Folder:** 
    *   Berkas core Laravel (seperti `app`, `routes`) diletakkan di *root directory* cPanel demi keamanan.
    *   Isi folder `public` Laravel diletakkan di `public_html` atau subdirektori terkait.
3.  **Pengaturan PHP:** Menyesuaikan versi PHP (minimal 8.1/8.2) dan mengaktifkan ekstensi yang diperlukan di cPanel.
4.  **Distribusi Mobile:** Aplikasi Flutter di-build ke `.apk` lalu diletakkan di dalam web untuk diunduh langsung atau dipublikasikan melalui Google Play Store.

---

## 8. Spesifikasi Keamanan & Kinerja (NFR)
*   **Perlindungan API:** Kalkulasi penambahan XP dan validasi kuis dilakukan murni di server (Laravel) untuk menghindari kecurangan atau modifikasi *client-side* (*reverse engineering*).
*   **Manajemen Sesi:** Menerapkan sistem *expiry* pada *Bearer Token*.
*   **Optimalisasi Gambar:** Aset dari guru dibatasi ukuran unggahnya dan dikompresi sebelum disimpan untuk mempercepat proses *load* di aplikasi seluler siswa.