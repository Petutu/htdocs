<?php
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/security.php';
require_once __DIR__.'/config/db_connect.php';


if (empty($_SESSION['user_id'])) {
  header('Location: /login.php'); exit;
}
$uid = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
  SELECT z.ID, u.UZIVATELSKE_JMENO AS sender, z.PREDMET, z.DATUM, z.PRECTENO, z.OBSAH  
  FROM zprava z
  JOIN uzivatel u ON u.ID = z.ODESILATEL_ID
  WHERE z.PRIJEMCE_ID = ?
  ORDER BY z.DATUM DESC
");
$stmt->bind_param('i', $uid);
$stmt->execute();
$items = $stmt->get_result();
$csrf  = ensure_csrf();
?>
<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Doručené – Online Hry IS</title>
<link rel="stylesheet" href="assets/css/styles.css" />
</head>
<body>
<header class="nav">
  <div class="brand">🎮 Online Hry IS</div>
  <nav>
    <a href="index.php">Domů</a>
    <a href="register.php">Registrace</a>
    <a href="login.php">Přihlášení</a>
    <a href="inbox.php" aria-current="page">Doručené</a>
    <a href="sent.php">Odeslané</a>
    <a href="compose.php">Napsat</a>
    <a href="actions/logout.php">Odhlásit</a>
  </nav>
</header>

<main class="container">
<section class="card">
<h1>Doručené</h1>
<div class="table-wrapper">
<table class="table">
  <thead><tr><th>Od</th><th>Předmět</th><th>Datum</th><th>OBSAH</th><th>stav</th></tr></thead>
  <tbody>
  <?php while($m = $items->fetch_assoc()): ?>
    <tr class="<?= $m['PRECTENO'] ? '' : 'row-unread' ?>">
      <td><?= htmlspecialchars($m['sender']) ?></td>
      <td><?= htmlspecialchars($m['PREDMET']) ?></td>
      <td><?= htmlspecialchars($m['DATUM']) ?></td>
      <td><?= htmlspecialchars($m['OBSAH']) ?></td>
      <td><?= $m['PRECTENO'] ? 'přečteno' : '<b>nové</b>' ?></td>
      <td>
        <?php if (!$m['PRECTENO']): ?>
        <form method="post" action="actions/mark_read.php" style="display:inline">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="id" value="<?= (int)$m['ID'] ?>">
          <button type="submit">Označit jako přečtené</button>
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
