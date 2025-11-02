<?php
// actions/login_action.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/db_connect.php';

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if ($login === '' || $password === '') {
  http_response_code(400);
  exit('Chybí login nebo heslo.');
}

$stmt = $conn->prepare('SELECT ID, HESLO FROM uzivatel WHERE UZIVATELSKE_JMENO = ?');
$stmt->bind_param('s', $login);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if ($row && password_verify($password, $row['HESLO'])) {
  // DŮLEŽITÉ: po přihlášení zregenerovat ID session
  session_regenerate_id(true);
  $_SESSION['user_id'] = (int)$row['ID'];
  $_SESSION['login']   = $login;
  header('Location: /inbox.php');
  exit;
}

http_response_code(401);
echo 'Neplatné přihlašovací údaje.';
