<?php
// config/config.sample.php
// 1) Copy to config/config.php and set values.
// 2) Create MySQL schema using config/schema.sql
// 3) Visit /public/login.php to create your first passcode.

return [
  'db' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'intimatrack',
    'user' => 'intimatrack_user',
    'pass' => 'replace_me_strong_password'
  ],
  // 32-byte hex key recommended. Generate with: `php -r "echo bin2hex(random_bytes(32));"`
  'encryption_key_hex' => '0000000000000000000000000000000000000000000000000000000000000000',

  // Basic app settings
  'app_name' => 'IntimaTrack',
  'session_name' => 'it_session',
  'require_https' => false, // set true in production
  'allow_registration' => true // allows first admin passcode creation
];
