<?php
// actions/register_action.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config/crypto.php';

verify_csrf();

$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name']  ?? '');
$email     = trim($_POST['email']      ?? '');
$phone     = trim($_POST['phone']      ?? '');
$gender    = $_POST['gender']         ?? '';
$login     = trim($_POST['login']      ?? '');
$password  = $_POST['password']       ?? '';
$password2 = $_POST['password2']      ?? '';

// základní validace
if ($firstName === '' || $lastName === '' || $email === '' ||
    $phone === '' || $gender === '' || $login === '' ||
    $password === '' || $password2 === '') {
  http_response_code(400);
  exit('Vyplňte všechna povinná pole.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  exit('Neplatný formát emailu.');
}

if (!preg_match('/^\+?[0-9 ]{9,20}$/', $phone)) {
  http_response_code(400);
  exit('Telefon musí mít 9–20 číslic (případně s + a mezerami).');
}

if (!in_array($gender, ['M', 'F', 'O'], true)) {
  http_response_code(400);
  exit('Neplatná hodnota pohlaví.');
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

// kontrola unikátního loginu
$stmt = $conn->prepare('SELECT ID FROM uzivatel WHERE UZIVATELSKE_JMENO = ?');
$stmt->bind_param('s', $login);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
  http_response_code(409);
  exit('Tento login již existuje.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// zpracování profilové fotky → JPEG 800×XXX
if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  exit('Chyba při nahrávání profilové fotografie.');
}

$tmpFile = $_FILES['photo']['tmp_name'];
$imgInfo = @getimagesize($tmpFile);
if ($imgInfo === false) {
  http_response_code(400);
  exit('Soubor není platný obrázek.');
}

list($width, $height) = $imgInfo;

$srcData = file_get_contents($tmpFile);
$srcImg  = imagecreatefromstring($srcData);
if (!$srcImg) {
  http_response_code(400);
  exit('Obrázek se nepodařilo načíst.');
}

$newWidth  = 800;
$newHeight = (int)round($height * ($newWidth / $width));

$dstImg = imagecreatetruecolor($newWidth, $newHeight);
imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

// výstup do paměti jako JPEG kvalita 90
ob_start();
imagejpeg($dstImg, null, 90);
$photoData = ob_get_clean();

imagedestroy($srcImg);
imagedestroy($dstImg);

// šifrování citlivých údajů
$emailEnc = encrypt_field($email);
$phoneEnc = encrypt_field($phone);

// zápis do DB
$stmt = $conn->prepare('
  INSERT INTO uzivatel
    (UZIVATELSKE_JMENO, HESLO, JMENO, PRIJMENI, EMAIL, TELEFON, POHLAVI, DATUM_NAROZENI, JEADMIN, OBRAZEK)
  VALUES
    (?, ?, ?, ?, ?, ?, ?, NULL, 0, ?)
');

$null = null;
$stmt->bind_param(
  'sssssssb',
  $login,
  $hash,
  $firstName,
  $lastName,
  $emailEnc,
  $phoneEnc,
  $gender,
  $null
);
$stmt->send_long_data(7, $photoData);
$stmt->execute();

// auto-login po registraci
session_regenerate_id(true);
$_SESSION['user_id'] = $stmt->insert_id ?: $conn->insert_id;
$_SESSION['login']   = $login;

header('Location: /inbox.php');
exit;
