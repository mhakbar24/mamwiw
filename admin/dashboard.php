<?php
session_start();

// Menghubungkan aplikasi ke database
require_once __DIR__ . '/../koneksi.php';

// Memastikan hanya admin yang dapat mengakses dashboard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header("Location: ../index.php");
  exit;
}

$nama_pengguna = htmlspecialchars($_SESSION['username'] ?? 'Pengguna', ENT_QUOTES, 'UTF-8');
$role_pengguna = htmlspecialchars(
  ucwords(str_replace('_', ' ', $_SESSION['role'] ?? '')),
  ENT_QUOTES,
  'UTF-8'
);

// Mengambil data ringkasan inventory

// Menghitung jumlah seluruh produk
$queryProduk = mysqli_query(
  $koneksi,
  "SELECT COUNT(*) AS total FROM products"
);
$totalProduk = mysqli_fetch_assoc($queryProduk)['total'];


// Menghitung total stok semua produk
$queryStok = mysqli_query(
  $koneksi,
  "SELECT COALESCE(SUM(stock), 0) AS total FROM products"
);
$totalStok = mysqli_fetch_assoc($queryStok)['total'];


// Menghitung stok masuk pada bulan ini
$queryMasuk = mysqli_query(
  $koneksi,
  "SELECT COALESCE(SUM(quantity), 0) AS total
     FROM stocks_in
     WHERE MONTH(date) = MONTH(CURRENT_DATE())
     AND YEAR(date) = YEAR(CURRENT_DATE())"
);

$totalMasuk = mysqli_fetch_assoc($queryMasuk)['total'];


// Menghitung stok keluar pada bulan ini
$queryKeluar = mysqli_query(
  $koneksi,
  "SELECT COALESCE(SUM(quantity), 0) AS total
     FROM stocks_out
     WHERE MONTH(date) = MONTH(CURRENT_DATE())
     AND YEAR(date) = YEAR(CURRENT_DATE())"
);

$totalKeluar = mysqli_fetch_assoc($queryKeluar)['total'];



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

  <title>Dashboard Admin</title>

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
          <li class="menu-item active">
            <a href="/mamwiw/admin/dashboard.php" class="menu-link">
              <i class="menu-icon icon-base ri ri-dashboard-line"></i>
              <div data-i18n="Dashboard">Dashboard</div>
            </a>
          </li>

          <li class="menu-item">
            <a href="/mamwiw/admin/product.php" class="menu-link">
              <i class="menu-icon icon-base ri ri-shopping-bag-3-line"></i>
              <div data-i18n="Produk">Produk</div>
            </a>
          </li>

          <li class="menu-item">
            <a href="/mamwiw/admin/stock_out.php" class="menu-link">
              <i class="menu-icon icon-base ri ri-inbox-unarchive-line"></i>
              <div data-i18n="Stok Keluar">Stok Keluar</div>
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
                      <a class="btn btn-danger d-flex" href="/mamwiw/logout.php">
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

              <!-- Transactions -->
              <div class="col-lg-12">
                <div class="card h-100">

                  <div class="card-header">
                    <h5 class="card-title m-0">Ringkasan Inventory</h5>
                    <p class="small mb-0">
                      Informasi stok dan aktivitas bulan ini
                    </p>
                  </div>

                  <div class="card-body pt-lg-10">

                    <div class="row g-6">

                      <!-- TOTAL PRODUK -->
                      <div class="col-md-3 col-6">
                        <div class="d-flex align-items-center">

                          <div class="avatar">
                            <div class="avatar-initial bg-primary rounded shadow-xs">
                              <i class="icon-base ri ri-box-3-line icon-24px"></i>
                            </div>
                          </div>

                          <div class="ms-3">
                            <p class="mb-0">Total Produk</p>
                            <h5 class="mb-0">
                              <?= number_format($totalProduk); ?>
                            </h5>
                          </div>

                        </div>
                      </div>


                      <!-- TOTAL STOK -->
                      <div class="col-md-3 col-6">
                        <div class="d-flex align-items-center">

                          <div class="avatar">
                            <div class="avatar-initial bg-success rounded shadow-xs">
                              <i class="icon-base ri ri-stack-line icon-24px"></i>
                            </div>
                          </div>

                          <div class="ms-3">
                            <p class="mb-0">Total Stok</p>
                            <h5 class="mb-0">
                              <?= number_format($totalStok); ?>
                            </h5>
                          </div>

                        </div>
                      </div>


                      <!-- STOK MASUK -->
                      <div class="col-md-3 col-6">
                        <div class="d-flex align-items-center">

                          <div class="avatar">
                            <div class="avatar-initial bg-info rounded shadow-xs">
                              <i class="icon-base ri ri-arrow-down-line icon-24px"></i>
                            </div>
                          </div>

                          <div class="ms-3">
                            <p class="mb-0">Stok Masuk</p>

                            <h5 class="mb-0">
                              <?= number_format($totalMasuk); ?>
                            </h5>

                            <small>Bulan ini</small>
                          </div>

                        </div>
                      </div>


                      <!-- STOK KELUAR -->
                      <div class="col-md-3 col-6">
                        <div class="d-flex align-items-center">

                          <div class="avatar">
                            <div class="avatar-initial bg-warning rounded shadow-xs">
                              <i class="icon-base ri ri-arrow-up-line icon-24px"></i>
                            </div>
                          </div>

                          <div class="ms-3">
                            <p class="mb-0">Stok Keluar</p>

                            <h5 class="mb-0">
                              <?= number_format($totalKeluar); ?>
                            </h5>

                            <small>Bulan ini</small>
                          </div>

                        </div>
                      </div>

                    </div>

                  </div>
                </div>
              </div>
              <!--/ Transactions -->

              <!-- Data Tables -->
              <div class="col-12">

                <div class="card overflow-hidden">

                  <div class="card-header">

                    <h5 class="card-title mb-1">
                      Aktivitas Stok Terbaru
                    </h5>

                    <p class="text-muted mb-0">
                      10 transaksi stok terakhir
                    </p>

                  </div>


                  <div class="table-responsive">

                    <table class="table table-sm">

                      <thead>

                        <tr>

                          <th>
                            No
                          </th>

                          <th>
                            Tanggal
                          </th>

                          <th>
                            Produk
                          </th>

                          <th>
                            Aktivitas
                          </th>

                          <th>
                            Quantity
                          </th>

                          <th>
                            User
                          </th>

                        </tr>

                      </thead>


                      <tbody>

                        <?php

                        $no = 1;

                        if ($aktivitas && mysqli_num_rows($aktivitas) > 0) {

                          while ($row = mysqli_fetch_assoc($aktivitas)) {

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

                                <?php if ($row['type'] == 'masuk') { ?>

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

                                <?php if ($row['type'] == 'masuk') { ?>

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

  <!-- Vendors JS -->
  <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>

  <!-- Main JS -->

  <script src="../assets/js/main.js"></script>

  <!-- Page JS -->
  <script src="../assets/js/dashboards-analytics.js"></script>
  <!-- Place this tag before closing body tag for github widget button. -->
</body>

</html>
