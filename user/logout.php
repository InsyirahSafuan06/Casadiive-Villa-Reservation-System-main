<?php
/**
 * Logout handler.
 * This file ends the user session and sends the user back to the login page.
 */
require_once __DIR__ . '/../includes/auth.php';
logout();
header('Location: login.php');
exit;
