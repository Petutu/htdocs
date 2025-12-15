<?php
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/security.php';
require_once __DIR__.'/config/db_connect.php';
require_once __DIR__.'/config/crypto.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];

// načtení zpráv pro přihlášeného uživatele
$stmt = $conn->prepare("
    SELECT 
        z.ID,
        u.UZIVATELSKE_JMENO AS sender,
        z.PREDMET,
        z.OBSAH,
        z.DATUM,
        z.PRECTENO
    FROM zprava z
    JOIN uzivatel u ON u.ID = z.ODESILATEL_ID
    WHERE z.PRIJEMCE_ID = ?
    ORDER BY z.DATUM DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$items = $stmt->get_result();

$csrf = ensure_csrf();
?>
<script src="assets/js/unread.js" defer></script>

<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Doručené – Online Hry IS</title>
  <link rel="stylesheet" href="assets/css/styles.css" />
</head>
<body>
<?php
$isAdmin = false;

if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT JEADMIN FROM uzivatel WHERE ID=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $isAdmin = $stmt->get_result()->fetch_assoc()['JEADMIN'] == 1;
}
?>

 <header class="nav">
    <div class="brand">🎮 Online Hry IS</div>
   <nav>
      <a href="index.php">Domů</a>

      <?php if (empty($_SESSION['user_id'])): ?>
          <a href="register.php">Registrace</a>
          <a href="login.php">Přihlášení</a>
      <?php else: ?>
          <a href="inbox.php">
            Doručené (<span id="unreadCount">0</span> )
          </a>
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

<main class="container">
  <section class="card">
    <h1>Doručené zprávy</h1>

    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>Od</th>
            <th>Předmět</th>
            <th>Datum</th>
            <th>Stav</th>
            <th>Obsah</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php while ($m = $items->fetch_assoc()): ?>
          <?php
            // dešifrování šifrovaných polí
            $subject = decrypt_field($m['PREDMET']);
            $body    = decrypt_field($m['OBSAH']);
          ?>
          <tr class="<?= $m['PRECTENO'] ? '' : 'row-unread' ?>">
            <td><?= htmlspecialchars($m['sender']) ?></td>
            <td><?= htmlspecialchars($subject) ?></td>
            <td><?= htmlspecialchars($m['DATUM']) ?></td>
            <td><?= $m['PRECTENO'] ? 'přečteno' : '<strong>nové</strong>' ?></td>
            <td><?= nl2br(htmlspecialchars($body)) ?></td>
            <td>
              <?php if (!$m['PRECTENO']): ?>
                <form method="post" action="actions/mark_read.php" style="display:inline;">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="id" value="<?= (int)$m['ID'] ?>">
                  <button class= "read-btn" type="submit">Označit</button>
                </form>
              <?php endif; ?>
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
