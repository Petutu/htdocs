<?php
// actions/register_action.php
ini_set('display_errors', 1); error_reporting(E_ALL);
// DEBUG – dočasné, jen na ověření, že se akce spouští:
file_put_contents(__DIR__ . '/../debug_register.log', date('c') . " PING\n", FILE_APPEND);


require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/db_connect.php';

$login     = trim($_POST['login'] ?? '');
$password  = $_POST['password'] ?? '';
$password2 = $_POST['password2'] ?? '';

if ($login === '' || $password === '' || $password2 === '') {
  http_response_code(400);
  exit('Chybí povinná pole.');
}
if (!preg_match('/^[A-Za-z0-9_]{4,30}$/', $login)) {
  http_response_code(400);
  exit('Login musí mít 4–30 znaků [A-Za-z0-9_].');
}
if ($password !== $password2) {
  http_response_code(400);
  exit('Hesla se neshodují.');
}
if (mb_strlen($password) < 10) {
  http_response_code(400);
  exit('Heslo musí mít alespoň 10 znaků.');
}

$stmt = $conn->prepare('SELECT ID FROM uzivatel WHERE UZIVATELSKE_JMENO = ?');
$stmt->bind_param('s', $login);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
  http_response_code(409);
  exit('Tento login již existuje.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// volitelně uložení profilové fotky do OBRAZEK (BLOB)
$photoData = null;
if (!empty($_FILES['photo']['tmp_name'])) {
  $photoData = file_get_contents($_FILES['photo']['tmp_name']);
}

// DEBUG – smaž po testu
$whichDb = $conn->query("SELECT DATABASE()")->fetch_row()[0] ?? '(unknown)';
file_put_contents(__DIR__ . '/../debug_register.log',
  date('c') . " DB=" . $whichDb . " POST=" . json_encode($_POST) . PHP_EOL,
  FILE_APPEND
);

if ($photoData !== null) {
  // pozor: OBRAZEK je BLOB → použijeme send_long_data
  $stmt = $conn->prepare('INSERT INTO uzivatel (UZIVATELSKE_JMENO, HESLO, DATUM_NAROZENI, JEADMIN, OBRAZEK) VALUES (?, ?, NULL, 0, ?)');
  $null = NULL;
  $stmt->bind_param('ssb', $login, $hash, $null);
  $stmt->send_long_data(2, $photoData);
} else {
  $stmt = $conn->prepare('INSERT INTO uzivatel (UZIVATELSKE_JMENO, HESLO, DATUM_NAROZENI, JEADMIN) VALUES (?, ?, NULL, 0)');
  $stmt->bind_param('ss', $login, $hash);
}
$stmt->execute();

// auto-login
$_SESSION['user_id'] = $stmt->insert_id ?: $conn->insert_id;
$_SESSION['login']   = $login;

header('Location: /inbox.php');
exit;
