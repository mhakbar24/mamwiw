<?php
session_start();

// Menghubungkan aplikasi ke database
require_once __DIR__ . '/../koneksi.php';

// Memastikan hanya admin yang dapat mengakses profil
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$pesan_sukses = '';
$pesan_error = '';

// Memproses perubahan data profil
if (isset($_POST['simpan_profile'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    if ($username === '') {
        $pesan_error = 'Username wajib diisi.';
    } elseif ($password !== '' && $password !== $konfirmasi_password) {
        $pesan_error = 'Konfirmasi password tidak cocok.';
    } else {
        $cek_username = mysqli_prepare(
            $koneksi,
            'SELECT user_id FROM users WHERE username = ? AND user_id != ? LIMIT 1'
        );
        mysqli_stmt_bind_param($cek_username, 'si', $username, $user_id);
        mysqli_stmt_execute($cek_username);
        $username_sudah_dipakai = mysqli_stmt_get_result($cek_username);

        if (mysqli_num_rows($username_sudah_dipakai) > 0) {
            $pesan_error = 'Username sudah digunakan pengguna lain.';
        } else {
            if ($password === '') {
                $query = mysqli_prepare($koneksi, 'UPDATE users SET username = ? WHERE user_id = ?');
                mysqli_stmt_bind_param($query, 'si', $username, $user_id);
            } else {
                $password_md5 = md5($password);
                $query = mysqli_prepare($koneksi, 'UPDATE users SET username = ?, password = ? WHERE user_id = ?');
                mysqli_stmt_bind_param($query, 'ssi', $username, $password_md5, $user_id);
            }

            if (mysqli_stmt_execute($query)) {
                $_SESSION['username'] = $username;
                $pesan_sukses = 'Profile berhasil diperbarui.';
            } else {
                $pesan_error = 'Profile gagal diperbarui.';
            }
        }
    }
}

// Menyiapkan data pengguna untuk ditampilkan
$nama_pengguna = htmlspecialchars($_SESSION['username'] ?? 'Pengguna', ENT_QUOTES, 'UTF-8');
$role_pengguna = htmlspecialchars(
    ucwords(str_replace('_', ' ', $_SESSION['role'] ?? '')),
    ENT_QUOTES,
    'UTF-8'
);
?>
<!doctype html>
<html lang="id" class="layout-menu-fixed layout-compact" data-assets-path="../assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>My Profile</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    <link rel="stylesheet" href="../assets/vendor/fonts/iconify-icons.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
</head>
<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="dashboard.php" class="app-brand-link">
                        <span class="app-brand-text demo menu-text fw-semibold ms-2">MamaMiwa</span>
                    </a>
                </div>
                <ul class="menu-inner py-1">
                    <li class="menu-item">
                        <a href="dashboard.php" class="menu-link">
                            <i class="menu-icon icon-base ri ri-dashboard-line"></i>
                            <div>Dashboard</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="product.php" class="menu-link">
                            <i class="menu-icon icon-base ri ri-shopping-bag-3-line"></i>
                            <div>Produk</div>
                        </a>
                    </li>
                    <li class="menu-item active">
                        <a href="profile.php" class="menu-link">
                            <i class="menu-icon icon-base ri ri-user-line"></i>
                            <div>My Profile</div>
                        </a>
                    </li>
                </ul>
            </aside>

            <div class="layout-page">
                <nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                            <i class="icon-base ri ri-menu-line icon-md"></i>
                        </a>
                    </div>
                    <div class="navbar-nav-right d-flex align-items-center justify-content-end w-100">
                        <div class="ms-auto d-flex align-items-center">
                            <div class="avatar avatar-online me-2">
                                <img src="../assets/img/avatars/1.png" alt="Profil pengguna" class="rounded-circle" />
                            </div>
                            <div>
                                <div class="fw-semibold"><?= $nama_pengguna; ?></div>
                                <small class="text-muted"><?= $role_pengguna; ?></small>
                            </div>
                        </div>
                    </div>
                </nav>

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">My Profile</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($pesan_sukses !== '') { ?>
                                    <div class="alert alert-success"><?= htmlspecialchars($pesan_sukses); ?></div>
                                <?php } ?>
                                <?php if ($pesan_error !== '') { ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($pesan_error); ?></div>
                                <?php } ?>

                                <form method="POST" action="">
                                    <div class="mb-4">
                                        <label class="form-label" for="username">Username</label>
                                        <input type="text" id="username" name="username" class="form-control" value="<?= $nama_pengguna; ?>" required />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="password">Password Baru</label>
                                        <input type="password" id="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password" />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="konfirmasi_password">Konfirmasi Password Baru</label>
                                        <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="form-control" />
                                    </div>
                                    <button type="submit" name="simpan_profile" class="btn btn-primary">SIMPAN PERUBAHAN</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
</body>
</html>
