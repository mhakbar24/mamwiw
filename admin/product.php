<?php
session_start();

// Menghubungkan aplikasi ke database
require_once __DIR__ . '/../koneksi.php';

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Memastikan hanya admin yang dapat mengakses halaman produk
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

// Memproses penambahan produk baru
if (isset($_POST['tambah_produk'])) {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $pesan_error = 'Nama produk wajib diisi.';
    } else {
        $name_db = mysqli_real_escape_string($koneksi, $name);
        $cek_produk = mysqli_query(
            $koneksi,
            "SELECT product_id
             FROM products
             WHERE LOWER(name) = LOWER('$name_db')
             LIMIT 1"
        );

        if (mysqli_num_rows($cek_produk) > 0) {
            $pesan_error = 'Produk dengan nama tersebut sudah ada.';
        } else {
            $query = mysqli_query(
                $koneksi,
                "INSERT INTO products (name, stock)
                 VALUES ('$name_db', 0)"
            );

            if ($query) {
                header('Location: product.php?status=success');
                exit;
            }

            $pesan_error = 'Produk gagal ditambahkan: ' . mysqli_error($koneksi);
        }
    }
}

// Memproses perubahan nama produk
if (isset($_POST['edit_produk'])) {

    $product_id = (int) $_POST['product_id'];
    $name = trim($_POST['name']);

    if ($name == "") {

        header("Location: product.php?status=empty");
        exit;

    } else {

        $name = mysqli_real_escape_string($koneksi, $name);

        // Memeriksa apakah nama produk sudah digunakan produk lain
        $cek = mysqli_query(
            $koneksi,
            "SELECT product_id 
             FROM products 
             WHERE LOWER(name) = LOWER('$name')
             AND product_id != '$product_id'"
        );

        if (mysqli_num_rows($cek) > 0) {

            header("Location: product.php?status=name_exists");
            exit;

        }

        // Memperbarui nama produk
        $query = mysqli_query(
            $koneksi,
            "UPDATE products
             SET name = '$name'
             WHERE product_id = '$product_id'"
        );

        if ($query) {

            header("Location: product.php?status=updated");
            exit;

        } else {

            header("Location: product.php?status=error");
            exit;
        }
    }
}


// Memproses penghapusan produk
if (isset($_GET['delete'])) {

    $product_id = (int) $_GET['delete'];

    $query = mysqli_query(
        $koneksi,
        "DELETE FROM products
         WHERE product_id = '$product_id'"
    );

    if ($query) {

        header("Location: product.php?status=deleted");
        exit;

    } else {

        header("Location: product.php?status=error");
        exit;
    }
}


// Mengambil semua data produk dari database
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

        #adminProductPagination .page-link {
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

                    <li class="menu-item active">
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
                        <!-- Search -->
                        <div class="navbar-nav align-items-center">
                            <div class="nav-item d-flex align-items-center">
                                <i class="icon-base ri ri-search-line icon-lg lh-0"></i>
                                <input type="search" id="adminProductSearch" class="form-control border-0 shadow-none" placeholder="Cari produk..."
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
                                <div class="mb-4">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                                        TAMBAH PRODUK
                                    </button>
                                </div>

                                <?php if (isset($_GET['status']) && $_GET['status'] == 'success') { ?>

                                    <div class="alert alert-success alert-dismissible" role="alert">
                                        Produk berhasil ditambahkan!

                                        <button
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="alert"
                                            aria-label="Close">
                                        </button>
                                    </div>

                                <?php } ?>

                                <?php if (isset($pesan_error)) { ?>

                                    <div class="alert alert-danger alert-dismissible" role="alert">

                                        <?= $pesan_error; ?>

                                        <button
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="alert"
                                            aria-label="Close">
                                        </button>

                                    </div>

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
                                            <tbody id="adminProductTableBody">

                                                <?php
                                                $no = 1;

                                                if (mysqli_num_rows($products) > 0) {

                                                    while ($product = mysqli_fetch_assoc($products)) {
                                                ?>

                                                        <tr class="admin-product-row" data-product-id="<?= $product['product_id']; ?>" data-product-name="<?= htmlspecialchars(strtolower($product['name']), ENT_QUOTES); ?>" data-product-stock="<?= $product['stock']; ?>">

                                                            <!-- NO -->
                                                            <td>
                                                                <?= $no++; ?>
                                                            </td>

                                                            <!-- ID -->
                                                            <td class="text-truncate">
                                                                <?= $product['product_id']; ?>
                                                            </td>

                                                            <!-- NAMA PRODUK -->
                                                            <td class="text-truncate">
                                                                <strong>
                                                                    <?= htmlspecialchars($product['name']); ?>
                                                                </strong>
                                                            </td>

                                                            <!-- STOCK -->
                                                            <td class="text-truncate">

                                                                <span class="badge bg-label-primary rounded-pill">
                                                                    <?= $product['stock']; ?>
                                                                </span>

                                                            </td>

                                                            <!-- ACTION -->
                                                            <td>

                                                                <!-- EDIT -->
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-sm btn-warning"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#editModal<?= $product['product_id']; ?>">

                                                                    <i class="ri ri-edit-line"></i>
                                                                    Edit

                                                                </button>


                                                                <!-- DELETE -->
                                                                <a
                                                                    href="product.php?delete=<?= $product['product_id']; ?>"
                                                                    class="btn btn-sm btn-danger"
                                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus produk <?= htmlspecialchars($product['name'], ENT_QUOTES); ?>?')">

                                                                    <i class="ri ri-delete-bin-line"></i>
                                                                    Delete

                                                                </a>

                                                            </td>

                                                        </tr>


                                                        <!-- Menampilkan modal untuk mengedit produk -->

                                                        <div
                                                            class="modal fade"
                                                            id="editModal<?= $product['product_id']; ?>"
                                                            tabindex="-1"
                                                            aria-hidden="true">

                                                            <div class="modal-dialog">

                                                                <div class="modal-content">

                                                                    <form method="POST" action="">

                                                                        <div class="modal-header">

                                                                            <h5 class="modal-title">
                                                                                Edit Produk
                                                                            </h5>

                                                                            <button
                                                                                type="button"
                                                                                class="btn-close"
                                                                                data-bs-dismiss="modal">
                                                                            </button>

                                                                        </div>


                                                                        <div class="modal-body">

                                                                            <!-- ID Produk -->
                                                                            <input
                                                                                type="hidden"
                                                                                name="product_id"
                                                                                value="<?= $product['product_id']; ?>">


                                                                            <!-- Nama Produk -->
                                                                            <div class="form-floating form-floating-outline">

                                                                                <input
                                                                                    type="text"
                                                                                    name="name"
                                                                                    class="form-control"
                                                                                    value="<?= htmlspecialchars($product['name']); ?>"
                                                                                    required>

                                                                                <label>Nama Produk</label>

                                                                            </div>


                                                                            <!-- Informasi Stock -->
                                                                            <div class="mt-3">

                                                                                <small class="text-muted">
                                                                                    Stok saat ini:
                                                                                    <strong>
                                                                                        <?= $product['stock']; ?>
                                                                                    </strong>
                                                                                </small>

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
                                                                                name="edit_produk"
                                                                                class="btn btn-primary">

                                                                                SIMPAN PERUBAHAN

                                                                            </button>

                                                                        </div>

                                                                    </form>

                                                                </div>

                                                            </div>

                                                        </div>

                                                    <?php
                                                    }
                                                } else {
                                                    ?>

                                                    <tr>

                                                        <td colspan="5" class="text-center py-4">

                                                            Belum ada data produk.

                                                        </td>

                                                    </tr>

                                                <?php
                                                }
                                                ?>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div id="adminProductSearchEmpty" class="text-center text-muted py-4 d-none">
                                    Produk tidak ditemukan.
                                </div>
                                <div class="d-flex justify-content-center mt-4">
                                    <ul id="adminProductPagination" class="pagination theme-primary"></ul>
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
                            <input type="text" id="new_product_name" name="name" class="form-control" placeholder="Nama Produk" required />
                            <label for="new_product_name">Nama Produk</label>
                        </div>
                        <div class="form-text mt-2">Produk baru akan dibuat dengan stok awal 0.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">TUTUP</button>
                        <button type="submit" name="tambah_produk" class="btn btn-primary">SIMPAN</button>
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
        const adminProductSearch = document.getElementById('adminProductSearch');
        const adminProductRows = Array.from(document.querySelectorAll('.admin-product-row'));
        const adminProductTableBody = document.getElementById('adminProductTableBody');
        const adminProductSearchEmpty = document.getElementById('adminProductSearchEmpty');
        const adminProductPagination = document.getElementById('adminProductPagination');
        const adminProductPageSize = 10;
        let adminProductCurrentPage = 1;
        const adminProductSortDirections = { id: 'desc', name: 'asc', stock: 'desc' };

        // Memfilter baris produk berdasarkan kata pencarian
        function getAdminProductRows() {
            const searchTerm = adminProductSearch.value.toLowerCase().trim();
            return adminProductRows.filter(function(row) {
                return row.textContent.toLowerCase().includes(searchTerm);
            });
        }

        // Menampilkan tombol pagination produk
        function renderAdminProductPagination(totalPages) {
            adminProductPagination.innerHTML = '';
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
                    adminProductCurrentPage = page;
                    renderAdminProducts();
                });
                item.appendChild(button);
                adminProductPagination.appendChild(item);
            }

            addPage('Previous', adminProductCurrentPage - 1, adminProductCurrentPage === 1, false);
            for (let page = 1; page <= totalPages; page++) {
                addPage(String(page), page, false, page === adminProductCurrentPage);
            }
            addPage('Next', adminProductCurrentPage + 1, adminProductCurrentPage === totalPages, false);
        }

        // Menampilkan produk sesuai pencarian dan halaman aktif
        function renderAdminProducts() {
            const rows = getAdminProductRows();
            const totalPages = Math.ceil(rows.length / adminProductPageSize);
            if (totalPages > 0 && adminProductCurrentPage > totalPages) adminProductCurrentPage = totalPages;

            adminProductRows.forEach(function(row) { row.classList.add('d-none'); });
            const start = (adminProductCurrentPage - 1) * adminProductPageSize;
            rows.slice(start, start + adminProductPageSize).forEach(function(row) {
                row.classList.remove('d-none');
                adminProductTableBody.appendChild(row);
            });

            adminProductSearchEmpty.classList.toggle('d-none', rows.length !== 0 || adminProductRows.length === 0);
            renderAdminProductPagination(totalPages);
        }

        document.querySelectorAll('.sortable-header').forEach(function(button) {
            button.addEventListener('click', function() {
                const key = this.dataset.sortKey;
                const direction = adminProductSortDirections[key];
                adminProductRows.sort(function(first, second) {
                    let result = key === 'name'
                        ? first.dataset.productName.localeCompare(second.dataset.productName)
                        : Number(first.dataset['product' + (key === 'id' ? 'Id' : 'Stock')]) - Number(second.dataset['product' + (key === 'id' ? 'Id' : 'Stock')]);
                    return direction === 'asc' ? result : -result;
                });
                adminProductSortDirections[key] = direction === 'asc' ? 'desc' : 'asc';
                adminProductCurrentPage = 1;
                renderAdminProducts();
            });
        });

        adminProductSearch.addEventListener('input', function() {
            adminProductCurrentPage = 1;
            renderAdminProducts();
        });
        renderAdminProducts();
    </script>
</body>

</html>
