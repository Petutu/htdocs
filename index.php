
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Online Hry IS – Přehled</title>
  <link rel="stylesheet" href="assets/css/styles.css" />
</head>
<body>
  <header class="nav">
    <div class="brand">🎮 Online Hry IS</div>
    <nav>
      <a href="index.php" aria-current="page">Domů</a>
      <a href="register.php">Registrace</a>
      <?php if (empty($_SESSION['user_id'])): ?>
        <a href="login.php">Přihlášení</a>
      <?php else: ?>
        <a href="inbox.php">Doručené</a>
        <a href="sent.php">Odeslané</a>
        <a href="compose.php">Napsat</a>
        <a href="actions/logout.php">Odhlásit</a>
      <?php endif; ?>
    </nav>
  </header>

  <main class="container">
    <section class="card">
      <h1>Vítejte v Online Hry IS</h1>
      <p class="lead">Přihlaste se a napište zprávu, nebo si prohlédněte doručené.</p>
    </section>
  </main>
</body>
</html>
