<?php
// config/security.php
// Jednoduchá CSRF ochrana kompatibilní s hidden inputem ve formuláři

function ensure_csrf(): string {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
  // CSRF kontrolujeme jen u POST požadavků
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
  }

  $sent  = $_POST['csrf'] ?? '';
  $valid = $_SESSION['csrf_token'] ?? '';

  if ($sent === '' || $valid === '' || !hash_equals($valid, $sent)) {
    http_response_code(419); // Authentication Timeout
    exit('CSRF token nesouhlasí.');
  }
}
