<?php
header('Content-Type: application/json');
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
echo json_encode(['ip' => $ip]);
