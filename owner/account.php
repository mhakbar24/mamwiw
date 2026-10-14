<?php

session_start();

// Menghubungkan aplikasi ke database
require_once __DIR__ . '/../koneksi.php';


// Memastikan hanya owner yang dapat mengakses dashboard

if (!isset($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit;
}

if ($_SESSION['role'] != 'owner') {
  header("Location: ../index.php");
  exit;
}

// Menyiapkan nama dan role pengguna untuk dropdown profil
$nama_pengguna = htmlspecialchars($_SESSION['username'] ?? 'Pengguna', ENT_QUOTES, 'UTF-8');
$role_pengguna = htmlspecialchars(
  ucwords(str_replace('_', ' ', $_SESSION['role'] ?? '')),
  ENT_QUOTES,
  'UTF-8'
);

// Menyiapkan pesan hasil proses tambah akun
$pesan_sukses = '';
$pesan_error = '';

// Memproses penambahan akun admin atau tim produksi
if (isset($_POST['tambah_akun'])) {
  $username_baru = trim($_POST['username'] ?? '');
  $password_baru = $_POST['password'] ?? '';
  $role_baru = $_POST['role'] ?? '';

  if ($username_baru === '' || $password_baru === '') {
    $pesan_error = 'Username dan password wajib diisi.';
  } elseif (!in_array($role_baru, ['admin', 'production_team'], true)) {
    $pesan_error = 'Role akun tidak valid.';
  } else {
    // Memeriksa apakah username sudah digunakan
    $cek_username = mysqli_prepare(
      $koneksi,
      'SELECT user_id FROM users WHERE username = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($cek_username, 's', $username_baru);
    mysqli_stmt_execute($cek_username);
    mysqli_stmt_store_result($cek_username);

    if (mysqli_stmt_num_rows($cek_username) > 0) {
      $pesan_error = 'Username sudah digunakan.';
    } else {
      // Menyimpan akun baru dengan password yang sudah dienkripsi
      $password_md5 = md5($password_baru);
      $tambah_akun = mysqli_prepare(
        $koneksi,
        'INSERT INTO users (username, password, role) VALUES (?, ?, ?)'
      );
      mysqli_stmt_bind_param($tambah_akun, 'sss', $username_baru, $password_md5, $role_baru);

      if (mysqli_stmt_execute($tambah_akun)) {
        $pesan_sukses = 'Akun berhasil ditambahkan.';
      } else {
        $pesan_error = 'Akun gagal ditambahkan.';
      }

      mysqli_stmt_close($tambah_akun);
    }

    mysqli_stmt_close($cek_username);
  }
}

// Memproses perubahan password akun admin atau tim produksi
if (isset($_POST['ubah_password'])) {
  $user_id = (int) ($_POST['user_id'] ?? 0);
  $password_baru = $_POST['password_baru'] ?? '';

  if ($user_id <= 0 || $password_baru === '') {
    $pesan_error = 'Password baru wajib diisi.';
  } else {
    // Mengubah password hanya untuk akun admin atau tim produksi
    $password_md5 = md5($password_baru);
    $ubah_password = mysqli_prepare(
      $koneksi,
      "UPDATE users
       SET password = ?
       WHERE user_id = ?
       AND role IN ('admin', 'production_team')"
    );
    mysqli_stmt_bind_param($ubah_password, 'si', $password_md5, $user_id);

    if (mysqli_stmt_execute($ubah_password)) {
      $pesan_sukses = 'Password akun berhasil diperbarui.';
    } else {
      $pesan_error = 'Password akun gagal diperbarui.';
    }

    mysqli_stmt_close($ubah_password);
  }
}

// Mengambil daftar akun admin dan tim produksi
$daftar_akun = mysqli_query(
  $koneksi,
  "SELECT user_id, username, role
   FROM users
   WHERE role IN ('admin', 'production_team')
   ORDER BY user_id DESC"
);


// Mengambil data stok dan riwayat stok setiap produk

$products = mysqli_query(
  $koneksi,
  "SELECT
      p.product_id,
      p.name,
      p.stock,
      COALESCE((
        SELECT SUM(si.quantity)
        FROM stocks_in si
        WHERE si.product_id = p.product_id
      ), 0) AS stock_in,
      COALESCE((
        SELECT SUM(so.quantity)
        FROM stocks_out so
        WHERE so.product_id = p.product_id
      ), 0) AS stock_out
   FROM products p
   ORDER BY p.name ASC"
);


// Mengambil sepuluh aktivitas stok terbaru

$aktivitas = mysqli_query(
  $koneksi,
  "
    SELECT
        si.stockIn_id AS transaction_id,
        si.date,
        si.quantity,
        p.name AS product_name,
        u.username,
        'masuk' AS type

    FROM stocks_in si

    JOIN products p
        ON si.product_id = p.product_id

    JOIN users u
        ON si.user_id = u.user_id


    UNION ALL


    SELECT
        so.stockOut_id AS transaction_id,
        so.date,
        so.quantity,
        p.name AS product_name,
        u.username,
        'keluar' AS type

    FROM stocks_out so

    JOIN products p
        ON so.product_id = p.product_id

    JOIN users u
        ON so.user_id = u.user_id

    ORDER BY date DESC

    LIMIT 10
    "
);

?>

<!doctype html>

<html lang="en" class="layout-menu-fixed layout-compact" data-assets-path="../assets/"
  data-template="vertical-menu-template-free">

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />

  <title>Management Akun - MamaMiwa</title>

  <meta name="description" content="" />

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
    rel="stylesheet" />

  <link rel="stylesheet" href="../assets/vendor/fonts/iconify-icons.css" />

  <!-- Core CSS -->
  <!-- build:css assets/vendor/css/theme.css -->

  <link rel="stylesheet" href="../assets/vendor/libs/node-waves/node-waves.css" />

  <link rel="stylesheet" href="../assets/vendor/css/core.css" />
  <link rel="stylesheet" href="../assets/css/demo.css" />

  <!-- Vendors CSS -->

  <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

  <!-- endbuild -->

  <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />

  <!-- Page CSS -->
  <style>
    .account-table .account-action-column {
      width: 1%;
    }

    .account-table th,
    .account-table td {
      vertical-align: middle;
    }
  </style>

  <!-- Helpers -->
  <script src="../assets/vendor/js/helpers.js"></script>
  <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

  <!--? Config: Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file. -->

  <script src="../assets/js/config.js"></script>
</head>

<body>
  <!-- Layout wrapper -->
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      <!-- Menu -->

      <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
        <div class="app-brand demo">
          <a href="dashboard.php" class="app-brand-link">
            <span class="app-brand-logo demo me-1">
              <span class="text-primary">
                <svg width="30" height="24" viewBox="0 0 250 196" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M12.3002 1.25469L56.655 28.6432C59.0349 30.1128 60.4839 32.711 60.4839 35.5089V160.63C60.4839 163.468 58.9941 166.097 56.5603 167.553L12.2055 194.107C8.3836 196.395 3.43136 195.15 1.14435 191.327C0.395485 190.075 0 188.643 0 187.184V8.12039C0 3.66447 3.61061 0.0522461 8.06452 0.0522461C9.56056 0.0522461 11.0271 0.468577 12.3002 1.25469Z"
                    fill="currentColor" />
                  <path opacity="0.077704" fill-rule="evenodd" clip-rule="evenodd"
                    d="M0 65.2656L60.4839 99.9629V133.979L0 65.2656Z" fill="black" />
                  <path opacity="0.077704" fill-rule="evenodd" clip-rule="evenodd"
                    d="M0 65.2656L60.4839 99.0795V119.859L0 65.2656Z" fill="black" />
                  <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M237.71 1.22393L193.355 28.5207C190.97 29.9889 189.516 32.5905 189.516 35.3927V160.631C189.516 163.469 191.006 166.098 193.44 167.555L237.794 194.108C241.616 196.396 246.569 195.151 248.856 191.328C249.605 190.076 250 188.644 250 187.185V8.09597C250 3.64006 246.389 0.027832 241.935 0.027832C240.444 0.027832 238.981 0.441882 237.71 1.22393Z"
                    fill="currentColor" />
                  <path opacity="0.077704" fill-rule="evenodd" clip-rule="evenodd"
                    d="M250 65.2656L189.516 99.8897V135.006L250 65.2656Z" fill="black" />
                  <path opacity="0.077704" fill-rule="evenodd" clip-rule="evenodd"
                    d="M250 65.2656L189.516 99.0497V120.886L250 65.2656Z" fill="black" />
                  <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M12.2787 1.18923L125 70.3075V136.87L0 65.2465V8.06814C0 3.61223 3.61061 0 8.06452 0C9.552 0 11.0105 0.411583 12.2787 1.18923Z"
                    fill="currentColor" />
                  <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M12.2787 1.18923L125 70.3075V136.87L0 65.2465V8.06814C0 3.61223 3.61061 0 8.06452 0C9.552 0 11.0105 0.411583 12.2787 1.18923Z"
                    fill="white" fill-opacity="0.15" />
                  <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M237.721 1.18923L125 70.3075V136.87L250 65.2465V8.06814C250 3.61223 246.389 0 241.935 0C240.448 0 238.99 0.411583 237.721 1.18923Z"
                    fill="currentColor" />
                  <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M237.721 1.18923L125 70.3075V136.87L250 65.2465V8.06814C250 3.61223 246.389 0 241.935 0C240.448 0 238.99 0.411583 237.721 1.18923Z"
                    fill="white" fill-opacity="0.3" />
                </svg>
              </span>
            </span>
            <span class="app-brand-text demo menu-text fw-semibold ms-2">MamaMiwa</span>
          </a>
        </div>

        <div class="menu-inner-shadow"></div>

        <ul class="menu-inner py-1">

          <!-- Apps & Pages -->
          <li class="menu-header mt-1">
            <span class="menu-header-text">Apps &amp; Pages</span>
          </li>
          <li class="menu-item">
            <a href="/mamwiw/owner/dashboard.php" class="menu-link">
              <i class="menu-icon icon-base ri ri-dashboard-line"></i>
              <div data-i18n="Dashboard">Dashboard</div>
            </a>
          </li>

          <li class="menu-item active">
            <a href="/mamwiw/owner/account.php" class="menu-link">
              <i class="menu-icon icon-base ri ri-group-line"></i>
              <div data-i18n="Management Akun">Management Akun</div>
            </a>
          </li>

          </li>

          <li class="menu-item">
            <a href="/mamwiw/logout.php" class="menu-link">
              <i class="menu-icon icon-base ri ri-logout-box-r-line"></i>
              <div data-i18n="Logout">Logout</div>
            </a>
          </li>

        </ul>
      </aside>
      <!-- / Menu -->

      <!-- Layout container -->
      <div class="layout-page">
        <!-- Navbar -->

        <nav
          class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
          id="layout-navbar">
          <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
            <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
              <i class="icon-base ri ri-menu-line icon-md"></i>
            </a>
          </div>

          <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
            <ul class="navbar-nav flex-row align-items-center ms-md-auto">
              <!-- User -->
              <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow p-0 d-flex align-items-center" href="javascript:void(0);" data-bs-toggle="dropdown">
                  <div class="avatar avatar-online">
                    <img src="../assets/img/avatars/1.png" alt="alt" class="rounded-circle" />
                  </div>
                  <span class="ms-2">
                    <span class="d-block fw-semibold"><?= $nama_pengguna; ?></span>
                    <small class="text-muted"><?= $role_pengguna; ?></small>
                  </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <a class="dropdown-item" href="#">
                      <div class="d-flex">
                        <div class="flex-shrink-0 me-3">
                          <div class="avatar avatar-online">
                            <img src="../assets/img/avatars/1.png" alt="alt" class="w-px-40 h-auto rounded-circle" />
                          </div>
                        </div>
                        <div class="flex-grow-1">
                          <h6 class="mb-0"><?= $nama_pengguna; ?></h6>
                          <small class="text-body-secondary"><?= $role_pengguna; ?></small>
                        </div>
                      </div>
                    </a>
                  </li>
                  <li>
                    <div class="dropdown-divider my-1"></div>
                  </li>
                  <li>
                    <a class="dropdown-item" href="profile.php">
                      <i class="icon-base ri ri-user-line icon-md me-3"></i>
                      <span>My Profile</span>
                    </a>
                  </li>
                  <li>
                    <div class="dropdown-divider my-1"></div>
                  </li>
                  <li>
                    <div class="d-grid px-4 pt-2 pb-1">
                      <a class="btn btn-danger d-flex" href="../logout.php">
                        <small class="align-middle">Logout</small>
                        <i class="ri ri-logout-box-r-line ms-2 ri-xs"></i>
                      </a>
                    </div>
                  </li>
                </ul>
              </li>
              <!--/ User -->
            </ul>
          </div>
        </nav>

        <!-- / Navbar -->

        <!-- Content wrapper -->
        <div class="content-wrapper">
          <!-- Content -->
          <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row gy-6">

              <!-- FORM TAMBAH AKUN -->
              <div class="col-12">
                <div class="card">
                  <div class="card-header">
                    <h5 class="card-title mb-1">Tambah Akun</h5>
                    <p class="text-muted mb-0">Buat akun admin atau tim produksi</p>
                  </div>

                  <div class="card-body">
                    <?php if ($pesan_sukses !== '') { ?>
                      <div class="alert alert-success" role="alert">
                        <?= htmlspecialchars($pesan_sukses); ?>
                      </div>
                    <?php } ?>

                    <?php if ($pesan_error !== '') { ?>
                      <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($pesan_error); ?>
                      </div>
                    <?php } ?>

                    <form method="POST" action="">
                      <div class="row g-4">
                        <div class="col-md-4">
                          <div class="form-floating form-floating-outline">
                            <input type="text" name="username" class="form-control" placeholder="Username" required />
                            <label>Username</label>
                          </div>
                        </div>

                        <div class="col-md-4">
                          <div class="form-floating form-floating-outline">
                            <input type="password" name="password" class="form-control" placeholder="Password" required />
                            <label>Password</label>
                          </div>
                        </div>

                        <div class="col-md-4">
                          <div class="form-floating form-floating-outline">
                            <select name="role" class="form-select" required>
                              <option value="" selected disabled>Pilih role</option>
                              <option value="admin">Admin</option>
                              <option value="production_team">Tim Produksi</option>
                            </select>
                            <label>Role Akun</label>
                          </div>
                        </div>
                      </div>

                      <button type="submit" name="tambah_akun" class="btn btn-primary mt-4">
                        Tambah Akun
                      </button>
                    </form>
                  </div>
                </div>
              </div>

              <?php if (false) { ?>
              <!-- Konten dashboard owner tidak ditampilkan di halaman management akun -->
              <!-- STOK PRODUK -->

              <div class="col-lg-12">

                <div class="card h-100">

                  <div class="card-header">

                    <div class="d-flex align-items-center justify-content-between">

                      <div>

                        <h5 class="card-title m-0">
                          Stok Produk
                        </h5>

                        <p class="small mb-0">
                          Stok produk yang tersedia saat ini
                        </p>

                      </div>

                    </div>

                  </div>


                  <div class="table-responsive">

                    <table class="table table-sm mb-0">

                      <thead>
                        <tr>
                          <th>Nama Produk</th>
                          <th class="text-end">Stok Produk</th>
                          <th class="text-end">Jumlah Masuk</th>
                          <th class="text-end">Jumlah Keluar</th>
                        </tr>
                      </thead>

                      <tbody>
                        <?php if ($products && mysqli_num_rows($products) > 0) { ?>
                          <?php while ($product = mysqli_fetch_assoc($products)) { ?>
                            <tr>
                              <td>
                                <strong><?= htmlspecialchars($product['name']); ?></strong>
                              </td>
                              <td class="text-end">
                                <span class="badge bg-label-primary rounded-pill">
                                  <?= number_format($product['stock']); ?>
                                </span>
                              </td>
                              <td class="text-end text-success">
                                +<?= number_format($product['stock_in']); ?>
                              </td>
                              <td class="text-end text-danger">
                                -<?= number_format($product['stock_out']); ?>
                              </td>
                            </tr>
                          <?php } ?>
                        <?php } else { ?>
                          <tr>
                            <td colspan="4" class="text-center py-4">
                              Belum ada produk.
                            </td>
                          </tr>
                        <?php } ?>
                      </tbody>

                    </table>

                  </div>

                </div>

              </div>
              <!--/ Transactions -->

              <!-- Data Tables -->
              <div class="col-12">

                <div class="card overflow-hidden">


                  <!-- HEADER -->

                  <div class="card-header">

                    <h5 class="card-title mb-1">

                      Log Aktivitas Stok

                    </h5>

                    <p class="text-muted mb-0">

                      Aktivitas stok masuk dan keluar terbaru

                    </p>

                  </div>


                  <!-- TABLE -->

                  <div class="table-responsive">

                    <table class="table table-sm">

                      <thead>

                        <tr>

                          <th>No</th>

                          <th>Tanggal</th>

                          <th>Produk</th>

                          <th>Aktivitas</th>

                          <th>Quantity</th>

                          <th>User</th>

                        </tr>

                      </thead>


                      <tbody>

                        <?php

                        $no = 1;

                        if (
                          $aktivitas &&
                          mysqli_num_rows($aktivitas) > 0
                        ) {

                          while (
                            $row =
                            mysqli_fetch_assoc($aktivitas)
                          ) {

                        ?>

                            <tr>


                              <!-- NO -->

                              <td>

                                <?= $no++; ?>

                              </td>


                              <!-- TANGGAL -->

                              <td class="text-truncate">

                                <?= date(
                                  'd-m-Y H:i',
                                  strtotime($row['date'])
                                ); ?>

                              </td>


                              <!-- PRODUK -->

                              <td>

                                <strong>

                                  <?= htmlspecialchars(
                                    $row['product_name']
                                  ); ?>

                                </strong>

                              </td>


                              <!-- AKTIVITAS -->

                              <td>

                                <?php
                                if (
                                  $row['type'] == 'masuk'
                                ) {
                                ?>

                                  <span class="badge bg-label-success rounded-pill">

                                    <i class="ri ri-arrow-down-line me-1"></i>

                                    Stok Masuk

                                  </span>

                                <?php } else { ?>

                                  <span class="badge bg-label-danger rounded-pill">

                                    <i class="ri ri-arrow-up-line me-1"></i>

                                    Stok Keluar

                                  </span>

                                <?php } ?>

                              </td>


                              <!-- QUANTITY -->

                              <td>

                                <?php
                                if (
                                  $row['type'] == 'masuk'
                                ) {
                                ?>

                                  <strong class="text-success">

                                    +<?= number_format(
                                        $row['quantity']
                                      ); ?>

                                  </strong>

                                <?php } else { ?>

                                  <strong class="text-danger">

                                    -<?= number_format(
                                        $row['quantity']
                                      ); ?>

                                  </strong>

                                <?php } ?>

                              </td>


                              <!-- USER -->

                              <td>

                                <?= htmlspecialchars(
                                  $row['username']
                                ); ?>

                              </td>


                            </tr>

                          <?php

                          }
                        } else {

                          ?>

                          <tr>

                            <td
                              colspan="6"
                              class="text-center py-4">

                              Belum ada aktivitas stok.

                            </td>

                          </tr>

                        <?php } ?>

                      </tbody>

                    </table>

                  </div>

                </div>

              </div>
              <!--/ Data Tables -->
              <?php } ?>

              <!-- DAFTAR AKUN -->
              <div class="col-12">
                <div class="card overflow-hidden">
                  <div class="card-header">
                    <h5 class="card-title mb-1">Daftar Akun</h5>
                    <p class="text-muted mb-0">Akun admin dan tim produksi yang terdaftar</p>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle account-table">
                      <colgroup>
                        <col />
                        <col />
                        <col class="account-action-column" />
                      </colgroup>
                      <thead>
                        <tr>
                          <th>Username</th>
                          <th>Role</th>
                          <th class="text-nowrap">Aksi</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if ($daftar_akun && mysqli_num_rows($daftar_akun) > 0) { ?>
                          <?php while ($akun = mysqli_fetch_assoc($daftar_akun)) { ?>
                            <tr>
                              <td><?= htmlspecialchars($akun['username']); ?></td>
                              <td><?= $akun['role'] === 'production_team' ? 'Tim Produksi' : 'Admin'; ?></td>
                              <td class="text-nowrap">
                                <button
                                  type="button"
                                  class="btn btn-sm btn-warning btn-edit-password text-nowrap"
                                  data-bs-toggle="modal"
                                  data-bs-target="#editPasswordModal"
                                  data-user-id="<?= (int) $akun['user_id']; ?>"
                                  data-username="<?= htmlspecialchars($akun['username'], ENT_QUOTES); ?>">
                                  Edit Password
                                </button>
                              </td>
                            </tr>
                          <?php } ?>
                        <?php } else { ?>
                          <tr>
                            <td colspan="3" class="text-center py-4">Belum ada akun.</td>
                          </tr>
                        <?php } ?>
                      </tbody>
                    </table>
                  </div>

                  <div class="modal fade" id="editPasswordModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <form method="POST" action="">
                          <div class="modal-header">
                            <h5 class="modal-title">Edit Password</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="mb-3">
                              <label for="edit_password_username" class="form-label">Username</label>
                              <input type="text" id="edit_password_username" class="form-control" readonly />
                            </div>
                            <div class="form-floating form-floating-outline">
                              <input type="hidden" name="user_id" id="edit_password_user_id" />
                              <input type="password" name="password_baru" id="edit_password_value" class="form-control" placeholder="Password baru" required />
                              <label for="edit_password_value">Password Baru</label>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" name="ubah_password" class="btn btn-primary">Simpan Password</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Content -->

          <div class="content-backdrop fade"></div>
        </div>
        <!-- Content wrapper -->
      </div>
      <!-- / Layout page -->
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>
  </div>

  <!-- Core JS -->

  <script src="../assets/vendor/libs/jquery/jquery.js"></script>

  <script src="../assets/vendor/libs/popper/popper.js"></script>
  <script src="../assets/vendor/js/bootstrap.js"></script>
  <script src="../assets/vendor/libs/node-waves/node-waves.js"></script>

  <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

  <script src="../assets/vendor/js/menu.js"></script>

  <!-- endbuild -->

  <!-- Main JS -->

  <script src="../assets/js/main.js"></script>

  <!-- Page JS -->
  <script src="../assets/js/dashboards-analytics.js"></script>

  <script>
    // Mengisi modal dengan akun yang dipilih
    document.querySelectorAll('.btn-edit-password').forEach(function(button) {
      button.addEventListener('click', function() {
        document.getElementById('edit_password_user_id').value = this.dataset.userId;
        document.getElementById('edit_password_username').value = this.dataset.username;
        document.getElementById('edit_password_value').value = '';
      });
    });
  </script>

</body>

</html>
