<?php
session_start();

// Menghubungkan aplikasi ke database
require_once '../koneksi.php';

// Memastikan hanya owner yang dapat mengakses profil
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: ../index.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$pesan_sukses = '';
$pesan_error = '';

// Memproses perubahan username dan password owner
if (isset($_POST['simpan_profile'])) {
    $username_baru = trim($_POST['username'] ?? '');
    $password_baru = $_POST['password'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    if ($username_baru === '') {
        $pesan_error = 'Username wajib diisi.';
    } elseif ($password_baru !== '' && $password_baru !== $konfirmasi_password) {
        $pesan_error = 'Konfirmasi password tidak cocok.';
    } else {
        // Memeriksa apakah username sudah digunakan akun lain
        $cek_username = mysqli_prepare(
            $koneksi,
            'SELECT user_id FROM users WHERE username = ? AND user_id != ? LIMIT 1'
        );
        mysqli_stmt_bind_param($cek_username, 'si', $username_baru, $user_id);
        mysqli_stmt_execute($cek_username);
        mysqli_stmt_store_result($cek_username);

        if (mysqli_stmt_num_rows($cek_username) > 0) {
            $pesan_error = 'Username sudah digunakan.';
        } else {
            if ($password_baru === '') {
                // Memperbarui username tanpa mengubah password
                $query = mysqli_prepare($koneksi, 'UPDATE users SET username = ? WHERE user_id = ?');
                mysqli_stmt_bind_param($query, 'si', $username_baru, $user_id);
            } else {
                // Memperbarui username dan password owner
                $password_md5 = md5($password_baru);
                $query = mysqli_prepare(
                    $koneksi,
                    'UPDATE users SET username = ?, password = ? WHERE user_id = ?'
                );
                mysqli_stmt_bind_param($query, 'ssi', $username_baru, $password_md5, $user_id);
            }

            if (mysqli_stmt_execute($query)) {
                $_SESSION['username'] = $username_baru;
                $pesan_sukses = 'Profil berhasil diperbarui.';
            } else {
                $pesan_error = 'Profil gagal diperbarui.';
            }

            mysqli_stmt_close($query);
        }

        mysqli_stmt_close($cek_username);
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
    <title>My Profile - MamaMiwa</title>
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
                <div class="menu-inner-shadow"></div>
                <ul class="menu-inner py-1">
                    <li class="menu-header mt-1"><span class="menu-header-text">Apps &amp; Pages</span></li>
                    <li class="menu-item"><a href="dashboard.php" class="menu-link"><i class="menu-icon icon-base ri ri-dashboard-line"></i><div>Dashboard</div></a></li>
                    <li class="menu-item"><a href="account.php" class="menu-link"><i class="menu-icon icon-base ri ri-group-line"></i><div>Management Akun</div></a></li>
                    <li class="menu-item active"><a href="profile.php" class="menu-link"><i class="menu-icon icon-base ri ri-user-line"></i><div>My Profile</div></a></li>
                    <li class="menu-item"><a href="../logout.php" class="menu-link"><i class="menu-icon icon-base ri ri-logout-box-r-line"></i><div>Logout</div></a></li>
                </ul>
            </aside>

            <div class="layout-page">
                <nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)"><i class="icon-base ri ri-menu-line icon-md"></i></a>
                    </div>
                    <div class="navbar-nav-right d-flex align-items-center justify-content-end w-100">
                        <h4 class="mb-0">My Profile</h4>
                        <span class="ms-auto text-muted"><?= $nama_pengguna; ?> · <?= $role_pengguna; ?></span>
                    </div>
                </nav>

                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <div class="row justify-content-center">
                            <div class="col-xl-8 col-lg-10 col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-1">Edit Profile</h5>
                                        <p class="text-muted mb-0">Perbarui username atau password owner</p>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($pesan_sukses !== '') { ?>
                                            <div class="alert alert-success" role="alert"><?= htmlspecialchars($pesan_sukses); ?></div>
                                        <?php } ?>
                                        <?php if ($pesan_error !== '') { ?>
                                            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($pesan_error); ?></div>
                                        <?php } ?>

                                        <form method="POST" action="">
                                            <div class="form-floating form-floating-outline mb-4">
                                                <input type="text" name="username" class="form-control" value="<?= $nama_pengguna; ?>" placeholder="Username" required />
                                                <label>Username</label>
                                            </div>
                                            <div class="form-floating form-floating-outline mb-4">
                                                <input type="password" name="password" class="form-control" placeholder="Password baru" />
                                                <label>Password Baru</label>
                                            </div>
                                            <div class="form-floating form-floating-outline mb-4">
                                                <input type="password" name="konfirmasi_password" class="form-control" placeholder="Konfirmasi password" />
                                                <label>Konfirmasi Password</label>
                                            </div>
                                            <button type="submit" name="simpan_profile" class="btn btn-primary">Simpan Perubahan</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>

    <script src="../assets/vendor/libs/jquery/jquery.js"></script>
    <script src="../assets/vendor/libs/popper/popper.js"></script>
    <script src="../assets/vendor/js/bootstrap.js"></script>
    <script src="../assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="../assets/vendor/js/menu.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
