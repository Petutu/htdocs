<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/crypto.php';

verify_csrf();

if (empty($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$currentUserId = $_SESSION['user_id'];
$editId = (int)($_POST['id'] ?? 0);

if ($editId <= 0) {
    exit("Neplatné ID.");
}

// zjistíme, zda je aktuální uživatel admin
$stmt = $conn->prepare("SELECT JEADMIN FROM uzivatel WHERE ID=?");
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$isAdmin = $stmt->get_result()->fetch_assoc()['JEADMIN'] == 1;

// neadmin nesmí upravovat jiné osoby
if (!$isAdmin && $editId !== $currentUserId) {
    exit("Nemáte oprávnění upravovat tento účet.");
}

// načtení hodnot
$first = trim($_POST['first_name'] ?? '');
$last  = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$gender = $_POST['gender'] ?? '';
$role = $isAdmin ? (int)($_POST['role'] ?? 0) : null;

// validace
if ($first=='' || $last=='' || !filter_var($email,FILTER_VALIDATE_EMAIL)
    || !preg_match('/^\+?[0-9 ]{9,20}$/',$phone)
    || !in_array($gender,['M','F','O'])
) {
    exit("Neplatné vstupní údaje.");
}

$emailEnc = encrypt_field($email);
$phoneEnc = encrypt_field($phone);

// zpracování obrázku (pokud je nahrán)
$photoData = null;

if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $img = @getimagesize($_FILES['photo']['tmp_name']);
    if ($img === false) {
        exit("Soubor není platný obrázek.");
    }

    list($w,$h) = $img;
    $src = imagecreatefromstring(file_get_contents($_FILES['photo']['tmp_name']));
    if (!$src) exit("Chyba při zpracování obrázku.");

    $newW = 800;
    $newH = (int)round($h*($newW/$w));

    $dst = imagecreatetruecolor($newW,$newH);
    imagecopyresampled($dst,$src,0,0,0,0,$newW,$newH,$w,$h);

    ob_start();
    imagejpeg($dst,null,90);
    $photoData = ob_get_clean();
}

// UPDATE dotaz
if ($photoData !== null) {
    if ($isAdmin) {
        $stmt = $conn->prepare("
            UPDATE uzivatel
            SET JMENO=?, PRIJMENI=?, EMAIL=?, TELEFON=?, POHLAVI=?, JEADMIN=?, OBRAZEK=?
            WHERE ID=?
        ");
        $stmt->bind_param("sssssi bi", $first,$last,$emailEnc,$phoneEnc,$gender,$role,$photoData,$editId);
        $stmt->send_long_data(6,$photoData);
    } else {
        $stmt = $conn->prepare("
            UPDATE uzivatel
            SET JMENO=?, PRIJMENI=?, EMAIL=?, TELEFON=?, POHLAVI=?, OBRAZEK=?
            WHERE ID=?
        ");
        $stmt->bind_param("ssss sbi", $first,$last,$emailEnc,$phoneEnc,$gender,$photoData,$editId);
        $stmt->send_long_data(5,$photoData);
    }
} else {
    if ($isAdmin) {
        $stmt = $conn->prepare("
            UPDATE uzivatel
            SET JMENO=?, PRIJMENI=?, EMAIL=?, TELEFON=?, POHLAVI=?, JEADMIN=?
            WHERE ID=?
        ");
        $stmt->bind_param("ssss sii", $first,$last,$emailEnc,$phoneEnc,$gender,$role,$editId);
    } else {
        $stmt = $conn->prepare("
            UPDATE uzivatel
            SET JMENO=?, PRIJMENI=?, EMAIL=?, TELEFON=?, POHLAVI=?
            WHERE ID=?
        ");
        $stmt->bind_param("sssss i", $first,$last,$emailEnc,$phoneEnc,$gender,$editId);
    }
}

$stmt->execute();

header("Location: /profile.php?id=$editId");
exit;
