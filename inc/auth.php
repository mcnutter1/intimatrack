<?php
// inc/auth.php
require_once __DIR__ . '/../inc/helpers.php';
$config = require __DIR__ . '/../config/config.php';

ini_set('session.use_strict_mode', 1);
session_name($config['session_name']);
session_start();
if ($config['require_https'] && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
  // In prod, force HTTPS (left as a note).
}

require __DIR__ . '/../inc/db.php';

function current_user(){
  return $_SESSION['user'] ?? null;
}

function require_login(){
  if (!current_user()) redirect('login.php');
}

function login($email, $pass){
  global $db;
  $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
  $stmt->execute([$email]);
  $u = $stmt->fetch();
  if ($u && password_verify($pass, $u['pass_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user'] = ['id'=>$u['id'],'email'=>$u['email']];
    return true;
  }
  return false;
}

function register_first_user($email, $pass){
  global $db, $config;
  // Check any users exist
  $cnt = (int)$db->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
  if ($cnt > 0 && !$config['allow_registration']) return false;
  if ($cnt == 0 || $config['allow_registration']) {
    $stmt = $db->prepare('INSERT INTO users(email, pass_hash) VALUES(?,?)');
    $stmt->execute([$email, password_hash($pass, PASSWORD_DEFAULT)]);
    return true;
  }
  return false;
}

function logout(){
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params["path"], $params["domain"], $params["secure"], $params["httponly"]
    );
  }
  session_destroy();
}
