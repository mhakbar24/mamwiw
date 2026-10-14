<?php
session_start();

// Menghubungkan aplikasi ke database
require_once __DIR__ . '/../koneksi.php';

if (!$koneksi) {
  die("Koneksi database gagal: " . mysqli_connect_error());
}

// Memastikan hanya admin yang dapat mengakses stok keluar
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

// Memproses pengurangan stok keluar
if (isset($_POST['stok_keluar'])) {

    $product_id = (int) $_POST['product_id'];
    $quantity   = (int) $_POST['jumlah'];

    // Mengambil ID user yang sedang login
    $user_id = (int) $_SESSION['user_id'];

    if ($quantity <= 0) {

        header("Location: stock_out.php?status=invalid");
        exit;
    }

    // Memulai transaksi database
    mysqli_begin_transaction($koneksi);

    try {

        // Mengunci dan mengambil data produk
        $cek = mysqli_query(
            $koneksi,
            "SELECT product_id, stock
             FROM products
             WHERE product_id = '$product_id'
             FOR UPDATE"
        );

        if (!$cek || mysqli_num_rows($cek) == 0) {
            throw new Exception("Produk tidak ditemukan.");
        }

        $product = mysqli_fetch_assoc($cek);

        // Memastikan stok yang tersedia mencukupi
        if ($quantity > $product['stock']) {

            mysqli_rollback($koneksi);

            header("Location: stock_out.php?status=insufficient");
            exit;
        }

        // Mengurangi jumlah stok pada tabel produk
        $update = mysqli_query(
            $koneksi,
            "UPDATE products
             SET stock = stock - $quantity
             WHERE product_id = '$product_id'"
        );

        if (!$update) {
            throw new Exception("Gagal mengurangi stok.");
        }

        // Menyimpan riwayat stok keluar
        $insert = mysqli_query(
            $koneksi,
            "INSERT INTO stocks_out
                (product_id, user_id, date, quantity)
             VALUES
                ('$product_id', '$user_id', NOW(), '$quantity')"
        );

        if (!$insert) {
            throw new Exception("Gagal menyimpan riwayat stok keluar.");
        }

        // Menyimpan seluruh perubahan jika proses berhasil
        mysqli_commit($koneksi);

        header("Location: stock_out.php?status=success");
        exit;

    } catch (Exception $e) {

        // Membatalkan seluruh perubahan jika terjadi kesalahan
        mysqli_rollback($koneksi);

        header("Location: stock_out.php?status=error");
        exit;
    }
}

// Mengambil semua data produk untuk pilihan stok keluar
$products = mysqli_query(
    $koneksi,
    "SELECT product_id, name, stock
     FROM products
     ORDER BY product_id DESC"
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

  <title>Dashboard Admin / Produk</title>

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
    .sortable-header {
      background: none;
      border: 0;
      box-shadow: none;
      color: inherit;
      font: inherit;
      font-weight: 600;
      padding: 0;
    }

    .sortable-header:hover,
    .sortable-header:focus {
      background: none;
      box-shadow: none;
      color: inherit;
    }

    #adminStockOutPagination .page-link {
      border-radius: 0 !important;
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

          <li class="menu-item active">
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
            <!-- Search -->
            <div class="navbar-nav align-items-center">
              <div class="nav-item d-flex align-items-center">
                <i class="icon-base ri ri-search-line icon-lg lh-0"></i>
                <input type="search" id="adminStockOutSearch" class="form-control border-0 shadow-none" placeholder="Cari produk..."
                  aria-label="Cari produk..." />
              </div>
            </div>
            <!-- /Search -->

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
              <!-- Data Tables -->
              <div class="col-12">
                <div class="mb-7">
                </div>

                <?php if (isset($_GET['status'])) { ?>

                  <?php if ($_GET['status'] == 'success') { ?>

                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                      <strong>Berhasil!</strong>
                      Stok produk berhasil dikeluarkan.

                      <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                      </button>
                    </div>

                  <?php } elseif ($_GET['status'] == 'invalid') { ?>

                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                      <strong>Gagal!</strong>
                      Jumlah stok keluar harus lebih dari 0.

                      <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                      </button>
                    </div>

                  <?php } elseif ($_GET['status'] == 'insufficient') { ?>

                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <strong>Gagal!</strong>
                      Jumlah stok keluar melebihi stok yang tersedia.

                      <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                      </button>
                    </div>

                  <?php } elseif ($_GET['status'] == 'notfound') { ?>

                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <strong>Gagal!</strong>
                      Produk tidak ditemukan.

                      <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                      </button>
                    </div>

                  <?php } elseif ($_GET['status'] == 'error') { ?>

                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <strong>Error!</strong>
                      Terjadi kesalahan saat mengurangi stok.

                      <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                      </button>
                    </div>

                  <?php } ?>

                <?php } ?>
                <div class="card overflow-hidden">
                  <div class="table-responsive">
                    <table class="table table-sm">
                      <thead>
                        <tr>
                          <th class="text-truncate">No</th>
                          <th class="text-truncate"><button type="button" class="sortable-header" data-sort-key="id">ID <span>↕</span></button></th>
                          <th class="text-truncate"><button type="button" class="sortable-header" data-sort-key="name">Nama Produk <span>↕</span></button></th>
                          <th class="text-truncate"><button type="button" class="sortable-header" data-sort-key="stock">Stock <span>↕</span></button></th>
                          <th class="text-truncate">Action</th>
                        </tr>
                      </thead>
                      <tbody id="adminStockOutTableBody">

                        <?php
                        $no = 1;

                        if (mysqli_num_rows($products) > 0) {

                          while ($product = mysqli_fetch_assoc($products)) {
                        ?>

                            <tr class="admin-stock-out-row" data-product-id="<?= $product['product_id']; ?>" data-product-name="<?= htmlspecialchars(strtolower($product['name']), ENT_QUOTES); ?>" data-product-stock="<?= $product['stock']; ?>">

                              <td>
                                <?= $no++; ?>
                              </td>

                              <td class="text-truncate">
                                <?= $product['product_id']; ?>
                              </td>

                              <td class="text-truncate">
                                <strong>
                                  <?= htmlspecialchars($product['name']); ?>
                                </strong>
                              </td>

                              <td class="text-truncate">

                                <span class="badge bg-label-primary rounded-pill">
                                  <?= $product['stock']; ?>
                                </span>

                              </td>

                              <td>

                                <button
                                  type="button"
                                  class="btn btn-primary btn-stok-keluar"
                                  data-bs-toggle="modal"
                                  data-bs-target="#basicModal"

                                  data-id="<?= $product['product_id']; ?>"
                                  data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES); ?>"
                                  data-stock="<?= $product['stock']; ?>"

                                  <?= $product['stock'] <= 0 ? 'disabled' : ''; ?>>

                                  STOK KELUAR

                                </button>

                              </td>

                            </tr>

                          <?php
                          }
                        } else {
                          ?>

                          <tr>
                            <td colspan="5" class="text-center py-4">
                              Belum ada data produk.
                            </td>
                          </tr>

                        <?php } ?>

                      </tbody>
                    </table>
                  </div>
                  <div id="adminStockOutSearchEmpty" class="text-center text-muted py-4 d-none">
                    Produk tidak ditemukan.
                  </div>
                  <div class="d-flex justify-content-center mt-4">
                    <ul id="adminStockOutPagination" class="pagination theme-primary"></ul>
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


  <!-- Modal -->
  <!-- Modal Stok Keluar -->
  <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog" role="document">

      <div class="modal-content">

        <form method="POST" action="">

          <div class="modal-header">

            <h5 class="modal-title">
              Stok Keluar
            </h5>

            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close">
            </button>

          </div>


          <div class="modal-body">

            <!-- PRODUCT ID -->
            <input
              type="hidden"
              name="product_id"
              id="product_id">


            <!-- NAMA PRODUK -->
            <div class="row">

              <div class="col mb-4 mt-2">

                <div class="form-floating form-floating-outline">

                  <input
                    type="text"
                    id="product_name"
                    class="form-control"
                    placeholder="Nama Produk"
                    readonly>

                  <label for="product_name">
                    Nama Produk
                  </label>

                </div>

              </div>

            </div>


            <!-- STOK SAAT INI -->
            <div class="row">

              <div class="col mb-4">

                <div class="form-floating form-floating-outline">

                  <input
                    type="number"
                    id="current_stock"
                    class="form-control"
                    placeholder="Stok Saat Ini"
                    readonly>

                  <label for="current_stock">
                    Stok Saat Ini
                  </label>

                </div>

              </div>

            </div>


            <!-- JUMLAH STOK KELUAR -->
            <div class="row">

              <div class="col mb-2">

                <div class="form-floating form-floating-outline">

                  <input
                    type="number"
                    name="jumlah"
                    id="jumlah"
                    class="form-control"
                    placeholder="Jumlah Stok Keluar"
                    min="1"
                    required>

                  <label for="jumlah">
                    Jumlah Stok Keluar
                  </label>

                </div>

                <small class="text-muted">
                  Jumlah tidak boleh melebihi stok yang tersedia.
                </small>

              </div>

            </div>

          </div>


          <div class="modal-footer">

            <button
              type="button"
              class="btn btn-outline-secondary"
              data-bs-dismiss="modal">

              TUTUP

            </button>

            <button
              type="submit"
              name="stok_keluar"
              class="btn btn-primary">

              SIMPAN

            </button>

          </div>

        </form>

      </div>

    </div>

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

  <script>
    document.querySelectorAll('.btn-stok-keluar').forEach(function(button) {

      button.addEventListener('click', function() {

        const productId = this.getAttribute('data-id');
        const productName = this.getAttribute('data-name');
        const stock = this.getAttribute('data-stock');

        // Isi data produk ke modal
        document.getElementById('product_id').value = productId;
        document.getElementById('product_name').value = productName;
        document.getElementById('current_stock').value = stock;

        // Kosongkan input stok keluar
        const jumlahInput = document.getElementById('jumlah');

        jumlahInput.value = '';

        // Maksimal stok keluar = stok yang tersedia
        jumlahInput.max = stock;
      });

    });
  </script>
  <script>
    const adminStockOutSearch = document.getElementById('adminStockOutSearch');
    const adminStockOutRows = Array.from(document.querySelectorAll('.admin-stock-out-row'));
    const adminStockOutTableBody = document.getElementById('adminStockOutTableBody');
    const adminStockOutPagination = document.getElementById('adminStockOutPagination');
    const adminStockOutSearchEmpty = document.getElementById('adminStockOutSearchEmpty');
    const adminStockOutPageSize = 10;
    let adminStockOutCurrentPage = 1;
    const adminStockOutSortDirections = { id: 'desc', name: 'asc', stock: 'desc' };

    // Memfilter baris stok keluar berdasarkan kata pencarian
    function getAdminStockOutRows() {
      const searchTerm = adminStockOutSearch.value.toLowerCase().trim();
      return adminStockOutRows.filter(function(row) {
        return row.textContent.toLowerCase().includes(searchTerm);
      });
    }

    // Menampilkan tombol pagination stok keluar
    function renderAdminStockOutPagination(totalPages) {
      adminStockOutPagination.innerHTML = '';
      if (totalPages <= 1) return;

      // Membuat satu tombol pagination
      function addPage(label, page, disabled, active) {
        const item = document.createElement('li');
        item.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'page-link';
        button.textContent = label;
        button.disabled = disabled;
        button.addEventListener('click', function() {
          adminStockOutCurrentPage = page;
          renderAdminStockOut();
        });
        item.appendChild(button);
        adminStockOutPagination.appendChild(item);
      }

      addPage('Previous', adminStockOutCurrentPage - 1, adminStockOutCurrentPage === 1, false);
      for (let page = 1; page <= totalPages; page++) addPage(String(page), page, false, page === adminStockOutCurrentPage);
      addPage('Next', adminStockOutCurrentPage + 1, adminStockOutCurrentPage === totalPages, false);
    }

    // Menampilkan data stok keluar sesuai pencarian dan halaman aktif
    function renderAdminStockOut() {
      const rows = getAdminStockOutRows();
      const totalPages = Math.ceil(rows.length / adminStockOutPageSize);
      if (totalPages > 0 && adminStockOutCurrentPage > totalPages) adminStockOutCurrentPage = totalPages;
      adminStockOutRows.forEach(function(row) { row.classList.add('d-none'); });
      const start = (adminStockOutCurrentPage - 1) * adminStockOutPageSize;
      rows.slice(start, start + adminStockOutPageSize).forEach(function(row) {
        row.classList.remove('d-none');
        adminStockOutTableBody.appendChild(row);
      });
      adminStockOutSearchEmpty.classList.toggle('d-none', rows.length !== 0 || adminStockOutRows.length === 0);
      renderAdminStockOutPagination(totalPages);
    }

    document.querySelectorAll('.sortable-header').forEach(function(button) {
      button.addEventListener('click', function() {
        const key = this.dataset.sortKey;
        const direction = adminStockOutSortDirections[key];
        adminStockOutRows.sort(function(first, second) {
          const firstValue = key === 'name' ? first.dataset.productName : Number(first.dataset['product' + (key === 'id' ? 'Id' : 'Stock')]);
          const secondValue = key === 'name' ? second.dataset.productName : Number(second.dataset['product' + (key === 'id' ? 'Id' : 'Stock')]);
          const result = key === 'name' ? firstValue.localeCompare(secondValue) : firstValue - secondValue;
          return direction === 'asc' ? result : -result;
        });
        adminStockOutSortDirections[key] = direction === 'asc' ? 'desc' : 'asc';
        adminStockOutCurrentPage = 1;
        renderAdminStockOut();
      });
    });

    adminStockOutSearch.addEventListener('input', function() {
      adminStockOutCurrentPage = 1;
      renderAdminStockOut();
    });
    renderAdminStockOut();
  </script>
</body>

</html>
