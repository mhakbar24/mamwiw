<?php

session_start();

// Menghubungkan aplikasi ke database
require_once __DIR__ . '/../koneksi.php';

// Memastikan hanya tim produksi yang dapat mengakses dashboard

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SESSION['role'] != 'production_team') {
    header("Location: ../index.php");
    exit;
}

$nama_pengguna = htmlspecialchars($_SESSION['username'] ?? 'Pengguna', ENT_QUOTES, 'UTF-8');
$role_pengguna = htmlspecialchars(
    ucwords(str_replace('_', ' ', $_SESSION['role'] ?? '')),
    ENT_QUOTES,
    'UTF-8'
);

// Memproses penambahan produk baru
if (isset($_POST['tambah_produk'])) {

    $name = trim($_POST['name'] ?? '');

    if ($name == "") {
        $pesan_error = "Nama produk wajib diisi!";
    } else {
        $name = mysqli_real_escape_string($koneksi, $name);
        $cek_produk = mysqli_query(
            $koneksi,
            "SELECT product_id
             FROM products
             WHERE LOWER(name) = LOWER('$name')
             LIMIT 1"
        );

        if (mysqli_num_rows($cek_produk) > 0) {
            $pesan_error = "Produk dengan nama tersebut sudah ada.";
        } else {
            $query = mysqli_query(
                $koneksi,
                "INSERT INTO products (name, stock)
                 VALUES ('$name', 0)"
            );

            if ($query) {
                header("Location: dashboard.php?status=success");
                exit;
            }

            $pesan_error = "Produk gagal ditambahkan: " . mysqli_error($koneksi);
        }
    }
}

// Memproses penambahan stok produk
if (isset($_POST['tambah_stok'])) {

    $product_id = (int) ($_POST['product_id'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 0);
    $user_id = (int) $_SESSION['user_id'];

    if ($product_id <= 0 || $quantity <= 0) {
        $pesan_error = "Pilih produk dan masukkan jumlah stok yang valid.";
    } else {
        mysqli_begin_transaction($koneksi);

        try {
            $query = mysqli_query(
                $koneksi,
                "UPDATE products
                 SET stock = stock + $quantity
                 WHERE product_id = '$product_id'"
            );

            if (!$query || mysqli_affected_rows($koneksi) === 0) {
                throw new Exception("Produk tidak ditemukan.");
            }

            $riwayat = mysqli_query(
                $koneksi,
                "INSERT INTO stocks_in (product_id, user_id, date, quantity)
                 VALUES ('$product_id', '$user_id', NOW(), '$quantity')"
            );

            if (!$riwayat) {
                throw new Exception("Gagal menyimpan riwayat stok.");
            }

            mysqli_commit($koneksi);
            header("Location: dashboard.php?status=stock_updated");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $pesan_error = "Gagal menambahkan stok: " . $e->getMessage();
        }
    }
}

// Mengambil produk yang diurutkan berdasarkan jumlah stok
$products = mysqli_query(
    $koneksi,
    "SELECT product_id, name, stock
     FROM products
     ORDER BY stock ASC, name ASC"
);

// Mengambil semua produk untuk pilihan stok
$stock_products = mysqli_query(
    $koneksi,
    "SELECT product_id, name, stock
    FROM products
    ORDER BY name ASC"
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

        #productPagination .page-link {
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
                    <li class="menu-item active">
                        <a href="/mamwiw/production_team/dashboard.php" class="menu-link">
                            <i class="menu-icon icon-base ri ri-dashboard-line"></i>
                            <div data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>

                    <li class="menu-item">
                        <a href="/mamwiw/production_team/product.php" class="menu-link">
                            <i class="menu-icon icon-base ri ri-shopping-bag-3-line"></i>
                            <div data-i18n="Produk">Riwayat Stok Produk</div>
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
                        <div class="navbar-nav align-items-center">
                            <div class="nav-item d-flex align-items-center">
                                <i class="icon-base ri ri-search-line icon-lg lh-0"></i>
                                <input type="search" id="productSearch" class="form-control border-0 shadow-none" placeholder="Cari produk..." aria-label="Cari produk" />
                            </div>
                        </div>

                        <ul class="navbar-nav flex-row align-items-center ms-md-auto">
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow p-0 d-flex align-items-center" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="../assets/img/avatars/1.png" alt="Profil pengguna" class="rounded-circle" />
                                    </div>
                                    <span class="ms-2">
                                        <span class="d-block fw-semibold"><?= $nama_pengguna; ?></span>
                                        <small class="text-muted"><?= $role_pengguna; ?></small>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <div class="dropdown-item-text">
                                            <h6 class="mb-0"><?= $nama_pengguna; ?></h6>
                                            <small class="text-muted"><?= $role_pengguna; ?></small>
                                        </div>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="../logout.php">
                                            <i class="icon-base ri ri-logout-box-r-line me-2"></i>
                                            Logout
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>

                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">

                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">

                        <div class="row gy-6">

                            <div class="col-12 d-flex flex-wrap gap-3">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                                    TAMBAH PRODUK
                                </button>
                            </div>

                            <?php if (isset($_GET['status']) && $_GET['status'] == 'success') { ?>
                                <div class="col-12">
                                    <div class="alert alert-success alert-dismissible" role="alert">
                                        Produk berhasil ditambahkan!
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                </div>
                            <?php } elseif (isset($_GET['status']) && $_GET['status'] == 'stock_updated') { ?>
                                <div class="col-12">
                                    <div class="alert alert-success alert-dismissible" role="alert">
                                        Stok berhasil ditambahkan!
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                </div>
                            <?php } elseif (isset($pesan_error)) { ?>
                                <div class="col-12">
                                    <div class="alert alert-danger alert-dismissible" role="alert">
                                        <?= htmlspecialchars($pesan_error); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                </div>
                            <?php } ?>


                            <!-- ===================================== -->
                            <!-- RINGKASAN HARI INI -->
                            <!-- ===================================== -->

                            <div class="col-lg-12 d-none">

                                <div class="card h-100">

                                    <div class="card-header">

                                        <h5 class="card-title m-0">
                                            Ringkasan Hari Ini
                                        </h5>

                                        <p class="text-muted mb-0">
                                            Aktivitas inventory hari ini
                                        </p>

                                    </div>


                                    <div class="card-body pt-lg-6">

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

                                                        <p class="mb-0">
                                                            Total Produk
                                                        </p>

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

                                                        <div class="avatar-initial bg-info rounded shadow-xs">

                                                            <i class="icon-base ri ri-stack-line icon-24px"></i>

                                                        </div>

                                                    </div>


                                                    <div class="ms-3">

                                                        <p class="mb-0">
                                                            Total Stok
                                                        </p>

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

                                                        <div class="avatar-initial bg-success rounded shadow-xs">

                                                            <i class="icon-base ri ri-arrow-down-line icon-24px"></i>

                                                        </div>

                                                    </div>


                                                    <div class="ms-3">

                                                        <p class="mb-0">
                                                            Stok Masuk
                                                        </p>

                                                        <h5 class="mb-0">

                                                            <?= number_format($masukHariIni); ?>

                                                        </h5>

                                                        <small class="text-muted">
                                                            Hari ini
                                                        </small>

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

                                                        <p class="mb-0">
                                                            Stok Keluar
                                                        </p>

                                                        <h5 class="mb-0">

                                                            <?= number_format($keluarHariIni); ?>

                                                        </h5>

                                                        <small class="text-muted">
                                                            Hari ini
                                                        </small>

                                                    </div>

                                                </div>

                                            </div>


                                        </div>

                                    </div>

                                </div>

                            </div>



                            <div class="col-lg-12">
                                <div class="card overflow-hidden">
                                    <div class="card-header">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                            <h5 class="mb-0">Daftar Produk</h5>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>
                                                        <button type="button" class="sortable-header" data-sort-key="id">
                                                            ID <span class="sort-indicator ms-2">↕</span>
                                                        </button>
                                                    </th>
                                                    <th>
                                                        <button type="button" class="sortable-header" data-sort-key="name">
                                                            Nama Produk <span class="sort-indicator ms-2">↕</span>
                                                        </button>
                                                    </th>
                                                    <th>
                                                        <button type="button" class="sortable-header" data-sort-key="stock">
                                                            Stock <span class="sort-indicator ms-2">↕</span>
                                                        </button>
                                                    </th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="productTableBody">
                                                <?php
                                                $product_no = 1;
                                                if ($products && mysqli_num_rows($products) > 0) {
                                                    while ($product = mysqli_fetch_assoc($products)) {
                                                ?>
                                                        <tr
                                                            class="product-row"
                                                            data-product-id="<?= $product['product_id']; ?>"
                                                            data-product-name="<?= htmlspecialchars(strtolower($product['name']), ENT_QUOTES); ?>"
                                                            data-product-stock="<?= $product['stock']; ?>">
                                                            <td><?= $product_no++; ?></td>
                                                            <td><?= $product['product_id']; ?></td>
                                                            <td><strong><?= htmlspecialchars($product['name']); ?></strong></td>
                                                            <td>
                                                                <span class="badge bg-label-primary rounded-pill">
                                                                    <?= $product['stock']; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-sm btn-success btn-stock-row"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#stockModal"
                                                                    data-id="<?= $product['product_id']; ?>"
                                                                    data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES); ?>">
                                                                    TAMBAH STOK
                                                                </button>
                                                            </td>
                                                        </tr>
                                                <?php
                                                    }
                                                } else {
                                                ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center py-4">Belum ada data produk.</td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div id="productSearchEmpty" class="text-center text-muted py-4 d-none">
                                        Produk tidak ditemukan.
                                    </div>
                                    <div class="card-footer d-flex justify-content-center">
                                        <nav aria-label="Pagination produk">
                                            <ul id="productPagination" class="pagination theme-primary"></ul>
                                        </nav>
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

        <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST" action="">
                        <div class="modal-header">
                            <h5 class="modal-title">Tambah Produk</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="name" name="name" class="form-control" placeholder="Masukkan Nama Produk" required />
                                <label for="name">Nama Produk</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">TUTUP</button>
                            <button type="submit" name="tambah_produk" class="btn btn-primary">SIMPAN</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="stockModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST" action="">
                        <div class="modal-header">
                            <h5 class="modal-title">Tambah Stok</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-floating form-floating-outline">
                                <input type="text" id="stock_product_name" class="form-control" list="productOptions" placeholder="Pilih atau cari produk" autocomplete="off" required />
                                <input type="hidden" name="product_id" id="stock_product_id">
                                <datalist id="productOptions">
                                    <?php while ($stock_product = mysqli_fetch_assoc($stock_products)) { ?>
                                        <option value="<?= htmlspecialchars($stock_product['name'], ENT_QUOTES); ?>" data-id="<?= $stock_product['product_id']; ?>">
                                            Stok saat ini: <?= $stock_product['stock']; ?>
                                        </option>
                                    <?php } ?>
                                </datalist>
                                <label for="stock_product_name">Produk</label>
                            </div>
                            <div class="form-text mb-4">Ketik nama produk untuk mencari.</div>
                            <div class="form-floating form-floating-outline">
                                <input type="number" name="quantity" id="quantity" class="form-control" placeholder="Jumlah Stok" min="1" required />
                                <label for="quantity">Jumlah Stok</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">TUTUP</button>
                            <button type="submit" name="tambah_stok" class="btn btn-success">SIMPAN STOK</button>
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
        const stockProductName = document.getElementById('stock_product_name');
        const stockProductId = document.getElementById('stock_product_id');
        const productOptions = document.querySelectorAll('#productOptions option');

        // Menyinkronkan nama produk dengan ID produk
        function syncStockProduct() {
            stockProductId.value = '';
            productOptions.forEach(function(option) {
                if (option.value === stockProductName.value) {
                    stockProductId.value = option.dataset.id;
                }
            });
        }

        stockProductName.addEventListener('input', syncStockProduct);
        stockProductName.addEventListener('change', syncStockProduct);
        document.querySelectorAll('.btn-stock-row').forEach(function(button) {
            button.addEventListener('click', function() {
                stockProductName.value = this.dataset.name;
                stockProductId.value = this.dataset.id;
            });
        });

        stockProductName.form.addEventListener('submit', function(event) {
            syncStockProduct();
            if (!stockProductId.value) {
                event.preventDefault();
                stockProductName.setCustomValidity('Pilih produk dari daftar yang tersedia.');
                stockProductName.reportValidity();
            } else {
                stockProductName.setCustomValidity('');
            }
        });

        const productTableBody = document.getElementById('productTableBody');
        const productRows = Array.from(document.querySelectorAll('.product-row'));
        const productSearch = document.getElementById('productSearch');
        const productSearchEmpty = document.getElementById('productSearchEmpty');
        const productPagination = document.getElementById('productPagination');
        const pageSize = 10;
        let currentPage = 1;
        const sortDirections = {
            id: 'desc',
            name: 'asc',
            stock: 'desc'
        };

        // Memfilter baris produk berdasarkan kata pencarian
        function getFilteredRows() {
            const searchTerm = productSearch.value.toLowerCase().trim();

            return productRows.filter(function(row) {
                const searchableText = [
                    row.dataset.productId,
                    row.dataset.productName,
                    row.dataset.productStock
                ].join(' ');

                return searchableText.includes(searchTerm);
            });
        }

        // Menampilkan tombol pagination produk
        function renderPagination(totalPages) {
            productPagination.innerHTML = '';

            if (totalPages <= 1) {
                return;
            }

            // Membuat satu tombol pagination
            const addPageButton = function(label, page, disabled, active) {
                const item = document.createElement('li');
                item.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'page-link';
                button.textContent = label;
                button.disabled = disabled;
                button.addEventListener('click', function() {
                    currentPage = page;
                    renderProducts();
                });

                item.appendChild(button);
                productPagination.appendChild(item);
            };

            addPageButton('Sebelumnya', currentPage - 1, currentPage === 1, false);

            for (let page = 1; page <= totalPages; page++) {
                addPageButton(String(page), page, false, page === currentPage);
            }

            addPageButton('Berikutnya', currentPage + 1, currentPage === totalPages, false);
        }

        // Menampilkan produk sesuai pencarian dan halaman aktif
        function renderProducts() {
            const filteredRows = getFilteredRows();
            const totalPages = Math.ceil(filteredRows.length / pageSize);

            if (totalPages > 0 && currentPage > totalPages) {
                currentPage = totalPages;
            }

            productRows.forEach(function(row) {
                row.classList.add('d-none');
            });

            const startIndex = (currentPage - 1) * pageSize;
            filteredRows.slice(startIndex, startIndex + pageSize).forEach(function(row) {
                row.classList.remove('d-none');
                productTableBody.appendChild(row);
            });

            productSearchEmpty.classList.toggle('d-none', filteredRows.length !== 0 || productRows.length === 0);
            renderPagination(totalPages);
        }

        document.querySelectorAll('.sortable-header').forEach(function(button) {
            button.addEventListener('click', function() {
                const sortKey = this.dataset.sortKey;
                const direction = sortDirections[sortKey];

                productRows.sort(function(firstRow, secondRow) {
                    let result;

                    if (sortKey === 'name') {
                        result = firstRow.dataset.productName.localeCompare(secondRow.dataset.productName);
                    } else if (sortKey === 'stock') {
                        result = Number(firstRow.dataset.productStock) - Number(secondRow.dataset.productStock);
                    } else {
                        result = Number(firstRow.dataset.productId) - Number(secondRow.dataset.productId);
                    }

                    return direction === 'asc' ? result : -result;
                });

                productRows.forEach(function(row, index) {
                    row.cells[0].textContent = index + 1;
                    productTableBody.appendChild(row);
                });

                sortDirections[sortKey] = direction === 'asc' ? 'desc' : 'asc';
                document.querySelectorAll('.sortable-header').forEach(function(header) {
                    header.querySelector('.sort-indicator').textContent = '↕';
                });
                this.querySelector('.sort-indicator').textContent = direction === 'asc' ? '↑' : '↓';
                currentPage = 1;
                renderProducts();
            });
        });

        productSearch.addEventListener('input', function() {
            currentPage = 1;
            renderProducts();
        });

        renderProducts();
    </script>
</body>

</html> 
