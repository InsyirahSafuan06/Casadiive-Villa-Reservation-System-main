<?php
/**
 * Pengendali log keluar.
 * Fail ini menamatkan sesi pengguna dan hantar pengguna kembali ke halaman log masuk.
 */
require_once __DIR__ . '/../includes/auth.php';
logout();
header('Location: login.php');
exit;
