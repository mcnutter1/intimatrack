<?php
// inc/db.php
$config = require __DIR__ . '/../config/config.php';
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
  $config['db']['host'], $config['db']['port'], $config['db']['name']);
try {
  $db = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo 'Database connection error.';
  exit;
}
