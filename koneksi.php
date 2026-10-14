<?php

// Mendukung koneksi lokal XAMPP dan database Heroku seperti JawsDB/ClearDB.
$database_url = getenv('JAWSDB_URL') ?: getenv('CLEARDB_DATABASE_URL') ?: getenv('DATABASE_URL');

// DATABASE_URL aplikasi lama bisa berisi PostgreSQL, bukan MySQL.
if ($database_url && !in_array(strtolower(parse_url($database_url, PHP_URL_SCHEME) ?: ''), ['mysql', 'mariadb'], true)) {
    if (!getenv('DB_HOST')) {
        http_response_code(503);
        exit('Database MySQL belum dikonfigurasi. Atur JAWSDB_URL atau DB_HOST pada Heroku.');
    }
    $database_url = false;
}

if ($database_url) {
    $database = parse_url($database_url);

    $host = $database['host'] ?? 'localhost';
    $user = rawurldecode($database['user'] ?? 'root');
    $pass = rawurldecode($database['pass'] ?? '');
    $db   = isset($database['path']) ? rawurldecode(ltrim($database['path'], '/')) : 'mamwiw';
    $port = $database['port'] ?? 3306;
} else {
    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $db   = getenv('DB_NAME') ?: 'mamwiw';
    $port = getenv('DB_PORT') ?: 3306;
}

$koneksi = mysqli_connect($host, $user, $pass, $db, (int) $port);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, 'utf8mb4');

?>
