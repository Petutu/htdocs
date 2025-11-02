<?php
// actions/message_send.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/db_connect.php';

if (empty($_SESSION['user_id'])) {
  http_response_code(403);
  exit('Musíte být přihlášen.');
}

verify_csrf(); // DOPLNĚNO

$sender    = (int)$_SESSION['user_id'];
$recipient = (int)($_POST['recipient'] ?? 0);
$subject   = trim($_POST['subject'] ?? '');
$body      = trim($_POST['message'] ?? '');

if ($recipient <= 0 || $subject === '' || $body === '') {
  http_response_code(400);
  exit('Vyplňte všechna pole.');
}

$st = $conn->prepare('SELECT ID FROM uzivatel WHERE ID = ?');
$st->bind_param('i', $recipient);
$st->execute();
if (!$st->get_result()->fetch_assoc()) {
  http_response_code(404);
  exit('Příjemce neexistuje.');
}

$stmt = $conn->prepare('INSERT INTO zprava (ODESILATEL_ID, PRIJEMCE_ID, PREDMET, OBSAH) VALUES (?, ?, ?, ?)');
$stmt->bind_param('iiss', $sender, $recipient, $subject, $body);
$stmt->execute();

header('Location: /sent.php');
exit;
