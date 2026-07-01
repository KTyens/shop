<?php

require __DIR__ . '/catalog.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

echo json_encode([
    'products' => crtlu_public_products(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

