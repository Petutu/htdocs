<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/crypto.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = $_SESSION['user_id'];

// pokud admin, může editovat někoho jiného přes ?id=
$editId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUserId;

// zjistíme, zda je uživatel admin
$stmt = $conn->prepare("SELECT JEADMIN FROM uzivatel WHERE ID=?");
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$isAdmin = $stmt->get_result()->fetch_assoc()['JEADMIN'] == 1;

// neadmin nesmí editovat nikoho jiného
if (!$isAdmin && $editId !== $currentUserId) {
    header("HTTP/1.1 403 Forbidden");
    exit("Nemáte oprávnění upravovat cizí účet.");
}

// načteme profilová data
$stmt = $conn->prepare("
    SELECT * FROM uzivatel WHERE ID=?
");
$stmt->bind_param("i", $editId);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

$email   = decrypt_field($u['EMAIL']);
$phone   = decrypt_field($u['TELEFON']);
$photo64 = $u['OBRAZEK'] ? "data:image/jpeg;base64," . base64_encode($u['OBRAZEK']) : null;

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
      <!-- Uživatel není přihlášen → zobrazit Registraci a Přihlášení -->
      <a href="register.php">Registrace</a>
      <a href="login.php">Přihlášení</a>
  <?php else: ?>
      <!-- Uživatel je přihlášen → zobrazit zprávy + odhlášení -->
      <a href="inbox.php">
        Doručené (<span id="unreadCount">0</span>)
      </a>
      <a href="sent.php">Odeslané</a>
      <a href="compose.php">Napsat</a>
      <a href="profile.php">Profil</a>
      <a href="actions/logout.php">Odhlásit</a>
  <?php endif; ?>
</nav>

</header>

<main class="page">
  <div class="card" style="max-width: 700px; margin: auto;">

    <h1>Profil uživatele</h1>

    <?php if ($photo64): ?>
      <img src="<?= $photo64 ?>" style="max-width: 200px; border-radius: 8px;">
    <?php else: ?>
      <p><i>Bez fotografie</i></p>
    <?php endif; ?>

    <form method="post" action="actions/profile_update.php" enctype="multipart/form-data">

      <input type="hidden" name="csrf" value="<?= htmlspecialchars(ensure_csrf()) ?>">
      <input type="hidden" name="id" value="<?= (int)$editId ?>">

      <label class="field">
        <span class="field-label">Jméno</span>
        <input type="text" name="first_name" value="<?= htmlspecialchars($u['JMENO']) ?>" required>
      </label>

      <label class="field">
        <span class="field-label">Příjmení</span>
        <input type="text" name="last_name" value="<?= htmlspecialchars($u['PRIJMENI']) ?>" required>
      </label>

      <label class="field">
        <span class="field-label">Email</span>
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
      </label>

      <label class="field">
        <span class="field-label">Telefon</span>
        <input type="tel" name="phone" value="<?= htmlspecialchars($phone) ?>"
               pattern="^\+?[0-9 ]{9,20}$" required>
      </label>

      <label class="field">
        <span class="field-label">Pohlaví</span>
        <select name="gender" required>
          <option value="M" <?= $u['POHLAVI']=='M'?'selected':'' ?>>Muž</option>
          <option value="F" <?= $u['POHLAVI']=='F'?'selected':'' ?>>Žena</option>
          
        </select>
      </label>

      <label class="field">
        <span class="field-label">Nová profilová fotografie (volitelné)</span>
        <input type="file" name="photo" accept="image/*">
      </label>

      <?php if ($isAdmin): ?>
        <label class="field">
          <span class="field-label">Role</span>
          <select name="role">
            <option value="0" <?= $u['JEADMIN']==0?'selected':'' ?>>Uživatel</option>
            <option value="1" <?= $u['JEADMIN']==1?'selected':'' ?>>Admin</option>
          </select>
        </label>
      <?php endif; ?>

      <button class="btn-primary" style="margin-top:20px;">Uložit změny</button>

    </form>

  </div>
</main>

</body>
</html>
