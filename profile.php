<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/crypto.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$editId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUserId;

/* zjistíme, zda je uživatel admin */
$stmt = $conn->prepare("SELECT JEADMIN FROM uzivatel WHERE ID=?");
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$isAdmin = $stmt->get_result()->fetch_assoc()['JEADMIN'] == 1;

/* neadmin nesmí editovat cizí profil */
if (!$isAdmin && $editId !== $currentUserId) {
    header("HTTP/1.1 403 Forbidden");
    exit("Nemáte oprávnění upravovat cizí účet.");
}

/* načteme data profilu */
$stmt = $conn->prepare("SELECT * FROM uzivatel WHERE ID=?");
$stmt->bind_param("i", $editId);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

$email  = decrypt_field($u['EMAIL']);
$phone  = decrypt_field($u['TELEFON']);
$photo64 = $u['OBRAZEK'] ? "data:image/jpeg;base64," . base64_encode($u['OBRAZEK']) : null;

$csrf = ensure_csrf();
?>
<script src="assets/js/unread.js" defer></script>

<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<title>Profil – Online Hry IS</title>
<link rel="stylesheet" href="assets/css/styles.css">
<style>
.field-inline {
    margin-bottom: 18px;
    padding: 12px;
    border: 1px solid #2c2c2c;
    border-radius: 8px;
    background: #111625;
}
.field-inline button {
    margin-top: 8px;
}
.profile-photo {
    max-width: 200px;
    border-radius: 8px;
    margin-bottom: 10px;
}
</style>
</head>
<body>

<header class="nav">
    <div class="brand">🎮 Online Hry IS</div>
    <nav>
        <a href="index.php">Domů</a>

        <?php if (empty($_SESSION['user_id'])): ?>
            <a href="register.php">Registrace</a>
            <a href="login.php">Přihlášení</a>
        <?php else: ?>
            <a href="inbox.php">Doručené (<span id="unreadCount">0</span>)</a>
            <a href="sent.php">Odeslané</a>
            <a href="compose.php">Napsat</a>
            <a href="profile.php">Profil</a>

            <?php if ($isAdmin): ?>
                <a href="admin_users.php" class="admin-link">Admin</a>
            <?php endif; ?>

            <a href="actions/logout.php">Odhlásit</a>
        <?php endif; ?>
    </nav>
</header>

<main class="page">
<div class="card" style="max-width: 700px; margin: auto;">
    <h1>Profil uživatele</h1>

    <!-- Fotka -->
    <h2>Profilová fotografie</h2>
    <?php if ($photo64): ?>
        <img class="profile-photo" src="<?= $photo64 ?>">
    <?php else: ?>
        <p><i>Žádná fotografie</i></p>
    <?php endif; ?>

    <form method="post" action="actions/profile_update.php" enctype="multipart/form-data" class="field-inline">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <label>Nová fotografie:</label>
        <input type="file" name="photo" accept="image/*">
        <button class="btn-primary">Uložit fotku</button>
    </form>


    <!-- Jméno -->
    <h2>Osobní údaje</h2>

    <form method="post" action="actions/profile_update.php" class="field-inline">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <label>Jméno</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($u['JMENO']) ?>" required>
        <button class="btn-primary">Uložit jméno</button>
    </form>

    <!-- Příjmení -->
    <form method="post" action="actions/profile_update.php" class="field-inline">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <label>Příjmení</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($u['PRIJMENI']) ?>" required>
        <button class="btn-primary">Uložit příjmení</button>
    </form>

    <!-- Email -->
    <form method="post" action="actions/profile_update.php" class="field-inline">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        <button class="btn-primary">Uložit email</button>
    </form>

    <!-- Telefon -->
    <form method="post" action="actions/profile_update.php" class="field-inline">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <label>Telefon</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($phone) ?>" required pattern="^\+?[0-9 ]{9,20}$">
        <button class="btn-primary">Uložit telefon</button>
    </form>

    <!-- Pohlaví -->
    <form method="post" action="actions/profile_update.php" class="field-inline">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <label>Pohlaví</label>
        <select name="gender">
            <option value="M" <?= $u['POHLAVI']=='M'?'selected':'' ?>>Muž</option>
            <option value="F" <?= $u['POHLAVI']=='F'?'selected':'' ?>>Žena</option>
            <option value="O" <?= $u['POHLAVI']=='O'?'selected':'' ?>>Jiné</option>
        </select>
        <button class="btn-primary">Uložit pohlaví</button>
    </form>

    <!-- Role (admin only) -->
    <?php if ($isAdmin): ?>
    <h2>Role</h2>
    <form method="post" action="actions/profile_update.php" class="field-inline">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <label>Role</label>
        <select name="role">
            <option value="0" <?= $u['JEADMIN']==0?'selected':'' ?>>Uživatel</option>
            <option value="1" <?= $u['JEADMIN']==1?'selected':'' ?>>Admin</option>
        </select>
        <button class="btn-primary">Uložit roli</button>
    </form>
    <?php endif; ?>

</div>
</main>

</body>
</html>
