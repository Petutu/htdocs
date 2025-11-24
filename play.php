<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/config/security.php';

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$game_id = intval($_GET['game'] ?? 0);

// Načtení hry z databáze
$stmt = $conn->prepare("SELECT ID, NAZEV, POPIS, ZANR, OBTIZNOST, ADRESA FROM hra WHERE ID = ?");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$result = $stmt->get_result();

$game = $result->fetch_assoc();
if (!$game) {
    die("Hra nebyla nalezena.");
}

// Načtení komentářů k dané hře
$commentStmt = $conn->prepare("
    SELECT 
        u.UZIVATELSKE_JMENO,
        kom.KOMENTAR,
        kom.CAS_VLOZENI
    FROM komentar kom
    JOIN uzivatel u ON u.ID = kom.ID_UZIVATELE
    WHERE kom.ID_HRY = ?
    ORDER BY kom.CAS_VLOZENI DESC
    LIMIT 10
");
$commentStmt->bind_param("i", $game_id);
$commentStmt->execute();
$comments = $commentStmt->get_result();

$csrf = ensure_csrf();
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($game['NAZEV']) ?> – Hra</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="assets/js/unread.js" defer></script>
</head>
<body>
<header class="nav">
    <div class="brand">🎮 Online Hry IS</div>
    <nav>
      <a href="index.php">Domů</a>
      <a href="register.php">Registrace</a>

      <?php if (empty($_SESSION['user_id'])): ?>
        <a href="login.php">Přihlášení</a>
      <?php else: ?>
        <a href="inbox.php">
          Doručené (<span id="unreadCount">0</span>)
        </a>
        <a href="sent.php">Odeslané</a>
        <a href="compose.php">Napsat</a>
        <a href="actions/logout.php">Odhlásit</a>
      <?php endif; ?>
    </nav>
</header>

<main class="page">
    <div class="hero">
        <div class="card">
            <h1><?= htmlspecialchars($game['NAZEV']) ?></h1>
            <p><strong>Žánr:</strong> <?= htmlspecialchars($game['ZANR']) ?></p>
            <p><strong>Obtížnost:</strong> <?= htmlspecialchars($game['OBTIZNOST']) ?></p>
            <p><?= nl2br(htmlspecialchars($game['POPIS'])) ?></p>
            <hr>

            <iframe
                id="hra"
                frameborder="0"
                height="600"
                width="100%"
                allow="autoplay"
                allowfullscreen
                seamless
                scrolling="no"
                src="<?= htmlspecialchars($game['ADRESA']) ?>"></iframe>

            <hr>
            <h2>Komentáře</h2>

            <!-- Formulář pro nový komentář -->
            <form method="post" action="actions/comment_add.php" class="comment-form">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="game_id" value="<?= (int)$game_id ?>">

                <label class="field">
                    <span class="field-label">Váš komentář</span>
                    <textarea name="comment" rows="4" required
                              placeholder="Napište, jak se vám hra líbí..."></textarea>
                </label>

                <button type="submit" class="btn-primary">Odeslat komentář</button>
            </form>

            <!-- Výpis posledních komentářů -->
            <?php if ($comments->num_rows > 0): ?>
                <ol class="comment-list">
                    <?php while ($row = $comments->fetch_assoc()): ?>
                        <li>
                            <strong><?= htmlspecialchars($row['UZIVATELSKE_JMENO']) ?></strong>
                            <small>
                                (<?= htmlspecialchars($row['CAS_VLOZENI']) ?>)
                            </small>
                            <br>
                            <?= nl2br(htmlspecialchars($row['KOMENTAR'])) ?>
                        </li>
                    <?php endwhile; ?>
                </ol>
            <?php else: ?>
                <p>Zatím tu nejsou žádné komentáře. Buďte první 😊</p>
            <?php endif; ?>

        </div>
    </div>
</main>
</body>
</html>
