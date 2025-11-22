<?php
session_start();

// Timeout v sekundách (20 minut)
$SESSION_TIMEOUT = 20 * 60;

$now = time();

// Pokud existuje last_activity → kontrolujeme timeout
if (isset($_SESSION['LAST_ACTIVITY'])) {

    // Pokud od poslední aktivity uběhlo víc než 20 min → odhlásit
    if (($now - $_SESSION['LAST_ACTIVITY']) > $SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header("Location: /login.php?timeout=1");
        exit;
    }
}

// aktualizujeme čas aktivity
$_SESSION['LAST_ACTIVITY'] = $now;

// session hijacking ochrana
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = $now;
} else if ($now - $_SESSION['CREATED'] > 300) { // každých 5 min
    session_regenerate_id(true);
    $_SESSION['CREATED'] = $now;
}
