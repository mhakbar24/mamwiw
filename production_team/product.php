<?php
session_start();

// Menghubungkan aplikasi ke database
require_once __DIR__ . '/../koneksi.php';

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Menyiapkan data pengguna untuk ditampilkan
$nama_pengguna = htmlspecialchars($_SESSION['username'] ?? 'Pengguna', ENT_QUOTES, 'UTF-8');
$role_pengguna = htmlspecialchars(
    ucwords(str_replace('_', ' ', $_SESSION['role'] ?? '')),
    ENT_QUOTES,
    'UTF-8'
);

// Memproses penghapusan riwayat stok masuk
if (isset($_POST['hapus_stok'])) {

    $stock_in_id = (int) ($_POST['stock_in_id'] ?? 0);

    if ($stock_in_id <= 0) {
        $pesan_error = "Transaksi stok tidak valid.";
    } else {
        mysqli_begin_transaction($koneksi);

        try {
            $riwayat = mysqli_query(
                $koneksi,
                "SELECT stockIn_id, product_id, quantity, date
                 FROM stocks_in
                 WHERE stockIn_id = '$stock_in_id'
                 FOR UPDATE"
            );

            if (!$riwayat || mysqli_num_rows($riwayat) === 0) {
                throw new Exception("Riwayat stok tidak ditemukan.");
            }

            $stock_entry = mysqli_fetch_assoc($riwayat);

            if (strtotime($stock_entry['date']) <= time() - 86400) {
                throw new Exception("Stok hanya dapat dihapus dalam waktu kurang dari 1 hari.");
            }

            $product_id = (int) $stock_entry['product_id'];
            $quantity = (int) $stock_entry['quantity'];

            $produk = mysqli_query(
                $koneksi,
                "SELECT stock
                 FROM products
                 WHERE product_id = '$product_id'
                 FOR UPDATE"
            );

            if (!$produk || mysqli_num_rows($produk) === 0) {
                throw new Exception("Produk tidak ditemukan.");
            }

            $product = mysqli_fetch_assoc($produk);

            if ((int) $product['stock'] < $quantity) {
                throw new Exception("STOK SUDAH TERPAKAI");
            }

            $update = mysqli_query(
                $koneksi,
                "UPDATE products
                 SET stock = stock - $quantity
                 WHERE product_id = '$product_id'"
            );

            $delete = mysqli_query(
                $koneksi,
                "DELETE FROM stocks_in
                 WHERE stockIn_id = '$stock_in_id'"
            );

            if (!$update || !$delete || mysqli_affected_rows($koneksi) === 0) {
                throw new Exception("Gagal menghapus stok.");
            }

            mysqli_commit($koneksi);
            header("Location: product.php?status=stock_deleted");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $pesan_error = "Gagal menghapus stok: " . $e->getMessage();
        }
    }
}

// Mengambil riwayat stok masuk dari database
$stock_history = mysqli_query(
    $koneksi,
        "SELECT si.stockIn_id, si.product_id, si.date, si.quantity, p.name,
            CASE
                WHEN p.stock < si.quantity THEN 1
                ELSE 0
            END AS stock_used,
            CASE
                WHEN si.date > NOW() - INTERVAL 1 DAY
                 AND p.stock >= si.quantity THEN 1
                ELSE 0
            END AS can_delete
    FROM stocks_in si
    INNER JOIN products p ON p.product_id = si.product_id
    ORDER BY si.date DESC, si.stockIn_id DESC"
);
?>

<!doctype html>

<html lang="en" class="layout-menu-fixed layout-compact" data-assets-path="../assets/"
    data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
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
                    <li class="menu-item">
                        <a href="/mamwiw/production_team/dashboard.php" class="menu-link">
                            <i class="menu-icon icon-base ri ri-dashboard-line"></i>
                            <div data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>

                    <li class="menu-item active">
                        <a href="/mamwiw/production_team/product.php" class="menu-link">
                            <i class="menu-icon icon-base ri ri-shopping-bag-3-line"></i>
                            <div data-i18n="Produk">Produk</div>
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
                            <!-- Data Tables -->
                            <div class="col-12">
                                <?php if (isset($_GET['status']) && $_GET['status'] == 'stock_deleted') { ?>

                                    <div class="alert alert-success alert-dismissible" role="alert">
                                        Stok berhasil dihapus!

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

                                <?php if (false) { ?>
                                <div class="card overflow-hidden">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th class="text-truncate">No</th>
                                                    <th class="text-truncate">ID</th>
                                                    <th class="text-truncate">Nama Produk</th>
                                                    <th class="text-truncate">Stock</th>
                                                    <!-- <th class="text-truncate">Action</th> -->
                                                </tr>
                                            </thead>
                                            <tbody>

                                                <?php
                                                $no = 1;

                                                if (mysqli_num_rows($products) > 0) {

                                                    while ($product = mysqli_fetch_assoc($products)) {
                                                ?>

                                                        <tr>

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
                                                            <!-- <td>

                                                                
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-sm btn-warning"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#editModal<?= $product['product_id']; ?>">

                                                                    <i class="ri ri-edit-line"></i>
                                                                    Edit

                                                                </button>


                                                                
                                                                <a
                                                                    href="product.php?delete=<?= $product['product_id']; ?>"
                                                                    class="btn btn-sm btn-danger"
                                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus produk <?= htmlspecialchars($product['name'], ENT_QUOTES); ?>?')">

                                                                    <i class="ri ri-delete-bin-line"></i>
                                                                    Delete

                                                                </a>

                                                            </td> -->

                                                        </tr>


                                                        <!-- ========================== -->
                                                        <!-- MODAL EDIT -->
                                                        <!-- ========================== -->

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

                                <?php } ?>
                                <div class="card overflow-hidden mt-6">
                                    <div class="card-header">
                                        <h5 class="mb-0">Riwayat Stok Masuk</h5>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Produk</th>
                                                    <th>Jumlah</th>
                                                    <th>Tanggal</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $history_no = 1;
                                                if ($stock_history && mysqli_num_rows($stock_history) > 0) {
                                                    while ($stock_entry = mysqli_fetch_assoc($stock_history)) {
                                                ?>
                                                        <tr>
                                                            <td><?= $history_no++; ?></td>
                                                            <td><?= htmlspecialchars($stock_entry['name']); ?></td>
                                                            <td>
                                                                <span class="badge bg-label-success rounded-pill">
                                                                    <?= $stock_entry['quantity']; ?>
                                                                </span>
                                                            </td>
                                                            <td><?= htmlspecialchars($stock_entry['date']); ?></td>
                                                            <td>
                                                                <?php if ((int) $stock_entry['can_delete'] === 1) { ?>
                                                                    <form method="POST" action="" onsubmit="return confirm('Hapus penambahan stok ini? Jumlah stok produk akan dikurangi.');">
                                                                        <input type="hidden" name="stock_in_id" value="<?= $stock_entry['stockIn_id']; ?>">
                                                                        <button type="submit" name="hapus_stok" class="btn btn-sm btn-outline-danger">
                                                                            HAPUS STOK
                                                                        </button>
                                                                    </form>
                                                                <?php } elseif ((int) $stock_entry['stock_used'] === 1) { ?>
                                                                    <span class="text-muted">STOK SUDAH TERPAKAI</span>
                                                                <?php } else { ?>
                                                                    <span class="text-muted">SUDAH TIDAK BISA DIHAPUS</span>
                                                                <?php } ?>
                                                            </td>
                                                        </tr>
                                                <?php
                                                    }
                                                } else {
                                                ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center py-4">Belum ada riwayat stok masuk.</td>
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
