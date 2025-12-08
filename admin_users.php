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

// zjistíme, jestli je aktuální uživatel admin
$stmt = $conn->prepare("SELECT JEADMIN FROM uzivatel WHERE ID = ?");
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$isAdmin = $row && (int)$row['JEADMIN'] === 1;

if (!$isAdmin) {
    header("HTTP/1.1 403 Forbidden");
    exit("Nemáte oprávnění pro přístup do administrace.");
}

// načteme všechny uživatele
$result = $conn->query("
    SELECT ID, UZIVATELSKE_JMENO, JMENO, PRIJMENI, EMAIL, TELEFON, JEADMIN
    FROM uzivatel
    ORDER BY UZIVATELSKE_JMENO
");
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8" />
  <title>Administrace uživatelů – Online Hry IS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="assets/css/styles.css" />
  <script src="assets/js/unread.js" defer></script>
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
      <a href="inbox.php">
        Doručené (<span id="unreadCount">0</span>)
      </a>
      <a href="sent.php">Odeslané</a>
      <a href="compose.php">Napsat</a>
      <a href="profile.php">Profil</a>
      <?php if ($isAdmin): ?>
        <a href="admin_users.php" aria-current="page">Admin</a>
      <?php endif; ?>
      <a href="actions/logout.php">Odhlásit</a>
    <?php endif; ?>
  </nav>
</header>

<main class="container">
  <section class="card">
    <h1>Administrace uživatelů</h1>
    

    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Login</th>
            <th>Jméno</th>
            <th>Email</th>
            <th>Telefon</th>
            <th>Role</th>
            <th>Akce</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($u = $result->fetch_assoc()): ?>
          <?php
            $email = $u['EMAIL']   ? decrypt_field($u['EMAIL'])   : '';
            $phone = $u['TELEFON'] ? decrypt_field($u['TELEFON']) : '';
            $roleLabel = ((int)$u['JEADMIN'] === 1) ? 'Admin' : 'Uživatel';
          ?>
          <tr>
            <td><?= (int)$u['ID'] ?></td>
            <td><?= htmlspecialchars($u['UZIVATELSKE_JMENO']) ?></td>
            <td><?= htmlspecialchars($u['JMENO'] . ' ' . $u['PRIJMENI']) ?></td>
            <td><?= htmlspecialchars($email) ?></td>
            <td><?= htmlspecialchars($phone) ?></td>
            <td><?= htmlspecialchars($roleLabel) ?></td>
            <td>
              <a class="btn-link" href="profile.php?id=<?= (int)$u['ID'] ?>">
                Upravit
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

</body>
</html>
