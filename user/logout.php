<?php
# calling function require_once() untuk load fail auth.php supaya boleh guna fungsi logout()
require_once __DIR__ . '/../includes/auth.php';
# calling function logout() untuk padam session & cookie user yang sedang login
logout();
# calling function header() untuk redirect balik ke page login.php
header('Location: login.php');
exit;
