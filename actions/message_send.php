<?php
// actions/message_send.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config/crypto.php';

verify_csrf();

// musí být přihlášený uživatel
if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$sender    = (int)$_SESSION['user_id'];
$recipient = (int)($_POST['recipient'] ?? 0);
$subject   = trim($_POST['subject'] ?? '');
$body      = trim($_POST['message'] ?? '');

// základní validace
if ($recipient <= 0 || $subject === '' || $body === '') {
    http_response_code(400);
    exit('Vyplňte příjemce, předmět i text zprávy.');
}

// volitelně můžeš ověřit, že příjemce existuje
$check = $conn->prepare('SELECT ID FROM uzivatel WHERE ID = ?');
$check->bind_param('i', $recipient);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    http_response_code(400);
    exit('Zadaný příjemce neexistuje.');
}

// 🔐 šifrování před uložením
$subjectEnc = encrypt_field($subject);
$bodyEnc    = encrypt_field($body);

// zápis do tabulky zprava
$stmt = $conn->prepare('
    INSERT INTO zprava
        (ODESILATEL_ID, PRIJEMCE_ID, PREDMET, OBSAH, DATUM, PRECTENO)
    VALUES
        (?, ?, ?, ?, NOW(), 0)
');
$stmt->bind_param('iiss', $sender, $recipient, $subjectEnc, $bodyEnc);
$stmt->execute();

// po úspěšném odeslání přesměrujeme na "Odeslané"
header('Location: /sent.php');
exit;
