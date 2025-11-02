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
  $sent = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
    http_response_code(419); // Authentication Timeout
    exit('CSRF token nesouhlasí.');
  }
}
