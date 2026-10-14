<?php

session_start();

// Hapus semua data session
$_SESSION = [];

// Hapus session
session_destroy();

// Kembali ke halaman login
header("Location: index.php?status=logout");
exit;

?>