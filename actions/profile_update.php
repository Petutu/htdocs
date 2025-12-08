<?php
// actions/profile_update.php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/crypto.php';

verify_csrf();

if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$editId        = (int)($_POST['id'] ?? 0);
if ($editId <= 0) {
    exit('Neplatné ID uživatele.');
}

/* zjištění, zda je aktuální uživatel admin */
$stmt = $conn->prepare('SELECT JEADMIN FROM uzivatel WHERE ID = ?');
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$isAdmin = $row && (int)$row['JEADMIN'] === 1;

/* ne-admin nesmí editovat cizí účet */
if (!$isAdmin && $editId !== $currentUserId) {
    header('HTTP/1.1 403 Forbidden');
    exit('Nemáte oprávnění upravovat cizí účet.');
}

/* načtení aktuálních dat uživatele */
$stmt = $conn->prepare('
    SELECT JMENO, PRIJMENI, EMAIL, TELEFON, POHLAVI, JEADMIN, OBRAZEK
      FROM uzivatel
     WHERE ID = ?
');
$stmt->bind_param('i', $editId);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if (!$u) {
    exit('Uživatel nenalezen.');
}

/* rozbalíme aktuální hodnoty */
$first     = $u['JMENO'];
$last      = $u['PRIJMENI'];
$emailEnc  = $u['EMAIL'];
$phoneEnc  = $u['TELEFON'];
$gender    = $u['POHLAVI'];
$role      = (int)$u['JEADMIN'];

/* které pole se má měnit? */
$updateFirst  = array_key_exists('first_name', $_POST);
$updateLast   = array_key_exists('last_name', $_POST);
$updateEmail  = array_key_exists('email', $_POST);
$updatePhone  = array_key_exists('phone', $_POST);
$updateGender = array_key_exists('gender', $_POST);
$updateRole   = $isAdmin && array_key_exists('role', $_POST);

/* Jméno */
if ($updateFirst) {
    $newFirst = trim($_POST['first_name']);
    if ($newFirst === '') {
        exit('Jméno a příjmení jsou povinné.');
    }
    $first = $newFirst;
}

/* Příjmení */
if ($updateLast) {
    $newLast = trim($_POST['last_name']);
    if ($newLast === '') {
        exit('Jméno a příjmení jsou povinné.');
    }
    $last = $newLast;
}

/* Email */
if ($updateEmail) {
    $emailPlain = trim($_POST['email'] ?? '');
    if (!filter_var($emailPlain, FILTER_VALIDATE_EMAIL)) {
        exit('Neplatný email.');
    }
    $emailEnc = encrypt_field($emailPlain);
}

/* Telefon */
if ($updatePhone) {
    $phonePlain = trim($_POST['phone'] ?? '');
    if (!preg_match('/^\+?[0-9 ]{9,20}$/', $phonePlain)) {
        exit('Telefon musí mít 9–20 číslic (případně s + a mezerami).');
    }
    $phoneEnc = encrypt_field($phonePlain);
}

/* Pohlaví */
if ($updateGender) {
    $g = $_POST['gender'] ?? '';
    if (!in_array($g, ['M', 'F', 'O'], true)) {
        exit('Neplatná hodnota pohlaví.');
    }
    $gender = $g;
}

/* Role – jen admin */
if ($updateRole) {
    $role = (int)$_POST['role'];
}

/* zpracování nové fotky – volitelné */
$photoData = null;

if (!empty($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        exit('Chyba při nahrávání obrázku.');
    }

    if (!function_exists('imagecreatefromstring')) {
        exit('Na serveru není povolené rozšíření GD pro práci s obrázky.');
    }

    $tmp = $_FILES['photo']['tmp_name'];

    // ověření, že jde o obrázek
    $imgInfo = @getimagesize($tmp);
    if ($imgInfo === false) {
        exit('Soubor není platný obrázek.');
    }

    $mime = $imgInfo['mime'] ?? '';
    $allowed = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/x-ms-bmp',
        'image/tiff',
        'image/x-tiff'
    ];
    if (!in_array($mime, $allowed, true)) {
        exit('Povolené formáty jsou JPEG, PNG, GIF, BMP a TIFF.');
    }

    list($width, $height) = $imgInfo;

    if ($width <= 0 || $height <= 0) {
        exit('Neplatné rozlišení obrázku.');
    }

    if ($width < 800) {
        exit('Obrázek musí mít šířku alespoň 800 px.');
    }

    // načtení libovolného podporovaného formátu a převod na JPEG 800×XXX
    $srcImg = imagecreatefromstring(file_get_contents($tmp));
    if (!$srcImg) {
        exit('Obrázek se nepodařilo načíst.');
    }

    $newWidth  = 800;
    $newHeight = (int)round($height * ($newWidth / $width));

    $dstImg = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    ob_start();
    imagejpeg($dstImg, null, 90);        // JPEG, kvalita 90
    $photoData = ob_get_clean();

    imagedestroy($srcImg);
    imagedestroy($dstImg);
}

/* UPDATE podle toho, zda je nová fotka a zda je admin */

if ($photoData !== null) {
    if ($isAdmin) {
        $stmt = $conn->prepare('
            UPDATE uzivatel
               SET JMENO = ?, PRIJMENI = ?, EMAIL = ?, TELEFON = ?, POHLAVI = ?, JEADMIN = ?, OBRAZEK = ?
             WHERE ID = ?
        ');
        $stmt->bind_param(
            'sssssibi',
            $first,
            $last,
            $emailEnc,
            $phoneEnc,
            $gender,
            $role,
            $photoData,
            $editId
        );
        $stmt->send_long_data(6, $photoData);
    } else {
        $stmt = $conn->prepare('
            UPDATE uzivatel
               SET JMENO = ?, PRIJMENI = ?, EMAIL = ?, TELEFON = ?, POHLAVI = ?, OBRAZEK = ?
             WHERE ID = ?
        ');
        $stmt->bind_param(
            'sssssbi',
            $first,
            $last,
            $emailEnc,
            $phoneEnc,
            $gender,
            $photoData,
            $editId
        );
        $stmt->send_long_data(5, $photoData);
    }
} else {
    if ($isAdmin) {
        $stmt = $conn->prepare('
            UPDATE uzivatel
               SET JMENO = ?, PRIJMENI = ?, EMAIL = ?, TELEFON = ?, POHLAVI = ?, JEADMIN = ?
             WHERE ID = ?
        ');
        $stmt->bind_param(
            'sssssii',
            $first,
            $last,
            $emailEnc,
            $phoneEnc,
            $gender,
            $role,
            $editId
        );
    } else {
        $stmt = $conn->prepare('
            UPDATE uzivatel
               SET JMENO = ?, PRIJMENI = ?, EMAIL = ?, TELEFON = ?, POHLAVI = ?
             WHERE ID = ?
        ');
        $stmt->bind_param(
            'sssssi',
            $first,
            $last,
            $emailEnc,
            $phoneEnc,
            $gender,
            $editId
        );
    }
}

$stmt->execute();

header('Location: /profile.php?id=' . $editId);
exit;
