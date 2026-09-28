<?php
require_once __DIR__ . '/../includes/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$accommodation = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM accommodation WHERE accommodation_id = :id');
    $stmt->execute(['id' => $id]);
    $accommodation = $stmt->fetch();
}

$features = [];
if ($accommodation && !empty($accommodation['features'])) {
    $features = array_filter(array_map('trim', explode("\n", $accommodation['features'])));
}

$base = '../';
$active = $accommodation && $accommodation['accommodation_type'] === 'Campsite' ? 'campsite' : 'villa';
$pageTitle = ($accommodation ? $accommodation['accommodation_name'] : 'Package not found') . ' — Casadive Villa';
$pageCss = 'style/detail.css';

require __DIR__ . '/views/detail.view.php';
