<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db_connect.php';
?>
<script src="assets/js/unread.js" defer></script>

<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Online Hry  – Přehled</title>
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
            Doručené (<span id="unreadCount">0</span>)
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
      <h1>Vítejte v Online Hry </h1>
      <p class="lead">Vyberte hru a začněte hrát.</p>
    </section>

    <!-- Výběr her -->
    <section class="card">
      <h2>Dostupné hry</h2>

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;margin-top:16px;">
        <?php
          $games = $conn->query("SELECT ID, NAZEV, POPIS FROM hra ORDER BY ID");
          if ($games && $games->num_rows):
            while ($g = $games->fetch_assoc()):
        ?>
          <div class="card" style="padding:16px;">
            <h3><?= htmlspecialchars($g['NAZEV']) ?></h3>
            <p><?= htmlspecialchars(substr($g['POPIS'], 0, 100)) ?>...</p>
            <a href="play.php?game=<?= (int)$g['ID'] ?>" class="btn-primary" style="margin-top:10px;">Hrát</a>
          </div>
        <?php
            endwhile;
          else:
            echo "<p>Žádné hry nejsou dostupné.</p>";
          endif;
        ?>
      </div>
    </section>

  </main>
</body>
</html>
