<?php
// inc/helpers.php
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect($path){ header('Location: ' . $path); exit; }
function post($key,$default=null){ return $_POST[$key] ?? $default; }
function get($key,$default=null){ return $_GET[$key] ?? $default; }

function csrf_token(){
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}
function csrf_check(){
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? '')) {
      http_response_code(403); echo 'CSRF check failed.'; exit;
    }
  }
}
