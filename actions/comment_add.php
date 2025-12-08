<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/db_connect.php';

verify_csrf();

if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$game_id = (int)($_POST['game_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($game_id <= 0) {
    http_response_code(400);
    exit('Neplatné ID hry.');
}

if ($comment === '') {
    http_response_code(400);
    exit('Komentář nesmí být prázdný.');
}

//maximalni delka komentare
if (mb_strlen($comment) > 1000) {
    $comment = mb_substr($comment, 0, 1000);
}

// vložení komentáře do tabulky komentar
$stmt = $conn->prepare("
    INSERT INTO komentar (ID_HRY, ID_UZIVATELE, KOMENTAR, CAS_VLOZENI)
    VALUES (?, ?, ?, NOW())
");
$stmt->bind_param('iis', $game_id, $user_id, $comment);
$stmt->execute();

// zpět na stránku hry
header('Location: /play.php?game=' . $game_id);
// pokud se soubor jmenuje jinak (např. game.php), uprav název:
# header('Location: /nazev_tve_stranky.php?game=' . $game_id);
exit;
