# Deploy melalui browser

Proyek menggunakan PHP dan MySQL. File Composer dan Procfile sudah tersedia.

1. Pastikan perubahan terbaru sudah di-push ke repository GitHub `mhakbar24/mamwiw`, branch `main`.
2. Buka https://dashboard.heroku.com/ lalu pilih **New > Create new app**. Tentukan nama unik dan region yang tersedia.
3. Pada **Settings > Buildpacks**, tambahkan `heroku/php` bila PHP tidak terdeteksi otomatis.
4. Pada **Resources**, tambahkan **JawsDB MySQL**. Periksa harga dan persyaratan pembayaran sebelum memilih paket. Add-on akan membuat config var `JAWSDB_URL` yang dibaca oleh `koneksi.php`.
5. Buka dashboard JawsDB untuk melihat host, port, username, password, dan nama database. Gunakan client MySQL seperti MySQL Workbench untuk mengimpor `mamwiw.sql` ke database tersebut. File SQL ini belum tentu mencakup perubahan data terbaru dari XAMPP; ekspor ulang database lokal jika diperlukan. Jangan membagikan kredensial database.
6. Pada **Deploy > Deployment method**, pilih **GitHub**, hubungkan akun, cari repository `mamwiw`, lalu pilih **Connect**.
7. Pada **Manual deploy**, pilih branch `main`, lalu **Deploy Branch**. Tunggu sampai build selesai.
8. Pada **Resources**, pastikan proses `web` aktif dan pilih paket dyno sesuai kebutuhan serta harga yang ditampilkan.
9. Klik **Open app**. Uji login dan operasi stok. Jika gagal, periksa **More > View logs** dan pastikan database sudah diimpor.

Heroku tidak menyediakan hosting dyno gratis. Biaya database bergantung pada paket add-on yang dipilih.

Jika repository GitHub bersifat private, berikan akses integrasi Heroku ke repository tersebut. Jangan mengunggah file `.env` atau password database ke GitHub.
