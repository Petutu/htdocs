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

// načteme odeslané zprávy – kde JÁ jsem odesílatel
$stmt = $conn->prepare("
    SELECT 
        z.ID,
        u.UZIVATELSKE_JMENO AS recipient,
        z.PREDMET,
        z.OBSAH,
        z.DATUM,
        z.PRECTENO
    FROM zprava z
    JOIN uzivatel u ON u.ID = z.PRIJEMCE_ID
    WHERE z.ODESILATEL_ID = ?
    ORDER BY z.DATUM DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$items = $stmt->get_result();
?>
<script src="assets/js/unread.js" defer></script>

<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Odeslané – Online Hry IS</title>
  <link rel="stylesheet" href="assets/css/styles.css" />
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

<main class="container">
  <section class="card">
    <h1>Odeslané zprávy</h1>

    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>Komu</th>
            <th>Předmět</th>
            <th>Datum</th>
            <th>Stav u příjemce</th>
            <th>Obsah</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($m = $items->fetch_assoc()): ?>
          <?php
            // 🔓 dešifrování šifrovaných polí
            $subject = decrypt_field($m['PREDMET']);
            $body    = decrypt_field($m['OBSAH']);
          ?>
          <tr class="<?= $m['PRECTENO'] ? '' : 'row-unread' ?>">
            <td><?= htmlspecialchars($m['recipient']) ?></td>
            <td><?= htmlspecialchars($subject) ?></td>
            <td><?= htmlspecialchars($m['DATUM']) ?></td>
            <td><?= $m['PRECTENO'] ? 'přečteno' : 'nepřečteno' ?></td>
            <td><?= nl2br(htmlspecialchars($body)) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
</body>
</html>
