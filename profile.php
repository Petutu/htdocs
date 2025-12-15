<?php
// PHP kód zůstává beze změny
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

$email = decrypt_field($u['EMAIL']);
$phone = decrypt_field($u['TELEFON']);
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
            <a href="profile.php" aria-current="page">Profil</a>

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

    <h2>Profilová fotografie</h2>
    <?php if ($photo64): ?>
        <img class="photo" src="<?= $photo64 ?>" alt="Profilová fotografie">
    <?php else: ?>
        <p style="color: var(--muted);"><i>Žádná fotografie</i></p>
    <?php endif; ?>

    <form method="post" action="actions/profile_update.php" enctype="multipart/form-data" class="section-item">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <div class="input-label">Nová fotografie:</div>
        
        <div class="file-upload-wrap input-field">
            <input type="file" name="photo" id="profilePhoto" accept="image/*">
            
            <label for="profilePhoto" class="custom-file-btn">
                Vybrat soubor
            </label>
            
            <span class="file-name-display" id="fileNameDisplay">Soubor nevybrán</span>
        </div>

        <button class="save-btn">Uložit fotku</button>
    </form>


    <h2 style="margin-top: 40px;">Osobní údaje</h2>

    <form method="post" action="actions/profile_update.php" class="section-item">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <div class="input-label">Jméno:</div>
        <div class="input-field">
            <input type="text" name="first_name" value="<?= htmlspecialchars($u['JMENO']) ?>" required>
        </div>
        <button class="save-btn">Uložit jméno</button>
    </form>

    <form method="post" action="actions/profile_update.php" class="section-item">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <div class="input-label">Příjmení:</div>
        <div class="input-field">
            <input type="text" name="last_name" value="<?= htmlspecialchars($u['PRIJMENI']) ?>" required>
        </div>
        <button class="save-btn">Uložit příjmení</button>
    </form>

    <form method="post" action="actions/profile_update.php" class="section-item">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <div class="input-label">Email:</div>
        <div class="input-field">
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
        </div>
        <button class="save-btn">Uložit email</button>
    </form>

    <form method="post" action="actions/profile_update.php" class="section-item">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <div class="input-label">Telefon:</div>
        <div class="input-field">
            <input type="tel" name="phone" value="<?= htmlspecialchars($phone) ?>" required pattern="^\+?[0-9 ]{9,20}$">
        </div>
        <button class="save-btn">Uložit telefon</button>
    </form>

    <form method="post" action="actions/profile_update.php" class="section-item">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <div class="input-label">Pohlaví:</div>
        <div class="input-field">
            <div class="select-wrap"> 
                <select name="gender">
                    <option value="M" <?= $u['POHLAVI']=='M'?'selected':'' ?>>Muž</option>
                    <option value="F" <?= $u['POHLAVI']=='F'?'selected':'' ?>>Žena</option>
                    <option value="O" <?= $u['POHLAVI']=='O'?'selected':'' ?>>Jiné</option>
                </select>
            </div>
        </div>
        <button class="save-btn">Uložit pohlaví</button>
    </form>

    <?php if ($isAdmin): ?>
    <h2 style="margin-top: 40px;">Role</h2>
    <form method="post" action="actions/profile_update.php" class="section-item">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= $editId ?>">

        <div class="input-label">Role:</div>
        <div class="input-field">
            <select name="role">
                <option value="0" <?= $u['JEADMIN']==0?'selected':'' ?>>Uživatel</option>
                <option value="1" <?= $u['JEADMIN']==1?'selected':'' ?>>Admin</option>
            </select>
        </div>
        <button class="save-btn">Uložit roli</button>
    </form>
    <?php endif; ?>

</div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const fileInput = document.getElementById('profilePhoto');
        const fileNameDisplay = document.getElementById('fileNameDisplay');

        if (fileInput && fileNameDisplay) {
            fileInput.addEventListener('change', (event) => {
                if (event.target.files.length > 0) {
                    fileNameDisplay.textContent = event.target.files[0].name;
                } else {
                    fileNameDisplay.textContent = 'Soubor nevybrán';
                }
            });
        }
    });
</script>

</body>
</html>