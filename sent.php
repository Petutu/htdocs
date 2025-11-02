<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db_connect.php';
if (empty($_SESSION['user_id'])) { header('Location: /login.php'); exit; }
$uid = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT u.UZIVATELSKE_JMENO AS recipient, z.PREDMET, z.DATUM
  FROM zprava z JOIN uzivatel u ON u.ID = z.PRIJEMCE_ID
  WHERE z.ODESILATEL_ID = ? ORDER BY z.DATUM DESC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$res = $stmt->get_result();
?>
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
      <a href="register.php">Registrace</a>
      <a href="login.php">Přihlášení</a>
      <a href="inbox.php">Doručené</a>
      <a href="sent.php" aria-current="page">Odeslané</a>
      <a href="compose.php">Napsat</a>
      <a href="actions/logout.php">Odhlásit</a>
    </nav>
  </header>

  <main class="page">
    <div class="hero">
      <div class="hero-inner">
        <section class="card">
          <h1>Odeslané zprávy</h1>
          <div class="table-wrapper">
            <table class="table">
              <thead>
                <tr>
                  <th>Komu</th>
                  <th>Předmět</th>
                  <th>Datum</th>
                </tr>
              </thead>
              <tbody>
                <?php while($m = $res->fetch_assoc()): ?>
                <tr>
                  <td><?php echo htmlspecialchars($m['recipient']); ?></td>
                  <td><?php echo htmlspecialchars($m['PREDMET']); ?></td>
                  <td><?php echo htmlspecialchars($m['DATUM']); ?></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>
  </main>
</body>
</html>
