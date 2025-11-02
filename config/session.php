<?php
// config/session.php
if (session_status() !== PHP_SESSION_ACTIVE) {
  ini_set('session.use_strict_mode', '1');
  if (session_name() !== 'online_hry_sid') {
    session_name('online_hry_sid');
  }
  session_start();
}
